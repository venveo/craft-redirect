<?php
/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
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
