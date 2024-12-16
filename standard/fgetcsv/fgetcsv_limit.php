<?php
//var_dump(str_getcsv('"","a'));
//var_dump(str_getcsv('"","'));
$handle = fopen(__DIR__ . "/test.csv", "r");
var_dump(fgetcsv($handle, 5));
