<?php

return [
    'dsn' => getenv('DB_DSN') ?: null,
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'server' => getenv('DB_SERVER') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_DATABASE') ?: 'test',
    'user' => getenv('DB_USER') ?: 'project',
    'password' => getenv('DB_PASSWORD') ?: 'project',
    'schema' => getenv('DB_SCHEMA') ?: 'public',
    'tablePrefix' => getenv('DB_TABLE_PREFIX'),
];
