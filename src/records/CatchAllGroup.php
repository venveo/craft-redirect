<?php
/**
 *
 * @author    Venveo
 * @copyright Copyright (c) 2020 Venveo
 */
namespace venveo\redirect\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property ActiveQueryInterface $catchAllUrls
 */
class CatchAllGroup extends ActiveRecord
{

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%venveo_redirects_404_groups}}';
    }

    /**
     * Returns the group’s caught errors.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCatchAllUrls(): ActiveQueryInterface
    {
        return $this->hasMany(CatchAllUrl::class, ['groupId' => 'id']);
    }
}
