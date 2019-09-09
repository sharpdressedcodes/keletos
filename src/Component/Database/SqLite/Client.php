<?php

namespace Keletos\Component\Database\SqLite;

class Client extends \Keletos\Component\Database\Client {

	const EXTENSION_NAME = 'SQLite3';
	const NO_ERROR = 'not an error';

	protected $_fileName = null;

	public function __construct(array $config = array()){

		self::$extensions = array(self::EXTENSION_NAME);

		if (!isset($config['fileName'])){
			throw new \Exception('Error: ' . self::EXTENSION_NAME . ' fileName not specified.');
		}

		$this->_fileName = $config['fileName'];

		parent::__construct($config);

	}

	public function getFileName(){
		return $this->_fileName;
	}

	public function connect(array $config = array()){

		$this->_loadConfig($config);

		$this->_lastInsertId = null;
		$this->_lastError = null;
		$this->_connected = false;

		try {

			if (!file_exists($this->_fileName)){
				$this->_lastError = new \Exception("Error loading " . self::EXTENSION_NAME . " database, {$this->_fileName} does not exist.");
			} else {
				$this->_client = new \SQLite3($this->_fileName);
				$this->_connected = true;
			}

		} catch (\Exception $ex){
			$this->_lastError = $ex;
		}

		return $this->_connected;

	}

	public function disconnect(){

		$this->_connected = false;
		!is_null($this->_client) && $this->_client->close();
		$this->_client = null;

	}

	/**
	 * @param string $sql The sql query
	 * @param bool $assoc (Optional | true)
	 * @return array Empty array or array of arrays
	 */
	public function query($sql, $assoc = true){

		$result = false;

		if (!is_null($this->_client)){
			$dbResult = $this->_client->query($sql);
			$hasError = $this->changeLastError();
			$result = !$hasError;

			if (!$hasError){
				$result = $this->parseResults($sql, $dbResult, $assoc);
			}
		}

		return $result;

	}

	/**
	 * Prepares a sql query, and if values are supplied, also binds those values, executes the query and parses the results.
	 * The return value of this method depends on if values are supplied or not.
	 *
	 * @param string $sql The sql query
	 * @param array $values (Optional) The values to bind
	 * @param bool $assoc (Optional) return an associative array? defaults to true
	 * @return mixed (\SQLite3Stmt|bool|array) SQLiteStmt|array if successful, false otherwise
	 */
	public function prepare($sql, array $values = array(), $assoc = true){

		$result = false;

		if (!is_null($this->_client)){
			$statement = $this->_client->prepare($sql);
			$hasError = $this->changeLastError();
			$result = $hasError ? $hasError : $statement;

			if (!$hasError && !empty($values)){
				foreach ($values as $key => $value){
					if (is_array($value)){
						$bound = $statement->bindValue(":$key", $value['value'], $value['type']);
					} else {
						$bound = $statement->bindValue(":$key", $value);
					}
					if (!$bound){
						$hasError = $this->changeLastError();
						break;
					}
				}
				if (!$hasError){
					$dbResult = $statement->execute();
					$result = $this->parseResults($sql, $dbResult, $assoc);
					$statement->close();
				}
			}
		}

		return $result;

	}

	public function quote($sql){
		return \SQLite3::escapeString($sql);
	}

	/**
	 * @return int
	 */
	public function getLastInsertId() {

		$this->_lastInsertId = $this->_client->lastInsertRowID();

		return $this->_lastInsertId;

	}

	/**
	 * @return bool true if there is an error, false otherwise
	 */
	protected function changeLastError(){

		$this->_lastError = null;
		$lastErrorMessage = $this->_client->lastErrorMsg();

		if ($lastErrorMessage !== self::NO_ERROR){
			$this->_lastError = new \Exception(self::EXTENSION_NAME . " Error: $lastErrorMessage");
		}

		return !is_null($this->_lastError);

	}

	protected function parseResults($sql, $sqlResult, $assoc = true){

		$result = array();

		if (strpos(strtolower($sql), 'insert ') !== false){

			$this->_lastInsertId = $this->_client->lastInsertRowID();

		} else if ($sqlResult instanceof \SQLite3Result && strpos(strtolower($sql), 'select ') !== false) {

			$count = 0;

			while($row = $sqlResult->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_BOTH)){
				foreach($row as $key => $value) {
					$result[$count][$key] = $value;
				}
				$count++;
			}

		}

		return $result;

	}

}
