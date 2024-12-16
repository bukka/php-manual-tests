<?php
$pid = getmypid();
echo 'pid:', $pid, ', start@', $_SERVER['REQUEST_TIME_FLOAT'], ', elapsed: ', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], PHP_EOL;

if (isset($_GET['later'])) {
    if ($_GET['later'] === '1') {
        // using PR's version
        // $c = fastcgi_finish_request(true) ? 'modified:true' : 'modified:false';
        $c = fastcgi_finish_request() ? 'raw:true' : 'raw:false';
    } else {
        $c = fastcgi_finish_request() ? 'raw:true' : 'raw:false';
    }
    $ret = req(['pid' => $pid, 'sleep' => 5, 'c' => $c]);
    var_dump($ret);
} elseif (isset($_GET['sleep'])) {
    sleep((int)$_GET['sleep']);
} else {
    echo 'nothing', PHP_EOL;
}
$now = microtime(true);

echo 'pid:', $pid, ', end@', $now, ', elapsed: ', $now - $_SERVER['REQUEST_TIME_FLOAT'], PHP_EOL;


function req(array $args) {
    $tFmt = (new DateTime())->format('Y.m.d.H-i-s.u');
    $path = basename(__FILE__);
    $url = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/{$path}?reqAt={$tFmt}&" . http_build_query($args);
    return file_get_contents($url, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'timeout' => 10,
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'a' => 1,
                'b' => 2,
            ]),
        ],
    ]));
}
