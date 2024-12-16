<?php

$fp = fsockopen("localhost\0.some-domain-for-me.com", 4000);
fwrite($fp, "TEST\n");
fclose($fp);
