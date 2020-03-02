<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m200223_232039_add_post_and_expiry_dates migration.
 */
class m200223_232039_add_post_and_expiry_dates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%venveo_redirects}}',
            'postDate',
            $this->dateTime()->after('hitAt')
        );
        $this->addColumn(
            '{{%venveo_redirects}}',
            'expiryDate',
            $this->dateTime()->after('postDate')
        );

        $this->createIndex(null, '{{%venveo_redirects}}', ['postDate'], false);
        $this->createIndex(null, '{{%venveo_redirects}}', ['expiryDate'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200223_232039_add_post_and_expiry_dates cannot be reverted.\n";
        return false;
    }
}
