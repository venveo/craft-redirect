<?php

/**
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 * @link      https://www.venveo.com
 */

namespace venveo\redirect\controllers;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\AdminTable;
use craft\helpers\Html;
use craft\web\Controller;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl;
use yii\web\Response;

class CatchAllController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @param null $siteId
     * @return Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex($siteId = null)
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        if ($siteId) {
            Craft::$app->sites->setCurrentSite($siteId);
        }

        return $this->renderTemplate('vredirect/_catch-all/index', []);
    }

    /**
     * @param $siteId
     * @return Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIgnored($siteId = null): Response
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        if ($siteId) {
            Craft::$app->sites->setCurrentSite($siteId);
        }

        return $this->renderTemplate('vredirect/_catch-all/ignored', []);
    }


    /**
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::deleteAll(['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDeleteOne(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $data = \GuzzleHttp\json_decode(Craft::$app->request->getRawBody(), true);
        CatchAllUrl::deleteAll(['in', 'id', $data]);
        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIgnore(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::updateAll(['ignored' => true], ['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionUnignore(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);

        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::updateAll(['ignored' => false], ['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response
     * @throws SiteNotFoundException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionHitsTable(): Response
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $page = $request->getParam('page', 1);
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $search = $request->getParam('search', null);
        $ignoredOnly = $request->getParam('ignored', false);
        $siteId = $request->getParam('siteId', Craft::$app->sites->getCurrentSite()->id);
        $offset = ($page - 1) * $limit;

        $recordQuery = CatchAllUrl::find();

        if ($search) {
            $likeOperator = Craft::$app->getDb()->getIsPgsql() ? 'ILIKE' : 'LIKE';
            $recordQuery->andWhere([
                'or',
                [$likeOperator, '[[id]]', $search],
                [$likeOperator, '[[uri]]', $search],
                [$likeOperator, '[[uid]]', $search],
                [$likeOperator, '[[query]]', $search],
                [$likeOperator, '[[referrer]]', $search],
                [$likeOperator, '[[dateUpdated]]', $search],
                [$likeOperator, '[[dateCreated]]', $search],
            ]);
        }

        if ($siteId) {
            $recordQuery->andWhere(['=', '[[siteId]]', $siteId]);
        }

        $recordQuery->andWhere(['=', '[[ignored]]', (bool)$ignoredOnly]);

        if ($sort) {
            $sortData = explode('|', $sort);
            $sortKey = $sortData[0];
            $sortDir = $sortData[1] === 'asc' ? SORT_ASC : SORT_DESC;
            $orderParam = [$sortKey => $sortDir];
            $recordQuery->orderBy($orderParam);
        } else {
            $recordQuery->orderBy(['dateCreated' => SORT_DESC]);
        }

        $total = $recordQuery->count();

        $recordQuery->offset($offset);
        $recordQuery->limit($limit);

        $registered404s = $recordQuery->all();

        $rows = [];
        foreach ($registered404s as $item) {
            $uri = $item['uri'];
            if (isset($item['query']) && $item['query']) {
                $uri .= '?' . $item['query'];
            }
            $rows[] = [
                'title' => $uri,
                'id' => $item['id'],
                'siteId' => $item['siteId'],
                'ignored' => $item['ignored'],
                'uri' => Html::encode($uri),
                'referrer' => Html::encode($item['referrer']),
                'hitCount' => $item['hitCount'],
                'dateCreated' => $item['dateCreated'],
                'dateUpdated' => $item['dateUpdated'],
                'menu' => ['id' => $item['id']],
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }
}
