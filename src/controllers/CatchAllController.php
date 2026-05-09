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
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class CatchAllController extends Controller
{
    private const SORT_COLUMNS = [
        'id',
        'uri',
        'query',
        'referrer',
        'hitCount',
        'dateCreated',
        'dateUpdated',
    ];
    private const MAX_PER_PAGE = 100;

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
            Craft::$app->sites->setCurrentSite($this->requireEditableSiteId((int)$siteId));
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
            Craft::$app->sites->setCurrentSite($this->requireEditableSiteId((int)$siteId));
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
        $ids = $this->normalizeIds(Craft::$app->request->getRequiredBodyParam('ids'));
        if ($ids) {
            CatchAllUrl::deleteAll($this->editableRowsCondition($ids));
        }
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
        $data = json_decode(Craft::$app->request->getRawBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Invalid JSON body.');
        }

        $ids = $this->normalizeIds($data);
        if ($ids) {
            CatchAllUrl::deleteAll($this->editableRowsCondition($ids));
        }
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
        $ids = $this->normalizeIds(Craft::$app->request->getRequiredBodyParam('ids'));
        if ($ids) {
            CatchAllUrl::updateAll(['ignored' => true], $this->editableRowsCondition($ids));
        }
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

        $ids = $this->normalizeIds(Craft::$app->request->getRequiredBodyParam('ids'));
        if ($ids) {
            CatchAllUrl::updateAll(['ignored' => false], $this->editableRowsCondition($ids));
        }
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

        $page = max(1, (int)$request->getParam('page', 1));
        $sort = $request->getParam('sort', null);
        $limit = min(self::MAX_PER_PAGE, max(1, (int)$request->getParam('per_page', 10)));
        $search = $request->getParam('search', null);
        $ignoredOnly = filter_var($request->getParam('ignored', false), FILTER_VALIDATE_BOOLEAN);
        $siteId = $this->requireEditableSiteId((int)$request->getParam('siteId', Craft::$app->sites->getCurrentSite()->id));
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

        $recordQuery->andWhere(['=', '[[siteId]]', $siteId]);

        $recordQuery->andWhere(['=', '[[ignored]]', (bool)$ignoredOnly]);

        if ($sort) {
            $sortData = array_pad(explode('|', (string)$sort, 2), 2, 'desc');
            $sortKey = $sortData[0];
            if (in_array($sortKey, self::SORT_COLUMNS, true)) {
                $sortDir = strtolower($sortData[1]) === 'asc' ? SORT_ASC : SORT_DESC;
                $recordQuery->orderBy([$sortKey => $sortDir]);
            }
        }
        if (!$recordQuery->orderBy) {
            $recordQuery->orderBy(['dateCreated' => SORT_DESC]);
        }

        $total = $recordQuery->count();

        $recordQuery->offset($offset);
        $recordQuery->limit($limit);

        $registered404s = $recordQuery->all();

        $rows = [];
        $canCreateRedirects = Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_MANAGE_REDIRECTS);
        foreach ($registered404s as $item) {
            $uri = $item['uri'];
            if (isset($item['query']) && $item['query'] !== '') {
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
                'menu' => $canCreateRedirects ? [
                    'id' => $item['id'],
                    'siteId' => $item['siteId'],
                ] : null,
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }

    /**
     * @return int[]
     */
    private function editableSiteIds(): array
    {
        return array_map('intval', Craft::$app->getSites()->getEditableSiteIds());
    }

    /**
     * @throws ForbiddenHttpException
     */
    private function requireEditableSiteId(int $siteId): int
    {
        if (!in_array($siteId, $this->editableSiteIds(), true)) {
            throw new ForbiddenHttpException('User not authorized to manage registered 404s for this site.');
        }

        return $siteId;
    }

    /**
     * @param mixed $ids
     * @return int[]
     */
    private function normalizeIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn($id) => is_numeric($id) ? (int)$id : null,
            $ids
        ))));
    }

    /**
     * @param int[] $ids
     * @return array
     */
    private function editableRowsCondition(array $ids): array
    {
        return [
            'and',
            ['id' => $ids],
            ['siteId' => $this->editableSiteIds()],
        ];
    }
}
