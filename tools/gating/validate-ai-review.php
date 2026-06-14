<?php

declare(strict_types=1);

const EXIT_OK = 0;
const EXIT_CONFIG_MISSING = 10;
const EXIT_INVALID_OUTPUT = 21;
const EXIT_SCHEMA_FAILED = 22;
const EXIT_FINDINGS = 30;

function usage(): void
{
    fwrite(STDERR, "Usage: php tools/gating/validate-ai-review.php --result <path> [--schema <path>] [--contract <path>]\n");
}

function fail(int $code, string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function readJsonFile(string $path): array
{
    if (!is_file($path)) {
        fail(EXIT_CONFIG_MISSING, sprintf('Missing file: %s', $path));
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        fail(EXIT_CONFIG_MISSING, sprintf('Unable to read file: %s', $path));
    }

    try {
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        fail(EXIT_INVALID_OUTPUT, sprintf('Invalid JSON in %s: %s', $path, $e->getMessage()));
    }

    if (!is_array($decoded)) {
        fail(EXIT_INVALID_OUTPUT, sprintf('Expected a JSON object in %s.', $path));
    }

    return $decoded;
}

function normalizeList(mixed $value): array
{
    return is_array($value) ? $value : [];
}

function validateExactKeys(array $data, array $allowedKeys, string $label): void
{
    $extraKeys = array_diff(array_keys($data), $allowedKeys);
    if ($extraKeys !== []) {
        fail(EXIT_SCHEMA_FAILED, sprintf('%s contains unsupported keys: %s', $label, implode(', ', $extraKeys)));
    }
}

function validateStringField(array $data, string $key, string $label): string
{
    if (!array_key_exists($key, $data) || !is_string($data[$key]) || trim($data[$key]) === '') {
        fail(EXIT_SCHEMA_FAILED, sprintf('%s is missing or invalid.', $label));
    }

    return $data[$key];
}

function validateEnumField(array $data, string $key, array $allowed, string $label): string
{
    $value = validateStringField($data, $key, $label);
    if (!in_array($value, $allowed, true)) {
        fail(EXIT_SCHEMA_FAILED, sprintf('%s must be one of: %s', $label, implode(', ', $allowed)));
    }

    return $value;
}

function validateMetadata(array $metadata): void
{
    $allowed = ['project', 'scope', 'environment', 'workflow', 'commit', 'baseCommit', 'promptVersion', 'modelProfile', 'reviewMode', 'branch', 'path', 'requestId', 'userId'];
    validateExactKeys($metadata, $allowed, 'metadata');

    validateStringField($metadata, 'project', 'metadata.project');
    validateEnumField($metadata, 'scope', ['Changed', 'Component', 'Repository', 'Architecture', 'Release'], 'metadata.scope');
    validateEnumField($metadata, 'environment', ['dev', 'prod', 'ci'], 'metadata.environment');
    validateStringField($metadata, 'workflow', 'metadata.workflow');
    validateStringField($metadata, 'commit', 'metadata.commit');
    validateStringField($metadata, 'baseCommit', 'metadata.baseCommit');
    validateStringField($metadata, 'promptVersion', 'metadata.promptVersion');
    validateEnumField($metadata, 'modelProfile', ['fast', 'review', 'deep'], 'metadata.modelProfile');
    validateStringField($metadata, 'reviewMode', 'metadata.reviewMode');
}

function validateFinding(array $finding, int $index): string
{
    $allowed = ['severity', 'rule', 'message', 'file', 'evidence', 'recommendation'];
    validateExactKeys($finding, $allowed, sprintf('findings[%d]', $index));

    $severity = validateEnumField($finding, 'severity', ['info', 'warning', 'error', 'critical'], sprintf('findings[%d].severity', $index));
    validateStringField($finding, 'rule', sprintf('findings[%d].rule', $index));
    validateStringField($finding, 'message', sprintf('findings[%d].message', $index));
    validateStringField($finding, 'file', sprintf('findings[%d].file', $index));

    return $severity;
}

function pathArg(array $argv, string $name, ?string $default = null): ?string
{
    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        if ($argv[$i] === $name && isset($argv[$i + 1])) {
            return $argv[$i + 1];
        }
    }

    return $default;
}

$resultPath = pathArg($argv, '--result');
if ($resultPath === null) {
    usage();
    fail(EXIT_CONFIG_MISSING, '--result is required.');
}

$schemaPath = pathArg($argv, '--schema', __DIR__ . '/../../.gating/ai/schemas/review-result.v1.schema.json');
$contractPath = pathArg($argv, '--contract', __DIR__ . '/../../.gating/ai/review-contract.v1.json');

$result = readJsonFile($resultPath);
$schema = readJsonFile($schemaPath);
$contract = readJsonFile($contractPath);

$topLevelAllowed = ['status', 'scope', 'summary', 'findings', 'metadata'];
validateExactKeys($result, $topLevelAllowed, 'review result');

$status = validateEnumField($result, 'status', ['pass', 'advisory', 'fail'], 'status');
$scope = validateEnumField($result, 'scope', ['Changed', 'Component', 'Repository', 'Architecture', 'Release'], 'scope');
$summary = validateStringField($result, 'summary', 'summary');

$findings = normalizeList($result['findings'] ?? null);
if ($findings === []) {
    if (!array_key_exists('findings', $result) || !is_array($result['findings'])) {
        fail(EXIT_SCHEMA_FAILED, 'findings must be an array.');
    }
}

$worstSeverity = null;
foreach ($findings as $index => $finding) {
    if (!is_array($finding)) {
        fail(EXIT_SCHEMA_FAILED, sprintf('findings[%d] must be an object.', $index));
    }

    $severity = validateFinding($finding, $index);
    if ($severity === 'critical' || $severity === 'error') {
        $worstSeverity = 'blocking';
    }
}

if (!isset($result['metadata']) || !is_array($result['metadata'])) {
    fail(EXIT_SCHEMA_FAILED, 'metadata must be an object.');
}

validateMetadata($result['metadata']);

if ($worstSeverity === 'blocking' || $status === 'fail') {
    fwrite(STDERR, sprintf("Review failed for scope %s: %s\n", $scope, $summary));
    exit(EXIT_FINDINGS);
}

fwrite(STDOUT, sprintf("Review validated for scope %s with status %s.\n", $scope, $status));
exit(EXIT_OK);
