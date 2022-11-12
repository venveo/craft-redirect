<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;

/**
 * m220719_141827_add_redirect_groups migration.
 */
class m220719_141827_add_redirect_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%venveo_redirect_groups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(128)->unique()->notNull(),
            'description' => $this->text()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->addColumn(
            '{{%venveo_redirects}}',
            'groupId',
            $this->integer()->null()->defaultValue(null)->after('createdAutomatically')
        );
        $this->addForeignKey(null, '{{%venveo_redirects}}', ['groupId'], '{{%venveo_redirect_groups}}', ['id'], 'SET NULL', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220719_141827_add_redirect_groups cannot be reverted.\n";
        return false;
    }
}
