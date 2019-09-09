<?php
//
//class Autoload {
//
//    const FRAMEWORK_ROOT = "SharpDressedCodes" . DIRECTORY_SEPARATOR . "Framework";
//    const APP_ROOT = "App" . DIRECTORY_SEPARATOR;
//    const DIR_ROOT = 'src';
//    const EXTENSIONS = ['php', 'inc'];
//
//    public static function run(string $className) : void {
//
//        $fileName = '';
//        $ds = DIRECTORY_SEPARATOR;
//        $className = ltrim($className, '\\');
//        $framework = self::FRAMEWORK_ROOT;
//        $app = self::APP_ROOT;
//
//        if ($lastNsPos = strrpos($className, '\\')) {
//            $namespace = substr($className, 0, $lastNsPos);
//            $className = substr($className, $lastNsPos + 1);
//            $fileName = str_replace('\\', $ds, $namespace) . $ds;
//        }
//
//        $fileName .= str_replace('_', $ds, $className);
//
//        // When running from index.php -> /var/www/html/src/public
//        // When running from tests -> /var/www/html
//        $cwd = preg_replace('/[\/\\\]public$/', '', getcwd());
//
//        if (substr($cwd, -strlen(self::DIR_ROOT)) !== self::DIR_ROOT) {
//            $cwd .= $ds . self::DIR_ROOT;
//        }
//
//        if (strpos($fileName, $framework) === 0) {
//            $fileName = 'framework' . substr($fileName, strlen($framework));
//        } elseif (strpos($fileName, $app) === 0) {
//            $fileName = "app{$ds}" . substr($fileName, strlen($app));
//        }
//
//        $fileName = "{$cwd}{$ds}$fileName";
//
//        foreach (self::EXTENSIONS as $extension) {
//
//            $f = "$fileName.$extension";
//
//            if (file_exists($f)) {
//                require_once $f;
//                break;
//            }
//        }
//
//    }
//}
//
//spl_autoload_register('Autoload::run');
