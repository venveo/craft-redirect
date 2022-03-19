<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\controllers;

use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use craft\web\Response;

class DashboardController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws SiteNotFoundException
     */
    public function actionIndex(): craft\web\Response
    {
        return $this->renderTemplate('vredirect/dashboard/index', []);
    }
}
