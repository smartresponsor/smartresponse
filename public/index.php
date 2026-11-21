<?php

declare(strict_types=1);

if ('cli-server' === PHP_SAPI) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($requestPath) && is_file(__DIR__.$requestPath)) {
        return false;
    }
}

use App\Kernel;
use App\Resolving\Factory\ResolveHttpBootstrapFactory;
use App\Resolving\Factory\ResolveHttpBoundaryFactory;
use App\Resolving\Service\ResolveRuntimeActivationService;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);
(new Dotenv())->bootEnv($projectDir.'/.env');

$resolveProfile = (new ResolveHttpBootstrapFactory())->create($projectDir)->activate(
    (new ResolveHttpBoundaryFactory())->fromServer($_SERVER),
    $projectDir.'/config/resolve_profiles.yaml',
);

$kernel = new Kernel(
    (string) ($_SERVER['APP_ENV'] ?? 'dev'),
    filter_var($_SERVER['APP_DEBUG'] ?? '1', FILTER_VALIDATE_BOOL),
);

$request = Request::createFromGlobals();
(new ResolveRuntimeActivationService())->attach($request, $resolveProfile);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
