<?php

namespace Keletos\Component;

abstract class Plugin extends Component {

	protected $_path;
	protected $_config;
	protected $_enabled;
	protected $_params;

	public function __construct(array $config, $path, $params){

		parent::__construct();

		Application::instace()->loadConfig("$path/Config");

		$this->_params = $params;
		$this->_path = $path;
		$this->_enabled = true;
		$this->_config = $config;

	}

	public function isEnabled(){
		return $this->_enabled;
	}

	public function setConfig($name, $value){
		$this->_config[$name] = $value;
	}

	public abstract function run(array $params = array());

}
