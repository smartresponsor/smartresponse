<?php

declare(strict_types=1);

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'prod';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
$_SERVER['APP_BASE_URI'] = $_ENV['APP_BASE_URI'] = 'http://127.0.0.1:8002';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['APP_CACHE_DIR'] = $_ENV['APP_CACHE_DIR'] = dirname(__DIR__).'/var/cache/walleting_http_probe';

require dirname(__DIR__).'/public/index.php';
