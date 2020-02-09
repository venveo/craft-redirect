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
 * @property ActiveQueryInterface $redirects
 */
class RedirectGroup extends ActiveRecord
{

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%venveo_redirects_redirect_groups}}';
    }

    /**
     * Returns the group’s redirects.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getRedirects(): ActiveQueryInterface
    {
        return $this->hasMany(Redirect::class, ['groupId' => 'id']);
    }
}
