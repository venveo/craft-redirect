<?php

namespace venveo\redirect\elements\conditions;

use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\elements\Redirect;

/**
 * Redirect type condition rule.
 *
 */
class TypeConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
    public ?string $type = null;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('vredirect', 'Redirect Type');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['type'];
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {

        // Set a default section
        if (!$this->type) {
            $this->type = Redirect::TYPE_STATIC;
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'type' => $this->type,
        ]);
    }

    /**
     * @return array
     */
    private function _typeOptions(): array
    {
        $options = [];
        $options[Redirect::TYPE_STATIC] = 'Static';
        $options[Redirect::TYPE_DYNAMIC] = 'Dynamic';
        return $options;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $html = Cp::selectHtml([
            'name' => 'type',
            'value' => $this->type,
            'options' => $this->_typeOptions(),
        ]);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var RedirectQuery $query */
        $query->type($this->type);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['type'], 'safe'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Redirect $element */
        return $element->type === $this->type;
    }
}
