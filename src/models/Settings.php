<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * Controls whether the system is active
     * @var bool
     */

    public $redirectsActive = true;
    /**
     * Controls whether 404s will be caught
     * @var bool
     */
    public $catchAllActive = true;

    /**
     * Trim trailing slashes from path of static redirects
     * Example:
     *  http://mysite.com/ becomes http://mysite.com
     *  http://mysite.com/somepage/?somequery=someparam/ becomes http://mysite.com/somepage?somequery=someparam/
     * @var bool
     */
    public $trimTrailingSlashFromPath = true;

    /**
     * Delete stale 404s automatically
     * @var bool
     */
    public $deleteStale404s = true;

    /**
     * Delete stale 404s after this many days if deleteStale404s is true
     * @var int
     */
    public $deleteStale404sHours = 24 * 7;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['redirectsActive', 'catchAllActive', 'trimTrailingSlashFromPath', 'deleteStale404s'], 'boolean'],
            [['deleteStale404sHours'], 'integer'],
        ];
    }
}
