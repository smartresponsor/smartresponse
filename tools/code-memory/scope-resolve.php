<?php

declare(strict_types=1);

use App\Service\CodeMemory\CodeMemoryScopeResolver;

require dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

try {
    $options = resolveArguments($argv);
    $cwd = $options['cwd'];
    $scope = (new CodeMemoryScopeResolver())->resolve($cwd);
    $json = json_encode($scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if ($options['writeCache']) {
        writeScopeCache($scope['activeRoot'], $json);
    }
    echo $json.PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}

/**
 * @param list<string> $argv
 * @return array{cwd: string, writeCache: bool}
 */
function resolveArguments(array $argv): array
{
    $cwd = getcwd() ?: '.';
    $writeCache = false;

    foreach ($argv as $index => $argument) {
        if (0 === $index) {
            continue;
        }

        if ('--cwd' === $argument) {
            $next = $argv[$index + 1] ?? null;
            if (!is_string($next) || '' === trim($next)) {
                throw new InvalidArgumentException('Option --cwd requires a non-empty value.');
            }
            $cwd = $next;
            continue;
        }

        if (str_starts_with($argument, '--cwd=')) {
            $value = substr($argument, 6);
            if ('' === trim($value)) {
                throw new InvalidArgumentException('Option --cwd requires a non-empty value.');
            }
            $cwd = $value;
            continue;
        }

        if ('--json' === $argument) {
            continue;
        }

        if ('--write-cache' === $argument) {
            $writeCache = true;
            continue;
        }

        throw new InvalidArgumentException('Unsupported argument: '.$argument);
    }

    return ['cwd' => $cwd, 'writeCache' => $writeCache];
}

function writeScopeCache(string $activeRoot, string $json): void
{
    $root = realpath($activeRoot) ?: $activeRoot;
    $cacheDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.codebase-memory';
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
        throw new RuntimeException('Unable to create Code Memory cache directory: '.$cacheDir);
    }

    $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.'resolved-scope.json';
    if (false === file_put_contents($cacheFile, $json.PHP_EOL)) {
        throw new RuntimeException('Unable to write Code Memory scope cache: '.$cacheFile);
    }
}
