<?php
echo "PHP version: ", PHP_VERSION, "\n";
// set safe test environment:
error_reporting(-1);
ini_set("track_errors", "1");

// maps errors to ErrorException:
function my_error_handler($errno, $message) {
	throw new ErrorException($message);
}
set_error_handler("my_error_handler");

define("UNREADABLE", "/proc/self/mem");
require UNREADABLE;
//require_once UNREADABLE;
//include UNREADABLE;
//include_once UNREADABLE;

echo "Just testing if error detection is still on:\n";
require "this file does not exist!";
