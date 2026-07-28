<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$url = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;

if (!is_string($url) || '' === trim($url)) {
    fwrite(STDERR, "DATABASE_URL is not configured.\n");
    exit(1);
}

$parts = parse_url($url);

if (false === $parts) {
    fwrite(STDERR, "DATABASE_URL is invalid.\n");
    exit(1);
}

$values = [
    'host' => (string) ($parts['host'] ?? '127.0.0.1'),
    'port' => (string) ($parts['port'] ?? 5432),
    'user' => isset($parts['user']) ? rawurldecode($parts['user']) : '',
    'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
];

foreach ($values as $key => $value) {
    echo $key.'='.base64_encode($value).PHP_EOL;
}
