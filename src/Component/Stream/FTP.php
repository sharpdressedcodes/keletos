<?php

namespace Keletos\Component\Stream;

use Keletos\Utility\FileSystem;
use Keletos\Utility\GString;

class FTP extends Socket {

	const DEFAULT_PORT = 21;
	const DEFAULT_USER = 'anonymous';
	const DEFAULT_PASSWORD = 'test@example.com';

	public function __construct($server = null, $port = self::DEFAULT_PORT){
		parent::__construct($server, $port);
	}

	public function open(array $params = array()){

		$user = self::DEFAULT_USER;
		$password = self::DEFAULT_PASSWORD;

		$this->close();
		$this->_handle = ftp_connect($this->_server, $this->_port);

		if (!is_bool($this->_handle)){
			if (array_key_exists('user', $params)) {
				$user = $params['user'];
			}
			if (array_key_exists('password', $params)) {
				$password = $params['password'];
			}
			return ftp_login($this->_handle, $user, $password);
		}

		return false;

	}

	public function close(){

		if (!is_null($this->_handle)){
			ftp_close($this->_handle);
			$this->_handle = null;
		}

	}

	public function put($data){

		$result = false;

		if (!is_null($this->_handle)) {
			$result = ftp_put($this->_handle, $data['remoteFile'], $data['localFile'], FTP_BINARY);
		}

		return $result;

	}

	public function get($data = null){

		$result = false;

		if (!is_null($this->_handle)) {
			$tempFileName = FileSystem::generateTempFileName();
			ftp_pasv($this->_handle, true);
			$result = ftp_get($this->_handle, $tempFileName, $data['remoteFile'], FTP_BINARY);
			ftp_pasv($this->_handle, false);
			$result && ($result = FileSystem::readFromFile($tempFileName));
			FileSystem::deleteFile($tempFileName);
		}

		return $result;

	}

	public function list($path = '.') {

		$result = array();

		if (!is_null($this->_handle)) {
			ftp_pasv($this->_handle, true);
			$result = ftp_nlist($this->_handle, $path);
			// ftp_pasv($this->_handle, false);
		}

		return array_map(function($item) use ($path) {
			if (GString::startsWith($item, $path)) {
				$item = substr($item, strlen($path) + 1);
			}
			return $item;
		}, $result);

	}

	public function getLine(){return null;}

}
