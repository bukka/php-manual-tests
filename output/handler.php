<?php

function outputHandler($buffer) {
    echo "extra stuff\n";
    return false;
}

ob_start("outputHandler");
echo "my data\n";
ob_end_flush();

?>