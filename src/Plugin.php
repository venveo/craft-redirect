<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect;

use Craft;
use craft\base\Element;
use craft\base\Plugin as BasePlugin;
use craft\events\DefineHtmlEvent;
use craft\events\ElementEvent;
use craft\events\ExceptionEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\feedme\events\RegisterFeedMeElementsEvent;
use craft\feedme\services\Elements as FeedMeElementsService;
use craft\helpers\Json;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\ErrorHandler;
use craft\web\Request;
use craft\web\UrlManager;
use Twig\Error\RuntimeError;
use venveo\redirect\elements\FeedMeRedirect;
use venveo\redirect\elements\Redirect;
use venveo\redirect\models\Settings;
use venveo\redirect\records\CatchAllUrl;
use venveo\redirect\services\CatchAll;
use venveo\redirect\services\Groups;
use venveo\redirect\services\Redirects;
use venveo\redirect\web\assets\redirectscp\RedirectsCpAsset;
use venveo\redirect\widgets\LatestErrors;
use yii\base\Event;
use yii\web\HttpException;

/**
 * @property mixed $settingsResponse
 * @property Redirects $redirects
 * @property array $cpNavItem
 * @property CatchAll $catchAll
 * @property Groups $groups
 */
class Plugin extends BasePlugin
{
    public const PERMISSION_MANAGE_REDIRECTS = 'vredirect:redirects:manage';
    public const PERMISSION_MANAGE_GROUPS = 'vredirect:groups:manage';
    public const PERMISSION_MANAGE_404S = 'vredirect:404s:manage';

    public string $schemaVersion = '4.0.0';

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    /*
    *
    *  The Craft plugin documentation points to the EVENT_REGISTER_CP_NAV_ITEMS event to register navigation items.
    *  The getCpNavItem was found in the source and will check the user privilages already.
    *
    */
    public function getCpNavItem(): ?array
    {
        $ret = parent::getCpNavItem();

        $ret['label'] = self::t('Site Redirects');

        $subnavItems = [];
        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser->can(static::PERMISSION_MANAGE_REDIRECTS)) {
            $subnavItems['redirects'] = [
                'label' => static::t('Redirects'),
                'url' => 'redirect/redirects',
            ];
        }
        if ($currentUser->can(static::PERMISSION_MANAGE_GROUPS)) {
            $subnavItems['groups'] = [
                'label' => static::t('Redirect Groups'),
                'url' => 'redirect/groups',
            ];
        }

        if ($currentUser->can(static::PERMISSION_MANAGE_404S)) {
            $subnavItems['catch-all'] = [
                'label' => static::t('Registered 404s'),
                'url' => 'redirect/catch-all',
            ];
            $count = CatchAllUrl::find()->where(['=', 'ignored', false])->count();

            if ($count) {
                $subnavItems['catch-all']['badgeCount'] = $count;
            }

            $subnavItems['ignored'] = [
                'label' => static::t('Ignored 404s'),
                'url' => 'redirect/catch-all/ignored',
            ];
        }

