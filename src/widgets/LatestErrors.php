<?php

namespace venveo\redirect\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\StringHelper;
use craft\web\assets\admintable\AdminTableAsset;
use venveo\redirect\Plugin;

/**
 * Top Products widget
 *
 * @property string|false $bodyHtml the widget's body HTML
 * @property string $settingsHtml the component’s settings HTML
 * @property null $subtitle
 * @property string $title the widget’s title
 * @since 3.0
 */
class LatestErrors extends Widget
{
    public $count = 25;

    /**
     * @var string
     */
    private $_title;

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_MANAGE_404S);
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('vredirect', 'Latest 404s');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->_title = Craft::t('vredirect', 'Latest 404s');

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return $this->_title;
    }

    /**
     * @inheritDoc
     */
    public function getSubtitle(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(AdminTableAsset::class);

        return $view->renderTemplate('vredirect/_components/widgets/latest-errors/body', [
            'id' => 'latest-errors' . StringHelper::randomString(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $id = 'latest-errors' . StringHelper::randomString();
        $namespaceId = Craft::$app->getView()->namespaceInputId($id);

        return Craft::$app->getView()->renderTemplate('vredirect/_components/widgets/latest-errors/settings', [
            'id' => $id,
            'namespaceId' => $namespaceId,
            'widget' => $this,
        ]);
    }
}
