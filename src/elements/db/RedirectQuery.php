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
     * @var string|null An element ID
     */
    public $destinationElementId;


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

        parent::__construct($elementType, $config);
    }

    /**
     * Sets the [[editable]] property.
     *
     * @param bool $value The property value (defaults to true)
     *
     * @return static self reference
     */
    public function editable(bool $value = true)
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function sourceUrl($value)
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
    public function destinationUrl($value)
    {
        $this->destinationUrl = $value;

        return $this;
    }

    public function destinationElementId($value)
    {
        $this->destinationElementId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('venveo_redirects');


        //   $this->joinElementTable('elements_sites');

        $this->query->select([
            'elements_sites.siteId',
            'venveo_redirects.type',
            'venveo_redirects.sourceUrl',
            'venveo_redirects.destinationUrl',
            'venveo_redirects.destinationElementId',
            'venveo_redirects.hitAt',
            'venveo_redirects.hitCount',
            'venveo_redirects.statusCode',
        ]);

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

        if ($this->hitAt && $this->hitAt > 0) {
            // TODO: Refactor...
            $inactiveDate = new DateTime();
            $inactiveDate->modify("-60 days");
            $this->subQuery->andWhere('([[venveo_redirects.hitAt]] < :calculatedDate AND [[venveo_redirects.hitAt]] IS NOT NULL)', [':calculatedDate' => $inactiveDate->format("Y-m-d H:m:s")]);
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

    // Private Methods
    // =========================================================================


}
