<?php

namespace Keletos\Controller;

use \Keletos\Component\TaskManager;
use \Keletos\Component\Routing\Router;
use \Keletos\Utility\MimeTypes;

abstract class Controller extends \Keletos\Component\Component {

	const FRAMEWORK_NAME = 'Keletos Framework';
	const DEFAULT_CSRF_NAME = 'token';

	const PAGE_HEAD_BEGIN_1 = 0;
	const PAGE_HEAD_BEGIN_2 = 1;
	const PAGE_HEAD_BEGIN_3 = 2;
	const PAGE_HEAD_END_1 = 3;
	const PAGE_HEAD_END_2 = 4;
	const PAGE_BODY_BEGIN = 5;
	const PAGE_BODY_END_1 = 6;
	const PAGE_BODY_END_2 = 7;
	const PAGE_BODY_END_3 = 8;
	const PAGE_BODY_END_4 = 9;

	const NAVBAR_SIDE_LEFT = 0;
	const NAVBAR_SIDE_CENTER = 1;
	const NAVBAR_SIDE_RIGHT = 2;

	protected $_config;
	protected $_viewPath;
	protected $_layoutFile;
	protected $_styles;
	protected $_inlineStyles;
	protected $_scripts;
	protected $_inlineScripts;
	protected $_taskManager;
	protected $_runTaskManager;
	protected $_navbarWidgets;
	protected $_pluginManager;
	protected $_runPluginManager;
	protected $_cache;

	public function __construct(array $params = array()){

		parent::__construct();

		@session_start();

		$this->_config = array();
		$ds = DIRECTORY_SEPARATOR;

		$this->addConfig('basePath', $params['basePath']);
		$this->addConfig('appPath', $params['basePath'] . 'app' . $ds);
		$this->addConfig('dataPath', $params['basePath'] . 'app' . $ds . 'Data' . $ds);
		$this->addConfig('frameworkPath', $params['frameworkPath']);
		$this->addConfig('frameworkDataPath', $params['frameworkPath'] . 'Data' . $ds);
		//$this->addConfig('frameworkSkeletonPath', $params['frameworkPath'] . 'Component' . $ds . 'Generator' . $ds . 'Skeletons' . $ds);

		$widgets = \Keletos\Utility\FileSystem::getFiles($params['frameworkPath'] . 'Widget', true);
		$configPaths = array();

		foreach ($widgets as $file){

			$dir = 'Config' . DIRECTORY_SEPARATOR;
			$pos = strpos($file, $dir);

			if ($pos !== false){
				$path = substr($file, 0, $pos + strlen($dir) - strlen(DIRECTORY_SEPARATOR));
				array_search($path, $configPaths) === false && ($configPaths[] = $path);
			}

		}

		$this->loadConfig($this->_config['frameworkPath'] . 'Config');

		foreach ($configPaths as $configPath){
			$this->loadConfig($configPath);
		}

		$this->loadConfig($this->_config['appPath'] . 'Config');

        $this->setupCache();

		ini_set('date.timezone', $this->_config['timeZone']);

		$this->_viewPath = null;
		$this->_layoutFile = null;
		$this->_styles = array();
		$this->_inlineStyles = array();
		$this->_scripts = array();
		$this->_inlineScripts = array();
		$this->_taskManager = new TaskManager($this->_config['timeZone'], $this->_config['dataPath']);
		$this->_runTaskManager = array_key_exists('runTaskManager', $params) ? $params['runTaskManager'] : true;
		$this->_navbarWidgets = array();
		$this->_widgets = array();
		$this->_pluginManager = new \Keletos\Component\PluginManager($this);
		$this->_runPluginManager = array_key_exists('runPluginManager', $params) ? $params['runPluginManager'] : true;
	}

	public function __destruct() {
	    if ($this->_cache) {
	        $this->_cache->close();
        }
    }

    public function getCache() {
	    return $this->_cache;
    }

	public function getStyles(){
		return $this->_styles;
	}

