<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\validators\DateTimeValidator;
use craft\validators\SiteIdValidator;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\redirect\elements\conditions\RedirectCondition;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\fieldlayoutelements\RedirectDestinationField;
use venveo\redirect\fieldlayoutelements\RedirectSourceField;
use venveo\redirect\models\Settings;
use venveo\redirect\Plugin;
use venveo\redirect\records\Redirect as RedirectRecord;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\i18n\Formatter;
use yii\web\Response;

/**
 * @property-read null|\craft\base\Element $destinationElement
 * @property-read null|string $postEditUrl
 * @property-read null|\craft\models\Site $destinationSite
 */
class Redirect extends Element
{
    public const TYPE_STATIC = 'static';
    public const TYPE_DYNAMIC = 'dynamic';

    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CODE_TEMPORARY = 302;
    public const STATUS_CODE_PERMANENT = 301;

    /**
     * @var string sourceUrl
     */
    public ?string $sourceUrl = null;
    /**
     * @var string|null destinationUrl
     */
    public ?string $destinationUrl = null;
    /**
     * @var DateTime|null
     */
    public ?DateTime $hitAt = null;
    /**
     * @var int hitCount
     */
    public int $hitCount = 0;
    /**
     * @var string|null statusCode
     */
    public ?string $statusCode = null;
    /**
     * @var string|null type
     */
    public ?string $type = null;
    /**
     * @var int|null siteId
     */
    public ?int $siteId = null;

    /**
     * @var int|null destinationElementId
     */
    public ?int $destinationElementId = null;

    /**
     * @var int|null destinationSiteId
     */
    public ?int $destinationSiteId = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $postDate = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $expiryDate = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Plugin::t('Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Plugin::t('Redirects');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'redirect';
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return parent::statuses() + [
                self::STATUS_LIVE => Plugin::t('Live'),
                self::STATUS_EXPIRED => Plugin::t('Expired'),
                self::STATUS_PENDING => Plugin::t('Pending')
            ];
    }

    /**
     * @inheritdoc
     */
    public static function find(): RedirectQuery
    {
        return new RedirectQuery(static::class);
    }


    /**
     * @inheritdoc
     * @return RedirectCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(RedirectCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $staleDate = new DateTime('2 months ago');

        $sources = [];
        if ($context === 'index') {
            $sources = [
                [
                    'key' => '*',
                    'label' => Plugin::t('All redirects'),
                    'criteria' => [],
                ],
                [
                    'key' => 'stale',
                    'label' => Plugin::t('Stale Redirects'),
                    'criteria' => ['hitAt' => '< ' . Db::prepareDateForDb($staleDate)],
                ],
            ];
            // TODO: Add redirect groups
        }
        return $sources;
    }


    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {

        $crumbs = [
            [
                'label' => Plugin::t('Redirects'),
                'url' => UrlHelper::url('redirect/redirects'),
            ]
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }


    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        // Edit
        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Plugin::t('Edit Redirect'),
        ]);

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Plugin::t('Are you sure you want to delete the selected redirects?'),
            'successMessage' => Plugin::t('Redirects deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Plugin::t('Redirects restored.'),
            'partialSuccessMessage' => Plugin::t('Some redirects restored.'),
            'failMessage' => Plugin::t('Redirects not restored.'),
        ]);

//        $actions[] = SetStatus::class;

        return $actions;
    }


    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Plugin::t('Redirect'),
                'orderBy' => 'venveo_redirects.sourceUrl',
                'attribute' => 'title',
            ],
            'venveo_redirects.type' => Plugin::t('Type'),
            [
                'label' => Plugin::t('Destination URL'),
                'orderBy' => 'venveo_redirects.destinationUrl',
                'attribute' => 'destinationUrl',
            ],
            [
                'label' => Plugin::t('Last Hit'),
                'orderBy' => 'venveo_redirects.hitAt',
                'attribute' => 'hitAt',
            ],
            'venveo_redirects.hitCount' => Plugin::t('Hit Count'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'type' => ['label' => Plugin::t('Type')],
            'destinationUrl' => ['label' => Plugin::t('Destination URL')],
            'hitAt' => ['label' => Plugin::t('Last Hit')],
            'hitCount' => ['label' => Plugin::t('Hit Count')],
            'postDate' => ['label' => Craft::t('app', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('app', 'Expiry Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'statusCode' => ['label' => Plugin::t('Status Code')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['destinationUrl', 'statusCode', 'hitAt', 'hitCount', 'dateCreated'];
    }


    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['sourceUrl', 'destinationUrl'];
    }

    public function getSupportedSites(): array
    {
        $supportedSites = [];
        $supportedSites[] = ['siteId' => $this->siteId, 'enabledByDefault' => true];
        return $supportedSites;
    }


    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl("redirect/redirects");
    }


    /**
     * @return array
     */
    public static function getRedirectTypes(): array
    {
        return [
            self::TYPE_STATIC => Plugin::t('Static'),
            self::TYPE_DYNAMIC => Plugin::t('Dynamic (RegExp)'),
        ];
    }

