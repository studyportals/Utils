<?php

/**
 * @file Utils\HTTP.php
 * HTTP Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @author Danko Adamczyk <danko@studyportals.com>
 * @author Rob van den Hout <vdhout@studyportals.com>
 * @author Rob Janssen <rob@studyportals.com>
 * @copyright © 2012-2016 StudyPortals B.V., all rights reserved.
 * @version 1.3.1
 */

namespace StudyPortals\Utils;

use ErrorException;

/**
 * HTTP.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 */

abstract class HTTP{

	const OK					= 200;
	const CREATED				= 201;
	const NO_CONTENT			= 204;

	const MULTIPLE_CHOICES		= 300;
	const MOVED_PERMANENTLY		= 301;
	const FOUND					= 302;
	const SEE_OTHER				= 303;
	const NOT_MODIFIED			= 304;
	const TEMPORARY_REDIRECT	= 307;

	const BAD_REQUEST			= 400;
	const UNAUTHORIZED			= 401;
	const FORBIDDEN				= 403;
	const NOT_FOUND				= 404;
	const METHOD_NOT_ALLOWED	= 405;
	const CONFLICT				= 409;
	const GONE					= 410;

	const INTERNAL_SERVER_ERROR	= 500;
	const NOT_IMPLEMENTED		= 501;
	const SERVICE_UNAVAILABLE	= 503;

	protected static $_messages = [
		self::OK						=> 'OK',
		self::CREATED					=> 'Created',
		self::NO_CONTENT				=> 'No Content',

		self::MULTIPLE_CHOICES			=> 'Multiple Choices',
		self::MOVED_PERMANENTLY			=> 'Moved Permanently',
		self::FOUND						=> 'Found',
		self::SEE_OTHER					=> 'See Other',
		self::NOT_MODIFIED				=> 'Not Modified',
		self::TEMPORARY_REDIRECT		=> 'Temporary Redirect',

		self::BAD_REQUEST				=> 'Bad Request',
		self::UNAUTHORIZED				=> 'Unauthorized',
		self::FORBIDDEN					=> 'Forbidden',
		self::NOT_FOUND					=> 'Not Found',
		self::METHOD_NOT_ALLOWED		=> 'Method Not Allowed',
		self::CONFLICT					=> 'Conflict',
		self::GONE						=> 'Gone',

		self::INTERNAL_SERVER_ERROR		=> 'Internal Server Error',
		self::NOT_IMPLEMENTED			=> 'Not Implemented',
		self::SERVICE_UNAVAILABLE		=> 'Service Unavailable',
	];

	/**
	 * Detect the base URL for the current request.
	 *
	 * <p>URL detection is based upon the server environment. It is assumed the
	 * URL always points to a directory on the server, <strong>not</strong> a
	 * file. A trailing slash is thus always appended to the URL returned.</p>
	 *
	 * <p>When When the request is run in CLI-mode, the URL
	 * <strong>http://php-cli.invalid/</strong> is returned.</p>
	 *
	 * @return string
	 */

	public static function detectBaseURL(){

		if(PHP_SAPI == 'cli') return 'http://php-cli.invalid/';

		$protocol = 'http://';

		if(isset($_SERVER['HTTPS']) &&
			filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)){

			$protocol = 'https://';
		}

		/*
		 * We use Apache's user-dir feature for our local development
		 * environments (e.g. "http://localhost/~Academic/"). In order for
		 * base URL detection to properly pick up on these URL's we  use the
		 * CONTEXT_PREFIX environment variable provided by recent (2.3.13+)
		 * versions of Apache.
		 */

		if(isset($_SERVER['CONTEXT_PREFIX'])){

			$path = trim($_SERVER['CONTEXT_PREFIX'], '/\\') . '/';
		}
		else{

			$path = './';
		}

