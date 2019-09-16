# Keletos

_Minimalist PHP Framework designed to get the job done with minimum fuss._

## Installation
```
composer require sharpdressedcodes/keletos
```

## Usage
At a minimum, you'll need to setup Routes inside `application/Routes/[filename].php`:
```
use Keletos\Component\Routing\Router;
use Application\Controller\Main as MainController;

Router::match(['GET', 'POST'], '/', MainController::class);

Router::get('/test/{id<\d+>?1}', function($id = -1) {
    echo 'no controller!!! ' . $id;
});

Router::get('/user/{name<\w+>}', function($name) {
    echo "name is $name";
}, [
    'requirements' => [
        'name' => '/^\w+$/',
    ]
]);

Router::any('*', [MainController::class, 'catchAll']);
```
You can use a Controller or an anonymous function as the action. If you just pass in a class without a method, it will default to an `index` method.

If you choose to use a Controller, then extend the `Keletos\Controller\Controller` class. Example controller:

```
class Main extends Controller {
    public function index() {
        $views[] = [
            'view' => 'google-search-api',
            'var' => 'content',
            'viewParams' => $viewParams,
        ];

        $this->render([
            //'layout' => $this->_layoutFile,
            'views' => $views,
            'title' => $pageTitle,
        ]);
    }
}
```

`$viewParams` are the variables passed to the view.

Then create an index page:
```
use Keletos\Component\Application\Application;
//use Keletos\Component\ConfigManager;
//use Keletos\Component\Rendering\Renderer;
//use Keletos\Component\Routing\Router;

(function() {

    $basePath = dirname(__DIR__);

    require_once $basePath . '/vendor/autoload.php';
    require_once dirname($basePath) . '/c3.php';

    //$configManager = new ConfigManager($basePath);
    //$config = $configManager->getConfig();

    //$renderer = new Renderer($config, ($config['debug'] ? 'Debug' : 'Main') . '.php');
    //$router = new Router(['web'], $renderer, $basePath);

    $application = Application::factory([
        'routes' => ['web'],
        'basePath' => $basePath,
        //'renderer' => $renderer,
        //'router' => $router,
        //'configManager' => $configManager,
    ]);

    $application->run();

})();
```

You can even override the default `Renderer` and `Router`.

Accessing the application variable from anywhere in the code:
```
$application = Application::instance();
```
