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
use Throwable;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use yii\base\Component;
use yii\db\StaleObjectException;

/**
 * Class CatchAll service.
 *
 */
class CatchAll extends Component
{
    /**
     * Register a hit to the catch-all uri by its uri.
     *
     * @param string $uri
     * @param string|null $queryString
     * @param int|null $siteId
     * @return bool
     * @throws StaleObjectException
     */
    public function registerHitByUri(string $uri, string $queryString = null, int $siteId = null): bool
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $query = $queryString != '' ? $queryString : null;

        // Not interested in storing giant requests.
        if (strlen($uri) > CatchAllUrlRecord::MAX_URI_LENGTH || strlen($query) > CatchAllUrlRecord::MAX_QUERY_LENGTH) {
            return true;
        }
        // TODO: Switch to insert with ON DUPLICATE id increment
        // https://planetscale.com/blog/the-slotted-counter-pattern

        // See if this URI already exists
        $params = [
            'uri' => $uri,
            'query' => $query,
            'siteId' => $siteId,
        ];
        $catchAllURL = CatchAllUrlRecord::findOne($params);
        // It doesn't exist, so create it.
        if (!$catchAllURL) {
            // not found, new one!
            $catchAllURL = new CatchAllUrlRecord();
            $catchAllURL->uri = $uri;
            $catchAllURL->hitCount = 1;
            $catchAllURL->ignored = false;
            $catchAllURL->siteId = $siteId;
            $catchAllURL->query = $query;
        } else {
            // Don't bother if it's ignored
            if ($catchAllURL->ignored) {
                return true;
            }
            ++$catchAllURL->hitCount;
        }

        if (Craft::$app->request->referrer && Plugin::getInstance()->getSettings()->storeReferrer) {
            $catchAllURL->referrer = Craft::$app->request->referrer;
        }

        $catchAllURL->save();

        // Give the plugin an opportunity to do some garbage collection
        if (Plugin::getInstance()->getSettings()->deleteStale404s === true) {
            // Let's only delete a few at a time to prevent flooding. Especially after initial feature roll-out
            $this->deleteStale404s(100);
        }

        return true;
    }

    /**
     * Deletes registered 404s that haven't been hit in a while
     * @param null $limit
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function deleteStale404s($limit = null): void
    {
        $hours = Plugin::getInstance()->getSettings()->deleteStale404sHours;

        $interval = DateTimeHelper::secondsToInterval($hours * 60 * 60);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $catchAllQuery = CatchAllUrlRecord::find()
            ->andWhere(['ignored' => false])
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

    /**
     * Marks a 404 as ignored
     * @param int $id
     * @return bool
     */
    public function ignoreUrlById(int $id): bool
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
     * @throws Throwable
     * @throws StaleObjectException
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
     * @param int $id
     * @return CatchAllUrlRecord|null
     */
    public function getUrlById(int $id): CatchAllUrlRecord|null
    {
        return CatchAllUrlRecord::findOne([
            'id' => $id,
        ]);
    }

    /**
     * @param string $uid
     * @return CatchAllUrlRecord|null
     */
    public function getUrlByUid(string $uid): CatchAllUrlRecord|null
    {
        return CatchAllUrlRecord::findOne([
            'uid' => $uid,
        ]);
    }
}
