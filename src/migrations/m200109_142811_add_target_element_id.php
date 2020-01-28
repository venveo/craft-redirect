<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;

/**
 * m200109_142811_add_target_entry_id migration.
 */
class m200109_142811_add_target_element_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%venveo_redirects}}',
            'destinationElementId',
            $this->integer()->null()->defaultValue(null)->after('destinationUrl')
        );
        $this->addForeignKey(null, '{{%venveo_redirects}}', ['destinationElementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200109_142811_add_target_entry_id cannot be reverted.\n";
        return false;
    }
}
