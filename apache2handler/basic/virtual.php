<?php

virtual('/subrequest.php');

echo "test";

$mep = str_repeat('test', 256 * 1000000);

echo "virtual!\n";
