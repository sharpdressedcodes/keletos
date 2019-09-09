<?php

namespace Keletos\Component\Database\MySql;

class Client extends \Keletos\Component\Database\Client {

	public function __construct(array $config = array()){

		self::$extensions = array('PDO');
		parent::__construct($config);

	}

	public function connect(array $config = array()){

		$this->_loadConfig($config);

		$this->_lastInsertId = null;
		$this->_lastError = null;

		try {

			$this->_client = new \PDO("{$this->_type}:host={$this->_host};port={$this->_port};dbname={$this->_lastDatabase}",
				$this->_username,
				$this->_password,
				array(
					\PDO::ATTR_PERSISTENT => $this->_persistent
				)
			);

			$this->_connected = true;

		} catch (\PDOException $ex){

			$this->_lastError = $ex;
			$this->_connected = false;

		}

		return $this->_connected;

	}

	public function disconnect(){
		$this->_connected = false;
		$this->_client = null;
	}

	public function query($sql){
		return $this->_client->query($sql);
	}

	public function prepare($sql){
		return $this->_client->prepare($sql);
	}

	public function quote($sql){
		return $this->_client->quote($sql);
	}

}
