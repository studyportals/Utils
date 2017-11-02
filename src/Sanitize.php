<?php

/**
 * @file Utils\Sanitize.php
 *
 * @author Danko Adamczyk <danko@studyportals.com>
 * @copyright © 2016 StudyPortals BV, all rights reserved.
 * @version 1.3.0
 */

namespace StudyPortals\Utils;

use DateTime;
use DateTimeZone;

/**
 * Class Sanitize
 *
 * Our restful API communicates via JSON which are just strings. Quite often we
 * need to do some basic data sanitizing like trimming, typecasting and validating
 * the input against a set of options.
 *
 * For instance an empty text should become NULL but the text '0' should stay 0.
 * The checks are not that complicated but they were implemented in every setter
 * and quite often even not complete or consistent. This class takes away all
 * those problems.
 *
 * It only sanitizes data, this means it does not throw exceptions. If you want
 * to throw for instance a BadParameterException you have to handle that yourself.
 *
 * @package StudyPortals\Universe
 */
class Sanitize{

	/**
	 * @param string $value
	 *
	 * @return string|null
	 */
	public static function text($value){

		return static::_nullValue($value);
	}

	/**
	 * @param string $value
	 *
	 * '' => null
	 * '0' => 0
	 *
	 * @return int|string
	 */
	public static function integer($value){

		$value = static::_nullValue($value);

		if($value !== null){

			$value = (int) $value;
		}

		return $value;
	}

	/**
	 * @param string $value
	 *
	 * @return float|null
	 */
	public static function float($value){

		$value = static::_nullValue($value);

		if($value !== null){

			// Avoid decimal separator nonsense
			$value = str_replace(',', '.', $value);
			$value = (float) $value;
		}

		return $value;
	}

	/**
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function boolean($value){

		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @param string $value
	 * @param array $options
	 *
	 * @return array
	 */
	public static function set($value, $options){

		$values = static::_toArray($value);
		return static::_intersect($values, $options);
	}

	/**
	 * @param string $value
	 * @param array $options
	 *
	 * @return string|null
	 */
	public static function enum($value, $options){

		$value = static::_nullValue($value);

		if(!in_array($value, $options)){

			return null;
		}

		return $value;
	}

	/**
	 * @param string $date
	 *
	 * @return string 2016-09-20 (UTC+00:00)
	 */
	public static function date($date){

		$Date = static::_utcZeroDateTime($date);

		if($Date === null){

			return $date;
		}

		return $Date->format('Y-m-d');
	}

	/**
	 * @param string $date
	 *
	 * @return string 2016-09-20T00:00:00+00:00
	 */
	public static function date_iso($date){

		$Date = static::_utcZeroDateTime($date);
		return $Date->format('c');
	}

	/**
	 * @param $part
	 *
	 * @return array|string
	 */
	public static function _nested_urldecode($part){

		if(is_array($part)){

			return array_map('static::_nested_urldecode', $part);
		}

		return urldecode($part);
	}

	/**
	 * This will rebuild the url with all the query-parts url_encoded.
	 *
	 * @param {string} $url
	 *
	 * @return string|null
	 */
	public static function url($url){

		$url = static::_nullValue($url);
		if(!$url){

			return $url;
		}

		$query = parse_url($url, PHP_URL_QUERY);
		if(!$query){

			return $url;
		}

		parse_str($query, $query_parts);

		// First urldecode to be sure that we do not re-encode the query parts.
		$query_parts = array_map('static::_nested_urldecode', $query_parts);

		// http_build_query will take care of the urlencode.
		return strtok($url, '?') . '?' . http_build_query($query_parts);
	}

	/**
	 * Find en replaces all http urls which should be https.
	 *
	 * @param {string} $value
	 *
	 * @return string
	 */
	public static function replaceHttpsUrls($value){

		// CDN resources
		$value = str_ireplace('http://cdn.prtl.eu', '//cdn.prtl.eu', $value);
		$value = str_ireplace('http://cdn2.prtl.eu', '//cdn2.prtl.eu', $value);
		$value = str_ireplace('http://studyportals-cdn2.imgix.net', '//studyportals-cdn2.imgix.net', $value);

		// Portal urls
		$value = str_ireplace('http://www.admissiontestportal.com', 'https://www.admissiontestportal.com', $value);
		$value = str_ireplace('http://www.preparationcoursesportal.com', 'https://www.preparationcoursesportal.com', $value);

		return $value; //NOSONAR
	}

	/**
	 * @param string $value - comma separated values.
	 *
	 * @return array
	 */
	public static function _toArray($value){

		if(!is_array($value)){

			$value = explode(',', $value);
		}

		return $value;
	}

	/**
	 * @param string $date
	 *
	 * @return DateTime|null
	 */
	private static function _utcZeroDateTime($date){

		$date = static::_nullValue($date);

		if($date === null){

			return $date;
		}

		$UTC = new DateTimeZone('UTC');
		$Date = new DateTime($date, $UTC);
		$Date->setTimezone($UTC);

		return $Date;
	}

	/**
	 * @param array $values
	 * @param array $options
	 *
	 * @return array
	 * @private
	 */
	private static function _intersect($values, $options){

		$filtered = [];

		foreach($values as $option){

			$option = static::_nullValue($option);

			if(in_array($option, $options)){

				$filtered[] = $option;
			}
		}

		return $filtered;
	}

	/**
	 * Trim the text and return null when it is an empty string.
	 *
	 * @param string $value
	 *
	 * @return string
	 * @private
	 */
	private static function _nullValue($value){

		$value = trim($value);

		if($value === ""){

			$value = null;
		}

		return $value;
	}
}