<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m200203_200023_add_404_groups migration.
 */
class m200203_200023_add_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%venveo_redirects_redirect_groups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%venveo_redirects_404_groups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->addColumn('{{%venveo_redirects}}', 'groupId', $this->integer()->null()->after('hitAt'));
        $this->addColumn('{{%venveo_redirects_catch_all_urls}}', 'groupId', $this->integer()->null()->after('query'));

        $this->createIndex(null, '{{%venveo_redirects_redirect_groups}}', ['name'], true);
        $this->createIndex(null, '{{%venveo_redirects_404_groups}}', ['name'], true);

        $this->addForeignKey(null, '{{%venveo_redirects}}', ['groupId'], '{{%venveo_redirects_redirect_groups}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%venveo_redirects_catch_all_urls}}', ['groupId'], '{{%venveo_redirects_404_groups}}', ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200203_200023_add_404_groups cannot be reverted.\n";
        return false;
    }
}
