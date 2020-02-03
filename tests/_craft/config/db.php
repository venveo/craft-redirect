<?php
/**
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2020 Venveo
 */

return [
    'dsn' => getenv('DB_DSN'),
    'user' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
    'schema' => getenv('DB_SCHEMA'),
    'tablePrefix' => getenv('DB_TABLE_PREFIX'),
];