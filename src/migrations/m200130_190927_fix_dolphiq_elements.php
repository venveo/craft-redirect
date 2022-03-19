<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;
use craft\db\Table;
use venveo\redirect\elements\Redirect;

/**
 * m200130_190927_fix_dolphiq_elements migration.
 */
class m200130_190927_fix_dolphiq_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(Table::ELEMENTS, ['type' => Redirect::class], ['type' => 'dolphiq\redirect\elements\Redirect']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200130_190927_fix_dolphiq_elements cannot be reverted.\n";
        return false;
    }
}
