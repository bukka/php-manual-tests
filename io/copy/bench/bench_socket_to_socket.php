<?php
/**
 * Benchmark: socket → socket
 *
 * Expected optimized path: double-splice via intermediate pipe (Linux),
 * generic read/write fallback elsewhere.
 *
 * Requires: pcntl
 */

require __DIR__ . '/bench_common.php';

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "This benchmark requires the pcntl extension.\n");
    exit(1);
}

$variantKey = 'socket_to_socket';

echo "=== socket → socket (double-splice / generic) ===\n";
bench_print_header();

$totalRounds = BENCH_WARMUP + BENCH_ITERATIONS;
$variantResults = [];

foreach (BENCH_SIZES as $name => $size) {
    $data = bench_generate_data($size);

    /* Source server: child writes data into each accepted connection. */
    [$srcServer, $srcAddr] = bench_tcp_server();
    $srcPid = bench_fork(function () use ($srcServer, $data, $totalRounds) {
        for ($i = 0; $i < $totalRounds; $i++) {
            $conn = stream_socket_accept($srcServer);
            $written = 0;
            $len = strlen($data);
            while ($written < $len) {
                $n = fwrite($conn, substr($data, $written, 131072));
                if ($n === false) break;
                $written += $n;
            }
            stream_socket_shutdown($conn, STREAM_SHUT_WR);
            fread($conn, 1);
            fclose($conn);
        }
        fclose($srcServer);
    });
    fclose($srcServer);

    /* Dest server: child drains each accepted connection. */
    [$dstServer, $dstAddr] = bench_tcp_server();
    $dstPid = bench_fork(function () use ($dstServer, $totalRounds) {
        for ($i = 0; $i < $totalRounds; $i++) {
            $conn = stream_socket_accept($dstServer);
            while (!feof($conn)) {
                $chunk = fread($conn, 131072);
                if ($chunk === false) break;
            }
            fclose($conn);
        }
        fclose($dstServer);
    });
    fclose($dstServer);

    $times = [];
    $copied = 0;
    for ($i = 0; $i < $totalRounds; $i++) {
        $src = stream_socket_client("tcp://$srcAddr", $e, $es, 10);
        $dst = stream_socket_client("tcp://$dstAddr", $e, $es, 10);

        $start = hrtime(true);
        $copied = stream_copy_to_stream($src, $dst);
        $elapsed = (hrtime(true) - $start) / 1e9;

        stream_socket_shutdown($dst, STREAM_SHUT_WR);
        fclose($src);
        fclose($dst);

        if ($i >= BENCH_WARMUP) {
            $times[] = $elapsed;
        }
    }

    pcntl_waitpid($srcPid, $status);
    pcntl_waitpid($dstPid, $status);

    $stats = bench_compute_stats($times, $size);
    $stats['ok'] = ($copied === $size);
    $variantResults[$name] = $stats;

    bench_print_row($name, $stats);
}

$BENCH_RESULTS[$variantKey] = $variantResults;
echo "\n";

bench_finalize();
