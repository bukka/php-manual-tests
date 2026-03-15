<?php
/**
 * Benchmark: socket → file
 *
 * Expected optimized path: splice (Linux), generic read/write elsewhere.
 *
 * Requires: pcntl
 */

require __DIR__ . '/bench_common.php';

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "This benchmark requires the pcntl extension.\n");
    exit(1);
}

$variantKey = 'socket_to_file';

echo "=== socket → file (splice / generic) ===\n";
bench_print_header();

$totalRounds = BENCH_WARMUP + BENCH_ITERATIONS;
$variantResults = [];

foreach (BENCH_SIZES as $name => $size) {
    $data = bench_generate_data($size);

    $dstPath = tempnam(sys_get_temp_dir(), 'bench_dst_');

    [$server, $addr] = bench_tcp_server();

    $pid = bench_fork(function () use ($addr, $data, $totalRounds) {
        for ($i = 0; $i < $totalRounds; $i++) {
            $sock = stream_socket_client("tcp://$addr", $e, $es, 10);
            $written = 0;
            $len = strlen($data);
            while ($written < $len) {
                $n = fwrite($sock, substr($data, $written, 131072));
                if ($n === false) break;
                $written += $n;
            }
            stream_socket_shutdown($sock, STREAM_SHUT_WR);
            fread($sock, 1);
            fclose($sock);
        }
    });

    $times = [];
    $copied = 0;
    for ($i = 0; $i < $totalRounds; $i++) {
        $conn = stream_socket_accept($server);
        $dst = fopen($dstPath, 'w');

        $start = hrtime(true);
        $copied = stream_copy_to_stream($conn, $dst);
        $elapsed = (hrtime(true) - $start) / 1e9;

        fclose($dst);
        fclose($conn);

        if ($i >= BENCH_WARMUP) {
            $times[] = $elapsed;
        }
    }

    pcntl_waitpid($pid, $status);
    fclose($server);
    @unlink($dstPath);

    $stats = bench_compute_stats($times, $size);
    $stats['ok'] = ($copied === $size);
    $variantResults[$name] = $stats;

    bench_print_row($name, $stats);
}

$BENCH_RESULTS[$variantKey] = $variantResults;
echo "\n";

bench_finalize();
