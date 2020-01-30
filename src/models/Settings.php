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
     * If true, we'll store the last referrer for a registered 404
     * @var bool
     */
    public $storeReferrer = true;

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
     * If true, when recording a 404, we'll remove query parameters first.
     * /blog?page=2 would be considered the same as /blog
     * @var bool
     */
    public $stripQueryParameters = true;

    /**
     * If true, we'll watch element save events and automatically create 301 redirects if the URI has changed
     * @var bool
     */
    public $createElementRedirects = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[
                'redirectsActive',
                'catchAllActive',
                'trimTrailingSlashFromPath',
                'deleteStale404s',
                'storeReferrer',
                'stripQueryParameters',
                'createElementRedirects'
            ], 'boolean'],
            [['deleteStale404sHours'], 'integer'],
        ];
    }
}
