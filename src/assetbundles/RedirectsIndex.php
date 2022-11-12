<?php

namespace venveo\redirect\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class RedirectsIndex extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = "@venveo/redirect/resources/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/RedirectsIndex.js',
        ];

        parent::init();
    }
}
