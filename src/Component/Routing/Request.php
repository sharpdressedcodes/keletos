<?php

namespace Keletos\Component\Routing;

use Keletos\Component\Component;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends Component {

    /**
     * @var RequestContext
     */
    protected $context = null;
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request = null;

    public function __construct(RequestContext $context = null, SymfonyRequest $symfonyRequest = null) {

        parent::__construct();

        $this->context = $context ?? new RequestContext();
        $this->request = $symfonyRequest ?? SymfonyRequest::createFromGlobals();
        $this->context->fromRequest($this->request);

    }

    public function getContext() : RequestContext {
        return $this->context;
    }

    public function getRequest() : SymfonyRequest {
        return $this->request;
    }

    public function getPathInfo() : string {
        return $this->context->getPathInfo();
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

    protected static function parseInput(array $options, string $method = 'GET', array &$errors = [], string $csrfName = self::DEFAULT_CSRF_NAME) : array {

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

}
