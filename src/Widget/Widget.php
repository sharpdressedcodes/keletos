<?php

namespace Keletos\Widget;

abstract class Widget
	extends \Keletos\Component\Component
	implements \Keletos\Component\Event\IObserver {

	protected $_params;
	protected $_tasks;
	protected $_config;

	public function __construct(array $params = array(), array $tasks = array(), $dir = null){

		parent::__construct();

		$params['dir'] = $dir;

		$this->_params = $params;
		$this->_tasks = $tasks;
		$this->_config = $params['controller']->getConfig();
		$taskManager = $params['controller']->getTaskManager();

		if (!is_null($taskManager) && $params['controller']->getRunTaskManager() && count($tasks) > 0)
			$taskManager->runTasks($params, $tasks);

	}

	public function getTasks(){
		return $this->_tasks;
	}

	public function render(){

//		$controller = $this->_params['controller'];
//		$view = $this->_params['view'];
//		$dir = $this->_params['dir'];
//
//		include $dir . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $view;

		extract($this->_params);

		$original = $dir . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $view;
		$file = $original;

		!file_exists($file) && ($file .= '.php');

		if (!file_exists($file)){
			throw new \Exception("Can't find view {$original} for widget " . get_class($this) . ".");
		} else {
			include $file;
		}

	}

	public function onEvent(\Keletos\Component\Event\IObservable $source, $event, array &$params = array()){
		//
	}

}
