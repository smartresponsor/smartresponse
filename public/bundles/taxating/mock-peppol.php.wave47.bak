<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
if ($raw === false) {
    $raw = '';
}

echo json_encode([
    'ok' => true,
    'id' => substr(sha1($raw), 0, 16),
    'len' => strlen($raw),
], JSON_UNESCAPED_UNICODE);
