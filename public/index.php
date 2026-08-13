<?php

declare(strict_types=1);

if ('cli-server' === PHP_SAPI) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($requestPath) && is_file(__DIR__.$requestPath)) {
        return false;
    }
}

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel(
    (string) ($_SERVER['APP_ENV'] ?? 'dev'),
    filter_var($_SERVER['APP_DEBUG'] ?? '1', FILTER_VALIDATE_BOOL),
);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
