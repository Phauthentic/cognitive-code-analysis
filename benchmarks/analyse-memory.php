<?php

/**
 * Measures wall time and peak memory of a full `analyse` run.
 *
 * Usage (inside the Docker container):
 *   php -d memory_limit=-1 benchmarks/analyse-memory.php <path> [extra analyse options...]
 *
 * Example:
 *   XDEBUG_MODE=off php -d memory_limit=-1 benchmarks/analyse-memory.php vendor \
 *     --config=benchmarks/phpcca-no-cache.yaml --report-type=json --report-file=/tmp/report.json
 *
 * The analysis runs in-process, so memory_get_peak_usage() covers parsing, metrics and the report.
 */

declare(strict_types=1);

use Phauthentic\CognitiveCodeAnalysis\Application;

require __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    fwrite(STDERR, "Usage: php benchmarks/analyse-memory.php <path> [analyse options...]\n");
    exit(1);
}

$start = hrtime(true);

register_shutdown_function(static function () use ($start): void {
    fwrite(STDERR, sprintf(
        "\nWall time: %.2f s\nPeak memory: %.1f MB (real: %.1f MB)\n",
        (hrtime(true) - $start) / 1e9,
        memory_get_peak_usage() / 1048576,
        memory_get_peak_usage(true) / 1048576,
    ));
});

$_SERVER['argv'] = array_merge([$argv[0], 'analyse'], array_slice($argv, 1));
$_SERVER['argc'] = count($_SERVER['argv']);

(new Application())->run();
