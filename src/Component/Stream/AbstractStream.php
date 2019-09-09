<?php

namespace Keletos\Component\Stream;

abstract class AbstractStream {

	const CHUNK_SIZE = 4096;

	protected $_handle;

	public function __destruct(){
		$this->close();
	}

	public function getHandle(){
		return $this->_handle;
	}

	public function close(){

		if ($this->_handle){
			fclose($this->_handle);
			$this->_handle = null;
		}

	}

	public function put($data){

		if (is_null($this->_handle)){
			//$this->_lastError = 'Error: not connected';
			return false;
		}

		$bytesWritten = 0;

		try {
			$bytesWritten = fputs($this->_handle, $data, strlen($data));
		} catch (\Exception $e){
			//$this->_lastError = $e->getMessage();
		}

		return $bytesWritten;

	}

	public abstract function open(array $params = array());

	public function get($data = null){

		if (is_null($this->_handle)){
			return false;
		}

		$buffer = array();

		try {

			while (!feof($this->_handle)){
				$packet = fgets($this->_handle, self::CHUNK_SIZE);
				$buffer[] = $packet;
				if (!is_null($data) && strstr($packet, $data))
					break;
			}

		} catch (\Exception $e){
			//$this->_lastError = $e->getMessage();
			return null;
		}

		return count($buffer) > 0 ? implode('', $buffer) : null;

	}

	public function getLine(){

		if (is_null($this->_handle)){
			return false;
		}

		$buffer = array();
		$count = 0;

		try {

			while (!feof($this->_handle)){
				$char = fgetc($this->_handle);
				$buffer[] = $char;
				$count++;
				if ($char === "\r" || $char === "\n"){
					if ($char === "\r" && !feof($this->_handle)){
						if (fgetc($this->_handle) !== "\n")
							fseek($this->_handle, $count);
					}
					$buffer[count($buffer) - 1] = substr($buffer[count($buffer) - 1], 0, strlen($buffer[count($buffer) - 1]) - 1);

					break;
				}
			}

		} catch (\Exception $e){
			//$this->_lastError = $e->getMessage();
			return null;
		}

		$result = count($buffer) > 0 ? implode('', $buffer) : null;

//		if (!is_null($result)){
//
//			$rpos = strpos($result, "\r");
//			$npos = strpos($result, "\n");
//
//			if ($rpos !== false && $npos === false)
//				$pos = $rpos;
//			elseif ($rpos === false && $npos !== false)
//				$pos = $npos;
//			elseif ($rpos !== false && $npos !== false)
//				$pos = $rpos < $npos ? $rpos : $npos;
//			else
//				$pos = false;
//
//			if ($pos !== false)
//				$result = substr($result, 0, $pos);
//
//		}

		return $result;

	}

}
