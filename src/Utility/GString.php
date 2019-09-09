<?php

namespace Keletos\Utility;

/**
 * Class GString
 *
 * String manipulation.
 * Was named String until PHP7.
 *
 * @package Keletos\Utility
 */
class GString {

	const DEFAULT_CONVERSION_TYPE = 'UTF-8';
	const DEFAULT_TRUNCATE_MAX_LENGTH = 500;
	const DEFAULT_TRUNCATE_ELLIPSIS = '...';

	public static function get($search1, $search2, $str){

		$result = null;
		$pos = strpos($str, $search1);

		if ($pos !== false){
			$pos2 = strpos($str, $search2, $pos + strlen($search1) + 1);
			$pos2 !== false && ($result = substr($str, $pos + strlen($search1), $pos2 - $pos - strlen($search1)));
		}

		return $result;

	}

	public static function convert($str, $type = self::DEFAULT_CONVERSION_TYPE){
		$encoding = mb_detect_encoding($str, 'auto');
		return mb_convert_encoding($str, $type, $encoding);
	}

	public static function truncate($str, $maxLength = self::DEFAULT_TRUNCATE_MAX_LENGTH, $breakWords = true, $ellipsis = self::DEFAULT_TRUNCATE_ELLIPSIS){

		$result = null;

		if (strlen($str) > $maxLength){

			if ($breakWords){

				$count = 0;
				$words = explode(' ', $str);

				foreach ($words as $word){

					$len = strlen($word);

					if ($count + $len + 1 < $maxLength){
						$result .= "$word ";
						$count += $len + 1;
					} else {
						break;
					}

				}

				$result = rtrim($result);
				$result = rtrim($result, '.,');

			} else {
				$result = substr($str, 0, $maxLength);
			}

			is_string($result) && ($result .= $ellipsis);

		} else {
			$result = $str;
		}

		return $result;

	}

	public static function startsWith($str, $startStr){

		$result = false;

		if (is_array($startStr)) {
			foreach ($startStr as $testStr) {
				if (substr($str, 0, strlen($testStr)) === $testStr) {
					$result = true;
					break;
				}
			}
		} else {
			$result = substr($str, 0, strlen($startStr)) === $startStr;
		}

		return $result;

	}

	public static function endsWith($str, $endStr){

		$result = false;

		if (is_array($endStr)) {
			foreach ($endStr as $testStr) {
			    if (substr($str, -strlen($testStr)) === $endStr) {
                    $result = true;
                    break;
                }
			}
		} else {
			$result = substr($str, -strlen($endStr)) === $endStr;
		}

		return $result;

	}

	/**
	 * Converts an XML string to a PHP Array.
	 * WARNING: This won't work properly on deeply nested XML. Parse manually instead.
	 *
	 * @param string $xml The XML string to convert.
	 * @return array An array representing the XML data.
	 */
	public static function convertXmlToArray($xml) {

		$xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$json = json_encode($xml);
		$data = json_decode($json, true);

		return $data;

	}

}
