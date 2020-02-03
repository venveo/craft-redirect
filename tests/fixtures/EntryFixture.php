<?php

namespace vredirectests\fixtures;

use crafttests\fixtures\EntryTypeFixture;
use crafttests\fixtures\SectionsFixture;
use crafttests\fixtures\SitesFixture;

class EntryFixture extends \crafttests\fixtures\EntryFixture {
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/entries.php';

    /**
     * @inheritdoc
     */
    public $depends = [SitesFixture::class, SectionsFixture::class, EntryTypeFixture::class];
}