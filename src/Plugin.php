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
use craft\elements\Entry;
use craft\events\DeleteElementEvent;
use craft\events\ElementEvent;
use craft\events\ExceptionEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
//use craft\feedme\events\RegisterFeedMeElementsEvent;
//use craft\feedme\services\Elements as FeedMeElementsService;
use craft\helpers\Json;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use craft\web\View;
use Twig\Error\RuntimeError;
use venveo\redirect\assetbundles\elementredirectslideout\ElementRedirectSlideout;
//use venveo\redirect\elements\FeedMeRedirect;
use venveo\redirect\elements\Redirect;
use venveo\redirect\models\Settings;
use venveo\redirect\records\CatchAllUrl;
use venveo\redirect\services\CatchAll;
use venveo\redirect\services\Redirects;
use venveo\redirect\widgets\LatestErrors;
use yii\base\Event;
use yii\web\HttpException;

/**
 * @property mixed $settingsResponse
 * @property Redirects $redirects
 * @property array $cpNavItem
 * @property CatchAll $catchAll
 * @property mixed _redirectsService
 * @property mixed _catchAllService
 */
class Plugin extends BasePlugin
{
    public const PERMISSION_MANAGE_REDIRECTS = 'vredirect:redirects:manage';
    public const PERMISSION_MANAGE_404S = 'vredirect:404s:manage';
    /** @var self $plugin */
    public static $plugin;
    public string $schemaVersion = '3.0.5';

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    protected $_redirectsService;
    protected $_catchAllService;

    /**
     * Returns the Redirects service.
     *
     * @return Redirects The Redirects service
     */
    public function getRedirects(): Redirects
    {
        if ($this->_redirectsService == null) {
            $this->_redirectsService = new Redirects();
        }
        return $this->_redirectsService;
    }

    public function getCatchAll()
    {
        if ($this->_catchAllService == null) {
            $this->_catchAllService = new CatchAll();
        }

        return $this->_catchAllService;
    }

    public function install(): void
    {
        if ($this->beforeInstall() === false) {
            return;
        }

        $migrator = $this->getMigrator();

        $oldPlugin = Craft::$app->plugins->getPlugin('redirect');
        if ($oldPlugin) {
            // We need to copy the migrations that have already been run on the original plugin to our new plugin
            $oldPluginMigrations = $oldPlugin->getMigrator()->getMigrationHistory();
            foreach ($oldPluginMigrations as $name) {
                $migrator->addMigrationHistory($name);
            }
            // Now we'll apply all the new migrations
            $migrator->up();

            // Disable the old plugin
            Craft::$app->plugins->disablePlugin('redirect');

            $this->isInstalled = true;
            $this->afterInstall();
            return;
        }

        // Run the install migration, if there is one
        if (($migration = $this->createInstallMigration()) !== null) {
            $migrator->migrateUp($migration);
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        $this->isInstalled = true;

        $this->afterInstall();
    }

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
        if ($currentUser->can('vredirect:redirects:manage')) {
            $subnavItems['redirects'] = [
                'label' => Craft::t('vredirect', 'Redirects'),
                'url' => 'redirect/redirects',
            ];
        }

        if ($currentUser->can('vredirect:404s:manage')) {
            $subnavItems['catch-all'] = [
                'label' => Craft::t('vredirect', 'Registered 404s'),
                'url' => 'redirect/catch-all',
            ];
            $count = CatchAllUrl::find()->where(['=', 'ignored', false])->count();

            if ($count) {
                $subnavItems['catch-all']['badgeCount'] = $count;
            }

            $subnavItems['ignored'] = [
                'label' => Craft::t('vredirect', 'Ignored 404s'),
                'url' => 'redirect/catch-all/ignored',
            ];
        }

        $ret['subnav'] = $subnavItems;
        $ret['url'] = 'redirect';
        return $ret;
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
    public static function t($message, $params = [], $language = null)
    {
        return Craft::t('vredirect', $message, $params, $language);
    }

    public function init()
    {
        parent::init();
        self::$plugin = $this;
        /** @var Settings $settings */
        $settings = self::$plugin->getSettings();

        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'venveo\redirect\controllers\console';
        } else {
            $this->registerCpRoutes();
        }
//        $this->registerFeedMeElement();
        $this->registerElementEvents();
        $this->registerWidgets();
        $this->registerPermissions();

        if (Craft::$app->request->isCpRequest) {
            $this->attachTemplateHooks();
            $this->registerWidgets();
        }

        // Remove our soft-deleted redirects when Craft is ready
        Event::on(Gc::class, Gc::EVENT_RUN, function() {
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
            static function(ExceptionEvent $event) {
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
                    self::$plugin->redirects->handle404($exception);
                }
            }
        );
    }

