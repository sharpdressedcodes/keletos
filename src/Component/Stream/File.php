<?php

namespace Keletos\Component\Stream;

class File extends AbstractStream {

	protected $_filename;

	public static $modes = array(
		'READ' => 'r',
		'READ+WRITE' => 'r+',
		'WRITE' => 'w',
		'READ+WRITE+TRUNCATE' => 'w+',
		'WRITE+APPEND' => 'a',
		'READ+WRITE+APPEND' => 'a+',
		'XCREATE+WRITE' => 'x',
		'XCREATE+READ+WRITE' => 'x+',
		'CREATE+WRITE' => 'c',
		'CREATE+READ+WRITE' => 'c+',
	);

	public function __construct($filename){

		$this->_filename = $filename;

	}

	public function getFilename(){
		return $this->_filename;
	}

	public function isEOF(){

		return feof($this->_handle);

	}

	public function open(array $params = array()){

		$this->close();

		//if (!file_exists($this->_filename))
			//return false;

		$this->_handle = fopen($this->_filename, self::$modes[$params['mode']]);

		return !is_bool($this->_handle);

	}

}
