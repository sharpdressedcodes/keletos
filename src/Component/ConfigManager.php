<?php

namespace Keletos\Component;

class ConfigManager extends Component {

    protected $config;

    public function __construct(string $basePath) {
        parent::__construct();

        $this->config = [];
        $this->setupConfig($basePath);
    }

    public function getConfig() {
        return $this->config;
    }

    protected function setupConfig(string $basePath) {

        $this
            ->addConfig('basePath', $basePath)
            ->addConfig('appPath', "$basePath/application")
            ->addConfig('publicPath', "$basePath/public")
            ->addConfig('dataPath', "{$this->config['appPath']}/Data")
            ->addConfig('frameworkPath', dirname(__DIR__))
            ->addConfig('frameworkDataPath', "{$this->config['frameworkPath']}/Data");

        $widgets = \Keletos\Utility\FileSystem::getFiles($this->config['frameworkPath'] . '/Widget', true);
        $configPaths = [];

        foreach ($widgets as $file){

            $dir = 'Config' . DIRECTORY_SEPARATOR;
            $pos = strpos($file, $dir);

            if ($pos !== false){
                $path = substr($file, 0, $pos + strlen($dir) - strlen(DIRECTORY_SEPARATOR));
                array_search($path, $configPaths) === false && ($configPaths[] = $path);
            }

        }

        $this->loadConfig($this->config['frameworkPath'] . '/Config');

        foreach ($configPaths as $configPath){
            $this->loadConfig($configPath);
        }

        $this->loadConfig($this->config['appPath'] . '/Config');

    }

    public function loadConfig(string $path = null) : bool {

        $path = is_null($path) ? dirname(__DIR__) . '/Config' : $path;

        if (!file_exists($path)) {
            return false;
        }

        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+(?<!\.default)\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $main = $path . DIRECTORY_SEPARATOR . 'Main.php';
        if (file_exists($main))
            $this->config = array_merge($this->config, include $main);
        //$this->_config = $this->_config + include $main;

        // Overwrite global config
        foreach ($regex as $file){
            if ($file[0] !== $main){
                try {
                    $this->config = array_merge($this->config, include is_array($file) ? $file[0] : $file);
                    //$this->_config = $this->_config + include is_array($file) ? $file[0] : $file;
                } catch (\Exception $e){}
            }
        }

        return true;

    }

    public function addConfig($key, $value) : ConfigManager {

        $this->config[$key] = $value;
        return $this;
    }

    public function removeConfig($key) : ConfigManager {

        if (isset($this->config[$key])) {
            unset($this->config[$key]);
        }

        return $this;
    }
    
}