	public function getScripts(){
		return $this->_scripts;
	}

	public function getInlineStyles(){
		return $this->_inlineStyles;
	}

	public function getInlineScripts(){
		return $this->_inlineScripts;
	}

	public function setViewPath($newValue){
		$this->_viewPath = $newValue;
		return $this;
	}

	public function getViewPath(){
		return $this->_viewPath;
	}

	public function getLayoutFile(){
		return $this->_layoutFile;
	}

	public function getConfig(){
		return $this->_config;
	}

	public function getTaskManager(){
		return $this->_taskManager;
	}

	public function getRunTaskManager(){
		return $this->_runTaskManager;
	}

	public function getPluginManager(){
		return $this->_pluginManager;
	}

	public function getRunPluginManager(){
		return $this->_runPluginManager;
	}


    public function run(array $params){

        if ($this->dispatchEvent('onBeforeRun', $params)) {

            if ($this->_runTaskManager) {
                $this->_taskManager->runTasks($params);
            }

            if ($this->_runPluginManager) {
                $this->_pluginManager->runPlugins($params);
            }

            $method = $params['method'];
            $this->$method($params);

            $this->dispatchEvent('onAfterRun', $params);
        }

    }

    public function render(array $params = array()){

        if (is_null($this->_viewPath)){
            $this->_viewPath = $this->_config['appPath'] . 'View' . DIRECTORY_SEPARATOR;
        }

        if (!array_key_exists('navbarCompact', $params)) {
            $params['navbarCompact'] = false;
        }

        $title = self::FRAMEWORK_NAME;
        if (array_key_exists('title', $params)) {
            $title = $params['title'];
            unset($params['title']);
        }

        $layout = array_key_exists('layout', $params) ? $params['layout'] : null;
        $views = array_key_exists('views', $params) ? $params['views'] : null;
        $showControls = true;
        $eventParams = array(
            'layout' => $layout,
            'views' => $views,
            'params' => $params,
            'title' => $title,
            'showControls' => $showControls,
        );

        if ($this->dispatchEvent('onBeforeRender', $eventParams)){

            $navbar = '';
            $controls = '';

            extract($eventParams);

            if (is_null($layout)){
                throw new \Exception('No layout specified');
            } elseif (!is_array($views) || (is_array($views) && count($views) === 0)){
                throw new \Exception('No view specified');
            }

            $rendered = array();

            foreach ($views as $view){
                !array_key_exists('viewParams', $view) && ($view['viewParams'] = array());
                $rendered[$view['var']] = $this->renderPartial($this->_getView($view['view']), $view['viewParams']);
            }

            ob_start();
            extract($rendered);

            empty($this->_navbarWidgets) && ($navbar = '');

            if ($this->dispatchEvent('onBeforeRenderLayout', $eventParams)) {
                extract($eventParams);
                !$showControls && ($controls = '');
                include $this->_getView($layout);
                $this->dispatchEvent('onAfterRenderLayout', $eventParams);
            }

            $output = ob_get_contents();
            ob_end_clean();

            $output = self::normaliseLineBreaks($this->_reorderResources($output));

            echo $output;

            $this->dispatchEvent('onAfterRender', $eventParams);
        }

    }

    public function renderPartial($view, array $viewParams = array()){

        $output = null;
        $params = array('view' => $view, 'params' => $viewParams);

        if ($this->dispatchEvent('onBeforeRenderPartial', $params)) {
            ob_start();
            extract($viewParams);
            include $view;
            $output = ob_get_contents();
            ob_end_clean();

            $params['output'] = $output;
            $this->dispatchEvent('onAfterRenderPartial', $params);
            $output = $params['output'];
        }

        return $output;
    }

    public function renderWidget(\Keletos\Widget\Widget $widget){

        $params = array('widget' => $widget);

        if ($this->dispatchEvent('onBeforeRenderWidget', $params)){
            $widget->render();
            $this->dispatchEvent('onAfterRenderWidget', $params);
        }

    }

