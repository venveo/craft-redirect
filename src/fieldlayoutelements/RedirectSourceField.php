<?php

namespace venveo\redirect\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Html;
use venveo\redirect\assetbundles\UrlFieldInputAsset;


class RedirectSourceField extends TextField
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        Craft::$app->getView()->registerAssetBundle(UrlFieldInputAsset::class);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $html = Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'type' => $this->type,
            'autocomplete' => $this->autocomplete,
            'class' => $this->class,
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'size' => $this->size,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'autocorrect' => $this->autocorrect,
            'autocapitalize' => $this->autocapitalize,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'required' => !$static && $this->required,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
        ]);
        $html = Html::tag('div', $element->site->baseUrl) . $html;
        $html = Html::tag('div', $html, [
            'class' => 'sourceUri flex'
        ]);
        return $html;

    }
}
