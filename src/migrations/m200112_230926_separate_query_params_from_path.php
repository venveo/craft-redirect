<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200112_230926_separate_query_params_from_path migration.
 */
class m200112_230926_separate_query_params_from_path extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%venveo_redirects_catch_all_urls}}',
            'params',
            $this->text()->null()->defaultValue(null)->after('uri')
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200112_230926_separate_query_params_from_path cannot be reverted.\n";
        return false;
    }
}
