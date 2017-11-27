<?php

/**
 * @file Utils\HTML.php
 * HTML Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @copyright © 2004-2009 Thijs Putman, all rights reserved.
 * @copyright © 2011-2015 StudyPortals B.V., all rights reserved.
 * @version 1.0.1
 */

namespace StudyPortals\Utils;

use DOMComment;
use DOMNode;
use StudyPortals\Exception\PHPErrorException;

if(!defined('DEFAULT_CHARSET')) define('DEFAULT_CHARSET', 'ISO-8859-1');

/**
 * HTML utility methods.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class HTML{

	/**
	 * Contains very basic tags to allow visitors to write text.
	 */
	const S_STRICT = 'strict';

	/**
	 * Contains basic tags to enable university admin editors without an 'a' tag.
	 */
	const S_BASIC = 'basic';

	/**
	 * Contains only the fields allowed for the new design and are only combined
	 * with tags from the media setting.
	 */
	const S_LIMITED = 'limited';

	/**
	 * Contains basic set and an 'a' tag.
	 */
	const S_LINK = 'link';

	/**
	 * Contains link set and media tags.
	 */
	const S_MEDIA = 'media';

	/**
	 * Tags: set of allowed HTML tags.
	 *
	 * <p>Tags not present in this set are removed from the cleaned HTML. The
	 * contents of these tag (and any of their child elements that <em>are</em>
	 * present in this list) are <em>not</em> removed from the HTML.<br>
	 * So, if you exclude "a" from this list, all links are removed from the HTML
	 * but the anchor text (and any allowed elements inside this text ) are
	 * still present in the HTML. Use {@link HTML::$_tags_drop} to fully remove
	 * elements from the output.</p>
	 *
	 * @var array
	 * @see HTML::$_tags_drop
	 */


	private static $_strict_tags = ['u', 'del', 'strong', 'em', 'p', 'br', 'ol', 'ul', 'li'];

	private static $_basic_tags = ['h4', 'h5', 'h6'];

	private static $_link_tags = ['a'];

	private static $_media_tags = ['img', 'video', 'source', 'track', 'embed'];

	private static $_limited_tags = ['p', 'ol', 'ul', 'li'];

	/**
	 * Tags_drop: set of explicitly disallowed HTML tags.
	 *
	 * <p>This list is used to specify a set of tags that should be dropped
	 * whenever encountered. As opposed to the normal filtering behaviour,
	 * dropped tags have their textual contents and any child elements fully
	 * removed from the filtered HTML. This is mostly useful to get rid
	 * of, for example, unwanted "script" or "style" elements.</p>
	 *
	 * @var array
	 */

	private static $_strict_tags_drop = ['style', 'script', 'head'];
	private static $_basic_tags_drop = [];
	private static $_link_tags_drop = [];
	private static $_media_tags_drop = [];
	private static $_limited_tags_drop = ['style', 'script', 'head'];

	/**
	 * Attributes: set of allowed attributes.
	 *
	 * <p>This array allows you to specify allowed attributes per tag. Create
	 * an array for each tag and in that array include all attributes that
	 * should not be removed. Three special purpose "tags" are defined:</p>
	 *
	 * <ul>
	 * 	<li><strong>*</strong>: Contains a list of attributes which are valid
	 * 	for all elements</li>
	 * 	<li><strong>*.schemes</strong>: Contains a list of acceptable schemes
	 * 	for the "href" and "src" attributes (e.g. "http")</li>
	 * 	<li><strong>*.types-href</strong>: Contains a list of acceptable link
	 * 	types for the "href" attribute. Accepted types are "absolute",
	 * 	"relative"and "anchor"</li>
	 * </ul>
	 *
	 * @var array
	 */

	private static $_strict_attributes = [];

	private static $_basic_attributes = [
		'*' => ['title'],
		'*.schemes' => ['http', 'https'],
		'*.types-href' => ['absolute', 'relative', 'anchor']
	];

	private static $_link_attributes = [
		'a' => ['href', 'target', 'name']
	];

	private static $_media_attributes = [
		'img' => ['src', 'alt', 'width', 'height', 'data-align', 'class', 'data-id', 'data-src']
	];

	private static $_limited_attributes = [];

	/**
	 * Get the filters.
	 *
	 * <p>Returns an array containing the default filters for the HTML library.
	 * The array consists of three elements:</p>
	 *
	 * <ul>
	 *    <li><strong>tags:</strong> An array of valid tags that are allowed to
	 *    remain inside the filtered HTML.</li>
	 *    <li><strong>tags_drop:</strong> An array of tags that should be
	 *    explicitly dropped from the output.<li>
	 *    <li><strong>attributes:</strong> An array of allowed attributes.</li>
	 * </ul>
	 *
	 * @param string $set
	 *
	 * @return array
	 */

	protected static function getFilters($set = self::S_MEDIA){

		$tags = self::$_strict_tags;
		$tags_drop = self::$_strict_tags_drop;
		$attributes = self::$_strict_attributes;

		switch($set){

			/** @noinspection PhpMissingBreakStatementInspection */
			case self::S_MEDIA:

				$tags = array_merge_recursive(
					$tags,
					self::$_media_tags
				);

				$tags_drop = array_merge_recursive(
					$tags_drop,
					self::$_media_tags_drop
				);

				$attributes = array_merge_recursive(
					$attributes,
					self::$_media_attributes
				);

			/** @noinspection PhpMissingBreakStatementInspection */
			case self::S_LINK:

				$tags = array_merge_recursive(
					$tags,
					self::$_link_tags
				);

				$tags_drop = array_merge_recursive(
					$tags_drop,
					self::$_link_tags_drop
				);

				$attributes = array_merge_recursive(
					$attributes,
					self::$_link_attributes
				);

			/** @noinspection PhpMissingBreakStatementInspection */
			case self::S_BASIC:

				$tags = array_merge_recursive(
					$tags,
					self::$_basic_tags
				);

				$tags_drop = array_merge_recursive(
					$tags_drop,
					self::$_basic_tags_drop
				);

				$attributes = array_merge_recursive(
					$attributes,
					self::$_basic_attributes
				);
			break;

			case self::S_LIMITED:

				// Limited is a bit special, it only consists of limited and
					// media. Therefore it doesn't need the fallthrough structure

				$tags = array_merge_recursive(
					static::$_limited_tags,
					static::$_media_tags
				);

				$tags_drop = array_merge_recursive(
					static::$_limited_tags_drop,
					static::$_media_tags_drop
				);

				$attributes = array_merge_recursive(
					static::$_limited_attributes,
					static::$_media_attributes
				);

			break;

			default:
			break;
		}

		return [
			'tags' => $tags,
			'tags_drop' => $tags_drop,
			'attributes' => $attributes
		];
	}

	/**
	 * Thoroughly clean HTML based on a set of filters.
	 *
	 * <p>When the optional argument {@link $filters} is omitted a default,
	 * relatively restrictive, set of filter is used. For more information
	 * on the possible filters see {@link HTML::getDefaultFilters()}.</p>
	 *
	 * @param string $html
	 * @param string $filter_set
	 *
	 * @throws HTMLException
	 *
	 * @return string
	 * @see HTML::getDefaultFilters()
	 */

	public static function cleanHTML($html, $filter_set = self::S_MEDIA){

		$filters = self::getFilters($filter_set);

		// Pre-processing

		$html = preg_replace('/[\s]+/', ' ', $html);
		$html = str_replace('> <', '><', $html);

		$html = preg_replace('/(<br>)+/', '<br>', $html);
		$html = str_replace(['<br></', '><br>'], ['</', '>'], $html);

		// Clean HTML

		libxml_clear_errors();

		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		$Document = new \DOMDocument();
		@$Document->loadHTML($html);

		if(empty($Document)){

			$libXMLError = libxml_get_last_error();
			$xml_error = '';

			if($libXMLError instanceof \LibXMLError){

				$xml_error = ", libXML reports: {$libXMLError->message}";
			}

			throw new HTMLException('Invalid HTML provided' . $xml_error);
		}

		$html = self::_cleanHTML($Document, $filters);

		// Post-processing

		$html = preg_replace('/[\s]+/', ' ', $html);
		$html = str_replace('> <', '><', $html);

		$html = preg_replace('/(<br>)+/', '<br>', $html);
		$html = str_replace(['<br></', '><br>'], ['</', '>'], $html);

		$html = Sanitize::replaceHttpsUrls($html);

		$html = trim($html);

		/**
		 * Sometimes we just have some leftover attributes like an br. That the
		 * text is not considered empty. But actually there is no visual content.
		 *
		 * Is the text without tags is empty we just return an empty string.
		 * One exception are images cause they do not contain an textual value.
		 */

		if($html === '<br>'){

			$html = '';
		}

		return $html;
	}

	/**
	 * Clean the HTML of the provided DOM-node.
	 *
	 * <p>Reconstructs the HTML contents of the provided DOM-node in such a way
	 * that all invalid or disallowed HTML is removed from it.</p>
	 *
	 * @param DOMNode $Node
	 * @param array $filters
	 *
	 * @return string
	 */

	private static function _cleanHTML(DOMNode $Node, array $filters){

		$node_contents = '';
		$tag_name = '';

		if(isset($Node->tagName)){

			$tag_name = strtolower(trim($Node->tagName));
		}

		// Drop tags

		assert('isset($filters[\'tags_drop\']) && is_array($filters[\'tags_drop\'])');
		if(!isset($filters['tags_drop']) || !is_array($filters['tags_drop'])){

			$filters['tags_drop'] = [];
		}

		if($Node instanceof DOMComment || in_array($tag_name, $filters['tags_drop'])){

			return '';
		}

		// Clean node contents

		if(!isset($Node->childNodes) || $Node->childNodes->length == 0){

			// As no charset conversion is needed, nodeValue can be directly used

			$node_contents = html_entity_decode($Node->nodeValue, ENT_QUOTES, DEFAULT_CHARSET);

			// Reduce every possible combination of whitespaces to a single space-character

			$node_contents = preg_replace('/\s+/', ' ', $node_contents);

			// Reapply HTML entities

			$node_contents = htmlentities($node_contents, ENT_QUOTES, DEFAULT_CHARSET);
		}
		else{

			foreach($Node->childNodes as $Child){

				$node_contents .= self::_cleanHTML($Child, $filters);
			}
		}

		// Nameless and disallowed nodes (return content only, no tags)

		assert('isset($filters[\'tags\']) && is_array($filters[\'tags\'])');
		if(!isset($filters['tags']) || !is_array($filters['tags'])){

			$filters['tags'] = [];
		}

		if($tag_name == '' || !in_array($tag_name, $filters['tags'])){

			return $node_contents;
		}

		// Allowed nodes (return tag and content)

		else{

			$node_attributes = self::_cleanAttributes($Node, $filters);

			// If (after filtering) "a" or "img" elements have no attributes, discard them

			if($node_attributes == '' && ($tag_name == 'a' || $tag_name == 'img')){

				return $node_contents;
			}

			switch($tag_name){

				// Empty elements (c.q. self-closing)

				case 'br':
				case 'hr':
				case 'input':
				case 'img':

					return "<$tag_name$node_attributes>";

				break;

				// Elements with content

				default:

					// Empty "a" elements (c.q. page anchors) are allowed

					if(trim($node_contents) == '' && $tag_name != 'a') return '';

					return "<$tag_name$node_attributes>$node_contents</$tag_name>";
			}
		}
	}

	/**
	 * Clean the HTML attributes of the provided DOM-node.
	 *
	 * <p>Removes all invalid arguments from the HTML element
	 * represented by the DOM-node. In case the DOM-node is not an
	 * HTML-element, no attributes are present so no harm is done when
	 * executing this method.<br>
	 * The method returns the cleaned arguments. It does <strong>not</strong>
	 * return the entire contents of the provided DOM-node.</p>
	 *
	 * @param DOMNode $Node
	 * @param array $filters
	 *
	 * @return string
	 */

	private static function _cleanAttributes(DOMNode $Node, array $filters){

		if(!$Node->hasAttributes()) return '';

		$tag_name = strtolower(trim($Node->tagName));

		// No sense in continuing if no attribute filters are defined

		assert('isset($filters[\'attributes\']) && is_array($filters[\'attributes\'])');
		if(!isset($filters['attributes']) || !is_array($filters['attributes'])){

			return '';
		}

		// Check structure of the attributes filter

		if(!isset($filters['attributes'][$tag_name]) ||
			!is_array($filters['attributes'][$tag_name])){

			$filters['attributes'][$tag_name] = [];
		}

		if(!isset($filters['attributes']['*']) ||
			!is_array($filters['attributes']['*'])){

			$filters['attributes']['*'] = [];
		}

		if(!isset($filters['attributes']['*.schemes']) ||
			!is_array($filters['attributes']['*.schemes'])){

			$filters['attributes']['*.schemes'] = [];
		}

		if(!isset($filters['attributes']['*.types-href']) ||
			!is_array($filters['attributes']['*.types-href'])){

			$filters['attributes']['*.types-href'] = [];
		}

		$node_attributes = [];

		foreach($Node->attributes as $Attribute){

			$attribute_name = strtolower(trim($Attribute->name));
			$attribute_value = trim($Attribute->value);

			// Check if the attribute is allowed

			if(in_array($attribute_name, $filters['attributes'][$tag_name]) ||
				in_array($attribute_name, $filters['attributes']['*'])){

				// Attribute specific processing

				switch($attribute_name){

					// Remove Microsoft Office clutter from "class"

					case 'class':

						$attribute_value = (array) explode(' ', $attribute_value);

						foreach($attribute_value as $key => $value){

							if(substr($value, 0, 3) == 'Mso') unset($attribute_value[$key]);
						}

						if(count($attribute_value) == 0){

							continue 2;
						}
						else{

							$attribute_value = trim(implode(' ', $attribute_value));
						}

					break;

					// Process "href" and "src" attributes

					case 'href':
					case 'src':

						// Default to "relative" type

						$type = 'relative';

						// Schema filtering

						if(strpos($attribute_value, ':') !== false){

							$type = 'absolute';

							list($protocol) = explode(':', strtolower($attribute_value), 2);

							// Check for allowed schemes

							if(!in_array($protocol, $filters['attributes']['*.schemes'])){

								continue 2;
							}
						}

						// Href-specific "type" filtering

						if($attribute_name == 'href'){

							if($type != 'absolute'){

								// Anchors

								if(substr($attribute_value, 0, 1) == '#'){

									$type = 'anchor';
								}
							}

							// Check allowed types

							if(!in_array($type, $filters['attributes']['*.types-href'])){

								continue 2;
							}
						}

						break;
				}

				// Store Attribute

				$node_attributes[] = htmlspecialchars($attribute_name, ENT_QUOTES, DEFAULT_CHARSET)
					. '="' . htmlentities($attribute_value, ENT_QUOTES, DEFAULT_CHARSET) . '"';
			}
		}

		// Rebuild attribute string

		if(count($node_attributes) > 0){

			return ' ' . implode(' ', $node_attributes);
		}
		else{

			return '';
		}
	}

	/**
	 * Convert HTML to plain-text.
	 *
	 * <p>Convert (clean) HTML into plain-text. This method applies some very
	 * basic formatting to maintain a bit of the previous appearance. This
	 * method returns <em>pure</em> plain-text, including having all HTML
	 * entities decoded.</p>
	 *
	 * <p>This method <strong>only</strong> functions properly if it is fed
	 * proper HTML (c.q. passed through {@link HTML::cleanHTML()}).</p>
	 *
	 * <p>The optional argument {@link $strip_urls} is used to forcibly remove
	 * everything that looks like a URL (c.q. starts with "http://" or "www.")
	 * from the plain-text output.</p>
	 *
	 * <p>The optional argument {@link $html_format} is used to "HTML format"
	 * the returned plain-text. This entails all line-breaks are replaced with
	 * an HTML "br" element, HTML ntities are re-encoded and the entire string
	 * is wrapped in a single HTML paragraph element. This way the plain-text
	 * can be displayed (as plain-text) in an HTML document.</p>
	 *
	 * @param string $html
	 * @param boolean $strip_urls
	 * @param boolean $html_format
	 * @return string
	 */

	public static function convertToPlainText($html, $strip_urls = false, $html_format = false){

		$plain_text = $html;

		// Attempt to maintain some basic formatting

		$plain_text = str_replace('<li>', ' * ', $plain_text);
		$plain_text = str_replace(['</p>', '</h1>', '</h2>', '</h3>'],
			PHP_EOL . PHP_EOL, $plain_text);
		$plain_text = str_replace(['</ul>', '</ol>', '<br>', '</li>', '</h4>', '</h5>'],
			PHP_EOL, $plain_text);

		// Strip out all remaining HTML tags

		$plain_text = preg_replace('/<\/?.*>/iU', '', $plain_text);

		// Strip out everything that resembles a URL

		if($strip_urls){

			$pattern = '/(?:(?:https?\:\/\/(?:www\.)?)|(?:www\.))\S+\s?/i';
			$plain_text = preg_replace($pattern, '', $plain_text);
		}

		$plain_text = html_entity_decode($plain_text, ENT_QUOTES, DEFAULT_CHARSET);
		$plain_text = trim($plain_text);

		// Apply "HTML formatting" to the plain-text

		if($html_format){

			$plain_text = htmlentities($plain_text, ENT_QUOTES, DEFAULT_CHARSET);
			$plain_text = str_replace(PHP_EOL, '<br>', $plain_text);
			$plain_text = "<p>$plain_text</p>";
		}

		return $plain_text;
	}
}