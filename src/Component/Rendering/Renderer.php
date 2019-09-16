<?php

namespace Keletos\Component\Rendering;

use \Keletos\Component\Component;
use \Keletos\Component\Application\Application;
use \Keletos\Utility\GString;

class Renderer extends Component implements IRenderer {

    protected $layoutPath;
    protected $viewPath;
    protected $config;

    public function __construct(array $config, string $layoutPath = null, string $viewPath = null) {
        parent::__construct();

        $this->config = $config;
        $this->layoutPath = $layoutPath;
        $this->viewPath = $viewPath;
    }

    public function getConfig() : array {
        return $this->config;
    }

    public function render(array $params = []){

        if (is_null($this->viewPath)){
            $this->viewPath = $this->config['appPath'] . '/View';
        }

        $title = Application::instance()::FRAMEWORK_NAME;

        if (isset($params['title'])) {
            $title = $params['title'];
            unset($params['title']);
        }

        $layout = isset($params['layout']) ? $params['layout'] : $this->layoutPath;
        $views = isset($params['views']) ? $params['views'] : null;
        $showControls = true;
        $eventParams = array(
            'layout' => $layout,
            'views' => $views,
            'params' => $params,
            'title' => $title,
        );

        if ($this->dispatchEvent('onBeforeRender', $eventParams)){

            extract($eventParams);

            if (is_null($layout)){
                throw new \Exception('No layout specified');
            } elseif (!is_array($views) || (is_array($views) && empty($views))){
                throw new \Exception('No view specified');
            }

            $rendered = array();

            foreach ($views as $view){
                !isset($view['viewParams']) && ($view['viewParams'] = []);
                $rendered[$view['var']] = $this->renderPartial($this->getView($view['view']), $view['viewParams']);
            }

            ob_start();
            extract($rendered);

            if ($this->dispatchEvent('onBeforeRenderLayout', $eventParams)) {
                extract($eventParams);
                include $this->getView($layout);
                $this->dispatchEvent('onAfterRenderLayout', $eventParams);
            }

            $output = ob_get_contents();
            ob_end_clean();

            echo $output;

            $this->dispatchEvent('onAfterRender', $eventParams);
        }

    }

    public function renderPartial($view, array $viewParams = []) : string {

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

    protected function getView($view){

        $ext = '.php';
        $v = $view;

        if (!GString::endsWith($v, $ext)) {
            $v .= $ext;
        }

        if (strpos($v, DIRECTORY_SEPARATOR) === false || !file_exists($v)){
            $f = $this->viewPath . "/$v";
            if (!file_exists($f))
                $f = $this->viewPath . '/Layout' . DIRECTORY_SEPARATOR . $v;
            if (!file_exists($f))
                throw new \Exception("View {$view} $f not found");
            $v = $f;
        }

        return $v;

    }
}
