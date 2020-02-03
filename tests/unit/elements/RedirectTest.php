<?php

namespace vredirectests\unit\elements;

use Codeception\Test\Unit;

use craft\elements\Entry;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\SitesFixture;
use UnitTester;
use Craft;
use venveo\redirect\elements\Redirect;

class RedirectTest extends Unit {

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class
            ],
            'entries' => [
                'class' => EntryFixture::class
            ]
        ];
    }

    public function testSaveRedirect() {
        $entry = Entry::find()->slug('with-url-1')->one();
        $entry->slug = 'needs-redirect';
        Craft::$app->elements->saveElement($entry);

        $redirect = Redirect::find()->sourceUrl('some-uri/with-url-1')->one();
        $this->assertNotNull($redirect);
    }
}