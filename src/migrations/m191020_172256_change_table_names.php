<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m191020_172256_change_table_names migration.
 */
class m191020_172256_change_table_names extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameTable('{{%dolphiq_redirects}}', '{{%venveo_redirects}}');
        $this->renameTable('{{%dolphiq_redirects_catch_all_urls}}', '{{%venveo_redirects_catch_all_urls}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191020_172256_change_table_names cannot be reverted.\n";
        return false;
    }
}
