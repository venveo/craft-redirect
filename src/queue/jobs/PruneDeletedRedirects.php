<?php

namespace venveo\redirect\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use venveo\redirect\elements\Redirect;

class PruneDeletedRedirects extends BaseJob
{
    public $deletedElementId;
    public $deletedElementUri;
    public $hardDelete;
    public $siteId;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        if ($this->deletedElementId) {
            $redirects = Redirect::find()->siteId($this->siteId)->destinationElementId($this->deletedElementId)->all();
            foreach ($redirects as $redirect) {
                Craft::$app->elements->deleteElement($redirect, $this->hardDelete);
            }
        }

        if ($this->deletedElementUri) {
            $redirectsByUri = Redirect::find()->siteId($this->siteId)->destinationUrl($this->deletedElementUri)->all();
            foreach ($redirectsByUri as $redirect) {
                Craft::$app->elements->deleteElement($redirect, $this->hardDelete);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('vredirect', 'Pruning redirects for deleted elements');
    }
}
