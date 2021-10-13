<?php

namespace venveo\redirect\assetbundles\elementredirectslideout;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ElementRedirectSlideout extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__ . '/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/ElementRedirectSlideout.js',
        ];

        parent::init();
    }
}
