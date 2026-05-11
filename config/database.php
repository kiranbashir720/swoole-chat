<?php

return [
    'host'     => getenv('DB_HOST')     ?: '127.0.0.1',
    'port'     => getenv('DB_PORT')     ?: '3306',
    'dbname'   => getenv('DB_NAME')     ?: 'swoole-chat',
    'username' => getenv('DB_USER')     ?: 'root',
    'password' => getenv('DB_PASS')     ?: '',
    'pool_size' => (int) (getenv('DB_POOL_SIZE') ?: 10),
];
