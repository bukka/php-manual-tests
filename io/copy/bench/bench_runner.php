#!/usr/bin/env php
<?php
/**
 * stream_copy_to_stream benchmark runner.
 *
 * Usage:
 *   # Run all benchmarks with the current PHP binary:
 *   php bench_runner.php
 *
 *   # Save results for later comparison:
 *   php bench_runner.php --save=master.json --label=master
 *
 *   # Run with a specific binary and save:
 *   php bench_runner.php --php=/path/to/php --save=io_copy.json --label=io_copy
 *
 *   # Run a single variant:
 *   php bench_runner.php --only=file_to_file
 *
 *   # Change iterations:
 *   php bench_runner.php --iterations=20
 */

require __DIR__ . '/bench_common.php';

$opts = getopt('', ['php:', 'save:', 'label:', 'only:', 'iterations:', 'help']);

if (isset($opts['help'])) {
    fwrite(STDOUT, <<<'HELP'
    stream_copy_to_stream benchmark suite

    Options:
      --php=PATH       PHP binary to use (default: current binary)
      --save=FILE      Save results to JSON file for later comparison
      --label=NAME     Human-readable label for this run (e.g. "master", "io_copy")
      --only=VARIANT   Run only one variant:
                         file_to_file, file_to_socket,
                         socket_to_file, socket_to_socket
      --iterations=N   Override iteration count (default: 10)
      --help           Show this help

    Examples:
      # Run on master, save results:
      git checkout master && make -j$(nproc)
      php bench_runner.php --save=master.json --label=master

      # Switch to feature branch, run again:
      git checkout io_copy && make -j$(nproc)
      php bench_runner.php --save=io_copy.json --label=io_copy

      # Compare the two runs:
      php bench_compare.php master.json io_copy.json

    HELP);
    exit(0);
}

$phpBin = $opts['php'] ?? PHP_BINARY;
$savePath = $opts['save'] ?? null;
$label = $opts['label'] ?? '';
$iterOverride = isset($opts['iterations']) ? (int) $opts['iterations'] : null;

$benchmarks = [
    'file_to_file'     => __DIR__ . '/bench_file_to_file.php',
    'file_to_socket'   => __DIR__ . '/bench_file_to_socket.php',
    'socket_to_file'   => __DIR__ . '/bench_socket_to_file.php',
    'socket_to_socket' => __DIR__ . '/bench_socket_to_socket.php',
];

if (isset($opts['only'])) {
    $only = $opts['only'];
    if (!isset($benchmarks[$only])) {
        fwrite(STDERR, "Unknown variant: $only\nAvailable: " . implode(', ', array_keys($benchmarks)) . "\n");
        exit(1);
    }
    $benchmarks = [$only => $benchmarks[$only]];
}

if (!is_executable($phpBin) && !is_file($phpBin)) {
    fwrite(STDERR, "Binary not found: $phpBin\n");
    exit(1);
}

$phpVersion = trim(shell_exec(escapeshellarg($phpBin) . " -r 'echo PHP_VERSION;' 2>/dev/null") ?: 'unknown');

echo "stream_copy_to_stream benchmark suite\n";
echo str_repeat('=', 68) . "\n";
echo "Date:       " . date('Y-m-d H:i:s') . "\n";
echo "Platform:   " . PHP_OS . " (" . php_uname('m') . ")\n";
echo "Binary:     $phpBin (PHP $phpVersion)\n";
if ($label) {
    echo "Label:      $label\n";
}
echo "Warmup:     " . BENCH_WARMUP . " rounds\n";
echo "Iterations: " . ($iterOverride ?? BENCH_ITERATIONS) . " rounds\n";
if ($savePath) {
    echo "Save to:    $savePath\n";
}
echo str_repeat('=', 68) . "\n\n";

$allResults = [];

foreach ($benchmarks as $variant => $script) {
    $env = [];
    if ($iterOverride !== null) {
        $env['BENCH_ITERATIONS'] = $iterOverride;
    }
    /* Request JSON blob from the child so we can aggregate results. */
    $env['BENCH_JSON'] = '1';

    $envStr = '';
    foreach ($env as $k => $v) {
        $envStr .= escapeshellarg("$k=$v") . ' ';
    }

    $cmd = "env {$envStr}" . escapeshellarg($phpBin) . ' ' . escapeshellarg($script) . ' 2>&1';

    $output = [];
    exec($cmd, $output, $exitCode);

    /* Split output: human-readable lines before __BENCH_JSON__, JSON after. */
    $jsonLine = null;
    $inJson = false;
    foreach ($output as $line) {
        if ($line === '__BENCH_JSON__') {
            $inJson = true;
            continue;
        }
        if ($inJson) {
            $jsonLine = $line;
            break;
        }
        /* Print the human-readable part. */
        echo $line . "\n";
    }

    if ($exitCode !== 0) {
        fwrite(STDERR, "  [WARN] $variant exited with code $exitCode\n");
    }

    if ($jsonLine !== null) {
        $parsed = json_decode($jsonLine, true);
        if (is_array($parsed)) {
            $allResults = array_merge($allResults, $parsed);
        }
    }
}

/* Save aggregated results if requested. */
if ($savePath && !empty($allResults)) {
    $payload = [
        'meta' => [
            'date'        => date('Y-m-d H:i:s'),
            'timestamp'   => time(),
            'platform'    => PHP_OS . ' (' . php_uname('m') . ')',
            'php_version' => $phpVersion,
            'binary'      => $phpBin,
            'label'       => $label,
            'warmup'      => BENCH_WARMUP,
            'iterations'  => $iterOverride ?? BENCH_ITERATIONS,
            'hostname'    => gethostname(),
        ],
        'results' => $allResults,
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($savePath, $json) !== false) {
        echo "\nResults saved to: $savePath\n";
    } else {
        fwrite(STDERR, "\nERROR: Failed to write results to $savePath\n");
    }
}

if (!$savePath) {
    echo "Tip: Use --save=results.json --label=mybranch to save results for comparison.\n";
    echo "     Then: php bench_compare.php baseline.json mybranch.json\n";
}
