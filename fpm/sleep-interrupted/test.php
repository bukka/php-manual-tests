<?php

slog("before sleep");

// this works:
// exec('sleep 10');

// but, this doesn't work:
usleep(10000000);

slog("after sleep");

echo "Done!" . PHP_EOL;
slog("after echo");


function slog(string $text) {
    $text = '[' . strftime("%m-%d-%Y %H:%M:%S") . '] ' . $text . PHP_EOL;
    file_put_contents(__DIR__ . '/app.log', $text, FILE_APPEND);
}
