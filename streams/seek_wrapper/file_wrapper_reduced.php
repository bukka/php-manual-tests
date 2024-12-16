<?php
class File_stream {
	static $stream;
	protected $position;
    public $context;

	function stream_open ($path, $mode) {
		if ($mode != 'r' && $mode != 'rb') {
			return false;
		}
		$this->position = 0;
		return true;
	}
	function stream_read ($length) {
		//fseek(self::$stream, $this->position);
		$bytes          = fread(self::$stream, $length);
		$this->position += strlen($bytes);
        printf("After read - pos: %d, len: %d, bytes: %s\n", $this->position, $length, $bytes);
		return $bytes;
	}
	function stream_tell () {
        echo "tell\n";
		return $this->position;
	}
	function stream_eof () {
		//fseek(self::$stream, $this->position);
		return feof(self::$stream);
	}
	function stream_seek ($offset, $whence = SEEK_SET) {
		//fseek(self::$stream, $this->position);
		$result         = fseek(self::$stream, $offset, $whence);
		$this->position = ftell(self::$stream);
        printf("After seek - pos: %d, offset: %d, result: %s\n", $this->position, $offset, $result);
		return $result >= 0;
	}
	function stream_stat () {
		return fstat(self::$stream);
	}
}
stream_wrapper_register('request-file', 'File_stream');

$stream_direct = fopen(__DIR__ . '/copy_data.txt', 'r');

File_stream::$stream = $stream_direct;

$stream = fopen('request-file://', 'r');
var_dump(fread($stream, 3));
var_dump(fseek($stream, -1, SEEK_CUR));
var_dump(fread($stream, 2));
var_dump(ftell($stream));
fclose($stream);