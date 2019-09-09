<?php

namespace Keletos\Utility;

class Url {

	public static function strip($url, $params = array()){

		!is_array($params) && ($params = array($params));

		for ($i = 0, $i_ = count($params); $i < $i_; $i++){

			$rx = "/(\\?|&)({$params[$i]}=)([^&#]*)((&).*?){0,}/i";

			$url = preg_replace_callback($rx, function($matches){
				return count($matches) > 4 ? $matches[4] : '';
			}, $url);

		}

		return $url;

	}

	public static function replace($url, $params = array(), $values = array()){

		!is_array($params) && ($params = array($params));
		!is_array($values) && ($values = array($values));

		for ($i = 0, $i_ = count($params); $i < $i_; $i++){

			$rx = "/(\\?|&)({$params[$i]}=)([^&#]*)/i";

			$url = preg_replace_callback($rx, function($matches) use ($i, $values){
				return $matches[1] . $matches[2] . $values[$i];
			}, $url);

		}

		return $url;
	}

}
