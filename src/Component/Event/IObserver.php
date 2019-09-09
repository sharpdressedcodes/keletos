<?php

namespace Keletos\Component\Event;

interface IObserver {

	public function onEvent(\Keletos\Component\Event\IObservable $source, $event, array &$params = array());

}
