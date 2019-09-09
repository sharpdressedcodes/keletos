<?php

namespace Keletos\Component\Stream;

class HTTPS extends HTTP {

	const DEFAULT_PORT = 443;

	public function __construct($server = null, $port = self::DEFAULT_PORT){
		parent::__construct($server, $port);
	}

	public function open(array $params = array()){

		$result = parent::open($params);

		if ($result) {
			curl_setopt($this->_handle, CURLOPT_URL, 'https://' . $this->_server . $params['url']);
			curl_setopt($this->_handle, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->_handle, CURLOPT_SSL_VERIFYPEER, false);
		}

		return $result;

	}

}
