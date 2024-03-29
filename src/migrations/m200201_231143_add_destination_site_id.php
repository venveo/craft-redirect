<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;

/**
 * m200201_231143_add_destination_site_id migration.
 */
class m200201_231143_add_destination_site_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%venveo_redirects}}',
            'destinationSiteId',
            $this->integer()->null()->defaultValue(null)->after('destinationElementId')
        );
        $this->addForeignKey(null, '{{%venveo_redirects}}', ['destinationSiteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200201_231143_add_destination_site_id cannot be reverted.\n";
        return false;
    }
}
