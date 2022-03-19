<?php
/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\records;

use craft\db\ActiveRecord;

/**
 * @property string uri
 * @property string $query
 * @property int hitCount
 * @property int|null siteId
 * @property boolean ignored
 * @property string referrer
 * @property int $id [int(11)]
 */
class CatchAllUrl extends ActiveRecord
{
    const MAX_URI_LENGTH = 255;
    const MAX_QUERY_LENGTH = 255;

    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%venveo_redirects_catch_all_urls}}';
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['uri'], 'string', 'max' => self::MAX_URI_LENGTH];
        $rules[] = [['query'], 'string', 'max' => self::MAX_QUERY_LENGTH];
        return $rules;
    }
}