    public function catchAll(array $params) {
        Router::sendResponse('Error: Invalid route', true);
    }

	public function clearNavbarWidgets($side = null){

		if (is_null($side)){
			$this->_navbarWidgets = array();
		} else {
			$this->_navbarWidgets[$side] = '';
		}

	}

	public function addNavbarWidget($widget, $side = self::NAVBAR_SIDE_CENTER){
		$this->_navbarWidgets[$side] = $widget;
	}

	public function getNavbarWidgets($side = null){
		return is_null($side) ? $this->_navbarWidgets : $this->_navbarWidgets[$side];
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

    public static function generateCsrfToken(int $length = 64, bool $setSession = true) : string {

        $csrf = \Keletos\Utility\Uid::generate($length);
        $setSession && ($_SESSION['csrf'] = $csrf);

        return $csrf;
    }

	public static function getPostVars(array $keys, $sanitise = false){
		return self::getVars($_POST, $keys, $sanitise);
	}

	public static function getGetVars(array $keys, $sanitise = false){
		return self::getVars($_GET, $keys, $sanitise);
	}

	public static function getServerVars(array $keys, $sanitise = false){
		return self::getVars($_SERVER, $keys, $sanitise);
	}

	public static function getCookieVars(array $keys, $sanitise = false){
		return self::getVars($_COOKIE, $keys, $sanitise);
	}

	public static function getVars(array $subject, array $keys, $sanitise = false){

		$result = array();

		foreach ($keys as $key){
			$result[$key] = self::getVar($subject, $key, $sanitise);
		}

		return $result;

	}

	public static function getPostVar($key, $sanitise = false){
		return self::getVar($_POST, $key, $sanitise);
	}

	public static function getGetVar($key, $sanitise = false){
		return self::getVar($_GET, $key, $sanitise);
	}

	public static function getServerVar($key, $sanitise = false){
		return self::getVar($_SERVER, $key, $sanitise);
	}

	public static function getCookieVar($key, $sanitise = false){
		return self::getVar($_COOKIE, $key, $sanitise);
	}

	public static function getVar(array $subject, $key, $sanitise = false){

	    $var = null;

	    if (isset($subject[$key])) {
	        $var = $sanitise ? self::sanitiseString($subject[$key]) : $subject[$key];
        }

	    return $var;
	}

	public static function sanitiseString(string $str, int $type = FILTER_SANITIZE_STRING) : string {
		return filter_var($str, $type);
	}

    private static function parseInput(array $options, string $method = 'GET', array &$errors = [], string $csrfName = self::DEFAULT_CSRF_NAME) : array {

        $result = [];
        $default = '';
        $defaultMessage = 'Invalid value.';
        $method = strtolower($method);
        $vars = $method === 'get' ? $_GET : $_POST;

//        if ($method === 'post') {
//            //$options[$csrfName] = null;
//        }

        foreach ($options as $key => $value) {
            if (!is_array($options[$key])) {
                $options[$key] = [];
            }
        }

        foreach ($options as $key => $value) {
            $result[$key] = isset($value['default']) ? $value['default'] : $default;
        }

        if (is_array($vars) && !empty($vars)) {
            foreach ($options as $key => $value) {

                if (isset($vars[$key])) {

                    if (isset($value['sanitize'])) {

                        if ($value['sanitize'] !== true) {
                            $result[$key] = self::sanitiseString($vars[$key], $value['sanitize']);
                        } else {
                            $result[$key] = self::sanitiseString($vars[$key]);
                        }

                    } else {
                        $result[$key] = $vars[$key];
                    }

                    if (isset($value['csrf'])
                        && (is_null($vars[$csrfName])
                            || !is_string($vars[$csrfName])
                            || !isset($_SESSION['csrf'])
                            || $vars[$csrfName] !== $_SESSION['csrf'])) {
                        $errors[] = [$key => isset($value['message']) ? $value['message'] : 'Error: Invalid token.'];
                    }

                    if (isset($value['stripSlashes'])) {
                        $result[$key] = stripslashes($result[$key]);
                    }

                    if (isset($value['choices']) && !in_array($result[$key], $value['choices'], true)) {
                        $errors[] = [$key => isset($value['message']) ? $value['message'] : $defaultMessage];
                        $result[$key] = isset($value['default']) ? $value['default'] : $default;
                    }

                    if (isset($value['rx']) && !preg_match($value['rx'], $result[$key], $matches)) {
                        $errors[] = [$key => isset($value['message']) ? $value['message'] : $defaultMessage];
                        $result[$key] = isset($value['default']) ? $value['default'] : $default;
                    }

                    if (isset($value['numeric']) && !is_numeric($result[$key])) {
                        $errors[] = [$key => isset($value['message']) ? $value['message'] : $defaultMessage];
                        $result[$key] = isset($value['default']) ? $value['default'] : $default;
                    }
                }
            }
        }

        return $result;

    }

    public static function parseGetInput(array $options, array &$errors = [], string $csrfName = self::DEFAULT_CSRF_NAME) : array {
        return self::parseInput($options, 'GET', $errors, $csrfName);
    }

    public static function parsePostInput(array $options, array &$errors = [], string $csrfName = self::DEFAULT_CSRF_NAME) : array {
        return self::parseInput($options, 'POST', $errors, $csrfName);
    }

	public function loadConfig(string $path = null) : bool {

		$path = is_null($path) ? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' : $path;

		if (!file_exists($path))
			return false;

		$directory = new \RecursiveDirectoryIterator($path);
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex = new \RegexIterator($iterator, '/^.+(?<!\.default)\.php$/i', \RecursiveRegexIterator::GET_MATCH);

		$main = $path . DIRECTORY_SEPARATOR . 'Main.php';
		if (file_exists($main))
			$this->_config = array_merge($this->_config, include $main);
			//$this->_config = $this->_config + include $main;

		// Overwrite global config
		foreach ($regex as $file){
			if ($file[0] !== $main){
				try {
					$this->_config = array_merge($this->_config, include is_array($file) ? $file[0] : $file);
					//$this->_config = $this->_config + include is_array($file) ? $file[0] : $file;
				} catch (\Exception $e){}
			}
		}

		return true;

	}

	public function addConfig($key, $value){

		$this->_config[$key] = $value;

		return $this;

	}

	public function removeConfig($key){

		if (array_key_exists($key, $this->_config))
			unset($this->_config[$key]);

		return $this;

	}

	public function clearCookies(){

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

	private function _getResourcesForLocation($location, $matches){

		$results = array();

		for ($i = 0, $i_ = count($matches[0]); $i < $i_; $i++)
			if ((int)$location === (int)$matches[3][$i])
				$results[] = $matches[0][$i];

		return $results;

	}

	public static function convertLocationToString($location){

		$result = 'PAGE_HEAD_BEGIN_1';

		switch ($location){
			case self::PAGE_HEAD_BEGIN_1:
				$result = 'PAGE_HEAD_BEGIN_1';
				break;
			case self::PAGE_HEAD_BEGIN_2:
				$result = 'PAGE_HEAD_BEGIN_2';
				break;
			case self::PAGE_HEAD_BEGIN_3:
				$result = 'PAGE_HEAD_BEGIN_3';
				break;
			case self::PAGE_HEAD_END_1:
				$result = 'PAGE_HEAD_END_1';
				break;
			case self::PAGE_HEAD_END_2:
				$result = 'PAGE_HEAD_END_2';
				break;
			case self::PAGE_BODY_BEGIN:
				$result = 'PAGE_BODY_BEGIN';
				break;
			case self::PAGE_BODY_END_1:
				$result = 'PAGE_BODY_END_1';
				break;
			case self::PAGE_BODY_END_2:
				$result = 'PAGE_BODY_END_2';
				break;
			case self::PAGE_BODY_END_3:
				$result = 'PAGE_BODY_END_3';
				break;
			case self::PAGE_BODY_END_4:
				$result = 'PAGE_BODY_END_4';
				break;

		}

		return $result;

	}

	public static function convertStringToLocation($location){

		$result = self::PAGE_HEAD_BEGIN_1;

		switch ($location){
			case 'PAGE_HEAD_BEGIN_1':
				$result = self::PAGE_HEAD_BEGIN_1;
				break;
			case 'PAGE_HEAD_BEGIN_2':
				$result = self::PAGE_HEAD_BEGIN_2;
				break;
			case 'PAGE_HEAD_BEGIN_3':
				$result = self::PAGE_HEAD_BEGIN_3;
				break;
			case 'PAGE_HEAD_END_1':
				$result = self::PAGE_HEAD_END_1;
				break;
			case 'PAGE_HEAD_END_2':
				$result = self::PAGE_HEAD_END_2;
				break;
			case 'PAGE_BODY_BEGIN':
				$result = self::PAGE_BODY_BEGIN;
				break;
			case 'PAGE_BODY_END_1':
				$result = self::PAGE_BODY_END_1;
				break;
			case 'PAGE_BODY_END_2':
				$result = self::PAGE_BODY_END_2;
				break;
			case 'PAGE_BODY_END_3':
				$result = self::PAGE_BODY_END_3;
				break;
			case 'PAGE_BODY_END_4':
				$result = self::PAGE_BODY_END_4;
				break;

		}

		return $result;

	}

	public static function normaliseLineBreaks($data){

		$data = preg_replace("/(\r\n|\r|\n){2,}/", "\n", $data);
		$data = preg_replace("/(\r\n[ |\t]{1,}\r\n|\r[ |\t]{1,}\r|\n[ |\t]{1,}\n)/", "\n", $data);

		return $data;

	}

	private function _moveResources($data, $resources, $location, $stripLocation = true){

		for ($i = 0, $i_ = count($resources); $i < $i_; $i++){
			$data = str_replace($resources[$i], '', $data);
			$resources[$i] = preg_replace('/ data\-location\="\d+"/', '', $resources[$i]);

			if (preg_match('/ src\="((.*?)\.js)"/', $resources[$i], $matches))
				$resources[$i] = str_replace($matches[0], ' src="' . $this->_chooseFileName($matches[1]) . '"', $resources[$i]);

			if (preg_match('/ href\="((.*?)\.css)"/', $resources[$i], $matches))
				$resources[$i] = str_replace($matches[0], ' href="' . $this->_chooseFileName($matches[1]) . '"', $resources[$i]);

		}

		return str_replace($location, ($stripLocation ? '' : $location) . implode("\n", $resources), $data);

	}

	private function _reorderResources($data){

		$result = $this->_removeDuplicateResources($data);
		$scriptRegex = '/<script[^>]((.*?)data\-location\="(.*?)".*?)?>([\s\S]*?)<\/script>/';
		$styleRegex = '/<style[^>]((.*?)data\-location\="(.*?)".*?)?>([\s\S]*?)<\/style>/';
		$linkRegex = '/<link[^>]((.*?)data\-location\="(.*?)".*?)?(\s?)(\/?)>/';
		$locationRegex = '/<!\-\- (PAGE_.*?) \- DO NOT DELETE THIS COMMENT! \-\->/';

		if (preg_match_all($locationRegex, $result, $locations)){

			$hasScripts = preg_match_all($scriptRegex, $result, $scriptMatches);
			$hasStyles = preg_match_all($styleRegex, $result, $styleMatches);
			$hasLinks = preg_match_all($linkRegex, $result, $linkMatches);

			for ($i = 0, $i_ = count($locations[0]); $i < $i_; $i++){
				if (!$hasScripts && !$hasStyles && !$hasLinks){
					$result = str_replace($locations[0][$i], '', $result);
				} else {
					$styles = $this->_getResourcesForLocation(self::convertStringToLocation($locations[1][$i]), $styleMatches);
					$scripts = $this->_getResourcesForLocation(self::convertStringToLocation($locations[1][$i]), $scriptMatches);
					$links = $this->_getResourcesForLocation(self::convertStringToLocation($locations[1][$i]), $linkMatches);
					$result = $this->_moveResources($result, $styles, $locations[0][$i], false);
					$result = $this->_moveResources($result, $scripts, $locations[0][$i], false);
					$result = $this->_moveResources($result, $links, $locations[0][$i]);
				}
			}

		}

		return $result;

	}

	private function _removeDuplicateResources($resources){

		if (is_array($resources)){
			return array_unique($resources, SORT_REGULAR);
		} else {

			$result = $resources;
			$scriptRegex = '/<script([^>].*?)?>([\s\S]*?)<\/script>/';
			$styleRegex = '/<style([^>].*?)?>([\s\S]*?)<\/style>/';
			$linkRegex = '/<link[^>]((.*?)data\-location\="(.*?)".*?)?(\s?)(\/?)>/';
			$hasScripts = preg_match_all($scriptRegex, $result, $scriptMatches);
			$hasStyles = preg_match_all($styleRegex, $result, $styleMatches);
			$hasLinks = preg_match_all($linkRegex, $result, $linkMatches);

			if ($hasScripts)
				$result = $this->_removeDuplicateResourceFromString($result, $scriptMatches);

			if ($hasStyles)
				$result = $this->_removeDuplicateResourceFromString($result, $styleMatches);

			if ($hasLinks)
				$result = $this->_removeDuplicateResourceFromString($result, $linkMatches);

			return $result;

		}
	}

	private function _removeDuplicateResourceFromString($data, $matches){

		$s = '---%%%%%%REPLACEMENT%%%%%%---';
		$temp = array();
		$result = $data;

		for ($i = 0, $i_= count($matches[0]); $i < $i_; $i++){

			if (isset($temp[$matches[0][$i]])) {
				$temp[$matches[0][$i]]++;
			} else {
				$temp[$matches[0][$i]] = 1;
			}

			for ($j = $i + 1, $j_= count($matches[0]); $j < $j_; $j++) {
				if ($matches[0][$i] === $matches[0][$j]) {
					$temp[$matches[0][$i]]++;
				}
			}
		}

		foreach ($temp as $key => $value){
			if ($value > 1){
				$result = preg_replace('~' . preg_quote($key) . '~', $s, $result, 1);
				$result = str_replace($key, '', $result);
				$result = str_replace($s, $key, $result);
			}
		}

		return $result;

	}

	private function _getView($view){

		$ext = '.php';
		$v = $view;

		if (substr($v, strlen($v) - strlen($ext)) !== $ext)
			$v .= $ext;

		if (strpos($v, DIRECTORY_SEPARATOR) === false || !file_exists($v)){
			$f = $this->_viewPath . $v;
			if (!file_exists($f))
				$f = $this->_viewPath . 'Layout' . DIRECTORY_SEPARATOR . $v;
			if (!file_exists($f))
				throw new \Exception("View {$view} $f not found");
			$v = $f;
		}

		return $v;

	}

	public function addInlineXml($code, $elementId, $location = self::PAGE_HEAD_END_1, $options = array()){

		$script = array(
			'code' => preg_replace('/(\r|\n|\t)/', '', $code),
			'location' => $location,
			'type' => MimeTypes::APPLICATION_XML,
			'options' => array_merge($options, array(
				'id' => $elementId
			)),
		);

		$this->_inlineScripts[] = $script;

	}

	public function addInlineJson($code, $elementId, $location = self::PAGE_HEAD_END_1, $options = array()){

		$script = array(
			'code' => preg_replace('/(\r|\n|\t)/', '', $code),
			'location' => $location,
			'type' => MimeTypes::APPLICATION_JSON,
			'options' => array_merge($options, array(
				'id' => $elementId
			)),
		);

		$this->_inlineScripts[] = $script;

	}

	public function addInlineScript($code, $location = self::PAGE_BODY_END_4, $type = MimeTypes::TEXT_JAVASCRIPT, $options = array()){

		$script = array(
			'code' => $code,
			'location' => $location,
			'type' => $type,
			'options' => $options,
		);

		$this->_inlineScripts[] = $script;

	}

	public function addInlineStyle($code, $location = self::PAGE_BODY_END_4, $type = MimeTypes::TEXT_CSS, $options = array()){

		$style = array(
			'code' => $code,
			'location' => $location,
			'type' => $type,
			'options' => $options,
		);

		$this->_inlineStyles[] = $style;

	}

	public function addScript($file, $location = self::PAGE_BODY_END_4, $type = MimeTypes::TEXT_JAVASCRIPT, $options = array()){

		$script = array(
			'file' => $file,
			'location' => $location,
			'type' => $type,
			'options' => $options,
		);

		$this->_scripts[] = $script;

	}

	public function addStyle($file, $location = self::PAGE_BODY_END_4, $type = MimeTypes::TEXT_CSS, $media = 'all', $rel = 'stylesheet', $options = array()){

		$script = array(
			'file' => $file,
			'location' => $location,
			'type' => $type,
			'media' => $media,
			'rel' => $rel,
			'options' => $options,
		);

		$this->_styles[] = $script;

	}

	private function _chooseFileName($fileName){

		$result = $fileName;
		$type = substr($fileName, strlen($fileName) - 3);

		if (substr($type, 0, 1) === '.')
			$type = substr($type, 1);

		if (!$this->_config['debug']){

			$s = ".$type";
			$m = ".min$s";

			if (substr($fileName, strlen($fileName) - strlen($m), strlen($m)) !== $m)
				$result = substr($fileName, 0, strlen($fileName) - strlen($s)) . $m;
		}

		return $result;

	}

	public function getScriptsForLocation($location){

		$result = array();

		foreach ($this->_scripts as $script){
			if ($script['location'] === $location){
				//$output = '<script type="' . $script['type'] . '" src="' . $script['file'] . '"';
				$output = '<script src="' . $this->_chooseFileName($script['file']) . '" type="' . $script['type'] . '"';
				foreach ($script['options'] as $key => $value)
					$output .= " $key=\"$value\"";
				$output .= "></script>\n";
				$result[] = $output;
			}
		}

		foreach ($this->_inlineScripts as $script){
			if ($script['location'] === $location){
				$output = '<script type="' . $script['type'] . '"';
				foreach ($script['options'] as $key => $value)
					$output .= " $key=\"$value\"";
				$output .= ">{$script['code']}</script>\n";
				$result[] = $output;
			}
		}

		return implode('', $result);

	}

	public function getStylesForLocation($location){

		$result = array();

		foreach ($this->_styles as $style){
			if ($style['location'] === $location){
				$output = '<link rel="' . $style['rel'] . '" type="' . $style['type'] . '" href="' . $this->_chooseFileName($style['file']) . '" media="' . $style['media'] . '"';
				foreach ($style['options'] as $key => $value)
					$output .= " $key=\"$value\"";
				$output .= ">\n";
				$result[] = $output;
			}
		}

		foreach ($this->_inlineStyles as $style){
			if ($style['location'] === $location){
				$output = '<style type="' . $style['type'] . '"';
				foreach ($style['options'] as $key => $value)
					$output .= " $key=\"$value\"";
				$output .= ">{$style['code']}</style>\n";
				$result[] = $output;
			}
		}

		return implode('', $result);

	}

	public function getResourcesForLocation($location){

		$resources = array(
			$this->getStylesForLocation($location),
			$this->getScriptsForLocation($location),
			"<!-- " . self::convertLocationToString($location) . " - DO NOT DELETE THIS COMMENT! -->",
		);

		return implode("\n", $resources);

	}
}
