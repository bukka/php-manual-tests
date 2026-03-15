#!/usr/bin/env php
<?php
/**
 * Trace which syscalls stream_copy_to_stream actually uses.
 *
 * This tells you whether the optimized paths (copy_file_range, sendfile,
 * splice) are being taken or whether it falls back to generic read/write.
 *
 * Usage:
 *   php bench_trace_syscalls.php [php_binary]
 *
 * Requires: strace (Linux) or dtruss (macOS, needs root/SIP disabled)
 */

$phpBin = $argv[1] ?? PHP_BINARY;
$isLinux = PHP_OS === 'Linux';
$isMac   = PHP_OS === 'Darwin';

if (!$isLinux && !$isMac) {
    fwrite(STDERR, "This script supports Linux (strace) and macOS (dtruss) only.\n");
    exit(1);
}

if ($isLinux) {
    $tracer = trim(shell_exec('which strace 2>/dev/null'));
    if (!$tracer) {
        fwrite(STDERR, "strace not found. Install with: apt install strace\n");
        exit(1);
    }
} else {
    $tracer = trim(shell_exec('which dtruss 2>/dev/null'));
    if (!$tracer) {
        fwrite(STDERR, "dtruss not found or SIP prevents use.\n");
        exit(1);
    }
}

$syscalls = $isLinux
    ? 'read,write,sendfile,splice,copy_file_range,sendto,recvfrom'
    : 'read,write,sendfile,sendto,recvfrom';

$variants = [
    'file_to_file' => <<<'PHP'
        $src = tempnam(sys_get_temp_dir(), 'tr_');
        $dst = tempnam(sys_get_temp_dir(), 'tr_');
        file_put_contents($src, str_repeat('x', 1048576));
        $s = fopen($src, 'r'); $d = fopen($dst, 'w');
        stream_copy_to_stream($s, $d);
        fclose($s); fclose($d);
        @unlink($src); @unlink($dst);
        PHP,
    'file_to_socket' => <<<'PHP'
        $src = tempnam(sys_get_temp_dir(), 'tr_');
        file_put_contents($src, str_repeat('x', 1048576));
        $srv = stream_socket_server('tcp://127.0.0.1:0');
        $addr = stream_socket_get_name($srv, false);
        $pid = pcntl_fork();
        if ($pid === 0) {
            $c = stream_socket_client("tcp://$addr");
            while (!feof($c)) fread($c, 131072);
            fclose($c); exit;
        }
        $conn = stream_socket_accept($srv);
        $s = fopen($src, 'r');
        stream_copy_to_stream($s, $conn);
        fclose($s); fclose($conn); fclose($srv);
        pcntl_wait($st);
        @unlink($src);
        PHP,
    'socket_to_file' => <<<'PHP'
        $dst = tempnam(sys_get_temp_dir(), 'tr_');
        $srv = stream_socket_server('tcp://127.0.0.1:0');
        $addr = stream_socket_get_name($srv, false);
        $pid = pcntl_fork();
        if ($pid === 0) {
            $c = stream_socket_client("tcp://$addr");
            $data = str_repeat('x', 1048576);
            $w = 0; while ($w < 1048576) { $n = fwrite($c, substr($data, $w, 131072)); $w += $n; }
            stream_socket_shutdown($c, STREAM_SHUT_WR);
            fread($c, 1); fclose($c); exit;
        }
        $conn = stream_socket_accept($srv);
        $d = fopen($dst, 'w');
        stream_copy_to_stream($conn, $d);
        fclose($d); fclose($conn); fclose($srv);
        pcntl_wait($st);
        @unlink($dst);
        PHP,
    'socket_to_socket' => <<<'PHP'
        $srv1 = stream_socket_server('tcp://127.0.0.1:0');
        $addr1 = stream_socket_get_name($srv1, false);
        $pid1 = pcntl_fork();
        if ($pid1 === 0) {
            $conn = stream_socket_accept($srv1);
            $data = str_repeat('x', 1048576);
            $w = 0; while ($w < 1048576) { $n = fwrite($conn, substr($data, $w, 131072)); $w += $n; }
            stream_socket_shutdown($conn, STREAM_SHUT_WR);
            fread($conn, 1); fclose($conn); fclose($srv1); exit;
        }
        fclose($srv1);
        $srv2 = stream_socket_server('tcp://127.0.0.1:0');
        $addr2 = stream_socket_get_name($srv2, false);
        $pid2 = pcntl_fork();
        if ($pid2 === 0) {
            $conn = stream_socket_accept($srv2);
            while (!feof($conn)) fread($conn, 131072);
            fclose($conn); fclose($srv2); exit;
        }
        fclose($srv2);
        $src = stream_socket_client("tcp://$addr1");
        $dst = stream_socket_client("tcp://$addr2");
        stream_copy_to_stream($src, $dst);
        stream_socket_shutdown($dst, STREAM_SHUT_WR);
        fclose($src); fclose($dst);
        pcntl_wait($st); pcntl_wait($st);
        PHP,
];

echo "Tracing syscalls for each stream_copy_to_stream variant (1 MB payload)\n";
echo "PHP binary: $phpBin\n";
echo "Tracer:     " . ($isLinux ? 'strace' : 'dtruss') . "\n";
echo str_repeat('=', 60) . "\n\n";

foreach ($variants as $name => $code) {
    echo "--- $name ---\n";

    $tmpScript = tempnam(sys_get_temp_dir(), 'trace_') . '.php';
    file_put_contents($tmpScript, "<?php\n$code\n");

    if ($isLinux) {
        $cmd = sprintf(
            'strace -c -e trace=%s %s %s 2>&1 >/dev/null',
            escapeshellarg($syscalls),
            escapeshellarg($phpBin),
            escapeshellarg($tmpScript)
        );
    } else {
        $cmd = sprintf(
            'sudo dtruss -c %s %s 2>&1 >/dev/null',
            escapeshellarg($phpBin),
            escapeshellarg($tmpScript)
        );
    }

    passthru($cmd);
    @unlink($tmpScript);
    echo "\n";
}
