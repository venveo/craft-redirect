<?php
/**
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\redirect\controllers;

use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;

/**
 *
 * @property array $tools
 */
class ToolsController extends Controller
{

    protected function getTools()
    {
        $tools = [
            [
                'id' => 'url-validator',
                'displayName' => 'URL Validator',
                'template' => 'vredirect/_tools/url-validator'
            ]
        ];
        return $tools;
    }


    public function actionIndex($toolId = null)
    {
        $tools = $this->getTools();
        if (!$toolId) {
            $tool = $tools[0];
            return $this->redirect(UrlHelper::cpUrl('redirect/tools/' . $tool['id']));
        }

        $tool = ArrayHelper::firstWhere($tools, 'id', $toolId);
        if (!$tool) {
            throw new \Exception('Tool not found');
        }
        return $this->renderTemplate($tool['template'], [
            'tools' => $tools,
            'id' => $tool['id']
        ]);
    }

    public function actionRunUrlValidator()
    {
        $this->requirePostRequest();
        $urls = \Craft::$app->request->getBodyParam('urls');
        $urls = preg_split("/\r\n|\n|\r/", $urls);

        $matchedRoutes = [];
    }
}
