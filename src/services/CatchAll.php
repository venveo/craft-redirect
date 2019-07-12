<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use yii\base\Component;

/**
 * Class Redirects service.
 *
 */
class CatchAll extends Component
{

    /**
     * Register a hit to the catch all uri by its uri.
     *
     * @param string $uri
     *
     * @param int|null $siteId
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function registerHitByUri(string $uri, $siteId = null): bool
    {

        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        // search the redirect by its uri
        $catchAllURL = CatchAllUrlRecord::findOne([
            'uri' => $uri,
            'siteId' => $siteId,
        ]);

        if (!$catchAllURL) {
            // not found, new one!
            $catchAllURL = new CatchAllUrlRecord();
            $catchAllURL->uri = $uri;
            $catchAllURL->hitCount = 1;
            $catchAllURL->ignored = false;
            $catchAllURL->siteId = $siteId;
        } else {
            // Don't bother if it's ignored
            if ($catchAllURL->ignored) {
                return true;
            }
            ++$catchAllURL->hitCount;
        }

        if (Craft::$app->request->referrer) {
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
    public function ignoreUrlById(int $id) {
        // TODO check if the user has rights in the siteId..
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

    public function getUrlByUid(string $uid): CatchAllUrlRecord
    {
        // search the redirect by its uri
        $catchAllurl = CatchAllUrlRecord::findOne([
            'uid' => $uid,
        ]);


        return $catchAllurl;
    }

    /**
     * Deletes registered 404s that haven't been hit in a while
     * @param null $limit
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteStale404s($limit = null) {
        $hours = Plugin::$plugin->getSettings()->deleteStale404sHours;

        $interval = DateTimeHelper::secondsToInterval($hours * 60 * 60);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $catchAllQuery = CatchAllUrlRecord::find()
            ->andWhere(['<', 'dateUpdated', Db::prepareDateForDb($pastTime)]);

        if($limit) {
            $catchAllQuery->limit($limit);
        }

        $catchAll = $catchAllQuery->all();
        /** @var CatchAllUrlRecord $item */
        foreach($catchAll as $item) {
            $item->delete();
        }
    }


}
