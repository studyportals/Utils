<?php

/**
 * @file Utils\File.php
 * File Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @copyright © 2012-2013 StudyPortals B.V., all rights reserved.
 * @version 1.0.2
 */

namespace StudyPortals\Utils;

/**
 * File utilities.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class File{

	/**
	 * Get the extension of a file.
	 *
	 * <p>Returns an empty string in case the file has no extension (or if no
	 * file name was provided). The extension returned is always lowercase.</p>
	 *
	 * @param string $file
	 * @return string
	 */

	public static function getExtension($file){

		$file = trim($file);
		$extension = strtolower(substr(strrchr($file, '.'), 1));

		if($extension == $file){

			return '';
		}
		else{

			return $extension;
		}
	}

	/**
	 * Trim a path and ensure it has a (single) trailing-slash.
	 *
	 * @param string $path
	 * @return string
	 */

	public static function trimPath($path){

		return rtrim(trim($path), '/\\') . '/';
	}
}