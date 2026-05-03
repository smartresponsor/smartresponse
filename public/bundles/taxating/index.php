<?php

declare(strict_types=1);

// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

use App\Taxating\Log\Log;
use App\Taxating\Observability\Metric;
use App\Taxating\Security\Idempotency;
use App\Taxating\Service\Taxation\LegacyTaxCalculator;
use App\Taxating\Service\Taxation\LegacyTaxDocumentBuilder;
use App\Taxating\Service\Taxation\LegacyTaxProfileDeterminer;
use App\Taxating\Util\Ulid;

require __DIR__.'/../vendor/autoload.php';

$config = [
    'metric_dir' => sys_get_temp_dir().'/sr-metric-taxation',
    'log_file' => sys_get_temp_dir().'/sr-taxation.log',
    'bucket' => [50, 100, 250, 500, 1000],
];

$metric = new Metric($config['metric_dir'], $config['bucket']);
$log = new Log($config['log_file']);
$calculator = new LegacyTaxCalculator();
$determiner = new LegacyTaxProfileDeterminer();
$documentBuilder = new LegacyTaxDocumentBuilder($determiner, $calculator);
$idempotency = new Idempotency(sys_get_temp_dir().'/sr-idem-taxation');

$requestId = Ulid::generate();
header('X-Request-Id: '.$requestId);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';
$startedAt = microtime(true);

$raw = file_get_contents('php://input');
if (false === $raw) {
    $raw = '';
}

$input = [];
$invalidJson = false;
if ('' !== $raw) {
    try {
        $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        if (\is_array($decoded)) {
            $input = $decoded;
        } else {
            $invalidJson = true;
        }
    } catch (\JsonException) {
        $invalidJson = true;
    }
}

$status = 200;
$result = null;

try {
    if ('/metrics' === $path) {
        header('Content-Type: text/plain; version=0.0.4');
        echo $metric->renderPrometheus();
        exit;
    }

    header('Content-Type: application/json');

    if ($invalidJson) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid-json'], \JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ('/healthz' === $path) {
        echo json_encode(['ok' => true, 'ts' => gmdate('c')]);
        exit;
    }

    if ('/readyz' === $path) {
        echo json_encode(['ready' => true]);
        exit;
    }

    if ('/status' === $path) {
        $slo = ['p95_ms' => 250, 'error_rate' => 0.005];
        echo json_encode(['slo' => $slo, 'status' => $metric->status($slo)]);
        exit;
    }

    $idempotencyKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;
    if (\is_string($idempotencyKey) && '' !== $idempotencyKey) {
        $cached = $idempotency->recall($idempotencyKey);
        if (null !== $cached) {
            echo json_encode($cached, \JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    switch ($path) {
        case '/tax/determine':
            $result = $determiner->determine($input);
            break;

        case '/tax/calc':
            $result = $calculator->calculate($input);
            break;

        case '/tax/doc':
            $result = $documentBuilder->build($input);
            break;

        default:
            $status = 404;
            $result = ['error' => 'not-found', 'path' => $path];
            break;
    }

    if (\is_string($idempotencyKey) && '' !== $idempotencyKey && $status >= 200 && $status < 300 && \is_array($result)) {
        $idempotency->remember($idempotencyKey, $result);
    }

    http_response_code($status);
    echo json_encode($result, \JSON_UNESCAPED_UNICODE);
} finally {
    $elapsedMs = (microtime(true) - $startedAt) * 1000.0;
    $metric->observe($path, http_response_code(), $elapsedMs);
    $log->write([
        'rid' => $requestId,
        'path' => $path,
        'status' => http_response_code(),
        'ms' => round($elapsedMs, 2),
    ]);
}
