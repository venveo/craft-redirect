<?php

namespace venveo\redirect\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;
use venveo\redirect\assetbundles\UrlFieldInputAsset;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;


class RedirectDestinationField extends BaseNativeField
{

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['attribute'],
            $config['mandatory'],
            $config['requirable'],
            $config['translatable'],
        );

        parent::__construct($config);
    }

    protected function value(?ElementInterface $element = null): mixed
    {
        return parent::value($element); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset(
            $fields['mandatory'],
            $fields['translatable'],
        );
        return $fields;
    }


    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        Craft::$app->getView()->registerAssetBundle(UrlFieldInputAsset::class);
    }

    /**
     * @param Redirect|null $element
     * @param bool $static
     * @return string|null
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $config = [
            'id' => 'redirectDestination'
        ];
        $destinationSites = Plugin::getInstance()->redirects->getValidSites();
        $config['siteOptions'] = array_map(function ($site) {
            return [
                'id' => $site->id,
                'baseUrl' => $site->getBaseUrl(),
                'name' => $site->name,
                'handle' => $site->handle
            ];
        }, $destinationSites);


        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($namespace, $settings) => <<<JS
    const container = $('#' + Craft.namespaceId('redirectDestination', $namespace));
new Craft.Redirects.UrlFieldInput(container, $settings);
JS, [
            $view->getNamespace(),
            [
                'siteOptions' => $config['siteOptions']
            ]
        ]);

        $siteOptions = array_merge([['label' => 'External URL', 'value' => null]], array_map(function ($site) {
            return ['label' => $site->name, 'value' => $site->id];
        }, $destinationSites));

        $html = Html::beginTag('div', ['id' => $config['id']]) .
            Cp::selectHtml([
                'class' => 'sites',
                'options' => $siteOptions,
                'name' => 'destinationSiteId',
                'value' => $element->destinationSiteId
//                'data-attribute' => 'destinationSiteId'
            ]) .
            Html::beginTag('div', ['class' => 'destinationUrlWrapper']) .
            Html::tag('div', '', [
                'class' => 'prefix',
            ]) .
            Cp::textFieldHtml([
                'class' => 'url',
                'name' => 'destinationUrl',
                'value' => $element->destinationUrl
            ]) .
            Html::endTag('div') . // inner div
            Html::endTag('div'); // Outer Div
        return $html;

    }

    public function attribute(): string
    {
        return 'redirectDestination';
    }
}
