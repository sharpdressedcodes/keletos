<?php

namespace Keletos\Component;

class PluginManager extends Component {

	protected $_plugins;
	protected $_controller;
	protected $_config;

	public function __construct($config){

		parent::__construct();

		$this->_plugins = [];
		$this->_config = $config;

	}

	public function getPlugins(){
		return $this->_plugins;
	}

	public function addPlugin($plugin){
		$this->_plugins[get_class($plugin)] = $plugin;
		return $this;
	}

	public function removePlugin($plugin){

		$key = get_class($plugin);

		if (array_key_exists($key, $this->_plugins))
			unset($this->_plugins[$key]);

	}

	public function runPlugins(array $params = array()){

		$plugins = array();

		foreach ($this->_plugins as $key => $plugin)
			if ($plugin->isEnabled())
				$plugins[$key] = $plugin;


		$eventParams = array(
			'plugins' => $plugins,
		);

		if ($this->dispatchEvent('onBeforeRun', $eventParams)){

			foreach ($plugins as $key => $plugin){

				if ($plugin->dispatchEvent('onBeforeRun', $params)){
					$plugin->run($params);
					$plugin->dispatchEvent('onAfterRun', $params);
				}

			}

			$this->dispatchEvent('onAfterRun', $eventParams);

		}

	}

}
