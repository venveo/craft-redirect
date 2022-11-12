<?php

namespace venveo\redirect\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\Tip;
use venveo\redirect\elements\Redirect;

class RedirectSourceUrlExistingWarning extends Tip
{
    public string $style = self::STYLE_WARNING;

    public string $tip = '';

    public bool $showInForm = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    /**
     * @param Redirect $element
     * @return bool
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        return $this->showInForm;
    }
}
