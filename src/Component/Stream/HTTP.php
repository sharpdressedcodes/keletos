<?php

namespace Keletos\Component\Stream;

class HTTP extends Socket {

	const DEFAULT_CONNECTION_TIMEOUT = 10; // seconds
	const DEFAULT_TIMEOUT = 30; // seconds
	const DEFAULT_PORT = 80;

	protected $_curlInfo;
	protected $_requestHeaders;

	public function __construct($server = null, $port = self::DEFAULT_PORT){
		parent::__construct($server, $port);
		$this->_curlInfo = array();
		$this->_requestHeaders = [];
	}

	public function getCurlInfo(){
		return $this->_curlInfo;
	}

	public function getRequestHeaders() {
		return $this->_requestHeaders;
	}

	public function open(array $params = array()){

		$result = false;
		$this->_curlInfo = array();
		$this->_requestHeaders = [];
		$this->close();
		$this->_handle = curl_init();

		if ($this->_handle) {
			curl_setopt($this->_handle, CURLOPT_URL, 'http://' . $this->_server . $params['url']);
			curl_setopt($this->_handle, CURLOPT_PORT, $this->_port);
			curl_setopt($this->_handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->_handle, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($this->_handle, CURLOPT_CONNECTTIMEOUT, isset($params['connectTimeout']) ? $params['connectTimeout'] : self::DEFAULT_CONNECTION_TIMEOUT);
			curl_setopt($this->_handle, CURLOPT_TIMEOUT, isset($params['timeout']) ? $params['timeout'] : self::DEFAULT_TIMEOUT);

			if (array_key_exists('user', $params) && array_key_exists('password', $params)) {
				curl_setopt($this->_handle, CURLOPT_USERPWD, "{$params['user']}:{$params['password']}");
			}

			if (array_key_exists('headers', $params)) {
				curl_setopt($this->_handle, CURLINFO_HEADER_OUT, true); // enable tracking of headers
				curl_setopt($this->_handle, CURLOPT_HTTPHEADER, $params['headers']);
			}

			$result = true;
		}

		return $result;

	}

	public function close(){

		if (!is_null($this->_handle)){
			curl_close($this->_handle);
			$this->_handle = null;
		}

	}

	public function put($data = array()){

		if (is_null($this->_handle))
			return false;

		if (array_key_exists('method', $data) && $data['method'] === 'post'){
			curl_setopt($this->_handle, CURLOPT_POST, count($data['data']));
			curl_setopt($this->_handle, CURLOPT_POSTFIELDS, $data['data']);
		}

		return array_key_exists('data', $data) ? strlen($data['data']) : true;

	}

	public function get($data = null){

		if (is_null($this->_handle))
			return false;

		$result = curl_exec($this->_handle);
		$this->_curlInfo = curl_getinfo($this->_handle);
		$this->_requestHeaders = curl_getinfo($this->_handle, CURLINFO_HEADER_OUT); // inspect request headers here

		return $result;

	}

	public function getLine() { return null; }

}
