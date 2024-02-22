<?php
/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property ActiveQueryInterface $element
 * @property \DateTime|null hitAt
 * @property integer|null hitCount
 * @property int $destinationElementId [int(11)]
 * @property int $destinationSiteId [int(11)]
 * @property int|null id
 * @property string sourceUrl
 * @property string destinationUrl
 * @property string statusCode
 * @property string type
 * @property int|null $groupId [int]
 * @property bool $createdAutomatically
 * @property string $postDate [datetime]
 * @property string $expiryDate [datetime]
 */
class Redirect extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%venveo_redirects}}';
    }

    /**
     * Returns the redirect’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public static function find(): ActiveQuery
    {
        return parent::find()
            ->innerJoinWith(['element element'])
            ->where(['element.dateDeleted' => null]);
    }
}
