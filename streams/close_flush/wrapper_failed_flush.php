<?php
class astream {
	public static $max = 100;

	public $context;

	protected $read = 0;

	protected $flush = false;

	function stream_open($path, $mode) {
		$this->flush = basename($path) === 'flush';
		return true;
	}
	
	function stream_write($data) {
		var_dump($data);
		return strlen($data);
	}

	function stream_read($length) {
		if ($length > self::$max - $this->read) {
			$length = self::$max  - $this->read;
		}
		$this->read += $length;
		return str_repeat('a', $length);
	}

	function stream_tell() {
		return $this->read;
	}

	function stream_eof() {
		return $this->read == self::$max;
	}

	function stream_flush() {
		return $this->flush;
	}

	function stream_stat() {
		return fstat(fopen('php://memory', "r"));
	}

	function url_stat() {
		return fstat(fopen('php://memory', "r"));
	}
}

stream_wrapper_register('as', 'astream');

$stream = fopen('as://flush', 'r+');
var_dump(fwrite($stream, "data"));
var_dump(fread($stream, 3));
var_dump(fclose($stream));

$stream = fopen('as://nothing', 'r+');
var_dump(fwrite($stream, "data"));
var_dump(fread($stream, 3));
var_dump(fclose($stream));

var_dump(file_put_contents('as://', 'test nothing'));
var_dump(file_put_contents('as://flush', 'test flush'));

$path = __DIR__ . '/failed_flush.txt';
var_dump(file_put_contents($path, 'sdata'));
var_dump(copy($path, 'as://nothing'));
var_dump(copy($path, 'as://flush'));
unlink($path);