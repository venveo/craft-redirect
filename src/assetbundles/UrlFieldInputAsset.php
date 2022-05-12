<?php

namespace venveo\redirect\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class UrlFieldInputAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        $this->sourcePath = "@venveo/redirect/resources/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/UrlFieldInput.js',
        ];

        $this->css = [
            'css/UrlFieldInput.css',
        ];

        parent::init();
    }
}
