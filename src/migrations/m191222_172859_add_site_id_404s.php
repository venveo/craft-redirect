<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;

/**
 * m191222_172859_add_site_id_404s migration.
 */
class m191222_172859_add_site_id_404s extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%venveo_redirects_catch_all_urls}}', 'siteId',
            $this->integer()->null()->defaultValue(null));
        $this->addForeignKey(null, '{{%venveo_redirects_catch_all_urls}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191222_172859_add_site_id_404s cannot be reverted.\n";
        return false;
    }
}
