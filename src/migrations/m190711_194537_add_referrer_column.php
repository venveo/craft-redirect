<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190711_194537_add_referral_column migration.
 */
class m190711_194537_add_referrer_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%dolphiq_redirects_catch_all_urls}}', 'referrer',
            $this->string(2000)->null()->after('ignored')->defaultValue(null));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dolphiq_redirects_catch_all_urls}}', 'referrer');
        return true;
    }
}