    /**
     * @return array
     */
    public static function getRedirectStatusCodes(): array
    {
        return [
            self::STATUS_CODE_PERMANENT => Plugin::t('Permanent (301)'),
            self::STATUS_CODE_TEMPORARY => Plugin::t('Temporary (302)'),
        ];
    }

    /**
     * @return FieldLayout
     */
    public function getFieldLayout(): FieldLayout
    {
        $layoutElements = [];
        $layoutElements[] =
            new RedirectSourceField([
                'label' => Plugin::t('Source URI'),
                'attribute' => 'sourceUrl',
                'instructions' => Plugin::t('Enter the URI to redirect'),
            ]);
        $layoutElements[] =
            new RedirectDestinationField([
                'label' => Plugin::t('Redirect Destination'),
                'instructions' => Plugin::t('Configure the element or external URL to send this request to'),
            ]);

        $fieldLayout = new FieldLayout();

        $tab = new FieldLayoutTab();
        $tab->name = 'Settings';
        $tab->uid = 'redirectSettings';
        $tab->setLayout($fieldLayout);

        $tab->setElements($layoutElements);

        $fieldLayout->setTabs([$tab]);

        return $fieldLayout;
    }

    public function metaFieldsHtml(bool $static): string
    {
        $fields = [];

        $fields[] = (function () use ($static) {
            $redirectTypes = Redirect::getRedirectTypes();

            $redirectTypeOptions = [];

            foreach ($redirectTypes as $redirectType => $redirectTypeLabel) {
                $redirectTypeOptions[] = [
                    'label' => $redirectTypeLabel,
                    'value' => $redirectType,
                ];
            }

            if (!$static) {
                $view = Craft::$app->getView();
                $typeInputId = $view->namespaceInputId('type');
                $js = <<<EOD
(() => {
    const \$typeInput = $('#$typeInputId');
    const editor = \$typeInput.closest('form').data('elementEditor');
    if (editor) {
        editor.checkForm();
    }
})();
EOD;
                $view->registerJs($js);
            }

            return Cp::selectFieldHtml([
                'label' => Plugin::t('Redirect Type'),
                'id' => 'type',
                'name' => 'type',
                'value' => $this->type,
                'options' => $redirectTypeOptions,
                'disabled' => $static,
            ]);
        })();

        $fields[] = (function () use ($static) {
            $statusCodes = self::getRedirectStatusCodes();
            $statusCodeOptions = [];

            foreach ($statusCodes as $statusCode => $statusCodeLabel) {
                $statusCodeOptions[] = [
                    'label' => $statusCodeLabel,
                    'value' => $statusCode,
                ];
            }

            if (!$static) {
                $view = Craft::$app->getView();
                $statusInputId = $view->namespaceInputId('statusCode');
                $js = <<<EOD
(() => {
    const \$typeInput = $('#$statusInputId');
    const editor = \$typeInput.closest('form').data('elementEditor');
    if (editor) {
        editor.checkForm();
    }
})();
EOD;
                $view->registerJs($js);
            }

            return Cp::selectFieldHtml([
                'label' => Plugin::t('Status Code'),
                'id' => 'statusCode',
                'name' => 'statusCode',
                'value' => $this->statusCode,
                'options' => $statusCodeOptions,
                'disabled' => $static,
            ]);
        })();

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();
        $metadata = parent::metadata();

        if ($this->hitCount) {
            $metadata[Plugin::t('Total Hits')] = $formatter->asInteger($this->hitCount);
        }

        if ($syncedElement = $this->getDestinationElement()) {
            $metadata[Plugin::t('Linked Element')] = Cp::elementHtml($syncedElement);
        }
        if ($this->hitAt) {
            $metadata[Plugin::t('Last Hit')] = $formatter->asDatetime($this->hitAt,
                Formatter::FORMAT_WIDTH_SHORT);
        }


        return $metadata;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('redirect/redirects/' . $this->getCanonicalId());
    }

