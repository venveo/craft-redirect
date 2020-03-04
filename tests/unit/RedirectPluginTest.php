<?php

namespace venveo\redirect\tests;


use Codeception\Test\Unit;
use Craft;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use UnitTester;

class RedirectPluginTest extends Unit
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
    }

    /** @test * */
    public function it_can_install()
    {
        $this->assertTrue(Craft::$app->plugins->installPlugin('vredirect'));
    }
}