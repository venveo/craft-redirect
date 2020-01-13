<?php
/**
 *
 * @author    dolphiq & Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use yii\base\Component;

/**
 * Class CatchAll service.
 *
 */
class CatchAll extends Component
{

    /**
     * Register a hit to the catch all uri by its uri.
     *
     * @param string $uri
     * @param null $queryString
     * @param int|null $siteId
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function registerHitByUri(string $uri, $queryString = null, $siteId = null): bool
    {

        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $query = $queryString;

        // See if this URI already exists
        $catchAllURL = CatchAllUrlRecord::findOne([
            'uri' => $uri,
            'params' => $queryString,
            'siteId' => $siteId,
        ]);

        // It doesn't exist, so create it.
        if (!$catchAllURL) {
            // not found, new one!
            $catchAllURL = new CatchAllUrlRecord();
            $catchAllURL->uri = $uri;
            $catchAllURL->hitCount = 1;
            $catchAllURL->ignored = false;
            $catchAllURL->siteId = $siteId;
            $catchAllURL->params = $query;
        } else {
            // Don't bother if it's ignored
            if ($catchAllURL->ignored) {
                return true;
            }
            ++$catchAllURL->hitCount;
        }

        if (Craft::$app->request->referrer && Plugin::$plugin->getSettings()->storeReferrer) {
            $catchAllURL->referrer = Craft::$app->request->referrer;
        }

        $catchAllURL->save();

        // Give the plugin an opportunity to do some garbage collection
        if (Plugin::$plugin->getSettings()->deleteStale404s === true) {
            // Let's only delete a few at a time to prevent flooding. Especially after initial feature roll-out
            $this->deleteStale404s(100);
        }

        return true;
    }

    /**
     * Marks a 404 as ignored
     * @param int $id
     * @return bool
     */
    public function ignoreUrlById(int $id)
    {
        $catchAllURL = CatchAllUrlRecord::findOne($id);

        if (!$catchAllURL) {
            return false;
        }

        $catchAllURL->ignored = true;
        return $catchAllURL->save();
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteUrlById(int $id): bool
    {
        $catchAllURL = CatchAllUrlRecord::findOne($id);

        if (!$catchAllURL) {
            return false;
        }

        $catchAllURL->delete();
        return true;
    }

    /**
     * @param string $uid
     * @return CatchAllUrlRecord
     */
    public function getUrlByUid(string $uid): CatchAllUrlRecord
    {
        return CatchAllUrlRecord::findOne([
            'uid' => $uid,
        ]);
    }

    /**
     * Deletes registered 404s that haven't been hit in a while
     * @param null $limit
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteStale404s($limit = null)
    {
        $hours = Plugin::$plugin->getSettings()->deleteStale404sHours;

        $interval = DateTimeHelper::secondsToInterval($hours * 60 * 60);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $catchAllQuery = CatchAllUrlRecord::find()
            ->andWhere(['<', 'dateUpdated', Db::prepareDateForDb($pastTime)]);

        if ($limit) {
            $catchAllQuery->limit($limit);
        }

        $catchAll = $catchAllQuery->all();
        /** @var CatchAllUrlRecord $item */
        foreach ($catchAll as $item) {
            $item->delete();
        }
    }


}
