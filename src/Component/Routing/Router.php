<?php

namespace Keletos\Component\Routing;

use Keletos\Component\Component;
use Keletos\Component\Rendering\IRenderer;
use Keletos\Controller\Controller;
use Keletos\Utility\GString;
use Keletos\Utility\MimeTypes;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router extends Component {

    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
    const HTTP_VERBS = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH',
    ];

    /**
     * @var Request
     */
    protected $request = null;
    protected $basePath = null;
    protected $frameworkPath = null;
    /**
     * @var IRenderer
     */
    protected $renderer = null;
    /**
     * @var RouteCollection
     */
    protected static $collection = null;

    public function __construct(array $routes, IRenderer $renderer, string $basePath) {
        parent::__construct();

        set_error_handler([$this, 'onUnhandledError']);
        set_exception_handler([$this, 'onUnhandledException']);

        $this->request = new Request();
        $this->basePath = $basePath;
        $this->frameworkPath = dirname(dirname(__DIR__));
        $this->renderer = $renderer;

        // Load routes, this should set self::$collection if there are valid routes
        foreach ($routes as $route) {
            require_once "{$basePath}/application/Routes/{$route}.php";
        }
    }

    public static function getRoutes() : RouteCollection {
        return self::$collection;
    }

    public function getRequest() : Request {
        return $this->request;
    }

    public function handleRequest() : bool {

        $result = false;
        $col = self::$collection;

        foreach (self::$collection->getIterator() as $route) {

            if ($result) {
                break;
            }

            $path = trim($this->request->getPathInfo(), '/ '); // /test/9
            $routePath = trim($route->getPath(), '/ ');
            $requirements = $route->getRequirements(); // [ 'id' => \d+ ]
            $hasRequirements = !empty($requirements);
            $hasMetRequirements = [];
            $args = [];

            if ($routePath === $path) {
                $result = $this->loadRoute($route);
                break;
            }

            //if ($hasRequirements) {

            $currentPaths = explode('/', $path);
            $routePaths = explode('/', $routePath);
            $currentPathsLen = count($currentPaths);
            $routePathsLen = count($routePaths);

            //for ($i = 0; $i < $currentPathsLen; $i++) {
            for ($i = 0; $i < $routePathsLen; $i++) {

                if ($i > $currentPathsLen - 1) {

                }

                if ($i < $currentPathsLen && ($routePaths[$i] === '*' || $currentPaths[$i] === $routePaths[$i])) {

                    $hasMetRequirements[] = true;
                    $hasMetAllRequirements = array_product($hasMetRequirements);

                    if ($i < $routePathsLen - 1) {
                        continue;
                    }

                    $result = $this->loadRoute($route, $args);
                    break;

                } elseif ($i === 0 && $currentPaths[$i] !== $routePaths[$i]) {

                    break;

                } elseif (/*$i > 0 && */preg_match('/\{(.*?)\}/', $routePaths[$i], $matches)) {

                    $match = $matches[1];
                    $requirement = isset($requirements[$match]) ? $requirements[$match] : null;

                    $closure = $route->getDefault('_controller');
                    $reflection = new \ReflectionFunction($closure);
                    $arguments  = $reflection->getParameters();
                    $requiredParameterCount = $reflection->getNumberOfRequiredParameters();
                    $parameterIndex = -1;

                    for ($j = 0; $j < count($arguments); $j++) {
                        if ($arguments[$j]->getName() === $match) {
                            $parameterIndex = $j;
                            break;
                        }
                    }

                    $isOptional = $parameterIndex + 1 > $requiredParameterCount;

                    if (!$isOptional && $i > $currentPathsLen - 1) {
                        throw new \Exception("Required parameter '$match' is missing from URL");
                    }

                    $rx = $requirement;
                    if ($rx) {
                        if (GString::startsWith($rx, '/')) {
                            $rx = substr($rx, 1);
                        }
                        if (GString::endsWith($rx, '/')) {
                            $rx = substr($rx, 0, -1);
                        }
                    }
                    $matched = $requirement ? preg_match("/$rx/", $currentPaths[$i]) : 1;

                    //if ($isOptional || ($requirement && $matched)) {

                    $hasMetRequirements[] = $isOptional || $matched === 1;
                    $hasMetAllRequirements = array_product($hasMetRequirements);

                    if ($matched) {
                        $args[$match] = $currentPaths[$i];
                    } elseif (!$isOptional) {
                        throw new \Exception("Required parameter '$match' is missing or malformed.");
                    }
                    //$args[$match] = $currentPaths[$i];

                    if ($i < $routePathsLen - 1 /*|| !$hasMetAllRequirements*/) {
                        continue;
                    }

                    $result = $this->loadRoute($route, $args);
                    break;
                    //}
                }
            }
            //}
        }

        return $result;

    }

    protected function loadRoute(Route $route, array $args = []) : bool {

        $controller = $route->getDefault('_controller');
        $method = 'index'; // fallback
        $className = $controller;

        if (is_array($controller)) {
            $className = $controller[0];
            $method = $controller[1];
        }

        if ((!$controller instanceof \Closure) && (is_string($className) && !class_exists($className))) {
            return false;
        }

        $params = [
            'basePath' => $this->basePath,
            'frameworkPath' => $this->frameworkPath,
            'request' => $this->request,
            'method' => $method,
            'route' => $route,
            'router' => $this,
            'renderer' => $this->renderer,
        ];

        if ($controller instanceof \Closure) {

            if (!empty($args)) {
                call_user_func_array($controller, $args);
            } else {
                $controller();
            }

        } else {

            $reflected = new \ReflectionClass($className);

            if (!$reflected->hasMethod($method)) {
                return false;
            }

            $class = new $className($params);
            $class->run($params);

        }

        return true;

    }

    public function onUnhandledException($exception, array $params = array()){
        if ($exception instanceof \Exception) {
            self::sendResponse($exception->getMessage(), true);
        } else {
            $this->onUnhandledError($exception->getCode() === 0 ? 999 : $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
        }
    }

    public function onUnhandledError(int $errNo, string $errStr, string $errFile, int $errLine) : bool {

        if (!(error_reporting() & $errNo)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        switch ($errNo) {
            case E_USER_ERROR:

                $message = "Fatal Error occurred on line $errLine in file $errFile<br>\n[$errNo] $errStr<br>\n";
                //throw new \ErrorException($message,0, $errNo, $errFile, $errLine);
                self::sendResponse($message, true);
                break;

            case E_USER_WARNING:

                $message = "Warning occurred on line $errLine in file $errFile<br>\n[$errNo] $errStr<br>\n";

                //if ($this->_config['debug']) {
                //throw new \ErrorException($message,0, $errNo, $errFile, $errLine);
                self::sendResponse($message, true);
                //} else {
                //echo $message;
                //}
                break;

            case E_USER_NOTICE:

                $message = "Notice occurred on line $errLine in file $errFile<br>\n[$errNo] $errStr<br>\n";

                //if ($this->_config['debug']) {
                //throw new \ErrorException($message,0, $errNo, $errFile, $errLine);
                self::sendResponse($message, true);
                //} else {
                //echo $message;
                //}
                break;

            default:

                $message = "Unknown error occurred on line $errLine in file $errFile<br>\n[$errNo] $errStr<br>\n";

                //if ($this->_config['debug']) {
                //throw new \ErrorException($message,0, $errNo, $errFile, $errLine);
                self::sendResponse($message, true);
                //} else {
                //echo $message;
                //}
                break;
        }

        // Don't execute PHP internal error handler
        return true;
    }

    protected static function addRoute(Route $route, array $args = []) : void {

        if (isset($args['defaults'])) {
            foreach ($args['defaults'] as $key => $value) {
                $route->setDefault($key, $value);
            }
        }

        if (isset($args['requirements'])) {
            foreach ($args['requirements'] as $key => $value) {
                $route->setRequirement($key, $value);
            }
        }

        if (!self::$collection) {
            self::$collection = new RouteCollection();
        }

        $name = isset($args['name']) ? $args['name'] : $route->getPath();
        self::$collection->add($name, $route);

    }

    public static function any(string $path, $action, array $args = []) : Route {

        $route = new Route($path);

        $route->setMethods(self::HTTP_VERBS);
        $route->setDefault('_controller', $action);

        self::addRoute($route, $args);

        return $route;

    }

    public static function get(string $path, $action, array $args = []) : Route {

        $route = new Route($path);
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $action);

        self::addRoute($route, $args);

        return $route;

    }

    public static function post(string $path, $action, array $args = []) : Route {

        $route = new Route($path);
        $route->setMethods(['POST']);
        $route->setDefault('_controller', $action);

        self::addRoute($route, $args);

        return $route;

    }

    public static function match(array $methods, string $path, $action, array $args = []) : Route {

        $route = new Route($path);
        $route->setMethods($methods);
        $route->setDefault('_controller', $action);

        self::addRoute($route, $args);

        return $route;

    }

    public static function sendResponse($data, $isError = false){

        $response = $data;
        $accept =  isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
        $type = MimeTypes::TEXT_PLAIN;

        if (self::isJsonRequest() || strpos($accept, 'json') !== false) {

            $type = MimeTypes::APPLICATION_JSON;

            if ($isError && !isset($data['success'])) {
                $data['success'] = false;
            }

            $response = json_encode($data);

        } elseif (strpos($accept, MimeTypes::TEXT_HTML) !== false){

            $type = MimeTypes::TEXT_HTML;
        }

        header("Content-Type: $type; charset=utf-8");
        echo $response;

    }

    public static function isJsonRequest(){

        $result = false;
        $headers = [
            'HTTP_X_REQUESTED_WITH',
            'X_REQUESTED_WITH',
            'X-Requested-With',
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $result = strtolower($_SERVER[$header]) === 'xmlhttprequest';
                break;
            }
        }

        return $result;
    }

    /**
     * Important: You MUST call return after calling this method.
     * This ensures controllers will have their destructors called.
     *
     * @param $url
     * @param int $statusCode
     */
    public static function redirect(string $url, int $statusCode = 301) : void {
        header("Location: $url", true, $statusCode);
        echo('<h1>This page has moved.</h1><div>Click <a href="' . $url . '" title="' . $url . '">here</a> if you are not redirected</div>');
    }
}