    private function registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'redirect' => ['template' => 'vredirect/index'],

                'redirect/catch-all/<siteId:\d+>' => 'vredirect/catch-all/index',
                'redirect/catch-all' => 'vredirect/catch-all/index',

                'redirect/catch-all/ignored' => 'vredirect/catch-all/ignored',
                'redirect/catch-all/ignored/<siteId:\d+>' => 'vredirect/catch-all/ignored',

                'redirect/dashboard' => 'vredirect/dashboard/index',

                'redirect/redirects' => 'vredirect/redirects/index',
                'redirect/redirects/new' => 'vredirect/redirects/edit-redirect',
                'redirect/redirects/<redirectId:\d+>' => 'vredirect/redirects/edit-redirect',
            ]);
        });
    }

    /**
     * Registers our custom feed import logic if feed-me is enabled. Also note, we're checking for craft\feedme
     */
    private function registerFeedMeElement()
    {
//        if (Craft::$app->plugins->isPluginEnabled('feed-me') && class_exists(\craft\feedme\Plugin::class)) {
//            Event::on(
//                FeedMeElementsService::class,
//                FeedMeElementsService::EVENT_REGISTER_FEED_ME_ELEMENTS,
//                function(RegisterFeedMeElementsEvent $e) {
//                    $e->elements[] = FeedMeRedirect::class;
//                }
//            );
//        }
    }

    private function registerElementEvents()
    {
        if (!self::getInstance()->getSettings()->createElementRedirects) {
            return;
        }

        Event::on(Elements::class, Elements::EVENT_BEFORE_SAVE_ELEMENT, function(ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }

            Plugin::getInstance()->redirects->handleBeforeElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }

            Plugin::getInstance()->redirects->handleAfterElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_BEFORE_UPDATE_SLUG_AND_URI, function(ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }
            Plugin::getInstance()->redirects->handleBeforeElementSaved($e);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI, function(ElementEvent $e) {
            /** @var Element $element */
            $element = $e->element;

            $shouldProcess = !$element->getIsDraft() && !$element->isProvisionalDraft && $element->isCanonical && !$element->firstSave;
            if (!$shouldProcess) {
                return;
            }
            Plugin::getInstance()->redirects->handleAfterElementSaved($e);
        });



        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, function(DeleteElementEvent $e) {
            Plugin::getInstance()->redirects->handleElementDeleted($e);
        });

        Event::on(Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT, function(ElementEvent $e) {
            Plugin::getInstance()->redirects->handleElementRestored($e);
        });
    }

    private function registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[Craft::t('vredirect', 'Redirects')] = [
                    'vredirect:redirects:manage' => [
                        'label' => Craft::t('vredirect', 'Manage Redirects on Editable Sites'),
                    ],
                    'vredirect:404s:manage' => [
                        'label' => Craft::t('vredirect', 'Manage Registered 404s'),
                    ],
                ];
            }
        );
    }

    protected function attachTemplateHooks()
    {
        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function(TemplateEvent $e) {
            $currentUser = \Craft::$app->getUser()->getIdentity();
            if (!$currentUser || !$currentUser->can('vredirect:redirects:manage')) {
                return;
            }
            if ($e->template === 'entries/_edit') {
                Craft::$app->view->registerAssetBundle(ElementRedirectSlideout::class);
            }
        });
        Craft::$app->view->hook('cp.entries.edit.meta', function(array &$context) {
            $currentUser = \Craft::$app->getUser()->getIdentity();
            if (!$currentUser || !$currentUser->can('vredirect:redirects:manage')) {
                return '';
            }
            /** @var Entry $element */
            $element = $context['element'] ?? null;
            if (!$element || !$element->getCanonicalId()) {
                return '';
            }
            $elementId = $element->getCanonicalId();
            $idJs = Json::encode($elementId);
            $siteIdJs = Json::encode($element->siteId);
            Craft::$app->view->registerJs("Craft.elementRedirectSlideout = (new Craft.ElementRedirectSlideout($idJs, $siteIdJs))");
            $redirectCount = Redirect::find()->destinationElementId($elementId)->count();
            if ($redirectCount) {
                $word = $redirectCount > 1 ? Redirect::pluralDisplayName() : Redirect::displayName();
                return '
            <div class="data">
                <h5 class="heading">' . Redirect::pluralDisplayName() . '</h5>
                <div id="redirect-slideout-trigger" class="value"><button class="btn small">View ' . $redirectCount . ' ' . $word . '</button></div>
            </div>
            ';
            }
        });
    }

    private function registerWidgets()
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function(RegisterComponentTypesEvent $event) {
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
