<?php

class FSStreamWrapper {
	public $context;

	public $handle;

	function stream_open( $file, $mode ) {
		$this->handle = fopen( str_replace( 'fs://', __DIR__ . '/', $file ), $mode );
		return true;
	}
	function stream_read( $count ) {
		return fread( $this->handle, $count );
	}
	function stream_eof() {
		return feof( $this->handle );
	}
	function stream_seek( $offset, $whence ) {
		return fseek( $this->handle, $offset, $whence ) === 0;
	}
	function stream_stat() {
		return fstat( $this->handle );
	}
	function url_stat( $file ) {
		return stat( str_replace( 'fs://', '', $file ) );
	}
	function stream_tell() {
		return ftell( $this->handle );
	}
	function stream_close() {
		fclose( $this->handle );
	}
}

stream_register_wrapper( 'fs', 'FSStreamWrapper' );

var_dump(getimagesize( 'fs://large-exif.jpg', $info ));

