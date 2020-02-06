<?php
/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\services;

use Craft;
use craft\base\Element;
use craft\events\DeleteElementEvent;
use craft\events\ElementEvent;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\services\Elements;
use DateTime;
use Exception;
use Throwable;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;
use venveo\redirect\queue\jobs\PruneDeletedRedirects;
use venveo\redirect\records\Redirect as RedirectRecord;
use yii\base\Component;
use yii\base\Event;
use yii\base\ExitException;
use yii\db\StaleObjectException;
use yii\web\HttpException;

/**
 * Class Redirects service.
 *
 */
class Redirects extends Component
{
    /**
     * Returns a redirect by its ID.
     *
     * @param int $redirectId
     * @param int|null $siteId
     *
     * @return Redirect|null
     */
    public function getRedirectById(int $redirectId, int $siteId = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($redirectId, Redirect::class, $siteId);
    }


    /**
     * Processes a 404 event, checking for redirects
     *
     * @param HttpException $exception
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function handle404(HttpException $exception)
    {
        // Path with query params
        $fullPath = Craft::$app->request->getFullPath();
        $queryString = Craft::$app->request->getQueryString();
        $searchUri = $fullPath;
        if ($queryString) {
            $searchUri .= '?' . $queryString;
        }

        $query = new RedirectQuery(Redirect::class);
        $query->matchingUri = $searchUri;
        $matchedRedirects = $query->all();
        if (empty($matchedRedirects)) {
            if (Plugin::$plugin->getSettings()->catchAllActive) {
                $this->register404();
            }
            return;
        }

        // Make sure we handle static redirects first
        usort($matchedRedirects, function ($a, $b) {
            if ($a->type === 'static' && $b->type === 'dynamic') {
                return -1;
            }

            if ($a->type === 'dynamic' && $b->type === 'static') {
                return 1;
            }

            return 0;
        });

        try {
            $this->doRedirect($matchedRedirects[0], $fullPath);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Register the current request as a 404
     *
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function register404()
    {
        $catchAllService = Plugin::$plugin->catchAll;
        $settings = Plugin::getInstance()->getSettings();

        $fullPath = Craft::$app->request->getFullPath();
        $queryString = null;
        if (!$settings->stripQueryParameters) {
            $queryString = Craft::$app->request->getQueryString();
        }

        $catchAllService->registerHitByUri($fullPath, $queryString);
    }

    /**
     * Performs the actual redirect
     *
     * @param Redirect $redirect
     * @param $uri
     * @throws Exception
     */
    public function doRedirect(Redirect $redirect, $uri)
    {
        $destinationUrl = null;

        if ($redirect->type === Redirect::TYPE_STATIC) {
            $processedUrl = $redirect->getDestinationUrl();
        } elseif ($redirect->type === Redirect::TYPE_DYNAMIC) {
            $sourceUrl = $redirect->sourceUrl;
            // Add leading and trailing slashes for RegEx
            if (mb_strpos($sourceUrl, '/') !== 0) {
                $sourceUrl = '/' . $sourceUrl;
            }
            if (mb_strrpos($sourceUrl, '/') !== strlen($sourceUrl)) {
                $sourceUrl .= '/';
            }
            // Only preg_replace if there are replacements available
            if (preg_match('/\$[1-9]+/', $redirect->getDestinationUrl())) {
                $processedUrl = preg_replace($sourceUrl, $redirect->getDestinationUrl(), $uri);
            } else {
                $processedUrl = $redirect->getDestinationUrl();
            }
        } else {
            return;
        }

        // Saving elements takes a while - we're going to do our incrementing
        // directly on the record instead.
        /** @var RedirectRecord $redirect */
        $redirectRecord = RedirectRecord::findOne($redirect->id);

        if ($redirectRecord) {
            $redirectRecord->hitCount++;
            $redirectRecord->hitAt = Db::prepareDateForDb(new DateTime());
            $redirectRecord->save();
        }

        Craft::$app->response->redirect(UrlHelper::url($processedUrl), $redirect->statusCode)->send();

        try {
            Craft::$app->end();
        } catch (ExitException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    /**
     * Create a redirect for when an element URI changes on the site
     *
     * @param ElementEvent $e
     */
    public function handleElementSaved(ElementEvent $e)
    {
        $element = $e->element;
        $elementId = $element->id;
        $siteId = $element->siteId;
        $oldUri = $element->uri;

        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function (ElementEvent $e) use ($oldUri, $siteId, $elementId) {
            /** @var Element $savedElement */
            $savedElement = $e->element;
            if ($savedElement instanceof Redirect) {
                return;
            }
            if ($e->isNew || $savedElement->propagating || ElementHelper::isDraftOrRevision($savedElement)) {
                return;
            }

            if ($savedElement->id !== $elementId || $savedElement->siteId !== $siteId) {
                return;
            }

            if ($oldUri !== $savedElement->uri) {
                $exists = Redirect::find()
                    ->destinationElementId($savedElement->getSourceId())
                    ->siteId($siteId)
                    ->destinationSiteId($siteId)
                    ->sourceUrl($oldUri)->exists();
                if ($exists) {
                    return;
                }

                $redirect = new Redirect();
                $redirect->siteId = $siteId;
                $redirect->sourceUrl = $oldUri;
                $redirect->destinationElementId = $savedElement->getSourceId();
                $redirect->destinationSiteId = $siteId;
                $redirect->type = Redirect::TYPE_STATIC;
                $redirect->statusCode = '301';
                Craft::$app->elements->saveElement($redirect);
            }
        });
    }

    /**
     * When an element is deleted, we'll delete its redirects as well
     *
     * @param DeleteElementEvent $e
     */
    public function handleElementDeleted(DeleteElementEvent $e)
    {
        /** @var Element $element */
        $element = $e->element;
        if ($element instanceof Redirect) {
            return;
        }

        if (!$element->uri) {
            return;
        }

        $job = new PruneDeletedRedirects([
            'deletedElementId' => $element->id,
            'deletedElementUri' => $element->uri,
            'siteId' => $element->siteId,
            'hardDelete' => $e->hardDelete
        ]);
        Craft::$app->queue->push($job);
    }


    /**
     * When an element is restored, we'll try to restore any related redirects
     *
     * @param ElementEvent $e
     * @throws Throwable
     * @throws \yii\base\Exception
     */
    public function handleElementRestored(ElementEvent $e)
    {
        /** @var Element $element */
        $element = $e->element;
        if ($element instanceof Redirect) {
            return;
        }

        if (!$element->uri) {
            return;
        }

        // We'll search by URL and by ID just to be thorough
        $redirectsByUrl = Redirect::find()->destinationUrl($element->uri)->siteId($element->siteId)->trashed(true)->all();
        $redirectsById = Redirect::find()->destinationElementId($element->id)->siteId($element->siteId)->trashed(true)->all();
        Craft::$app->elements->restoreElements($redirectsById);
        Craft::$app->elements->restoreElements($redirectsByUrl);
    }

    /**
     * Returns all sites that the user can create redirects in
     *
     * @return Site[]
     */
    public function getValidSites()
    {
        $sites = [];
        foreach (Craft::$app->getSites()->getEditableSites() as $site) {
            if (!Craft::$app->config->general->headlessMode && !$site->hasUrls) {
                continue;
            }
            $sites[] = $site;
        }
        return $sites;
    }
}
