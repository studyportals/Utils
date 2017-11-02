<?php

/**
 * @file Utils\Net.php
 * Network Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @copyright © 2014 StudyPortals B.V., all rights reserved.
 * @version 1.1.1
 */

namespace StudyPortals\Utils;

use StudyPortals\Exception\ExceptionHandler;

/**
 * Network utility methods.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class Net{

	/**
	 * Check if an IP-address falls within a given subnet.
	 *
	 * <p>The provided {@link $subnet} should be in CIDR-notation (e.g.
	 * "192.168.1.0/24"). This function only supports IPv4-addresses.</p>
	 *
	 * <p>The optional, pass-by-reference, parameter {@link $prefix} is used to
	 * pass back the length of the CIDR-prefix in the provided {@link $subnet}.
	 * This method validates the prefix, so it's best to use this parameter
	 * instead of relying on your own logic. A valid prefix-length is between
	 * <em>0</em> and <em>31</em>; for invalid prefixes <em>-1</em> is
	 * returned.<br>
	 * This information is useful for determining the "specificity" of the
	 * match. The higher the prefix the smaller the provided {@link $subnet}
	 * and as such the "closer" the match with the provided {@link $ip}.</p>
	 *
	 * @param string $subnet
	 * @param string $ip
	 * @param integer &$prefix
	 * @return bool
	 * @see http://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing#CIDR_notation
	 */

	public static function subnetMatch($subnet, $ip, &$prefix = -1){

		$ip = ip2long($ip);

		if(strpos($subnet, '/') !== false){

			list($net, $prefix) = explode('/', $subnet, 2);

			// Ensure the provided CIDR-prefix (e.g. subnet mask) is valid

			if(!ctype_digit($prefix) || $prefix > 32){

				$prefix = -1;

				return false;
			}

			$net = ip2long($net);
			$prefix = (int) $prefix;
		}
		else{

			$net = ip2long($subnet);
			$prefix = 32;
		}

		// Empty/invalid IP-address or invalid subnet provided

		if(empty($ip) || $net === false){

			return false;
		}

		// Empty prefix; all IP-addresses will match (short-circuit)

		if($prefix === 0){

			if(!empty($net)){

				ExceptionHandler::notice("Invalid CIDR-notation '$subnet',
					assuming '0.0.0.0/0'");
			}

			return true;
		}

		if($prefix < 32){

			$mask = 0xffffffff << (32 - $prefix);

			$net_mask = $net & $mask;
			$ip_mask = $ip & $mask;

			/*
			 * When properly specified in CIDR-notation, both the input subnet
			 * and the "masked" input subnet (as calculated above) should be
			 * equal. Non-fatal, but a strong indication someone wasn't paying
			 * attention while configuring the subnet...
			 */

			if($net_mask !== $net){

				ExceptionHandler::notice("Invalid CIDR-notation '$subnet',
					assuming " . long2ip($net_mask) . "/$prefix");
			}

			return (($net_mask ^ $ip_mask) === 0);
		}
		else{

			return ($net === $ip);
		}
	}
}