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
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\validators\DateTimeValidator;
use craft\validators\SiteIdValidator;
use craft\validators\UriValidator;
use craft\validators\UrlValidator;
use craft\web\ErrorHandler;
use DateTime;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\redirect\elements\actions\DeleteRedirects;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\models\Settings;
use venveo\redirect\Plugin;
use venveo\redirect\records\Redirect as RedirectRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 *
 * @property null|Site $destinationSite
 * @property string $name
 */
class Redirect extends Element
{
    public const TYPE_STATIC = 'static';
    public const TYPE_DYNAMIC = 'dynamic';

    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CODE_OPTIONS = [
        '301' => 'Permanent (301)',
        '302' => 'Temporarily (302)',
    ];

    public const TYPE_OPTIONS = [
        'static' => 'Static',
        'dynamic' => 'Dynamic (RegExp)',
    ];
    /**
     * @var string sourceUrl
     */
    public $sourceUrl;
    /**
     * @var string|null destinationUrl
     */
    public $destinationUrl;
    /**
     * @var string|null hitAt
     */
    public $hitAt;
    /**
     * @var string|null hitCount
     */
    public $hitCount;
    /**
     * @var string|null statusCode
     */
    public $statusCode;
    /**
     * @var string type
     */
    public $type;
    /**
     * @var int|null siteId
     */
    public ?int $siteId;

    /**
     * @var int|null destinationElementId
     */
    public $destinationElementId;

    /**
     * @var int|null destinationSiteId
     */
    public $destinationSiteId;

    /**
     * @var DateTime
     */
    public $postDate;

