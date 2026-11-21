<?php

require __DIR__.'/../vendor/autoload.php';

// PHPUnit may refresh its schema. Never inherit the live shop database.
$mysql = getenv('SHOP_TEST_MYSQL') === '1';
foreach (['DB_URL' => '', 'DB_CONNECTION' => $mysql ? 'mysql' : 'sqlite', 'DB_DATABASE' => $mysql ? 'shop_test' : ':memory:'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