    /**
     * Gets the actual final absolute destination URL
     *
     * @return string|null
     * @throws \yii\base\InvalidConfigException
     */
    public function resolveDestinationUrl(string $requestUrl = null): ?string
    {

        // Redirect to a site element
        if ($this->destinationElementId) {
            $element = Craft::$app->elements->getElementById($this->destinationElementId, null,
                $this->destinationSiteId ?? $this->siteId);
            if ($element && $element->getUrl()) {
                return $element->getUrl();
            }
            // We don't have an element, but we do have a site ID, so send to that site, regardless of URL format
        } elseif (isset($this->destinationUrl, $this->destinationSiteId)) {
            // UrlHelper::siteUrl() will try to default to absolute URLs, so we'll handle it ourselves
            return $this->getDestinationSite()->getBaseUrl() . $this->destinationUrl;
        } else {
            // No site ID, so if it's absolute, send to that URL
            if (UrlHelper::isAbsoluteUrl($this->destinationUrl)) {
                return $this->destinationUrl;
            }
            // It's not absolute, so use the site the redirect was saved in
            return $this->getSite()->getBaseUrl() . $this->destinationUrl;
        }
        return null;
    }

    /**
     * Gets the destination site if one is set
     *
     * @return Site|null
     */
    public function getDestinationSite(): ?Site
    {
        if ($this->destinationSiteId === null) {
            return null;
        }
        return Craft::$app->sites->getSiteById($this->destinationSiteId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $status = parent::getStatus();

        if ($status == self::STATUS_ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

            if ($postDate <= $currentTime && ($expiryDate === null || $expiryDate > $currentTime)) {
                return self::STATUS_LIVE;
            }

            if ($postDate > $currentTime) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_EXPIRED;
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['hitAt'], DateTimeValidator::class];
        $rules[] = [['hitCount', 'destinationElementId', 'destinationSiteId'], 'number', 'integerOnly' => true];
        $rules[] = [['sourceUrl', 'destinationUrl'], 'string', 'max' => 255];
        $rules[] = [['sourceUrl', 'type'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];

        $rules[] = [
            ['type'],
            'in',
            'range' => [self::TYPE_STATIC, self::TYPE_DYNAMIC],
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]
        ];
        $rules[] = [['statusCode'], 'in', 'range' => array_keys(self::getRedirectStatusCodes())];

        $rules[] = [['destinationSiteId'], SiteIdValidator::class];
        $rules[] = [
            'destinationElementId',
            'exist',
            'targetClass' => \craft\records\Element::class,
            'targetAttribute' => ['destinationElementId' => 'id']
        ];
        $rules[] = [
            'destinationSiteId',
            'required',
            'when' => function ($model) {
                return !empty($model->destinationElementId);
            }
        ];
        $rules[] = [
            'destinationUrl',
            'required',
            'when' => function ($model) {
                return empty($model->destinationElementId);
            },
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]
        ];
        // TODO: Re-add validation for URLs
//        $rules[] = [
//            'destinationUrl',
//            UrlValidator::class,
//            'when' => function ($model) {
//                return empty($model->destinationSiteId);
//            }
//        ];
//        $rules[] = [
//            'destinationUrl',
//            UriValidator::class,
//            'when' => function ($model) {
//                return !empty($model->destinationSiteId);
//            }
//        ];

        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (
            !$this->postDate &&
            (
                in_array($this->scenario, [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT]) ||
                (!$this->getIsDraft() && !$this->getIsRevision())
            )
        ) {
            // Default the post date to the current date/time
            $this->postDate = new DateTime();
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
            // ...unless an expiry date is set in the past
            if ($this->expiryDate && $this->postDate >= $this->expiryDate) {
                $this->postDate = (clone $this->expiryDate)->modify('-1 day');
            }
        }
        if (!$this->type) {
            $this->type = self::TYPE_STATIC;
        }

        return parent::beforeValidate();
    }

