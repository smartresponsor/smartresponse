<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$appEnv = (string) ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev');
if (!isset($_SERVER['DEFAULT_URI']) && !isset($_ENV['DEFAULT_URI'])) {
    $defaultUri = 'prod' === $appEnv ? 'http://127.0.0.1:8001/' : 'http://localhost';
    $_SERVER['DEFAULT_URI'] = $defaultUri;
    $_ENV['DEFAULT_URI'] = $defaultUri;
}

$kernel = new Kernel(
    (string) ($_SERVER['APP_ENV'] ?? 'dev'),
    filter_var($_SERVER['APP_DEBUG'] ?? '1', FILTER_VALIDATE_BOOL),
);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
