<?php

/**
 * @file Utils\XML.php
 * XML Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @copyright © 2012-2014 StudyPortals B.V., all rights reserved.
 * @version 1.0.3
 */

namespace StudyPortals\Utils;

/**
 * XML.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class XML{

	/**
	 * Load and verify an XML-file using SimpleXML.
	 *
	 * <p>The optional second parameter {@link $verify_dtd} can be used to
	 * disable verification of the XML DTD upon loading. By default the DTD is
	 * verified.</p>
	 *
	 * <p>When an error occurs this methods throws a XMLException.</p>
	 *
	 * @param string $xml_file
	 * @param boolean $verify_dtd
	 * @return \SimpleXMLElement
	 * @throws XMLException
	 */

	public static function loadSimpleXML($xml_file, $verify_dtd = true){

		$options = ($verify_dtd ? LIBXML_DTDVALID : null);

		libxml_clear_errors();
		$SimpleXML = @simplexml_load_file($xml_file, null, $options);
		$LibXMLError = libxml_get_last_error();

		if($SimpleXML === false || $LibXMLError instanceof \LibXMLError){

			if($LibXMLError instanceof \LibXMLError){

				$xml_base = basename($LibXMLError->file);

				throw new XMLException("{$LibXMLError->message} in $xml_base
					on line {$LibXMLError->line}");
			}

			else{

				$xml_base = basename($xml_file);

				throw new XMLException("SimpleXML failed to load $xml_base,
					unknown error");
			}
		}

		return $SimpleXML;
	}

	/**
	 * Load (and optionally validate) an XML-file using DOM/libXML.
	 *
	 * <p>Loads the provided {@link $xml_file} using the DOM/libXML extension
	 * and optionally verifies it against the provided {@link $xsd_file}.</p>
	 *
	 * <p>If the optional argument {@link $xinclude} is set to <em>true</em>,
	 * instruct libXML to process "XInclude"-directives as part of loading the
	 * document (prior to validating the XML-file).</p>
	 *
	 * <p>Throws an {@link XMLException} when any kind of error occurs while
	 * loading or verifying the XML-file.</p>
	 *
	 * @param string $xml_file
	 * @param string $xsd_file
	 * @param bool $xinclude
	 *
	 * @throws XMLException
	 * @return \DOMDocument
	 * @see http://www.w3.org/TR/xinclude/
	 */

	public static function loadDOMDocument(
		$xml_file, $xsd_file = null, $xinclude = false){

		libxml_clear_errors();

		$xml_base = basename($xml_file);
		$xsd_base = basename($xsd_file);

		/*
		 * DOMDocument::load() is picky about slashes on Windows,
		 * so ensure they're correct...
		 */

		$xml_file = @realpath($xml_file);
		if(!empty($xsd_file)) $xsd_file = @realpath($xsd_file);

		if($xml_file === false || $xsd_file === false){

			$file_base = basename(!$xml_file ? $xml_base : $xsd_base);

			throw new XMLException("DOM failed to load '$file_base'");
		}

		$Document = new \DOMDocument();
		$result = $Document->load($xml_file);

		// Optionally process "XInclude"-directives

		if($xinclude){

			$Document->xinclude();
		}

		if(!empty($xsd_file)){

			$validation = $Document->schemaValidate($xsd_file);
		}
		else{

			$validation = true;
		}

		if(!$result || !$validation){

			$LibXMLError = libxml_get_last_error();

			if($LibXMLError instanceof \LibXMLError){
				throw new XMLException("{$LibXMLError->message} in '$xml_base'
					on line {$LibXMLError->line}");
			}
			else{

				throw new XMLException("DOM failed to load '$xml_base',
					unknown error");
			}
		}

		return $Document;
	}
}