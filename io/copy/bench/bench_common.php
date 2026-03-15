<?php

/**
 * Common helpers for stream_copy_to_stream benchmarks.
 *
 * Usage: require this file, then call bench_run() with a callback.
 */

const BENCH_SIZES = [
    '1K'   => 1024,
    '16K'  => 16384,
    '64K'  => 65536,
    '256K' => 262144,
    '1M'   => 1048576,
    '10M'  => 10485760,
    '100M' => 104857600,
];

const BENCH_WARMUP = 2;

/* Allow override via environment for the runner script. */
define('BENCH_ITERATIONS', (int) (getenv('BENCH_ITERATIONS') ?: 10));

/**
 * Global results collector.
 * Structure: [ 'variant_key' => [ 'size_label' => [...stats...], ... ], ... ]
 */
$BENCH_RESULTS = [];

/* ========== formatting ========== */

function bench_format_throughput(int $bytes, float $seconds): string
{
    if ($seconds < 1e-9) {
        return '       inf MB/s';
    }
    $mbps = ($bytes / 1048576) / $seconds;
    return sprintf('%10.2f MB/s', $mbps);
}

function bench_format_time(float $seconds): string
{
    if ($seconds < 0.001) {
        return sprintf('%8.1f µs', $seconds * 1e6);
    }
    return sprintf('%8.3f ms', $seconds * 1000);
}

/* ========== statistics ========== */

function bench_compute_stats(array $times, int $bytes): array
{
    sort($times);
    $n   = count($times);
    $avg = array_sum($times) / $n;
    $min = $times[0];
    $max = $times[$n - 1];

    $med = ($n % 2 === 0)
        ? ($times[$n / 2 - 1] + $times[$n / 2]) / 2
        : $times[(int) ($n / 2)];

    $p5  = $times[(int) floor($n * 0.05)];
    $p95 = $times[(int) min($n - 1, floor($n * 0.95))];

    $variance = 0.0;
    foreach ($times as $t) {
        $variance += ($t - $avg) ** 2;
    }
    $stddev = sqrt($variance / $n);

    $mbps = ($avg > 1e-12) ? ($bytes / 1048576) / $avg : INF;

    return [
        'bytes'      => $bytes,
        'iterations' => $n,
        'avg_s'      => $avg,
        'min_s'      => $min,
        'max_s'      => $max,
        'median_s'   => $med,
        'p5_s'       => $p5,
        'p95_s'      => $p95,
        'stddev_s'   => $stddev,
        'mbps'       => $mbps,
        'times'      => $times,   /* stripped on save */
    ];
}

/* ========== data generation ========== */

function bench_generate_data(int $size): string
{
    $chunk = str_repeat('ABCDEFGHIJ0123456789abcdefghij!@', 1024); // 32 KB
    $data  = str_repeat($chunk, (int) ceil($size / strlen($chunk)));
    return substr($data, 0, $size);
}

/* ========== bench_run (file-to-file style, single process) ========== */

function bench_run(string $label, callable $setup, callable $callback, ?callable $teardown = null): void
{
    global $BENCH_RESULTS;

    $variant = bench_label_to_key($label);

    echo "=== $label ===\n";
    bench_print_header();

    $variantResults = [];

    foreach (BENCH_SIZES as $name => $size) {
        $data = bench_generate_data($size);
        $ctx  = $setup($size, $data);

        for ($i = 0; $i < BENCH_WARMUP; $i++) {
            $callback($size, $data, $ctx);
        }

        $times  = [];
        $copied = 0;
        for ($i = 0; $i < BENCH_ITERATIONS; $i++) {
            $start   = hrtime(true);
            $copied  = $callback($size, $data, $ctx);
            $elapsed = (hrtime(true) - $start) / 1e9;
            $times[] = $elapsed;
        }

        if ($teardown) {
            $teardown($ctx);
        }

        $stats = bench_compute_stats($times, $size);
        $stats['ok'] = ($copied === $size);
        $variantResults[$name] = $stats;

        bench_print_row($name, $stats);
    }

    $BENCH_RESULTS[$variant] = $variantResults;
    echo "\n";
}

/* ========== printing ========== */

function bench_print_header(): void
{
    printf(
        "%-6s  %15s  %15s  %12s  %10s\n",
        'Size', 'Avg Time', 'Throughput', '± Stddev', 'Status'
    );
    echo str_repeat('-', 68) . "\n";
}

