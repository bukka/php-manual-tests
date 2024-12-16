<?php

class MyCustomFilter extends php_user_filter {
    public $stream;
    public function filter($in, $out, &$consumed, $closing): int {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // create first bucket
            $bucket = stream_bucket_new($this->stream, str_repeat("a", 8 * 1024));
            stream_bucket_append($out, $bucket);
            $bucket = stream_bucket_new($this->stream, str_repeat("b", 8 * 1024));
            stream_bucket_append($out, $bucket);

            return PSFS_ERR_FATAL;
        }
        return PSFS_PASS_ON; // Continue processing
    }
}

// Register the custom filter
stream_filter_register("mycustomfilter", "MyCustomFilter");

// Usage
$inputStream = fopen("php://memory", "r+");
fwrite($inputStream, "This is a test string.");
fseek($inputStream, 0);

stream_filter_append($inputStream, "mycustomfilter");
var_dump(fgets($inputStream));

var_dump(memory_get_usage());
