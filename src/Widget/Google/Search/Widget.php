<?php

namespace Keletos\Widget\Google\Search;

class Widget extends \Keletos\Widget\Widget {

    // protected $_client;

    public function __construct(array $params = array()){

        $params['dataPathName'] = 'google-search-api';
        $params['path'] = $this->_config['dataPath'] . $params['dataPathName'] . DIRECTORY_SEPARATOR;
//
//        $task->addObserver($this, array(
//            'onAfterRun',
//            'onNotRun'
//        ));

//        parent::__construct($params, array($task), __DIR__);
        parent::__construct($params, [], __DIR__);

    }

//    public function onEvent(\Keletos\Component\Event\IObservable $source, $event, array &$params = array()){
//
//        $controller = $this->_params['controller'];
//        $config = $controller->getConfig();
//
//        $controller->addInlineJsonWithReceiver(
//            'liveTraffic',
//            file_get_contents($this->_params['path'] . $config['liveTraffic']['fileName']),
//            $config['liveTraffic']['dataLayerId']
//        );
//
//    }

}
