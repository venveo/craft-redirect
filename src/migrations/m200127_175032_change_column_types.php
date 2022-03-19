<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;

/**
 * m200127_175032_change_column_types migration.
 */
class m200127_175032_change_column_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%venveo_redirects_catch_all_urls}}', 'referrer', $this->text());

        $this->alterColumn('{{%venveo_redirects}}', 'sourceUrl', $this->string(255));
        $this->alterColumn('{{%venveo_redirects}}', 'destinationUrl', $this->string(255));
        $this->alterColumn('{{%venveo_redirects}}', 'statusCode', $this->string(3));

        $this->createIndex($this->db->getIndexName('{{%venveo_redirects}}', ['sourceUrl'], false), '{{%venveo_redirects}}', ['sourceUrl'], false);
        $this->createIndex($this->db->getIndexName('{{%venveo_redirects_catch_all_urls}}', 'uri', false), '{{%venveo_redirects_catch_all_urls}}', 'uri', false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200127_175032_change_column_types cannot be reverted.\n";
        return false;
    }
}