    /**
     * Soft-delete the record with the element
     *
     * @return bool
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $record = RedirectRecord::findOne($this->id);
        return (bool)$record?->softDelete();
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        // TODO: Deal with redirects from 404 list
        /*
         *      $fromCatchAllId = Craft::$app->request->getBodyParam('catchAllRecordId');
        if ($fromCatchAllId) {
            $catchAllRecord = CatchAllUrl::findOne($fromCatchAllId);
            if ($catchAllRecord) {
                $catchAllRecord->delete();
            }
        }
         */
        if ($this->propagating) {
            parent::afterSave($isNew);
            return;
        }
        if ($isNew) {
            $record = new RedirectRecord();
            $record->id = (int)$this->id;
        } else {
            $record = RedirectRecord::findOne($this->id);
        }

        if (!$record) {
            throw new InvalidConfigException("Invalid redirect ID: $this->id");
        }

        $record->hitCount = $this->hitCount;
        $record->hitAt = $this->hitAt;
        $record->sourceUrl = $this->formatUrl(trim($this->sourceUrl), true);

        // Don't overwrite an existing destinationUrl, just in case...
        if ($this->destinationUrl) {
            $record->destinationUrl = $this->formatUrl(trim($this->destinationUrl), false);
        }

        $record->destinationElementId = $this->destinationElementId;
        $record->destinationSiteId = $this->destinationSiteId;

        $record->statusCode = $this->statusCode;
        $record->type = $this->type;

        $record->postDate = Db::prepareDateForDb($this->postDate);
        $record->expiryDate = Db::prepareDateForDb($this->expiryDate);

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($record->getDirtyAttributes());