        $ret['subnav'] = $subnavItems;
        $ret['url'] = 'redirect';
        return $ret;
    }

    public static function config(): array
    {
        return [
            'components' => [
                'catchAll' => CatchAll::class,
                'redirects' => Redirects::class,
                'groups' => Groups::class,
            ],
        ];
    }

    /**
     * @param $message
     * @param array $params
     * @param null $language
     * @return string
     * @see Craft::t()
     *
     * @since 2.2.0
     */
    public static function t($message, array $params = [], $language = null): string
    {
        return Craft::t('vredirect', $message, $params, $language);
    }

    public function init()
    {
        parent::init();
        /** @var Settings $settings */
        $settings = $this->getSettings();

        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'venveo\redirect\controllers\console';
        } else {
            $this->registerCpRoutes();
        }
        $this->registerFeedMeElement();
        $this->registerElementEvents();
        $this->registerWidgets();
        $this->registerPermissions();

        if (Craft::$app->request instanceof Request && Craft::$app->request->isCpRequest) {
            $this->attachTemplateHooks();
            $this->registerWidgets();
        }

        // Remove our soft-deleted redirects when Craft is ready
        Event::on(Gc::class, Gc::EVENT_RUN, function () {
            Craft::$app->gc->hardDelete('{{%venveo_redirects}}');
        });

        if (!$settings->redirectsActive) {
            // Return early.
            return;
        }

        // Start lookin' for some 404s!
        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event) {
                $request = Craft::$app->request;
                // We don't care about requests that aren't on our site frontend
                if (!$request->getIsSiteRequest() || $request->getIsLivePreview()) {
                    return;
                }
                $exception = $event->exception;

                if (
                    $exception instanceof RuntimeError &&
                    ($previousException = $exception->getPrevious()) !== null
                ) {
                    $exception = $previousException;
                }

                if ($exception instanceof HttpException && $exception->statusCode === 404) {
                    $this->redirects->handle404($exception);
                }
            }
        );
    }

    private function registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'redirect' => ['template' => 'vredirect/index'],

                'redirect/catch-all/<siteId:\d+>' => 'vredirect/catch-all/index',
                'redirect/catch-all' => 'vredirect/catch-all/index',

                'redirect/groups/<id:\d+>' => 'vredirect/groups/edit',
                'redirect/groups/new' => 'vredirect/groups/edit',
                'redirect/groups' => 'vredirect/groups/index',

                'redirect/catch-all/ignored' => 'vredirect/catch-all/ignored',
                'redirect/catch-all/ignored/<siteId:\d+>' => 'vredirect/catch-all/ignored',

                'redirect/dashboard' => 'vredirect/dashboard/index',

                'redirect/redirects' => 'vredirect/redirects/index',
                'redirect/redirects/create' => 'vredirect/redirects/create',
                'redirect/redirects/new' => 'vredirect/redirects/edit-redirect',
                'redirect/redirects/<elementId:\d+>' => 'elements/edit',
            ]);
        });
    }

    /**
     * Registers our custom feed import logic if feed-me is enabled. Also note, we're checking for craft\feedme
     */
    private function registerFeedMeElement(): void
    {
        if (Craft::$app->plugins->isPluginEnabled('feed-me') && class_exists(\craft\feedme\Plugin::class)) {
            Event::on(
                FeedMeElementsService::class,
                FeedMeElementsService::EVENT_REGISTER_FEED_ME_ELEMENTS,
                static function (RegisterFeedMeElementsEvent $e) {
                    $e->elements[] = FeedMeRedirect::class;
                }
            );
        }
    }

    private function registerElementEvents()
    {
        if (!self::getInstance()->getSettings()->createElementRedirects) {
            return;
        }

        Event::on(Elements::class, Elements::EVENT_BEFORE_SAVE_ELEMENT, static function (ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }

            Plugin::getInstance()->redirects->handleBeforeElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, static function (ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }

            Plugin::getInstance()->redirects->handleAfterElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_BEFORE_UPDATE_SLUG_AND_URI, static function (ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }
            Plugin::getInstance()->redirects->handleBeforeElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI, static function (ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }
            Plugin::getInstance()->redirects->handleAfterElementSaved($e);
        });
    }

    private function registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => static::t('Redirects'),
                    'permissions' => [
                        static::PERMISSION_MANAGE_REDIRECTS => ['label' => static::t('Manage Redirects on Editable Sites')],
                        static::PERMISSION_MANAGE_404S => ['label' => static::t('Manage Registered 404s')],
                        static::PERMISSION_MANAGE_GROUPS => ['label' => static::t('Manage Redirect Groups')],
                    ],
                ];
            });
    }

    /**
     * @return void
     */
    protected function attachTemplateHooks()
    {
        $currentUser = \Craft::$app->getUser()->getIdentity();
        if (!$currentUser || !$currentUser->can(static::PERMISSION_MANAGE_REDIRECTS)) {
            return;
        }

        Craft::$app->view->registerAssetBundle(RedirectsCpAsset::class);

        Event::on(
            Element::class,
            Element::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event) {
                if ($event->static) {
                    return;
                }

                /** @var Element $element */
                $element = $event->sender ?? null;

                if (!$element || !$element->getCanonical()->uri) {
                    return;
                }
                $elementId = $element->getCanonicalId();
                $idJs = Json::encode($elementId);
                $siteIdJs = Json::encode($element->siteId);
                Craft::$app->view->registerJs("Craft.elementRedirectSlideout = (new Craft.Redirects.ElementRedirectSlideout($idJs, $siteIdJs))");
                $redirectCount = Redirect::find()->destinationElementId($elementId)->count();
                if ($redirectCount) {
                    $word = $redirectCount > 1 ? Redirect::pluralDisplayName() : Redirect::displayName();
                    $event->html .= '
<dl class="meta read-only">
            <div class="data">
                <h5 class="heading">' . Redirect::pluralDisplayName() . '</h5>
                <div id="redirect-slideout-trigger" class="value"><button class="btn small">View ' . $redirectCount . ' ' . $word . '</button></div>
            </div>
</dl>
            ';
                }
            }
        );
    }

    private function registerWidgets()
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = LatestErrors::class;
            }
        );
    }

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'vredirect/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
