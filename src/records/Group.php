<?php
/**
 *
 * @copyright Copyright (c) 2022 Venveo
 */

namespace venveo\redirect\records;

use craft\db\ActiveRecord;

class Group extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%venveo_redirect_groups}}';
    }
}
