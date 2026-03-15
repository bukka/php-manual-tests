<?php
/**
 * Benchmark: file → socket
 *
 * Expected optimized path: sendfile (Linux/BSD), TransmitFile (Windows),
 * generic read/write fallback on macOS for non-TCP.
 *
 * Requires: pcntl
 */

require __DIR__ . '/bench_common.php';

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "This benchmark requires the pcntl extension.\n");
    exit(1);
}

$variantKey = 'file_to_socket';

echo "=== file → socket (sendfile / generic) ===\n";
bench_print_header();

$totalRounds = BENCH_WARMUP + BENCH_ITERATIONS;
$variantResults = [];

foreach (BENCH_SIZES as $name => $size) {
    $data = bench_generate_data($size);

    $srcPath = tempnam(sys_get_temp_dir(), 'bench_src_');
    file_put_contents($srcPath, $data);

    [$server, $addr] = bench_tcp_server();

    $pid = bench_fork(function () use ($addr, $totalRounds) {
        for ($i = 0; $i < $totalRounds; $i++) {
            $sock = stream_socket_client("tcp://$addr", $e, $es, 10);
            while (!feof($sock)) {
                fread($sock, 131072);
            }
            fclose($sock);
        }
    });

    $times = [];
    $copied = 0;
    for ($i = 0; $i < $totalRounds; $i++) {
        $conn = stream_socket_accept($server);
        $src = fopen($srcPath, 'r');

        $start = hrtime(true);
        $copied = stream_copy_to_stream($src, $conn);
        $elapsed = (hrtime(true) - $start) / 1e9;

        fclose($src);
        fclose($conn);

        if ($i >= BENCH_WARMUP) {
            $times[] = $elapsed;
        }
    }

    pcntl_waitpid($pid, $status);
    fclose($server);
    @unlink($srcPath);

    $stats = bench_compute_stats($times, $size);
    $stats['ok'] = ($copied === $size);
    $variantResults[$name] = $stats;

    bench_print_row($name, $stats);
}

$BENCH_RESULTS[$variantKey] = $variantResults;
echo "\n";

bench_finalize();
