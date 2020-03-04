<?php

namespace venveo\redirect\tests;


use Codeception\Test\Unit;
use Craft;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use UnitTester;
use venveo\redirect\elements\Redirect;

class RedirectsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;


    /** @var \craft\models\Section */
    private $section;

    protected function _before()
    {
        parent::_before();

        $section = new Section([
            'name' => 'Blog',
            'handle' => 'blog',
            'type' => Section::TYPE_CHANNEL,
            'siteSettings' => [
                new Section_SiteSettings([
                    'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                    'enabledByDefault' => true,
                    'hasUrls' => true,
                    'uriFormat' => 'blog/{slug}',
                    'template' => 'blog/_entry',
                ]),
            ],
        ]);

        Craft::$app->getSections()->saveSection($section);

        $this->section = $section;
        Craft::$app->plugins->installPlugin('vredirect');
    }

    /** @test * */
    public function create_redirect()
    {
        $redirect = new Redirect();
    }
}