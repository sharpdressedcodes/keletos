<?php

namespace Keletos\Component\Application;

use Keletos\Component\Routing\Router;

class Web extends Application {
    
    public function run(array $params = []) {

        $result = false;

        if ($this->dispatchEvent('onBeforeRun', $params)) {

            if ($this->runTaskManager) {
                $this->taskManager->runTasks($params);
            }

            if ($this->runPluginManager) {
                $this->pluginManager->runPlugins($params);
            }

            $result = $this->router->handleRequest();

            $this->dispatchEvent('onAfterRun', $params);
        }

        return $result;
    }
}
