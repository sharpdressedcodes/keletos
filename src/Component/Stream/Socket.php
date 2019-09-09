<?php

namespace Keletos\Component\Stream;

class Socket extends AbstractStream {

	const DEFAULT_TIMEOUT = 20;

	protected $_server;
	protected $_port;

	public function __construct($server = null, $port = null){

		if (!is_null($server))
			$this->_server = $server;

		if (!is_null($port))
			$this->_port = $port;

	}

	public function getServer(){
		return $this->_server;
	}

	public function getPort(){
		return $this->_port;
	}

	public function setServer($newValue){
		$this->_server = $newValue;
		return $this;
	}

	public function setPort($newValue){
		$this->_port = $newValue;
		return $this;
	}

	public function open(array $params = array()){

		$errNo = null;
		$errStr = null;

		$this->close();
		$this->_handle = @fsockopen($this->_server, $this->_port, $errNo, $errStr, self::DEFAULT_TIMEOUT);

		if (!$this->_handle)
			$this->_handle = null;

		return is_null($this->_handle) ? $errStr : true;

	}

}
