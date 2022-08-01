<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220719_140156_vredirect migration.
 */
class m220719_140156_track_autocreated extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(
            '{{%venveo_redirects}}',
            'createdAutomatically',
            $this->boolean()->defaultValue(false)->after('statusCode')
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220719_140156_track_autocreated cannot be reverted.\n";
        return false;
    }
}
