<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use DateTime;
use venveo\redirect\elements\Redirect;

/**
 * RedirectQuery represents a SELECT SQL statement for redirects in a way that is independent of DBMS.
 *
 * @supports-site-params
 * @supports-status-param
 */
class RedirectQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return global sets that the user has permission to edit.
     */
    public $editable = false;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $sourceUrl;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $destinationUrl;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $statusCode;
    /**
     * @var string|null hitAt
     */
    public $hitAt;

    /**
     * @var string|null The type of redirect (static/dynamic)
     */
    public $type;

    /**
     * @var string|null A URI you're trying to match against
     */
    public $matchingUri;

    /**
     * @var int|null An element ID
     */
    public $destinationElementId;

    /**
     * @var int|null site id for the destination
     */
    public $destinationSiteId;


    /**
     * @var mixed The Post Date that the resulting redirects must have.
     * @used-by postDate()
     */
    public $postDate;

    /**
     * @var string|array|\DateTime The maximum Post Date that resulting redirects can have.
     * @used-by before()
     */
    public $before;

    /**
     * @var string|array|\DateTime The minimum Post Date that resulting redirects can have.
     * @used-by after()
     */
    public $after;

    /**
     * @var mixed The Expiry Date that the resulting redirects must have.
     * @used-by expiryDate()
     */
    public $expiryDate;

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['venveo_redirects.postDate' => SORT_DESC];


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'sourceUrl';
        }
        if (!isset($config['status'])) {
            $config['status'] = ['live'];
        }

        parent::__construct($elementType, $config);
    }

    /**
     * Sets the [[editable]] property.
     *
     * @param bool $value The property value (defaults to true)
     *
     * @return static self reference
     */
    public function editable(bool $value = true): RedirectQuery
    {
        $this->editable = $value;

        return $this;
    }

    public function expiryDate($value)
    {
        $this->expiryDate = $value;
        return $this;
    }

    public function postDate($value)
    {
        $this->postDate = $value;
        return $this;
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function sourceUrl($value): RedirectQuery
    {
        $this->sourceUrl = $value;

        return $this;
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function destinationUrl($value): RedirectQuery
    {
        $this->destinationUrl = $value;

        return $this;
    }

    public function destinationElementId($value, $siteId = null): RedirectQuery
    {
        $this->destinationElementId = $value;
        if ($siteId !== null) {
            $this->destinationSiteId = $siteId;
        }
        return $this;
    }

    public function destinationSiteId($value): RedirectQuery
    {
        $this->destinationSiteId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('venveo_redirects');

        $this->query->select([
            'venveo_redirects.type',
            'venveo_redirects.sourceUrl',
            'venveo_redirects.destinationUrl',
            'venveo_redirects.destinationElementId',
            'venveo_redirects.destinationSiteId',
            'venveo_redirects.hitAt',
            'venveo_redirects.hitCount',
            'venveo_redirects.postDate',
            'venveo_redirects.expiryDate',
            'venveo_redirects.statusCode',
        ]);

        if ($this->postDate) {
            $this->subQuery->andWhere(Db::parseDateParam('venveo_redirects.postDate', $this->postDate));
        } else {
            if ($this->before) {
                $this->subQuery->andWhere(Db::parseDateParam('venveo_redirects.postDate', $this->before, '<'));
            }
            if ($this->after) {
                $this->subQuery->andWhere(Db::parseDateParam('venveo_redirects.postDate', $this->after, '>='));
            }
        }

        if ($this->expiryDate) {
            $this->subQuery->andWhere(Db::parseDateParam('venveo_redirects.expiryDate', $this->expiryDate));
        }

        if ($this->sourceUrl) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.sourceUrl', $this->sourceUrl));
        }
        if ($this->destinationUrl) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.destinationUrl', $this->destinationUrl));
        }
        if ($this->statusCode) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.statusCode', $this->statusCode));
        }
        if ($this->type) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.type', $this->type));
        }
        if ($this->destinationElementId) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.destinationElementId', $this->destinationElementId));
        }
        if ($this->destinationSiteId) {
            $this->subQuery->andWhere(Db::parseParam('venveo_redirects.destinationSiteId', $this->destinationSiteId));
        }

        if ($this->hitAt) {
            $this->subQuery->andWhere(Db::parseDateParam('venveo_redirects.hitAt', $this->hitAt));
        }

        if ($this->matchingUri) {
            $this->subQuery->andWhere(['and',
                ['[[venveo_redirects.type]]' => 'static'],
                ['[[venveo_redirects.sourceUrl]]' => $this->matchingUri]
            ]);
            if (Craft::$app->db->getIsPgsql()) {
                $this->subQuery->orWhere([
                    'and',
                    ['[[venveo_redirects.type]]' => 'dynamic'],
                    ':uri SIMILAR TO [[venveo_redirects.sourceUrl]]'
                ], ['uri' => $this->matchingUri]);
            } else {
                $this->subQuery->orWhere([
                    'and',
                    ['[[venveo_redirects.type]]' => 'dynamic'],
                    ':uri RLIKE [[venveo_redirects.sourceUrl]]'
                ], ['uri' => $this->matchingUri]);
            }
        }

        return parent::beforePrepare();
    }


    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime(), true);

        switch ($status) {
            case Redirect::STATUS_LIVE:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['<=', 'venveo_redirects.postDate', $currentTimeDb],
                    [
                        'or',
                        ['venveo_redirects.expiryDate' => null],
                        ['>', 'venveo_redirects.expiryDate', $currentTimeDb]
                    ]
                ];
            case Redirect::STATUS_PENDING:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true,
                    ],
                    ['>', 'venveo_redirects.postDate', $currentTimeDb]
                ];
            case Redirect::STATUS_EXPIRED:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['not', ['venveo_redirects.expiryDate' => null]],
                    ['<=', 'venveo_redirects.expiryDate', $currentTimeDb]
                ];
            default:
                return parent::statusCondition($status);
        }
    }

    // Private Methods
    // =========================================================================


}
