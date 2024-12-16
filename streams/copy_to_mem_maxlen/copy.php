<?php

ini_set('memory_limit', '10M');

file_put_contents('test.txt', str_repeat('h', 50000));

var_dump(strlen(file_get_contents('test.txt', length: 40000)));

var_dump(memory_get_peak_usage());