<?php

namespace venveo\redirect\web\assets\redirectscp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\htmx\HtmxAsset;
use craft\web\View;
use yii\web\JqueryAsset;
class RedirectsCpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
            JqueryAsset::class,
            HtmxAsset::class,
        ];

        $this->css[] = 'css/redirectscp.css';
        $this->js[] = 'redirectscp.js';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('vredirect', [
            ]);
        }
    }
}
