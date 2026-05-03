<?php

declare(strict_types=1);

// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

use App\Taxating\Infrastructure\Adapter\PeppolAdapter;
use App\Taxating\Service\CTCSubmit;

require __DIR__.'/../../../vendor/autoload.php';

$raw = file_get_contents('php://input');
if (false === $raw) {
    $raw = '';
}

$invoice = [];
$invalidJson = false;
if ('' !== $raw) {
    try {
        $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        if (\is_array($decoded)) {
            $invoice = $decoded;
        } else {
            $invalidJson = true;
        }
    } catch (\JsonException) {
        $invalidJson = true;
    }
}

if ($invalidJson) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid-json'], \JSON_UNESCAPED_UNICODE);
    exit;
}

$submit = new CTCSubmit(new PeppolAdapter());
$result = $submit->send($invoice, ['endpoint' => 'mock://local/peppol']);
header('Content-Type: application/json');
echo json_encode($result, \JSON_UNESCAPED_UNICODE);