    /**
     * @var DateTime
     */
    public $expiryDate;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('vredirect', 'Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('vredirect', 'Redirects');
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
     *
     * @return RedirectQuery The newly created [[RedirectQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new RedirectQuery(static::class);
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
                    'label' => Craft::t('vredirect', 'All Redirects'),
                    'criteria' => [],
                ],
                [
                    'key' => 'static',
                    'label' => Craft::t('vredirect', 'Static Redirects'),
                    'criteria' => ['type' => Redirect::TYPE_STATIC],
                ],
                [
                    'key' => 'dynamic',
                    'label' => Craft::t('vredirect', 'Dynamic Redirects'),
                    'criteria' => ['type' => Redirect::TYPE_DYNAMIC],
                ],
                [
                    'key' => 'stale',
                    'label' => Craft::t('vredirect', 'Stale Redirects'),
                    'criteria' => ['hitAt' => '< ' . Db::prepareDateForDb($staleDate)],
                ],
            ];
        }
        return $sources;
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
    protected static function defineSortOptions(): array
    {
        $attributes = [
            [
                'label' => Craft::t('vredirect', 'Source URL'),
                'orderBy' => 'venveo_redirects.sourceUrl',
                'attribute' => 'sourceUrl',
            ],
            'venveo_redirects.type' => Craft::t('vredirect', 'Type'),
            [
                'label' => Craft::t('vredirect', 'Destination URL'),
                'orderBy' => 'venveo_redirects.destinationUrl',
                'attribute' => 'destinationUrl',
            ],
            [
                'label' => Craft::t('vredirect', 'Last Hit'),
                'orderBy' => 'venveo_redirects.hitAt',
                'attribute' => 'hitAt',
            ],
            'venveo_redirects.hitCount' => Craft::t('vredirect', 'Hit Count'),
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
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'sourceUrl' => ['label' => Craft::t('vredirect', 'Source URL')],
            'type' => ['label' => Craft::t('vredirect', 'Type')],
            'destinationUrl' => ['label' => Craft::t('vredirect', 'Destination URL')],
            'hitAt' => ['label' => Craft::t('vredirect', 'Last Hit')],
            'hitCount' => ['label' => Craft::t('vredirect', 'Hit Count')],
            'postDate' => ['label' => Craft::t('app', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('app', 'Expiry Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'statusCode' => ['label' => Craft::t('vredirect', 'Redirect Type')],
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Edit
//        $actions[] = Craft::$app->getElements()->createAction(
//            [
//                'type' => Edit::class,
//                'label' => Craft::t('vredirect', 'Edit redirect'),
//            ]
//        );

        // Delete
        $actions[] = DeleteRedirects::class;

        // Restore
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('vredirect', 'Redirects restored.'),
            'partialSuccessMessage' => Craft::t('vredirect', 'Some redirects restored.'),
            'failMessage' => Craft::t('vredirect', 'Redirects not restored.'),
        ]);

        $actions[] = SetStatus::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['sourceUrl', 'destinationUrl', 'statusCode', 'hitAt', 'hitCount', 'dateCreated'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission('editSite:' . $this->getSite()->uid);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('redirect/redirects/' . $this->id . '?siteId=' . $this->siteId);
    }

    /**
     * Gets the actual final absolute destination URL
     *
     * @return string
     * @throws \yii\base\Exception
     */
    public function getDestinationUrl()
    {
        // Redirect to a site element
        if ($this->destinationElementId) {
            $element = Craft::$app->elements->getElementById($this->destinationElementId, null, $this->destinationSiteId ?? $this->siteId);
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
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('vredirect/_redirects/redirectfields', [
            'redirect' => $this,
            'isNewRedirect' => false,
            'meta' => false,
            'statusCodeOptions' => self::STATUS_CODE_OPTIONS,
            'typeOptions' => self::TYPE_OPTIONS,
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    /**
     * Gets the destination site if one is set
     *
     * @return Site|null
     */
    public function getDestinationSite()
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
        $rules[] = [['sourceUrl', 'type'], 'required'];

        $rules[] = [['type'], 'in', 'range' => [self::TYPE_STATIC, self::TYPE_DYNAMIC]];
        $rules[] = [['statusCode'], 'in', 'range' => array_keys(self::STATUS_CODE_OPTIONS)];

        $rules[] = [['destinationSiteId'], SiteIdValidator::class];
        $rules[] = ['destinationElementId', 'exist', 'targetClass' => \craft\records\Element::class, 'targetAttribute' => ['destinationElementId' => 'id']];
        $rules[] = ['destinationSiteId', 'required', 'when' => function($model) {
            return !empty($model->destinationElementId);
        }];
        $rules[] = ['destinationUrl', 'required', 'when' => function($model) {
            return empty($model->destinationElementId);
        }];
        $rules[] = ['destinationUrl', UrlValidator::class, 'when' => function($model) {
            return empty($model->destinationSiteId);
        }];
        $rules[] = ['destinationUrl', UriValidator::class, 'when' => function($model) {
            return !empty($model->destinationSiteId);
        }];

        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        return $rules;
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
        $record = RedirectRecord::findOne($this->id);
        if ($record) {
            $record->softDelete();
        }
        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($this->enabled && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = new \DateTime();
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = RedirectRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid redirect ID: ' . $this->id);
                }
            } else {
                $record = new RedirectRecord();
                $record->id = (int)$this->id;
            }

            if ($this->hitCount > 0) {
                $record->hitCount = $this->hitCount;
            } else {
                $record->hitCount = 0;
            }

            if ($record->hitAt != null) {
                $record->hitAt = $this->hitAt;
            } else {
                $record->hitAt = null;
            }

            $record->sourceUrl = $this->formatUrl(trim($this->sourceUrl), true);

            // Don't overwrite an existing destinationUrl, just in case...
            if ($this->destinationUrl) {
                $record->destinationUrl = $this->formatUrl(trim($this->destinationUrl), false);
            }

            $record->destinationElementId = $this->destinationElementId;
            $record->destinationSiteId = $this->destinationSiteId;

            $record->statusCode = $this->statusCode;
            $record->type = $this->type;
            if ($this->dateCreated) {
                $record->dateCreated = $this->dateCreated;
            }
            if ($this->dateUpdated) {
                $record->dateUpdated = $this->dateUpdated;
            }
            $record->postDate = $this->postDate;
            $record->expiryDate = $this->expiryDate;

            if ($this->enabled && !$this->postDate) {
                // Default the post date to the current date/time
                $this->postDate = new \DateTime();
                // ...without the seconds
                $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
            }


            $record->save(false);
        }
        parent::afterSave($isNew);
    }

    /**
     * Cleans a URL by removing its base URL if it's a relative one
     * Also strip leading slashes from absolute URLs
     * @param string $url
     * @param bool $isSource
     * @return string
     */
    public function formatUrl(string $url, $isSource = false): string
    {
        /** @var Settings $settings */
        $settings = Plugin::getInstance()->getSettings();

        $resultUrl = $url;
        $urlInfo = parse_url($resultUrl);
        $siteUrlHost = parse_url($this->site->baseUrl, PHP_URL_HOST);
        // If we're the source and we're static or we're not the source, we should check for relative URLs
        if ($this->type === self::TYPE_STATIC || !$isSource) {
            // If our redirect source or destination has our site URL, let's strip it out
            if (isset($urlInfo['host']) && $urlInfo['host'] === $siteUrlHost) {
                unset($urlInfo['scheme'], $urlInfo['host'], $urlInfo['port']);
            }

            // We're down to a relative URL, let's strip the leading slash from the path
            if (!isset($urlInfo['host']) && isset($urlInfo['path'])) {
                $urlInfo['path'] = ltrim($urlInfo['path'], '/');
            }

            // Remove the trailing slash from the path if enabled
            if (isset($urlInfo['path']) && $settings->trimTrailingSlashFromPath) {
                $urlInfo['path'] = rtrim($urlInfo['path'], '/');
            }

            // Rebuild our URL
            $resultUrl = self::unparseUrl($urlInfo);
        }
        return $resultUrl;
    }

    /**
     * Source: https://www.php.net/manual/en/function.parse-url.php#106731
     * @param array $parsed_url
     * @return string
     */
    private static function unparseUrl($parsed_url): string
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = $parsed_url['host'] ?? '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = $parsed_url['user'] ?? '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parsed_url['path'] ?? '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }


    public function __toString()
    {
        try {
            return $this->sourceUrl;
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'hitAt';
        $attributes[] = 'postDate';
        $attributes[] = 'expiryDate';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'statusCode':
                return $this->statusCode ? Html::encodeParams('{statusCode}', ['statusCode' => Craft::t('vredirect', self::STATUS_CODE_OPTIONS[$this->statusCode])]) : '';

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
     * @return string|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     */
    private function renderDestinationUrl()
    {
        if (isset($this->destinationElementId)) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => Craft::$app->elements->getElementById($this->destinationElementId, null, $this->destinationSiteId),
            ]);
        }
        if ($this->destinationUrl) {
            return Html::a(Html::tag('span', $this->destinationUrl, ['dir' => 'ltr']), $this->getDestinationUrl(), [
                'href' => $this->destinationUrl,
                'rel' => 'noopener',
                'target' => '_blank',
                'class' => 'go',
                'title' => Craft::t('app', 'Visit webpage'),
            ]);
        }
        return '';
    }

    /**
     * Attempt to figure out if the destination URL can be converted to an element
     */
    public function refreshDestinationElement()
    {
        if (!isset($this->destinationUrl)) {
            return;
        }

        $element = Craft::$app->getElements()->getElementByUri($this->destinationUrl, $this->destinationSiteId, true);
        if ($element) {
            $this->destinationElementId = $element->id;
        }
    }
}
