<?php

namespace venveo\redirect\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\Tip;
use Illuminate\Support\Collection;
use venveo\redirect\elements\Redirect;

class RedirectSourceUrlExistingWarning extends Tip
{
    public string $style = self::STYLE_WARNING;

    public string $tip = 'This URL is already in use by another redirect.';

    public Collection|null $conflictingRedirects = null;


    public bool $showInForm = false;

    protected function updateTip(): string
    {
        $conflictingRedirects = $this->conflictingRedirects;
        if (!$conflictingRedirects || $conflictingRedirects->isEmpty()) {
            return '';
        }
        $conflictingRedirects = $conflictingRedirects->map(function (Redirect $redirect) {
            return $redirect->getCpEditUrl();
        });
        $linkUrl = $conflictingRedirects->first();
        return "This URL is already in use by another redirect. Click [here]({$linkUrl}) to edit it.";
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->tip = $this->updateTip();
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
