<?php

namespace venveo\redirect\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ElementRedirectSlideout extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = "@venveo/redirect/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'js/ElementRedirectSlideout.js',
        ];

        parent::init();
    }
}
