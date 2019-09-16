<?php

namespace Keletos\Component\Application;

use Keletos\Component\Component;
use Keletos\Component\ConfigManager;
use Keletos\Component\Rendering\Renderer;
use Keletos\Component\Routing\Router;
use Keletos\Component\TaskManager;
use Keletos\Component\PluginManager;

abstract class Application extends Component {

    const FRAMEWORK_NAME = 'Keletos Framework';

    protected $router;
    protected $config;
    protected $renderer;
    protected $taskManager;
    protected $pluginManager;
    protected $runTaskManager;
    protected $runPluginManager;
    protected $configManager;

    protected static $instance;

    protected function __construct(array $params = []) {

        parent::__construct();

        $routes = $params['routes'];
        $basePath = $params['basePath'];

        $this->runTaskManager = isset($params['runTaskManager']) ? $params['runTaskManager'] : true;
        $this->runPluginManager = isset($params['runPluginManager']) ? $params['runPluginManager'] : true;
        $this->configManager = isset($params['configManager']) ? $params['configManager'] : new ConfigManager($basePath);

        $this->config = $this->getConfig();

        $this->taskManager = new TaskManager($this->getConfig()['timeZone'], $this->getConfig()['dataPath'] . '/');
        $this->pluginManager = new PluginManager($this->getConfig());
        $this->renderer = isset($params['renderer']) ? $params['renderer'] : new Renderer($this->getConfig(), ($this->getConfig()['debug'] ? 'Debug' : 'Main') . '.php');
        $this->router = isset($params['router']) ? $params['router'] : new Router($routes, $this->renderer, $basePath);

    }

    public function getRouter() : Router {
        return $this->router;
    }

    public function getRenderer() : Renderer {
        return $this->renderer;
    }

    public function getConfig() : array {
        //return $this->config;
        return $this->configManager->getConfig();
    }

    public function getTaskManager() : TaskManager {
        return $this->taskManager;
    }

    public function getRunTaskManager() : bool {
        return $this->runTaskManager;
    }

    public function getPluginManager() : PluginManager {
        return $this->pluginManager;
    }

    public function getRunPluginManager() : bool {
        return $this->runPluginManager;
    }

    public function getConfigManager() : ConfigManager {
        return $this->configManager;
    }

    public static function isCli() : bool {
        return http_response_code() === false;
    }

    public static function factory(array $params = []) : Application {
        self::$instance = self::isCli() ? new Console($params) : new Web($params);
        return self::$instance;
    }

    public static function instance() : Application {
        return self::$instance;
    }

    public abstract function run(array $params = []);
}
