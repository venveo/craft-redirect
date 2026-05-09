<?php

namespace venveo\redirect\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\Tip;
use Illuminate\Support\Collection;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;

class RedirectSourceUrlExistingWarning extends Tip
{
    public const TYPE_CONFLICT = 'conflict';
    public const TYPE_DUPLICATE = 'duplicate';

    public string $style = self::STYLE_WARNING;

    public string $tip = 'This URL is already in use by another redirect.';

    public Collection|null $conflictingRedirects = null;

    public string $warningType = self::TYPE_DUPLICATE;

    public bool $showInForm = false;

    private bool $resolved = false;

    private bool $resolvedShowInForm = false;

    private string $resolvedTip = '';

    protected function updateTip(?Redirect $element = null): string
    {
        if (!$element) {
            return $this->tip;
        }

        if ($this->warningType === self::TYPE_CONFLICT) {
            if (!$element->getConflictingElementForSource()) {
                return '';
            }

            return Plugin::t('This redirect source points to an existing page URL. The redirect will not function until the conflicting page URL is changed or the page is deactivated.');
        }

        $conflictingRedirects = $element->getDuplicateRedirects();
        if (!$conflictingRedirects || $conflictingRedirects->isEmpty()) {
            return '';
        }

        $conflictingRedirects = $conflictingRedirects->map(function(Redirect $redirect) {
            return $redirect->getCpEditUrl();
        });
        $linkUrl = $conflictingRedirects->first();
        return Plugin::t("This URL is already in use by another redirect. Click [here]({linkUrl}) to edit it.", [
            'linkUrl' => $linkUrl,
        ]);
    }

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
        if (!$this->resolve($element)) {
            return false;
        }

        return $this->resolvedShowInForm;
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$this->resolve($element)) {
            return null;
        }

        $this->tip = $this->resolvedTip;
        return parent::formHtml($element, $static);
    }

    private function resolve(?ElementInterface $element): bool
    {
        if (!$element instanceof Redirect) {
            return false;
        }

        if ($this->resolved) {
            return true;
        }

        $this->resolved = true;
        $this->resolvedTip = $this->updateTip($element);
        $this->resolvedShowInForm = $this->resolvedTip !== '';
        return true;
    }
}
