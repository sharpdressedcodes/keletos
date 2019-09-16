<?php

namespace Keletos\Controller;

use Keletos\Component\Application\Application;
use \Keletos\Component\TaskManager;
use \Keletos\Component\Routing\Router;
use \Keletos\Utility\MimeTypes;

abstract class Controller extends \Keletos\Component\Component {

    const DEFAULT_CSRF_NAME = 'token';

    protected $_config;
    protected $_cache;
    protected $_renderer;

    public function __construct(array $params = array()){

        parent::__construct();

        @session_start();

        // TODO: change this
        $this->_config = Application::instance()->getConfig();
        $this->setupCache();
        $this->_renderer = $params['renderer'];

        ini_set('date.timezone', $this->_config['timeZone']);
    }

    public function __destruct() {
        if ($this->_cache) {
            $this->_cache->close();
        }
    }

    public function getCache() {
        return $this->_cache;
    }

    public function getConfig(){
        return $this->_config;
    }

    public function run(array $params){

        if ($this->dispatchEvent('onBeforeRun', $params)) {
            $method = $params['method'];
            $this->$method($params);
            $this->dispatchEvent('onAfterRun', $params);
        }

    }

    public function render(array $params = []) {
        $this->_renderer->render($params);
    }

    public function catchAll(array $params) {
        Router::sendResponse('Error: Invalid route', true);
    }

    protected function setupCache() {

        $config = $this->_config['cache'];

        if (isset($config) && $config['enabled']) {

            // Only Redis is supported for now
            switch (strtolower($config['provider'])) {
                case 'redis':
                    $this->_cache = new \Keletos\Component\Cache\Redis\Client($config['host'], $config['port']);
                    break;
                default:
            }
        }

        if ($this->_cache) {
            $this->_cache->connect();
        }
    }

    public static function clearCookies(){

        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time()-1000);
                setcookie($name, '', time()-1000, '/');
            }
        }

    }
}
