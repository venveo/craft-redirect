<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\models;

use craft\base\Model;
use craft\validators\UniqueValidator;
use venveo\redirect\records\Group as GroupRecord;

class Group extends Model
{
    public ?int $id = null;
    
    public ?string $name = null;
    
    public ?string $description = null;

    public ?string $uid = null;
    
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['name'], 'string', 'max' => 128];
        $rules[] = [['name'], 'required'];
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => GroupRecord::class];
        $rules[] = [['description'], 'string', 'max' => 1024];
        return $rules;
    }
}
