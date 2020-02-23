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
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use craft\web\View;
use Throwable;
use venveo\redirect\assetbundles\urlfieldinput\UrlFieldInputAsset;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl;
use yii\base\Exception;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class RedirectsController extends Controller
{
    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws SiteNotFoundException
     */
    public function actionIndex(): craft\web\Response
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_REDIRECTS);

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }
        if (!$variables['siteIds']) {
            return Craft::$app->response->setStatusCode('403', Craft::t('vredirect', 'You have no access to any sites'));
        }

        return $this->renderTemplate('vredirect/_redirects/index', $variables);
    }

    /**
     * Edit a redirect
     *
     * @param int|null $redirectId The redirect's ID, if editing an existing site
     * @param Redirect $redirect The redirect being edited, if there were any validation errors
     *
     * @return Response
     */
    public function actionEditRedirect(int $redirectId = null, Redirect $redirect = null): craft\web\Response
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_REDIRECTS);

        $fromCatchAllId = Craft::$app->request->getQueryParam('from');
        $catchAllRecord = null;
        if ($fromCatchAllId) {
            $catchAllRecord = CatchAllUrl::findOne($fromCatchAllId);
        }

        $variables = [];

        if ($catchAllRecord) {
            $variables['catchAllRecord'] = $catchAllRecord;
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('vredirect', 'Redirects'),
                'url' => UrlHelper::cpUrl('redirect/redirects')
            ]
        ];

        $editableSitesOptions = [];
        $editableSiteData = [];

        foreach (Plugin::getInstance()->redirects->getValidSites() as $site) {
            $editableSitesOptions[$site->id] = [
                'value' => $site->id,
                'label' => $site->name
            ];

            $editableSiteData[] = [
                'id' => $site->id,
                'baseUrl' => $site->getBaseUrl(),
                'name' => $site->name,
                'handle' => $site->handle
            ];
        }

        $variables['statusCodeOptions'] = Redirect::STATUS_CODE_OPTIONS;
        $variables['typeOptions'] = Redirect::TYPE_OPTIONS;
        $variables['editableSitesOptions'] = $editableSitesOptions;

        $variables['brandNewRedirect'] = false;

        if ($redirectId !== null) {
            if ($redirect === null) {
                $siteId = Craft::$app->request->get('siteId');
                if ($siteId == null) {
                    $siteId = Craft::$app->getSites()->currentSite->id;
                }
                $redirect = Plugin::$plugin->getRedirects()->getRedirectById($redirectId, $siteId);

                if (!$redirect) {
                    throw new NotFoundHttpException('Redirect not found');
                }
            }

            $variables['title'] = $redirect->sourceUrl;
        } else {
            if ($redirect === null) {
                $redirect = new Redirect();

                // is there a sourceCatchALlUrlID ?

                $sourceCatchAllUrlId = Craft::$app->getRequest()->getQueryParam('sourceCatchAllUrlId', '');
                if ($sourceCatchAllUrlId !== '') {
                    // load some settings from the url
                    $url = Plugin::$plugin->getCatchAll()->getUrlByUid($sourceCatchAllUrlId);
                    if ($url !== null) {
                        $redirect->sourceUrl = $url->uri;
                        $redirect->siteId = $url->siteId;
                    }
                }

                $variables['brandNewRedirect'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new redirect');
        }

        $variables['redirect'] = $redirect;
        Craft::$app->view->registerJs('window.redirectEditableSiteData = '. Json::encode($editableSiteData) . ';', View::POS_HEAD);
        Craft::$app->view->registerAssetBundle(UrlFieldInputAsset::class);
        return $this->renderTemplate('vredirect/_redirects/edit', $variables);
    }


    /**
     * Saves a redirect.
     *
     * @return \yii\web\Response
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws MissingComponentException
     * @throws Exception
     * @throws StaleObjectException
     * @throws BadRequestHttpException
     */
    public function actionSaveRedirect()
    {
        $isNew = false;
        $this->requirePermission(Plugin::PERMISSION_MANAGE_REDIRECTS);

        $request = Craft::$app->getRequest();

        $this->requirePostRequest();

        $siteId = $request->getBodyParam('siteId');

        if ($siteId == null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }


        $redirectId = $request->getBodyParam('redirectId');
        $redirect = null;
        if ($redirectId && !$redirect = Plugin::getInstance()->redirects->getRedirectById($redirectId, $siteId)) {
            return Craft::$app->response->setStatusCode('404', Craft::t('vredirect', 'Redirect not found'));
        }

        if (!$redirect instanceof Redirect) {
            $isNew = true;
            $redirect = new Redirect();
        }

        // If the requested site ID isn't valid, we'll consider it an absolute URL
        $allowedSiteIds = ArrayHelper::getColumn(Plugin::getInstance()->redirects->getValidSites(), 'id');
        $destinationSiteId = $request->getBodyParam('destinationSiteId', $redirect->destinationSiteId);
        if (!in_array($destinationSiteId, $allowedSiteIds, true)) {
            $destinationSiteId = null;
        }
        $redirect->destinationSiteId = $destinationSiteId;

        $redirect->enabled = (bool)$request->getBodyParam('enabled', $redirect->enabled);
        $redirect->sourceUrl = $request->getBodyParam('sourceUrl', $redirect->sourceUrl);
        $redirect->destinationUrl = $request->getBodyParam('destinationUrl', $redirect->destinationUrl);
        $redirect->destinationElementId = $request->getBodyParam('destinationElementId', $redirect->destinationElementId);
        $redirect->statusCode = $request->getBodyParam('statusCode', $redirect->statusCode);
        $redirect->type = $request->getBodyParam('type', $redirect->type);
        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $redirect->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $redirect->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $redirect->siteId = $siteId;

        $redirect->refreshDestinationElement();

        $res = Craft::$app->getElements()->saveElement($redirect, true, false);

        if (!$res) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false
                ]);
            }
            // else, normal result
            Craft::$app->getSession()->setError(Craft::t('vredirect', 'Couldn’t save the redirect.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $redirect
            ]);

            return null;
        }

        $fromCatchAllId = Craft::$app->request->getBodyParam('catchAllRecordId');
        if ($fromCatchAllId) {
            $catchAllRecord = CatchAllUrl::findOne($fromCatchAllId);
            if ($catchAllRecord) {
                $catchAllRecord->delete();
            }
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $redirect->id
            ]);
        }
        // else, normal result
        Craft::$app->getSession()->setNotice(Craft::t('vredirect', 'Redirect saved.'));
        if ($isNew) {
            return $this->redirectToPostedUrl();
        }

        return $this->redirect($redirect->getCpEditUrl());
    }


    /**
     * Deletes a route.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDeleteRedirect()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser->can(Plugin::PERMISSION_MANAGE_REDIRECTS)) {
            return Craft::$app->response->setStatusCode('403', Craft::t('vredirect', 'You lack the required permissions to manage redirects'));
        }

        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $redirectId = $request->getRequiredBodyParam('id');
        Plugin::$plugin->getRedirects()->deleteRedirectById($redirectId);

        return $this->asJson(['success' => true]);
    }
}
