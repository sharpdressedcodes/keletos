<?php

namespace Keletos\Component;

abstract class Task extends Component {

	protected $_frequency;
	protected $_timeZone;

	public function __construct($frequency, $timeZone = null){

		parent::__construct();

		$this->_frequency = $frequency;
		$this->_timeZone = $timeZone;

	}

	public function setTimeZone($newValue){
		$this->_timeZone = $newValue;
	}

	public function shouldRun(\DateTime $lastRun = null){

		$result = true;

		if (!is_null($lastRun)){

			$nextRun = clone $lastRun;
			$nextRun->add(\DateInterval::createFromDateString($this->_frequency));

			$result = $nextRun <= new \DateTime('now', new \DateTimeZone($this->_timeZone));

		}

		return $result;

	}

	public abstract function run(array $params = array());

}
