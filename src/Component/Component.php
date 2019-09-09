<?php

namespace Keletos\Component;

abstract class Component implements \Keletos\Component\Event\IObservable {

	protected $_observers;

	protected static $extensions = array();

	public function __construct(){

		$this->_observers = array();
		!empty(self::$extensions) && $this->_checkExtensions();

	}

	public function addObserver(\Keletos\Component\Event\IObserver $observer, $event){

		$events = is_array($event) ? $event : array($event);

		if (is_null($this->_observers)){
			throw new \Exception('<strong>Fatal Error:</strong> Concrete class must call parent::__construct in Constructor');
		}

		foreach ($events as $event){

			if (!array_key_exists($event, $this->_observers)){
				$this->_observers[$event] = array();
			}

			$this->_observers[$event][] = $observer;

		}

		return $this;

	}

	public function dispatchEvent($event, array &$params = array()) : bool {

//		if (is_null($this->_observers)){
//			throw new \Exception('<strong>Fatal Error:</strong> Concrete class must call parent::__construct in Constructor');
//		}

		if (!array_key_exists($event, $this->_observers)){
			return true;
		}

		foreach ($this->_observers[$event] as $observer) {
            /**
             * @var $observer \Keletos\Component\Event\IObserver
             */
			if ($observer->onEvent($this, $event, $params) === false) {
				return false;
			}
		}

		return true;

	}

	protected function _checkExtensions(){

	    $offenders = [];

		foreach (self::$extensions as $extension) {
			if (!extension_loaded($extension)){
			    $offenders[] = $extension;
			}
		}

		if (!empty($offenders)) {
		    $str = implode(', ', $offenders);
		    $isPlural = count($offenders) > 1;
		    $message = "<strong>Fatal Error:</strong> The following extension" . ($isPlural ? 's are' : ' is') . " required but not found on this system [$str]. Please install and enable " . ($isPlural ? 'them.' : 'it.');
            throw new \Exception($message);
        }

	}

}
