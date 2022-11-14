<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\controllers;

use Craft;
use craft\base\Element;
use craft\errors\SiteNotFoundException;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;
use venveo\redirect\web\assets\redirectscp\RedirectsCpAsset;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class RedirectsController extends Controller
{
    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws SiteNotFoundException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex(): craft\web\Response
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_REDIRECTS);
        $this->getView()->registerAssetBundle(RedirectsCpAsset::class);

        $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        if (!$variables['siteIds']) {
            return Craft::$app->response->setStatusCode('403',
                Craft::t('vredirect', 'You have no access to any sites'));
        }

        return $this->renderTemplate('vredirect/_redirects/index.twig', $variables);
    }

    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @param string|null $group The group’s handle
     * @return \yii\web\Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate(?string $group = null): ?Response
    {
        if ($group || $group = $this->request->getBodyParam('group')) {
            $group = Plugin::getInstance()->groups->getGroupById($group);
            if (!$group) {
                throw new BadRequestHttpException("Invalid group ID supplied");
            }
        }

        $sitesService = Craft::$app->getSites();
        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = $sitesService->getSiteById($siteId);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site ID: $siteId");
            }
        } else {
            $site = Cp::requestedSite();
            if (!$site) {
                throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
            }
        }

        $user = static::currentUser();

        // Create & populate the draft
        /** @var Redirect $redirect */
        $redirect = Craft::createObject(Redirect::class);
        $redirect->siteId = $site->id;

        // Status
        if (($status = $this->request->getQueryParam('status')) !== null) {
            $enabled = $status === 'enabled';
        } else {
            $enabled = true;
        }
        if (Craft::$app->getIsMultiSite() && count($redirect->getSupportedSites()) > 1) {
            $redirect->enabled = true;
            $redirect->setEnabledForSite($enabled);
        } else {
            $redirect->enabled = $enabled;
            $redirect->setEnabledForSite(true);
        }

        // Make sure the user is allowed to create this redirect
        if (!Craft::$app->getElements()->canSave($redirect, $user)) {
            throw new ForbiddenHttpException('User not authorized to save this redirect.');
        }

        // Title & slug
        $redirect->sourceUrl = $this->request->getQueryParam('sourceUrl');
        $redirect->catchAllId = $this->request->getQueryParam('catchAllId');

        if ($group) {
            $redirect->groupId = $group->id;
        }


        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Post & expiry dates
        if (($postDate = $this->request->getQueryParam('postDate')) !== null) {
            $redirect->postDate = DateTimeHelper::toDateTime($postDate);
        } else {
            $redirect->postDate = DateTimeHelper::now();
        }

        if (($expiryDate = $this->request->getQueryParam('expiryDate')) !== null) {
            $redirect->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }

        // Save it
        $redirect->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($redirect, Craft::$app->getUser()->getId(), null, null,
            false);

        // Resume time
        DateTimeHelper::resume();

        if (!$success) {
            return $this->asModelFailure($redirect, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => Redirect::lowerDisplayName(),
            ]), 'redirect');
        }

        $editUrl = $redirect->getCpEditUrl();

        $response = $this->asModelSuccess($redirect, Craft::t('app', '{type} created.', [
            'type' => Redirect::displayName(),
        ]), 'redirect', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }
}