        $record->save(false);
        $this->setDirtyAttributes($dirtyAttributes);
        parent::afterSave($isNew);
    }

    /**
     * Cleans a URL by removing its base URL if it's a relative one
     * Also strip leading slashes from absolute URLs
     *
     * @param string $url
     * @param bool $isSource
     * @return string
     */
    public function formatUrl(string $url, bool $isSource = false): string
    {
        /** @var Settings $settings */
        $settings = Plugin::getInstance()->getSettings();

        $resultUrl = $url;
        $urlInfo = parse_url($resultUrl);
        $siteUrlHost = parse_url($this->site->getBaseUrl(true), PHP_URL_HOST);
        $siteBaseUrlParts = parse_url($this->site->getBaseUrl(true));

        // If we're the source and we're static or we're not the source, we should check for relative URLs
        if ($this->type === self::TYPE_STATIC || !$isSource) {
            // If our redirect source or destination has our site URL, let's strip it out
            if (isset($urlInfo['host']) && $urlInfo['host'] === $siteBaseUrlParts['host']) {
                unset($urlInfo['scheme'], $urlInfo['host'], $urlInfo['port']);
            }

            // We're down to a relative URL, let's strip the leading slash from the path
            if (!isset($urlInfo['host']) && isset($urlInfo['path'])) {
                $urlInfo['path'] = ltrim($urlInfo['path'], '/');
            }

            // Remove the trailing slash from the path if enabled (only for sourceUrls)
            if ($isSource && isset($urlInfo['path']) && $settings->trimTrailingSlashFromPath) {
                $urlInfo['path'] = rtrim($urlInfo['path'], '/');
            }

            // Rebuild our URL
            $resultUrl = self::unparseUrl($urlInfo);
        }
        return $resultUrl;
    }

    /**
     * Source: https://www.php.net/manual/en/function.parse-url.php#106731
     *
     * @param array $parsedUrl
     * @return string
     */
    private static function unparseUrl(array $parsedUrl): string
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = $parsedUrl['user'] ?? '';
        $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parsedUrl['path'] ?? '';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }


    public function __toString(): string
    {
        return $this->sourceUrl;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'statusCode':
                return $this->statusCode ? Html::encodeParams('{statusCode}', [
                    'statusCode' => Plugin::t(self::getRedirectStatusCodes()[$this->statusCode])
                ]) : '';

            case 'destinationUrl':
                if ($this->type === self::TYPE_STATIC) {
                    return $this->renderDestinationUrl();
                }
                return $this->destinationUrl;
            default:
                break;
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     */
    private function renderDestinationUrl(): string
    {
        if (isset($this->destinationElementId)) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => Craft::$app->elements->getElementById($this->destinationElementId, null,
                    $this->destinationSiteId),
            ]);
        }
        if ($this->destinationUrl) {
            return Html::a(Html::tag('span', $this->destinationUrl, ['dir' => 'ltr']), $this->resolveDestinationUrl(), [
                'href' => $this->destinationUrl,
                'rel' => 'noopener',
                'target' => '_blank',
                'class' => 'go',
                'title' => Craft::t('app', 'Visit webpage'),
            ]);
        }
        return '';
    }

    public function getDestinationElement(): ?Element
    {
        if ($this->destinationElementId !== null) {
            return Craft::$app->getElements()->getElementById($this->destinationElementId, null, $this->siteId);
        }
        return null;
    }

    /**
     * Attempt to figure out if the destination URL can be converted to an element
     */
    public function refreshDestinationElement(): void
    {
        if (!isset($this->destinationUrl)) {
            return;
        }

        $element = Craft::$app->getElements()->getElementByUri($this->destinationUrl, $this->destinationSiteId, true);
        if ($element) {
            $this->destinationElementId = $element->id;
        }
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can(Plugin::PERMISSION_MANAGE_REDIRECTS) && (Craft::$app->getIsMultiSite() && $user->can('editSite:' . $this->site->uid));
    }


    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        // Everyone with view permissions can create drafts
        return true;
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }
        return $user->can(Plugin::PERMISSION_MANAGE_REDIRECTS) && (Craft::$app->getIsMultiSite() && $user->can('editSite:' . $this->site->uid));
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        return $user->can(Plugin::PERMISSION_MANAGE_REDIRECTS) && (Craft::$app->getIsMultiSite() && $user->can('editSite:' . $this->site->uid));
    }

    public function canDeleteForSite(User $user): bool
    {
        if (parent::canDeleteForSite($user)) {
            return true;
        }
        return $user->can(Plugin::PERMISSION_MANAGE_REDIRECTS) && (Craft::$app->getIsMultiSite() && $user->can('editSite:' . $this->site->uid));
    }

    protected static function defineExporters(string $source): array
    {
        $exporters = parent::defineExporters($source);
        // TODO: Add custom exporter
        return $exporters;
    }
}
