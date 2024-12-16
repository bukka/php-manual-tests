<?php
ob_start(function () {
    throw new Exception('ob_start');
});
try {
    ob_flush();
} catch (Throwable) {}
ob_flush();
