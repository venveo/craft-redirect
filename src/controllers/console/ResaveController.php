<?php

/**
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\redirect\controllers\console;

use Craft;
use craft\base\ElementInterface;
use craft\console\Controller;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\BatchElementActionEvent;
use craft\services\Elements;
use venveo\redirect\elements\Redirect;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Allows you to bulk-save redirects
 */
class ResaveController extends Controller
{
    /**
     * @var int|string The ID(s) of the elements to resave.
     */
    public $elementId;

    /**
     * @var string The UUID(s) of the elements to resave.
     */
    public $uid;

    /**
     * @var string|null The site handle to save elements from.
     */
    public $site;

    /**
     * @var string The status(es) of elements to resave. Can be set to multiple comma-separated statuses.
     */
    public $status = 'any';

    /**
     * @var int|null The number of elements to skip.
     */
    public $offset;

    /**
     * @var int|null The number of elements to resave.
     */
    public $limit;

    /**
     * @var bool Whether to save the elements across all their enabled sites.
     */
    public $propagate = true;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     */
    public $updateSearchIndex = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'elementId';
        $options[] = 'uid';
        $options[] = 'site';
        $options[] = 'status';
        $options[] = 'offset';
        $options[] = 'limit';
        $options[] = 'propagate';
        $options[] = 'updateSearchIndex';

        return $options;
    }

    /**
     * Re-saves assets.
     *
     * @return int
     */
    public function actionRedirects(): int
    {
        $query = Redirect::find();
        return $this->saveElements($query);
    }


    /**
     * @see ResaveController::saveElements()
     */
    public function saveElements(ElementQueryInterface $query): int
    {
        /** @var ElementQuery $query */
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;

        if ($this->elementId) {
            $query->id(is_int($this->elementId) ? $this->elementId : explode(',', $this->elementId));
        }

        if ($this->uid) {
            $query->uid(explode(',', $this->uid));
        }

        if ($this->site) {
            $query->site($this->site);
        }

        if ($this->status === 'any') {
            $query->anyStatus();
        } elseif ($this->status) {
            $query->status(explode(',', $this->status));
        }

        if ($this->offset !== null) {
            $query->offset($this->offset);
        }

        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        $count = (int)$query->count();

        if ($count === 0) {
            $this->stdout('No ' . $elementType::pluralLowerDisplayName() . ' exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($query->limit) {
            $count = min($count, (int)$query->limit);
        }

        $elementsText = $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName();
        $this->stdout("Resaving {$count} {$elementsText} ..." . PHP_EOL, Console::FG_YELLOW);

        $elementsService = Craft::$app->getElements();
        $fail = false;

        $beforeCallback = function(BatchElementActionEvent $e) use ($query) {
            if ($e->query === $query) {
                $element = $e->element;
                $this->stdout("    - Resaving {$element} ({$element->id}) ... ");
            }
        };

        $afterCallback = function(BatchElementActionEvent $e) use ($query, &$fail) {
            if ($e->query === $query) {
                $element = $e->element;
                if ($e->exception) {
                    $this->stderr('error: ' . $e->exception->getMessage() . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } elseif ($element->hasErrors()) {
                    $this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } else {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $elementsService->resaveElements($query, true, true, $this->updateSearchIndex);

        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $this->stdout("Done resaving {$elementsText}." . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
        return $fail ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