function bench_print_row(string $name, array $stats): void
{
    $status = $stats['ok'] ? 'OK' : 'FAIL';
    $pct    = ($stats['avg_s'] > 1e-12)
        ? sprintf('± %5.1f%%', ($stats['stddev_s'] / $stats['avg_s']) * 100)
        : '± 0.0%';

    printf(
        "%-6s  %15s  %15s  %12s  %10s\n",
        $name,
        bench_format_time($stats['avg_s']),
        bench_format_throughput($stats['bytes'], $stats['avg_s']),
        $pct,
        $status
    );
}

function bench_label_to_key(string $label): string
{
    if (preg_match('/^(\w+)\s*→\s*(\w+)/', $label, $m)) {
        return strtolower($m[1]) . '_to_' . strtolower($m[2]);
    }
    return preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($label, ' _')));
}

/* ========== save / load ========== */

function bench_build_meta(string $label = ''): array
{
    return [
        'date'        => date('Y-m-d H:i:s'),
        'timestamp'   => time(),
        'platform'    => PHP_OS . ' (' . php_uname('m') . ')',
        'php_version' => PHP_VERSION,
        'binary'      => PHP_BINARY,
        'label'       => $label,
        'warmup'      => BENCH_WARMUP,
        'iterations'  => BENCH_ITERATIONS,
        'hostname'    => gethostname(),
    ];
}

function bench_strip_times(array $results): array
{
    $cleaned = [];
    foreach ($results as $variant => $sizes) {
        foreach ($sizes as $sizeName => $stats) {
            $s = $stats;
            unset($s['times']);
            $cleaned[$variant][$sizeName] = $s;
        }
    }
    return $cleaned;
}

/**
 * Save collected results to a JSON file.
 */
function bench_save(string $path, string $label = ''): void
{
    global $BENCH_RESULTS;

    $payload = [
        'meta'    => bench_build_meta($label),
        'results' => bench_strip_times($BENCH_RESULTS),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($path, $json) === false) {
        fwrite(STDERR, "ERROR: Failed to write results to $path\n");
        return;
    }
    fwrite(STDERR, "Results saved to: $path\n");
}

/**
 * Load results from a JSON file.
 */
function bench_load(string $path): array
{
    $json = file_get_contents($path);
    if ($json === false) {
        throw new \RuntimeException("Cannot read: $path");
    }
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['results'])) {
        throw new \RuntimeException("Invalid benchmark data in: $path");
    }
    return $data;
}

/* ========== JSON output for runner integration ========== */

/**
 * Emit collected results as JSON blob after a marker line.
 * The runner captures everything after __BENCH_JSON__.
 */
function bench_emit_json(): void
{
    global $BENCH_RESULTS;
    echo "__BENCH_JSON__\n";
    echo json_encode(bench_strip_times($BENCH_RESULTS), JSON_UNESCAPED_UNICODE) . "\n";
}

function bench_json_requested(): bool
{
    global $argv;
    return in_array('--json', $argv ?? [], true) || getenv('BENCH_JSON');
}

/**
 * Parse --save=path and --label=name from CLI.
 */
function bench_parse_save_args(): ?array
{
    global $argv;
    $save  = null;
    $label = '';
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--save=')) {
            $save = substr($arg, 7);
        }
        if (str_starts_with($arg, '--label=')) {
            $label = substr($arg, 8);
        }
    }
    return $save ? ['path' => $save, 'label' => $label] : null;
}

/**
 * Call at end of each standalone benchmark script to handle --save / --json.
 */
function bench_finalize(): void
{
    if (bench_json_requested()) {
        bench_emit_json();
    }

    $saveArgs = bench_parse_save_args();
    if ($saveArgs) {
        bench_save($saveArgs['path'], $saveArgs['label']);
    }
}

/* ========== forking / networking helpers ========== */

function bench_fork(callable $childFn): int
{
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new \RuntimeException('pcntl_fork() failed');
    }
    if ($pid === 0) {
        $childFn();
        exit(0);
    }
    return $pid;
}

function bench_tcp_server(): array
{
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$server) {
        throw new \RuntimeException("stream_socket_server: $errstr ($errno)");
    }
    $addr = stream_socket_get_name($server, false);
    return [$server, $addr];
}
