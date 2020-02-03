<?php

namespace vredirectests\unit\elements;

use Codeception\Test\Unit;

use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\SitesFixture;
use UnitTester;
use Craft;

class RedirectTest extends Unit {

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class
            ],
            'sites' => [
                'class' => SitesFixture::class
            ]
        ];
    }
}