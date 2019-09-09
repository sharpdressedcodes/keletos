<?php

namespace Keletos\Component;

abstract class Plugin extends Component {

	protected $_path;
	protected $_config;
	protected $_enabled;
	protected $_controller;
	protected $_params;

	public function __construct(\Keletos\Controller\Controller $controller, $path, $params){

		parent::__construct();

		$controller->loadConfig($path . DIRECTORY_SEPARATOR . 'Config');

		$this->_params = $params;
		$this->_path = $path;
		$this->_enabled = true;
		$this->_controller = $controller;
		$this->_config = $controller->getConfig();

	}

	public function isEnabled(){
		return $this->_enabled;
	}

	public function setConfig($name, $value){
		$this->_config[$name] = $value;
	}

	public abstract function run(array $params = array());

}
