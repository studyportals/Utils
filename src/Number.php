<?php

/**
 * @file Utils\Number.php
 * Number Utility Methods
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Rob Janssen <rob@studyportals.eu>
 * @copyright © 2006-2009 Thijs Putman, all rights reserved.
 * @copyright © 2010-2012 StudyPortals B.V., all rights reserved.
 * @version 1.0.0
 */

namespace StudyPortals\Utils;

use StudyPortals\Exception\ExceptionHandler;
/**
 * Number utility methods.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class Number{

	/**
	 * Get the number of bytes in a pretty-formated binary size string.
	 *
	 * <p>Turns values like "1M" or "1.5K" into the actual number of bytes
	 * these values represent.</p>
	 *
	 * @param string $value
	 * @return float
	 */

	public static function getBytes($value){

		$value = trim($value);
		$unit = $value{strlen($value)-1};

		$value = floatval($value);

		switch(strtolower($unit)){

			case 'g':

				$multiplier = 1073741824;

			break;

			case 'm':

				$multiplier = 1048576;

			break;

			case 'k':

				$multiplier = 1024;

			break;

			default:

				ExceptionHandler::notice('Number::getBytes() expects unit to be either "g", "m" or "k"');
				$multiplier = 1;
		}

		return $multiplier * $value;
	}

	/**
	 * Locale aware number-format.
	 *
	 * <p>Works similar to PHP's number_format(), only this version is locale
	 * aware and will automatically use the correct decimal and thousand
	 * seperators.</p>
	 *
	 * <p>The parameter {@link $invalid} can be used to supply a value to
	 * return in case a non-numeric value is passed in.</p>
	 *
	 * @param float $number
	 * @param integer $decimals
	 * @param string $invalid
	 * @return string
	 */

	public static function formatNumber($number, $decimals = 0, $invalid = '-'){

		if(!is_numeric($number)) return $invalid;

		$locale = localeconv();

		return number_format($number, $decimals, $locale['decimal_point'], $locale['thousands_sep']);
	}

	/**
	 * Format timestamp as a human-readable string.
	 *
	 * <p>This method is a wrapper around strftime() and as such accepts all
	 * {@link $format} strings strftime() accepts. On non-Win32 systems this
	 * method directly calls strftime(). On Win32 systems it attempts to
	 * implement most of the formatting options missing from the native Win32
	 * implementation.</p>
	 *
	 * @param string $format
	 * @param integer $time
	 * @return string
	 * @see \strftime()
	 */

	public static function formatTime($format, $time = null){

		if(empty($time)) $time = time();

		// Apply Windows-specific format mapping

		if(PHP_OS == 'WINNT'){

			$mapping = [
				'%C' => sprintf('%02d', date('Y', $time) / 100),
				'%D' => '%m/%d/%y',
				'%e' => sprintf('%\' 2d', date('j', $time)),
				'%h' => '%b',
				'%n' => "\n",
				'%r' => date('h:i:s', $time) . ' %p',
				'%R' => date('H:i', $time),
				'%t' => "\t",
				'%T' => '%H:%M:%S',
				'%u' => (($w = date('w', $time)) ? $w : 7)
			];

		   $format = str_replace(array_keys($mapping), array_values($mapping), $format);
		}

	   return strftime($format, $time);
	}

	/**
	 * Filter invalid characters from a telephone number.
	 *
	 * <p>A generic telephone number usually contains one plus, a few dashes
	 * (optional), perhaps some spaces for visual clarity, and for the rest,
	 * nothing else.</p>
	 *
	 * @param string $telephone
	 * @return string
	 */

	public static function filterTelephoneNumber($telephone){

		$patterns = [
			'/[^0-9\+-\s]+/',
			'/[\s]+/',
			'/[-]+/',
			'/[\+]+/'
		];

		$replacements = ['', ' ', '-', '+'];

		return preg_replace($patterns, $replacements, $telephone);
	}

	/**
	 * Check if a phone number has at least 8 numbers.
	 *
	 * <p>Really rough guesstimate.</p>
	 *
	 * @param $telephone
	 *
	 * @return bool
	 */

	public static function isValidTelephoneNumber($telephone){

		$numbers = str_split($telephone);
		$nums = [];

		foreach($numbers as $number){

			if(ctype_digit($number)){

				$nums[] = $number;
			}
		}

		if(count($nums) > 8){

			return true;
		}

		return false;
	}

	/**
	 * Formats the numer to populate 1, 10, 100, 1.1K, 10K, 1.1M, 10M
	 *
	 * @param integer $number
	 * @return string
	 */

	public static function formatSocialNumber($number){

		assert('is_integer($number)');

		if(!is_integer($number)) return $number;

		if($number > 10000000){

			$number = round($number / 1000000) . 'M';
		}
		elseif($number > 1000000){

			$number = round($number / 1000000, 1) . 'M';
		}
		elseif($number > 10000){

			$number = round($number / 1000) . 'K';
		}
		elseif($number > 1000){

			$number = round($number / 1000, 1) . 'K';
		}

		return $number;
	}
}