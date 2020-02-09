<?php
/**
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\redirect\models;

use Craft;
use craft\base\Model;
use craft\validators\UniqueValidator;
use venveo\redirect\records\CatchAllGroup as CatchAllGroupRecord;

/**
 * CatchAllGroup model class.
 *
 */
class CatchAllGroup extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name ?: static::class;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => CatchAllGroupRecord::class];
        $rules[] = [['name'], 'required'];
        return $rules;
    }
}
