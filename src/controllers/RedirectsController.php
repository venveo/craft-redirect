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
use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use craft\web\Response;
use venveo\redirect\Plugin;

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
        $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        if (!$variables['siteIds']) {
            return Craft::$app->response->setStatusCode('403',
                Craft::t('vredirect', 'You have no access to any sites'));
        }

        return $this->renderTemplate('vredirect/_redirects/index', $variables);
    }
}
