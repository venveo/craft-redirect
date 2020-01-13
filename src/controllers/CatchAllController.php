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
use craft\helpers\UrlHelper;
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
     * @return Response
     * @throws SiteNotFoundException
     */
    public function actionIndex()
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);

        return $this->renderTemplate('vredirect/_catch-all/index', [
            'catchAllQuery' => CatchAllUrl::find()->orderBy('hitCount DESC')
        ]);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::deleteAll(['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    public function actionDeleteOne()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $data = \GuzzleHttp\json_decode(Craft::$app->request->getRawBody(), true);
        CatchAllUrl::deleteAll(['in', 'id', $data]);
        return $this->asJson(['success' => true]);
    }

    public function actionIgnore()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);
        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::updateAll(['ignored' => true], ['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    public function actionUnIgnore()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(Plugin::PERMISSION_MANAGE_404S);

        $ids = Craft::$app->request->getRequiredBodyParam('ids');
        CatchAllUrl::updateAll(['ignored' => false], ['in', 'id', $ids]);
        return $this->asJson(['success' => true]);
    }

    public function actionHitsTable()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $page = $request->getParam('page', 1);
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $search = $request->getParam('search', null);
        $offset = ($page - 1) * $limit;

        $recordQuery = CatchAllUrl::find();

        if ($search) {
            $likeOperator = Craft::$app->getDb()->getIsPgsql() ? 'ILIKE' : 'LIKE';
            $recordQuery->andWhere([
                'or',
                [$likeOperator, '[[id]]', $search],
                [$likeOperator, '[[uri]]', $search],
                [$likeOperator, '[[uid]]', $search],
                [$likeOperator, '[[params]]', $search],
                [$likeOperator, '[[referrer]]', $search],
                [$likeOperator, '[[dateUpdated]]', $search],
                [$likeOperator, '[[dateCreated]]', $search]
            ]);
        }

        $recordQuery->andWhere(['=', '[[ignored]]', false]);

        if ($sort) {
            $sortData = explode('|', $sort);
            $sortKey = $sortData[0];
            $sortDir = $sortData[1] === 'asc' ? SORT_ASC : SORT_DESC;
            $orderParam = [$sortKey => $sortDir];
            $recordQuery->orderBy($orderParam);
        }

        $total = $recordQuery->count();

        $recordQuery->offset($offset);
        $recordQuery->limit($limit);

        $registered404s = $recordQuery->all();

        $rows = [];
        foreach ($registered404s as $item) {
            $title = $item['uri'];
            if (isset($item['params']) && $item['params']) {
                $title .= '?' . $item['params'];
            }
            $rows[] = [
                'id' => $item['id'],
                'title' => Html::encode($title),
                'referrer' => Html::encode($item['referrer']),
                'hitCount' => $item['hitCount'],
                'dateCreated' => $item['dateCreated'],
                'dateUpdated' => $item['dateUpdated'],
                'menu' => ['createUrl' => UrlHelper::cpUrl('redirect/redirects/new', ['from' => $item['id']])]
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }
}
