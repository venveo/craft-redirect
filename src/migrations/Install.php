<?php

/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();

        echo " done\n";
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
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

        $this->createTable('{{%venveo_redirects}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string('8')->null()->defaultValue('static')->notNull(),
            'sourceUrl' => $this->string(255),
            'destinationUrl' => $this->string(255),
            'destinationElementId' => $this->integer()->null()->defaultValue(null),
            'destinationSiteId' => $this->integer()->null()->defaultValue(null),
            'statusCode' => $this->string(3),
            'hitCount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'hitAt' => $this->dateTime(),
            'groupId' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid()
        ]);

        if (!$this->db->tableExists('{{%venveo_redirects_catch_all_urls}}')) {

            $this->createTable(
                '{{%venveo_redirects_catch_all_urls}}',
                [
                    'id' => $this->primaryKey(),
                    'uri' => $this->string(255)->notNull()->defaultValue(''),
                    'query' => $this->string(255)->null()->defaultValue(null),
                    'siteId' => $this->integer()->null()->defaultValue(null),
                    'hitCount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                    'groupId' => $this->integer()->null(),
                    'ignored' => $this->boolean()->notNull()->defaultValue(false),
                    'referrer' => $this->text()->null(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        $this->addForeignKey(null, '{{%venveo_redirects}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%venveo_redirects}}', ['destinationElementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%venveo_redirects}}', ['groupId'], '{{%venveo_redirects_redirect_groups}}', ['id'], 'SET NULL', null);

        $this->addForeignKey(null, '{{%venveo_redirects_catch_all_urls}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%venveo_redirects_catch_all_urls}}', ['groupId'], '{{%venveo_redirects_404_groups}}', ['id'], 'SET NULL', null);

        $this->createIndex(null, '{{%venveo_redirects_redirect_groups}}', ['name'], true);
        $this->createIndex(null, '{{%venveo_redirects_404_groups}}', ['name'], true);

        $this->createIndex($this->db->getIndexName('{{%venveo_redirects}}', ['sourceUrl'], false), '{{%venveo_redirects}}', ['sourceUrl'], false);
        $this->createIndex($this->db->getIndexName('{{%venveo_redirects_catch_all_urls}}', 'uri', false), '{{%venveo_redirects_catch_all_urls}}', 'uri', false);
        $this->createIndex($this->db->getIndexName('{{%venveo_redirects}}', 'type'), '{{%venveo_redirects}}', 'type');
    }

    // Protected Methods
    // =========================================================================

    public function safeDown()
    {
        $this->dropTableIfExists('{{%venveo_redirects}}');
        $this->dropTableIfExists('{{%venveo_redirects_catch_all_urls}}');
        $this->dropTableIfExists('{{%venveo_redirects_404_groups}}');
        $this->dropTableIfExists('{{%venveo_redirects_redirect_groups}}');
        return true;
    }
}
