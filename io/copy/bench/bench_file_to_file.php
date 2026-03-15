<?php
/**
 * Benchmark: file → file
 *
 * Expected optimized path: copy_file_range (Linux), CopyFileEx (Windows),
 * generic read/write fallback elsewhere.
 */

require __DIR__ . '/bench_common.php';

bench_run(
    'file → file (copy_file_range / generic)',
    setup: function (int $size, string $data) {
        $src = tempnam(sys_get_temp_dir(), 'bench_src_');
        $dst = tempnam(sys_get_temp_dir(), 'bench_dst_');
        file_put_contents($src, $data);
        return ['src' => $src, 'dst' => $dst, 'size' => $size];
    },
    callback: function (int $size, string $data, array $ctx): int {
        $src = fopen($ctx['src'], 'r');
        $dst = fopen($ctx['dst'], 'w');
        $copied = stream_copy_to_stream($src, $dst);
        fclose($dst);
        fclose($src);
        return $copied;
    },
    teardown: function (array $ctx) {
        @unlink($ctx['src']);
        @unlink($ctx['dst']);
    },
);

bench_finalize();
