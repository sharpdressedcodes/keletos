<?php

namespace Keletos\Component\Api;

class Nasa extends \Keletos\Component\Component {

	const DEFAULT_SERVER = 'api.nasa.gov';

	protected $_apiKey;
	protected $_https;
	protected $_country;
	protected $_city;

	public function __construct($apiKey, $server = self::DEFAULT_SERVER){

		$this->_apiKey = $apiKey;
		$this->_https = new \Keletos\Component\Stream\HTTPS($server);

		parent::__construct();

	}

	public function getApiKey(){
		return $this->_apiKey;
	}

	public function getHttps(){
		return $this->_https;
	}

	public function getApod(){
		return $this->api('planetary/apod');
	}

	protected function api($request){

		$s = null;
		$url = sprintf(
			'/%s?api_key=%s',
			$request,
			$this->_apiKey
		);

		$this->_https->open(array('url' => $url));

		if ($this->_https->put(array())){
			$s = $this->_https->get();
			$this->_https->close();
		}

		if (!is_null($s)){
			$s = json_decode($s, true);
			if (array_key_exists('response', $s)){
				unset($s['response']);
			}
		}

		return $s;

	}

}
