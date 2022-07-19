<?php

namespace vredirectests\unit\services;

use Codeception\Test\Unit;

use UnitTester;
use Craft;

class RedirectsTest extends Unit
{
    protected UnitTester $tester;

    public function testExample()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition());
    }
}