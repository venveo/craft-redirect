<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\controllers;

use craft\web\Controller;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;

class ElementSlideoutsController extends Controller
{
    public function actionGetElementViewHtml()
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_REDIRECTS);
        $elementId = $this->request->getRequiredQueryParam('elementId');
        $siteId = $this->request->getRequiredQueryParam('siteId');
        $element = \Craft::$app->elements->getElementById($elementId, null, $siteId);
        $redirects = Redirect::find()->destinationElementId($element->id)->all();
        return \Craft::$app->view->renderTemplate('vredirect/_components/slideouts/elementRedirects', [
            'element' => $element,
            'redirects' => $redirects,
        ]);
    }
}
