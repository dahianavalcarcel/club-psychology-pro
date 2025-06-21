<?php
declare(strict_types=1);

return [
    // Database server hostname or IP
    'host'     => defined('DB_HOST')     ? DB_HOST     : (getenv('DB_HOST')     ?: 'localhost'),
    // Database name
    'name'     => defined('DB_NAME')     ? DB_NAME     : (getenv('DB_NAME')     ?: ''),
    // Database user
    'user'     => defined('DB_USER')     ? DB_USER     : (getenv('DB_USER')     ?: ''),
    // Database password
    'pass'     => defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: ''),
    // Character set
    'charset'  => defined('DB_CHARSET')  ? DB_CHARSET  : (getenv('DB_CHARSET')  ?: 'utf8mb4'),
    // Collation
    'collate'  => defined('DB_COLLATE')  ? DB_COLLATE  : (getenv('DB_COLLATE')  ?: ''),
    // Plugin-specific table prefix (defaults to cpp_)
    'prefix'   => getenv('CPP_TABLE_PREFIX') ?: 'cpp_',
];
