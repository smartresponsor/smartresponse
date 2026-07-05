<?php

declare(strict_types=1);

use App\Service\CodeMemory\CodeMemoryScopeResolver;

require dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

try {
    $cwd = resolveCwdArgument($argv);
    $scope = (new CodeMemoryScopeResolver())->resolve($cwd);
    echo json_encode($scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}

/**
 * @param list<string> $argv
 */
function resolveCwdArgument(array $argv): string
{
    $cwd = getcwd() ?: '.';

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

        throw new InvalidArgumentException('Unsupported argument: '.$argument);
    }

    return $cwd;
}
