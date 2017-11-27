<?php

/**
 * @file Utils\Text.php
 * String Utility Methods
 *
 * <p>Replaced the String class with Text, this is to support PHP 7 which has
 * made String a reserved word.</p>
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Rob Janssen <rob@studyportals.eu>
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @copyright © 2006-2009 Thijs Putman, all rights reserved.
 * @copyright © 2010-2015 StudyPortals B.V., all rights reserved.
 * @version 2.0.1
 */

namespace StudyPortals\Utils;

use StudyPortals\Exception\ExceptionHandler;
use StudyPortals\Exception\PHPErrorException;

/**
 * String utility methods.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class Text{

	/**
	 * Default (c.q. fallback) character-set used throughout the framework.
	 *
	 * @var string
	 */

	const DEFAULT_CHARSET = 'UTF-8';

	/**
	 * Articles of association used by the title-case conversion.
	 *
	 * <p>Currently includes support for common English, Dutch, German, French
	 * and Spanish articles of association.</p>
	 *
	 * @param boolean $lower
	 * @return array
	 * @see Text::upperCaseTitle()
	 */

	private static function _getAdpositions($lower = false){

		$articles = [
			'A',	'An',	'And',	'At',	'For',	'In',
			'Of',	'On',	'Or',	'The',	'To',	'With',

			'Van',	'De',	'Het',	'Een',

			'Der',	'Die',	'Das',	'Des',	'Dem',
			'Den',	'Und',	'Vom',	'Nach',	'Zu',		'Ab',
			'Aus',	'Von',	'An',	'Auf',	'Außer',	'Bei',
			'Gegenüber',	'Hinter',		'In',		'Neben',
			'Über',	'Unter',		'Vor',	'Zwischen',	'Gegen',
			'An',	'Bis',	'Durch','Für',	'Am',		'Zu',

			'Le',	'La',	'L\'',	'Du',	'En',	'De',
			'Pour',	'Par',	'Et',	'Des',	'Les',	'Avec',
			'Chez', 'À',	'Au',	'Aux',	'Ou',	'Où',

			'Para',		'Por',	'Y', 	'E', 	'Del',		'De Los',
			'De Las',	'El',	'Ella',	'Los',	'Las',		'Lo',
			'La',		'Con',	'A',	'Al',	'A Los',	'A Las', 'O',
		];

		if($lower){

			$lower = function($str){

				return strtolower($str);
			};

			$articles = array_map($lower, $articles);
		}

		$pad = function($str){

			return " $str ";
		};

		return array_map($pad, $articles);
	}

	/**
	 * Characters that can be used to generate a secure passphrase.
	 *
	 * @var array
	 * @see generatePassPhrase()
	 */

	private static $_passphrase_chars = [
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'm', 'n', 'p',
		'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
		'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T',
		'U', 'V', 'W', 'X', 'Y', 'Z', 1, 2, 3, 4, 5, 6, 7, 8, 9, '@', '#',
		'$', '%', '*'
	];

	/**
	 * List of "ex-post" character mappings.
	 *
	 * <p>Not all conversions applied by iconv() lead to the desired result;
	 * sometimes accents are transliterated into (sets of) normal charaters and
	 * punction (e.g. "´s" instead of simply "s"). This array allows you to
	 * specify, per <em>output</em> character-set, which additional conversions
	 * should be applied be our enhanced iconv() method.</p>
	 *
	 * <p>For every <strong>ouput</strong> character-set this array contains a
	 * list of character mappings. The array-key is the transliterated value,
	 * the array value is the desired result.</p>
	 *
	 * @var array
	 */

	private static $_iconv_mapping = [
		'ISO-8859-1' => [
			'´c' => 'c',
			'´s' => 's',
			'´z' => 'z'
		]
	];

	/**
	 * Format a string in title-case.
	 *
	 * <p>Upper-cases every word in the string, except for the so called
	 * "articles of association".</p>
	 *
	 * @param string $string
	 * @return string
	 */

	public static function upperCaseTitle($string){

		// Only transform to lowercase if title is more than a single word

		if(strpos($string, ' ') !== false){

			if(strtoupper($string) == $string) $string = strtolower($string);
		}

		$string = ucwords($string);

		$string = str_replace(self::_getAdpositions(),
			self::_getAdpositions(true), $string);

		return ucfirst($string);
	}

	/**
	 * Generate a secure passphrase.
	 *
	 * @param integer $length
	 * @return string
	 */

	public static function generatePassphrase($length = 10){

		$recursion = 0;

		$generate = function($length){

			for($i = 0; $i < $length; $i++){

				$key = rand(0, count(self::$_passphrase_chars) - 1);
				/** @noinspection PhpUndefinedVariableInspection */
				$passphrase .= self::$_passphrase_chars[$key];
			}

			/** @noinspection PhpUndefinedVariableInspection */
			return $passphrase;
		};

		$passphrase = $generate($length);

		// Check if pass-phrase is secure enough

		$secure_pattern = "/^(?=.\{$length\})(?=(.*[A-Za-z].*){2,})(?=(.*[0-9].*){2,})(?=(.*\W.*){1,}).*$/";

		while(!preg_match($secure_pattern, $passphrase)){

			$passphrase = $generate($length);

			// We seem to be stuck; abort and go for last generated passphrase

			if($recursion > 10){

				ExceptionHandler::notice('Text::generatePassphrase() seems to be stuck, aborting');
				break;
			}
		}

		return $passphrase;
	}

	/**
	 * Generate a random (c.q. version 4) UUID.
	 *
	 * <p>Generates a random Universal Unique Identifier (UUID). Implementation
	 * taken from the PHP-manual; compliant with RFC 4122.<p>
	 *
	 * <p>A UUID is a 128-bit integer with the following properties:</p>
	 * <ul>
	 * 	<li>32 bits for "time_low"</li>
	 * 	<li>16 bits for "time_mid"</li>
	 *	<li>16 bits for "time_hi_and_version"; the four most significant bits
	 *	hold version number 4</li>
	 *	<li>8 bits for "clk_seq_hi_res" and 8 bits for "clk_seq_low"; two most
	 *	significant bits hold zero and one for variant DCE 1.1</li>
	 *	<li>48 bits for "node"</li>
	 * </ul>
	 *
	 * @return string
	 * @see http://tools.ietf.org/html/rfc4122
	 * @see http://www.php.net/manual/en/function.uniqid.php#94959
	 */

	public static function generateRandomUUID(){

		$time_low	= [mt_rand(0, 0xffff), mt_rand(0, 0xffff)];
		$time_mid	= mt_rand(0, 0xffff);
		$time_hi	= mt_rand(0, 0x0fff) | 0x4000;
		$clk_seq	= mt_rand(0, 0x3fff) | 0x8000;
		$node =		[mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)];

		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			$time_low[0], $time_low[1], $time_mid, $time_hi, $clk_seq,
			$node[0], $node[1], $node[2]);
	}

	/**
	 * Check if an e-mail address is "real-world" valid.
	 *
	 * <p>Validates "real-world" email addresses (e.g. not according to some
	 * obscure RFC). The PHP FILTER_VALIDATE_EMAIL option in filter_var and
	 * input_var marks some addresses as valid while they aren't (e.g.
	 * yoozer@gmail is considered valid).</p>
	 *
	 * @param string $email
	 * @return bool
	 * @see http://www.hm2k.com/posts/what-is-a-valid-email-address
	 * @see http://hm2k.googlecode.com/svn/trunk/code/php/functions/validate_email.php
	 */

	public static function isValidEMail($email){

		$pattern = '/^[\w!#$%&\'*+\/=?^`{|}~.-]+@(?:[a-z\d][a-z\d-]*'
			. '(?:\.[a-z\d][a-z\d-]*)?)+\.(?:[a-z][a-z\d-]+)$/iD';

		if(preg_match($pattern, $email) > 0){

			return true;
		}

		return false;
	}

	/** @noinspection PhpUndefinedClassInspection */

	/**
	 * Enhanced iconv().
	 *
	 * <p>This method first attempts to use iconv() to transliterate the string.
	 * It automatically switches to iconv() "ignore" if either transliteration
	 * fails, or if the transliterated string is significantly shorter than the
	 * original string.<br>
	 * This method furthermore applies a hard-coded, ex-post, character mapping
	 * to its output to prevent any further conversion "noise" from remaining in
	 * the string.</p>
	 *
	 * <p>All in all this method can be used as a drop-in replacement for PHP's
	 * internal iconv() function, with the different that his method (through
	 * some trail-and-error) will most likely end up with a better result.</p>
	 *
	 * @param string $in_charset
	 * @param string $out_charset
	 * @param string $string
	 * @throws \PHPErrorException
	 * @return string
	 */

	public static function iconv($in_charset, $out_charset, $string){

		trigger_error('This function is being deprecated. Contact Dmitrii or Ilia.', E_USER_WARNING);

		if(($i = strpos($out_charset, '//')) !== false){

			$out_charset = substr($out_charset, 0, $i);
		}

		try{

			$string_out = iconv($in_charset, "{$out_charset}//TRANSLIT", $string);

			// Apply "ex-post" character conversions

			if(!empty(self::$_iconv_mapping[$out_charset]) &&
				is_array(self::$_iconv_mapping[$out_charset])){

				$search = array_keys(self::$_iconv_mapping[$out_charset]);
				$replace = array_values(self::$_iconv_mapping[$out_charset]);

				assert('count($search) === count($replace)');

				$string_out = str_replace($search, $replace, $string_out);
			}

			if(mb_strlen($string, $in_charset) > mb_strlen($string_out, $out_charset)){

				throw new PHPErrorException('Original string is significantly larger
					than transliterated string');
			}
		}
		catch(PHPErrorException $e){

			/*
			 * Fallback to using iconv() "//IGNORE". No need for the ex-post
			 * character conversion here: In ignore-mode iconv() simply drops
			 * all offending characters.
			 */

			$string_out = iconv($in_charset, "{$out_charset}//IGNORE", $string);
		}

		return $string_out;
	}

	/**
	 * Clean up a search string.
	 *
	 * @param $keywords
	 *
	 * @return array
	 * @internal param string $string
	 */

	public static function purify($keywords){

		$keywords	= filter_var($keywords, FILTER_SANITIZE_STRING,
			FILTER_FLAG_NO_ENCODE_QUOTES);
		$keywords	= preg_replace('/\s\s+/', ' ', $keywords);
		$keywords	= trim($keywords);

		$dirty_tokens = explode(' ', $keywords);
		$clean_tokens = [];

		foreach($dirty_tokens as $token){

			// Check whether a string has at least 3 alphanumeric characters
			$pattern = "/[\w'\"`-]{3,}/i";
			if(preg_match($pattern, $token)){

				if(strlen($token) < 64){

					$clean_tokens[] = $token;
				}
			}
		}

		return [
			'dirty' => $dirty_tokens,
			'clean' => $clean_tokens,
			'stems' => $clean_tokens
		];
	}

	/**
	 * Format the given string as a valid (virtual) page name.
	 *
	 * <p>Removes all illegal characters and replaces spaces by "-".</p>
	 *
	 * @param string $name
	 *
	 * @return string
	 */

	public static function rewriteToURLFriendly($name){

		$name = strtolower(trim($name));

		// Approximate all non-ASCII characters with their ASCII counterpart

		$name = @iconv('Windows-1252', 'ASCII//TRANSLIT', $name);

		/*
		 * If text starts and ends with a brace, remove these first. Several
		 * studies have this for some reason and it screws up the page name.
		 */

		if(!empty($name)){

			if($name[0] === '(' && $name[strlen($name) - 1] === ')') {

				$name = substr($name, 1, strlen($name) - 2);
			}
		}

		// Remove text in braces

		$name = preg_replace('/\(.*?\)/', '', $name);

		// Remove all remaining invalid characters

		$name = preg_replace('/[^a-z0-9 \-]+/', '', $name);
		$name = preg_replace('/[ \-]+/', '-', trim($name));

		if(empty($name)) $name = 'blank';

		return $name;
	}

	/**
	 * Shorten a string and aim for the end of a sentence (period).
	 *
	 * <p>Generally used for descriptions.</p>
	 *
	 * @param string $string
	 * @param integer $length
	 * @return string
	 */

	public static function truncateToEndOfSentence($string, $length = 60){

		assert('$length >= 10');

		$string = ucfirst($string . '. ');
		$string = preg_replace('/(?:\r?\n)+/', ' ', $string);
		if(strlen($string) > $length){

			$string = substr($string, 0,
				strpos($string, '. ', $length));
		}

		$string = trim($string, '. ') . '.';
		return $string;
	}

	/**
	 * Filter out common punctuation.
	 *
	 * <p>Generally used for names of studies/courses.</p>
	 *
	 * @param string $string
	 * @return string
	 */

	public static function stripCommonPunctuation($string){

		$string = static::upperCaseTitle($string);
		$string = preg_replace('/(?:\(|\[).*?(?:\]|\))/', '', $string);
		return $string;
	}

	/**
	 * Base64 encoding for url parts.
	 *
	 * Make sure to
	 *
	 * @param string $data
	 * @return string
	 *
	 * @link http://php.net/manual/en/function.base64-encode.php#103849
	 */

	public static function base64url_encode($data){

	 	return rtrim(
			strtr(
				base64_encode($data),
				'+/',
				'-_'
			),
			'='
		);
	}

	/**
	 * Base64 decoding for url parts.
	 *
	 * @param string $data
	 * @return string
	 *
	 * @link http://php.net/manual/en/function.base64-encode.php#103849
	 */

	public static function base64url_decode($data){

		return base64_decode(
			str_pad(
				strtr($data, '-_', '+/'),
				strlen($data) % 4, '=',
				STR_PAD_RIGHT
			)
		);
	}
}