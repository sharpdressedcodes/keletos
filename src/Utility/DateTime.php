<?php

namespace Keletos\Utility;

class DateTime {

	public static $DAYS = array(
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday'
	);
	public static $EXTENDED_DAYS = array(
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday',
		'weekday',
		'weekend',
		'mon',
		'tue',
		'wed',
		'thu',
		'fri',
		'sat',
		'sun',
	);

	public static function secondsFromString($str){

		$arr = explode(' ', $str);
		$num = intval($arr[0]);
		$type = strtolower($arr[1]);
		$result = 0;

		if (substr($type, strlen($type) - 1, 1) === 's')
			$type = substr($type, 0, strlen($type) - 1);

		switch ($type){
			case 'second':
				$result = $num;
				break;
			case 'minute':
				$result = $num * 60;
				break;
			case 'hour':
				$result = ($num * 60) * 60;
				break;
			case 'day':
				$result = (($num * 60) * 60) * 24;
				break;
			case 'week':
				$result = ((($num * 60) * 60) * 24) * 7;
				break;
			case 'month':
				$result = (((($num * 60) * 60) * 24) * 7) * 4.5;
				break;
			case 'year':
				$result = ((($num * 60) * 60) * 24) * 365;
				break;
		}

		return $result;

	}

	public static function getCurrentDay($modifier = null){

		return date(is_null($modifier) ? 'l' : $modifier);

	}

	public static function getCurrentTime($format = null, $timezone = null){

		$date = new \DateTime();

		if (!is_null($timezone)){
			$date->setTimezone(new \DateTimeZone($timezone));
		}

		return $date->format(is_null($format) ? 'H:i' : $format);

	}

	public static function validateDayName($day, $useExtended){

		return in_array($day, $useExtended ? self::$EXTENDED_DAYS : self::$DAYS);

	}

	public static function formatSeconds($seconds, $longDescriptions = false){

		$minutes = (int)($seconds / 60);

		if ($seconds >= 60){
			$seconds -= (60 * $minutes);
		}

		$hours = (int)($minutes / 60);

		if ($minutes >= 60){
			$minutes -= (60 * $hours);
		}

		$days = (int)($hours / 24);

		if ($hours >= 24){
			$hours -= (24 * $days);
		}

		$weeks = (int)($days / 7);

		if ($days >= 7){
			$days -= (7 * $weeks);
		}

		$divisor = 30 / 7; // number of weeks in a month (4.285714285714286)
		$months = (int)($weeks / $divisor);

		if ($weeks >= $divisor){
			$weeks -= ($divisor * $months);
		}

		$years = (int)($months / 12);

		if ($months >= 12){
			$months -= (12 * $years);
		}

		$result = '';

		$years > 0 && ($result .= $years . ' y' . ($longDescriptions ? 'ear' : 'r') . ($years === 1 ? '' : 's') . ', ');
		$months > 0 && ($result .= $months . ' mon' . ($longDescriptions ? 'th' : '') . ($months === 1 ? '' : 's') . ', ');
		$weeks > 0 && ($result .= $weeks . ' w' . ($longDescriptions ? 'eek' : 'k') . ($weeks === 1 ? '' : 's') . ', ');
		$days > 0 && ($result .= $days . ' d' . ($longDescriptions ? 'ay' : 'y') . ($days === 1 ? '' : 's') . ', ');
		$hours > 0 && ($result .= $hours . ' h' . ($longDescriptions ? 'our' : 'r') . ($hours === 1 ? '' : 's') . ', ');
		$minutes > 0 && ($result .= $minutes . ' min' . ($longDescriptions ? 'ute' : '') . ($minutes === 1 ? '' : 's') . ', ');
		$seconds > 0 && ($result .= $seconds . ' sec' . ($longDescriptions ? 'ond' : '') . ($seconds === 1 ? '' : 's'));

		return rtrim($result, ', ');

	}

}
