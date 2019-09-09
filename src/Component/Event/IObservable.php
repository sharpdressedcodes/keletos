<?php

namespace Keletos\Component\Event;

interface IObservable {

	public function addObserver(\Keletos\Component\Event\IObserver $observer, $event);
	public function dispatchEvent($event, array &$params = array());

}
