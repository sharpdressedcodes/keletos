<?php

namespace Keletos\Component\Routing;

use Symfony\Component\Routing\Route;
use Keletos\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Keletos\Component\Component;
use Keletos\Utility\MimeTypes;

class Router extends Component {

    /**
     * @var RequestContext
     */
    protected $context = null;
    /**
     * @var Request
     */
    protected $request = null;
    protected $basePath = null;
    protected $frameworkPath = null;
    /**
     * @var RouteCollection
     */
    protected $collection = null;

    public function __construct(string $routesPath, string $basePath, string $frameworkPath) {
        parent::__construct();

        set_error_handler([$this, 'onUnhandledError']);
        set_exception_handler([$this, 'onUnhandledException']);

        $context = new RequestContext();
        $request = Request::createFromGlobals();
        $context->fromRequest($request);

        $this->context = $context;
        $this->request = $request;
        $this->basePath = $basePath;
        $this->frameworkPath = $frameworkPath;
        $this->collection = include $routesPath;
    }

    public function handleRequest() {

        $result = false;

        foreach ($this->collection->getIterator() as $route) {

            // TODO: add parameter matching
            if ($route->getPath() === $this->context->getPathInfo()) {
                $result = $this->loadRoute($route);
                break;
            }
        }

        $catchAll = $this->collection->get('catch-all');

        if (!$result && $catchAll) {
            $result = $this->loadRoute($catchAll);
        }

        return $result;

    }

    protected function loadRoute(Route $route) {

        $controller = $route->getDefault('_controller');
        $method = 'index'; // fallback
        $className = $controller;

        if (is_array($controller)) {
            $className = $controller[0];
            $method = $controller[1];
        }

        if (!class_exists($className)) {
            return false;
        }

        $params = [
            'basePath' => $this->basePath,
            'frameworkPath' => $this->frameworkPath,
            'request' => $this->request,
            'method' => $method,
            'route' => $route,
            'context' => $this->context,
            'router' => $this,
        ];

        /**
         * @var $class Controller
         */
        $class = new $className($params);

        if (!method_exists($class, $method) || !is_callable(array($class, $method))) {
            return false;
        }

        $class->run($params);

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