		return self::normaliseURL("{$protocol}{$_SERVER['HTTP_HOST']}/{$path}");
	}

	/**
	 * Set HTTP status-code.
	 *
	 * <p>Sets a HTTP/1.1 compliant header for the requested {@link $status}
	 * and returns a short message (e.g. "Not Found") that goes along with the
	 * status-code.</p>
	 *
	 * @param integer $status
	 * @param bool $suppress
	 *
	 * @return string
	 * @see _messages::$_codes
	 * @see HTTP::message()
	 */

	public static function status($status, $suppress = false){

		$message = self::getStatusMessage($status);

		if($suppress){

			@header("HTTP/1.1 $status $message");
		}
		else{

			header("HTTP/1.1 $status $message");
		}

		return $message;
	}

	/**
	 * Get the HTTP status-message
	 *
	 * @param $status
	 *
	 * @return string
	 * @see $_messages
	 */

	public static function getStatusMessage($status){

		if(isset(self::$_messages[$status])){

			return self::$_messages[$status];
		}

		return '';
	}

	/**
	 * Set HTTP status-code and display a message.
	 *
	 * <p>Calls {@link HTTP::status()} with {@link $status} and shows the
	 * provided {@link $message} in a simple HTML document. This method is
	 * primarily intended to transfer basic error messages to the client.</p>
	 *
	 * @param integer $status
	 * @param string $message
	 * @return void
	 * @see HTTP::status()
	 */

	public static function message($status, $message){

		$title = self::status($status);

		?>

			<html>
				<head>
					<title><?= $title; ?></title>
				</head>
				<body>
					<h1><?= $title; ?></h1>
					<p>
						<?= htmlentities($message, ENT_COMPAT |
							ENT_HTML401, 'ISO-8859-1'); ?>.
					</p>
				</body>
			</html>

		<?php
	}

	/**
	 * Redirect the browser to the provided path.
	 *
	 * <p>The provided {@link $url} can be both a relative path, or an absolute
	 * URL. In case the provided path is relative, the current request's base
	 * URL is prepended.<br>
	 * <strong>Note:</strong> This method does not terminate script execution.
	 * Under normal circumstances you will want to terminate execution directly
	 * after calling this method.</p>
	 *
	 * <p>The following response codes are supported for the optional parameter
	 * {@link $status}:</p>
	 * <ul>
	 * 		<li><b>300:</b> Multiple Choices (do not automatically redirect)</li>
	 * 		<li><b>301:</b> Moved Permanently</li>
	 * 		<li><b>302:</b> Found (if you want a "temporary redirect" do
	 * 		<strong>not</strong> use, this status code, send 307 instead!)</li>
	 * 		<li><b>303:</b> See Other (used to "transform" a POST-request
	 * 		into a GET-request)</li>
	 * 		<li><b>307:</b> Temporary Redirect (default, requires HTTP/1.1)</li>
	 *	</ul>
	 *
	 * @param string $url
	 * @param integer $status
	 * @return void
	 */

	public static function redirect($url, $status = 307){

		if(!in_array($status, [300, 301, 302, 303, 307])){

			$status = 307;
		}

		$url = trim($url);

		// Append base URL to a relative path

		if(strtolower(substr($url, 0, 4)) != 'http'){

			$base_url = self::detectBaseURL();

			// Unable to detect the base URL

			if(empty($base_url)){

				self::message(500, 'Unable to detect base URL');
				return;
			}

			$url = $base_url . ltrim($url, '/');
		}

		// Check target URL

		$url = self::normaliseURL($url);

		if($url == ''){

			self::message(500, 'Invalid URL provided for redirect operation');
			return;
		}

		// Automatic redirect

		if($status != HTTP::MULTIPLE_CHOICES){

			self::status($status);

			header("Location: $url");
		}

		// Manual redirect

		else{

			self::status(HTTP::MULTIPLE_CHOICES);

			// Prevent the notification page from being cached

			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Sat, 26 Jul 1997 05:00:00 CEST');
		}

		?>

			<html>
				<head>
					<title>This Page has Moved</title>
				</head>
				<body>
					<h1>This Page has Moved</h1>
					<p>
						The page you have requested has moved to another
						location.
					</p>
					<p>
						If you are not automatically redirected to
						<a href="<?= $url ?>">the page's new location</a>,
						please click <a href="<?= $url ?>">here</a> to do so
						manually.<br>
						If you keep seeing this message, or are otherwise unable
						to reach the new location, please return to
						<a href="/">our homepage</a> and attempt to
						reach the page from there.
					</p>
				</body>
			</html>

		<?php
	}

	/**
	 * Normalise a URL.
	 *
	 * <p>This function attempts to normalise a URL as best as possible. This
	 * function requires any URL passed to at least contain a host name and
	 * an indication of the scheme (HTTP or HTTPS) to be used.</p>
	 *
	 * <p>The optional, pass-by-reference, parameter {@link $components} is set
	 * to the result of the internal call to {@link parse_url()} done by this
	 * method (with a little bit of post-processing applied). This information
	 * can be used to, for example, retrieve the hostname from the URL.</p>
	 *
	 * <ul>
	 * 	<li>Invalid characters are removed from the URL;</li>
	 * 	<li>If no scheme information is present, "http://" is prepended;</li>
	 * 	<li>If the scheme and port match, the port is removed;</li>
	 * 	<li>If present, the trailing dot is removed from the host name;</li>
	 * 	<li>The hostname is made all lowercase;</li>
	 * 	<li>Optional path, query and fragment are appended to the URL;</li>
	 * 	<li>The path component is cleaned of unnecessary and incorrect
	 * 	slashes.</li>
	 * </ul>
	 *
	 * @param string $url
	 * @param array &$components
	 * @return string
	 * @see parse_url()
	 */

	public static function normaliseURL($url, array &$components = []){

		$url = filter_var(trim($url), FILTER_SANITIZE_URL);
		$components = @parse_url($url);

		if(!$components || !isset($components['host'])){

			return '';
		}

		// Scheme

		if(!preg_match('/https*/i', $components['scheme'])){

			switch($components['port']){

				case 443:

					$components['scheme'] = 'https';

				break;

				case 80:
				default:

					$components['scheme'] = 'http';

				break;
			}
		}

		// Remove default ports

		if(!empty($components['port'])){

			if($components['scheme'] == 'http' && $components['port'] == 80){

				unset($components['port']);
			}
			elseif($components['scheme'] == 'https' &&
				$components['port'] == 443){

				unset($components['port']);
			}
		}

		// Remove trailing dot from FQDN

		if(substr($components['host'], -1) == '.'){

			$components['host'] = substr($components['host'], 0, -1);
		}

		// Reconstruct URL

		$normalised =
			strtolower("{$components['scheme']}://{$components['host']}/");

		if(isset($components['port'])){

			$normalised = substr($normalised, 0, -1) . ":{$components['port']}/";
		}

		if(isset($components['path'])){

			// Clean path

			$components['path'] =
				str_replace(['\\', '/./'], '/', $components['path']);
			$components['path'] =
				preg_replace('/[\/]+/', '/', $components['path']);

			$normalised .= ltrim($components['path'], '/');
		}

		if(isset($components['query'])){

			$normalised .= "?{$components['query']}";
		}

		if(isset($components['fragment'])){

			$normalised .= "#{$components['fragment']}";
		}

		return $normalised;
	}

	/**
	 * Get the base URL for a given site URL.
	 *
	 * <p>This is the PHP equivalent of the JS Shared.getBaseUrl function.
	 * It can deal with localhost as well.</p>
	 *
	 * @param string $url
	 * @return string
	 * @throws ErrorException
	 */

	public static function getBaseURL($url){

		$parsed_url = parse_url($url);
		if($parsed_url === false){

			throw new ErrorException('Malformed URL.');
		}

		$base_url = [];

		if(empty($parsed_url['scheme'])){

			throw new ErrorException('Malformed URL, no scheme.');
		}

		$base_url[] = $parsed_url['scheme'];
		$base_url[] = '://';
		$base_url[] = $parsed_url['host'];

		// Check the path

		$path = $parsed_url['path'];
		$path_parts = explode('/', $path);

		// Discard the first slash.

		array_shift($path_parts);
		$base = array_shift($path_parts);

		// Check if we're on localhost
		if(strpos($base, '~') !== false){

			$base_url[] = '/' . $base;
		};

		$base_url[] = '/';

		return implode('', $base_url);
	}

	/**
	 * Convert a parsed URL back to a string.
	 *
	 * <p>Basically the opposite of parse_url. Specify a set of components
	 * and this function turns it back into a string again.</p>
	 *
	 * @see parse_url()
	 * @param array $parsed_url
	 * @return string
	 */

	public static function parsedURLToString($parsed_url) {

		$components = [];

		if(!empty($parsed_url['scheme'])){

			$components['scheme'] = $parsed_url['scheme'] . '://';
		}

		$components['user'] = '';
		$components['pass'] = '';

		if(!empty($parsed_url['user'])){

			$components['user'] = $parsed_url['user'];
		}

		if(!empty($parsed_url['pass'])){

			$components['pass'] = ':' . $parsed_url['pass'];
		}

		if($components['user'] || $components['pass']){

			$components['pass'] = $components['pass'] . '@';
		}

		if(!empty($parsed_url['host'])){

			$components['host'] = $parsed_url['host'];
		}

		if(!empty($parsed_url['port'])){

			$components['port'] = ':' . $parsed_url['port'];
		}

		if(!empty($parsed_url['path'])){

			$components['path'] = $parsed_url['path'];
		}

		if(!empty($parsed_url['query'])){

			$components['query'] = '?' . $parsed_url['query'];
		}

		if(!empty($parsed_url['fragment'])){

			$components['fragment'] = '#' . $parsed_url['fragment'];
		}

		return implode('', $components);
	}

	/**
	 * Strictly clean a URL-path from any unwanted characters.
	 *
	 * <p>Enforces a very strict "clean path"-policy, allowing only [A-Z],
	 * [a-z], [0-9] and hyphens ("-"). All spaces are replaced with a hyphen,
	 * all back-slashes are converted to forward-slashes and all superfluous
	 * slashes are removed. Any other remaining, invalid, characters are also
	 * removed.</p>
	 *
	 * @param string $path
	 * @return string
	 */

	public static function cleanPath($path){

		$path = str_replace(['\\', '/./'], '/', $path);
		$path = preg_replace('/[\/]+/', '/', $path);
		$path = rtrim($path, '/');

		$path = preg_replace('/[^a-z0-9 \-\/]+/i', '', $path);
		$path = preg_replace('/[ \-]+/', '-', $path);

		return $path;
	}

	/**
	 * Parse (raw) HTTP-headers into a key-value array.
	 *
	 * <p>Supports both raw HTTP-headers (passed in as an array of header lines)
	 * and key-value based headers (as generated for example by {@link
	 * apache_request_headers()}.<br>
	 * When raw HTTP-headers are provided the optional, passed-by-reference,
	 * {@link $status} argument is set to the HTTP-status code.</p>
	 *
	 * <p>Even though the HTTP RFC allows "empty" headers it is apparently not a
	 * best-practice (as not all clients deal with this properly). So, {@link
	 * HTTP::buildHeader()} uses pseudo-empty headers in the form of "" (two
	 * double-quotes without content) which are translated back to actual
	 * empty strings by this function.<br>
	 * This function furthermore supports "chunked" headers (e.g.
	 * "X-Something-1", X-Something-2", ...). When chunks are detected they are
	 * folded into a single element in the result, containing a key-value pair
	 * for each chunk encountered (taking the numeric suffix of the chunk as its
	 * array key, so don't make any assumptions about a continuous set of chunk
	 * keys!).</p>
	 *
	 * <p><strong>N.B.</strong>: For some (common) header fields additional
	 * processing is performed. These fields are interpreted and returned as
	 * different types (e.g. an array or an integer) depending on their content.
	 * All other, "unsupported", header fields have their name and value
	 * trimmed and sanitised, but are otherwise left untouched.<br>
	 * A note to future maintainers: Try not to turn this method into the place
	 * where esoteric header-fields go to die. Only add support for <em>common
	 * </em> HTTP/1.1 header-fields here; add application specific stuff to your
	 * actual application...</p>
	 *
	 * @param array $lines
	 * @param integer &$status
	 * @return array
	 * @see HTTP::buildHeader()
	 */

	public static function parseHeader(array $lines, &$status = 0){

		$headers = [];

		foreach($lines as $name => $value){

			// HTTP status

			if(strpos($value, 'HTTP/1') === 0){

				$status = explode(' ', $value, 3)[1];

				continue;
			}

			// Transform raw HTTP headers into key-value pairs

			if(is_int($name)){

				list($name, $value) = explode(':', trim($value), 2);
			}

			$name = trim($name);
			$name = filter_var($name, FILTER_SANITIZE_STRING,
				FILTER_FLAG_NO_ENCODE_QUOTES);

			$value = trim($value);
			$value = filter_var($value, FILTER_SANITIZE_STRING,
				FILTER_FLAG_NO_ENCODE_QUOTES);

			// Deal with pseudo-empty headers

			if($value == '""'){

				$value = '';
			}

			// Deal with "chunked" headers

			$name_parts = explode('-', $name);
			$index = end($name_parts);

			if(ctype_digit($index)){

				$index = (int) $index;

				array_pop($name_parts);
				$name = implode('-', $name_parts);
			}
			else{

				unset($index);
			}

			// Case-insensitive header match for additional processing

			switch(strtolower($name)){

				// Date(time) elements

				case 'date':
				case 'expires':
				case 'last-modified':

					$value = strtotime($value);

				break;

				// Lists of directives

				case 'keep-alive':
				case 'cache-control':

				/*
				 * The below are used by the ServiceLayer to handle header input
				 * from the client. Even though doing it here is efficient (c.q.
				 * no duplicate code) it is very confusing as to what happens
				 * where (as most of the client's header handling is already
				 * done by the client itself). See also my remark at the end of
				 * the PHPdoc-block for this function...
				 */

				case 'x-firelogger-counts':
				case 'x-studyportals-brand':
				case 'x-studyportals-tally':

					$directives = [];

					foreach(explode(',', $value) as $directive){

						$directive = trim($directive);

						if(strpos($directive, '=') !== false){

							list($directive_name, $directive_value) =
								explode('=', $directive, 2);

							if(ctype_digit($directive_value)){

								$directive_value = (int) $directive_value;
							}

							$directives[$directive_name] = $directive_value;
						}
						else{

							$directives[$directive] = true;
						}
					}

					$value = $directives;

				break;

				// Accept-Language

				case 'accept-language':

					$locales = [];

					foreach(explode(',', $value) as $locale){

						$locale = trim($locale);

						if(strpos($locale, '-') !== false){

							list($language, $culture) =
								explode('-', $locale, 2);
							$culture = strtoupper($culture);

							$locales["{$language}-{$culture}"] = 1.0;
						}
					}

					$value = $locales;

				break;

				// All other, "unsupported", fields

				default:

					// NOP

				break;
			}

			/*
			 * Correct header field-name capitalisation.
			 *
			 * The apache_request_headers() function (which is sometimes used
			 * to feed this method) messes with the field-name capitalisation.
			 * The below corrects the two most common problems this causes in
			 * our codebase.
			 * We always return headers using their original (c.q. not
			 * lower-cased) field-names.
			 */

			$name = str_replace(
				['Studyportals', 'Firelogger'],
				['StudyPortals', 'FireLogger'], $name);

			if(isset($index)){

				assert('!isset($headers[$name][$index])');
				$headers[$name][$index] = $value;
			}
			else{

				assert('!isset($headers[$name])');
				$headers[$name] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Turn a key-value array into (raw) header-lines.
	 *
	 * <p>This method tries to clean-up/correct the resulting header-lines as
	 * much as possible. Problems found are silently corrected (assertions are
	 * used to indicate problems).</p>
	 *
	 * <p>The optional {@link $prefix} parameter is used to prefix all header
	 * field-names with a give prefix (e.g. "X-StudyPortals"). When sending
	 * non-standard headers it is common practice to (at the very least) prefix
	 * them with "X".</p>
	 *
	 * <p>All non-alphanumeric characters are stripped from header-field names
	 * (with the exception of the hyphen used to separate words). Every word in
	 * the field-name is made to start with a capital letter.</p>
	 *
	 * <p>Header-values can be arrays, they are automatically converted back
	 * into a sensible string representation:  When the first key in the header-
	 * value is zero we assume it's *not* key-based (and thus output
	 * "value1,value2,..."). Otherwise we assume it *is* key-based (and thus
	 * output as "key1=value1,key2=value2,...").
	 * Furthermore, empty header values are turned into pseudo-empty values in
	 * the form of "" (two double-quotes without content). This is done because
	 * many clients don't cope well with empty header (even though the RFC does
	 * allow it). Our {@link HTTP::parseHeader()} function converts pseudo-
	 * empty back into "actually" empty.</p>
	 *
	 * <p>All header-values are truncated at 7.168 characters. Although the
	 * HTTP RFC does not limit header line-length, most web servers do. In our
	 * case Apache limits header line-length to 8 KiB, so this method truncates
	 * all lines that grow beyond 7 KiB.<br>
	 * <strong>N.B.</strong>: This method does not chunk long header lines. We
	 * have no idea what content is in the header, so chunking the values will
	 * most likely break them.</p>
	 *
	 * @param array $headers
	 * @param string $prefix
	 * @return array
	 * @see HTTP::parseHeader
	 */

	public static function buildHeader(array $headers, $prefix = ''){

		$lines = [];

		$header_reduce = function($key, $value){

			return "$key=$value";
		};

		foreach($headers as $key => $value){

			// Filter field-name

			if(!empty($prefix)){

				$key = "{$prefix}-{$key}";
			}

			assert('preg_match(\'/^[a-z0-9\-]+$/i\', $key)');

			$key = preg_replace('/[^a-z0-9 \-]+/i', '', $key);
			$key = trim(preg_replace('/[ \-]+/', ' ', $key));

			assert('!empty($key)');
			if(empty($key)){

				continue;
			}

			$key = ucwords($key);
			$key = str_replace(' ', '-', $key);

			// Process array values

			if(is_array($value)){

				// Differentiate between "vectors" and key-value arrays

				reset($value);

				if(key($value) !== 0){

					$value = array_map($header_reduce,
						array_keys($value), $value);
				}

				$value = implode(',', $value);
			}

			// Turn empty values into pseudo-empty values - 0 is not empty!

			if(empty($value) && $value !== 0){

				$value = '""';
			}

			/*
			 * Header-values cannot contain CRLF (but to be on the safe side we
			 * filter for CR and LF separately).
			 */

			assert('strpos($value, "\r") === false &&
				strpos($value, "\n") === false');
			$value = str_replace(["\r", "\n"], '', $value);

			// Truncate header-values larger than 7 KiB

			assert('strlen($value) <= 7168');
			if(strlen($value) > 7168){

				$value = substr($value, 0, 7168);
			}

			$lines[$key] = $value;
		}

		return $lines;
	}

	/**
	 * Re-encode a returned document to a JSON string.
	 *
	 * This function will first go convert back all values to UTF-8 before
	 * re-encoding it into a string. The end result should be the same as the raw
	 * ServiceLayer response.
	 *
	 * @param array $document
	 * @return string
	 */

	public static function reencode(array $document){

		if(!is_array($document)){

			return '';
		}

		return json_encode($document);
	}
}