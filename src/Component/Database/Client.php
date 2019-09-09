<?php

namespace Keletos\Component\Database;

abstract class Client extends \Keletos\Component\Component {

	protected $_lastInsertId = null;
	protected $_lastDatabase = null;
	protected $_lastError = null;

	protected $_client = null;
	protected $_host = null;
	protected $_port = null;
	protected $_username = null;
	protected $_password = null;
	protected $_type = null;
	protected $_persistent = false;
	protected $_connected = false;

	public function __construct(Array $config = array()){

		parent::__construct();
		$this->_loadConfig($config);

	}

	public function __destruct(){
		$this->disconnect();
	}

	protected function _loadConfig($config){

		array_key_exists('host', $config) && ($this->_host = $config['host']);
		array_key_exists('port', $config) && ($this->_port = $config['port']);
		array_key_exists('username', $config) && ($this->_username = $config['username']);
		array_key_exists('password', $config) && ($this->_password = $config['password']);
		array_key_exists('type', $config) && ($this->_type = $config['type']);
		array_key_exists('database', $config) && ($this->_lastDatabase = $config['database']);
		array_key_exists('persistent', $config) && ($this->_persistent = $config['persistent']);

	}

	public function getClient(){
		return $this->_client;
	}

	public function getLastDatabase(){
		return $this->_lastDatabase;
	}

	public function getLastInsertId(){
		return $this->_lastInsertId;
	}

	public function getHost(){
		return $this->_host;
	}

	public function getPort(){
		return $this->_port;
	}

	public function getUsername(){
		return $this->_username;
	}

	public function getPassword(){
		return $this->_password;
	}

	public function getType(){
		return $this->_type;
	}

	public function isConnected(){
		return $this->_connected;
	}

	public abstract function connect(Array $config = array());
	public abstract function disconnect();
	public abstract function query($sql);
	public abstract function prepare($sql);
	public abstract function quote($sql);

}
