<?php

namespace venveo\redirect\elements\conditions;

use craft\elements\conditions\ElementCondition;

class RedirectCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            TypeConditionRule::class,
        ]);
    }
}
