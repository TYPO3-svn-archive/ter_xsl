<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Susanne Moog <s.moog@neusta.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * The address controller for the Address package
 *
 * @version $Id: $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_TerXsl_Utility_Cli {

	/**
	 * This method is used to log messages on the console.
	 *
	 * $param mixed $message: the message to be outputted on the console
	 * @return void
	 */
	public static function log($message = '') {
		if (is_array($message) || is_object($message)) {
			print_r($message);
		} elseif (is_bool($message) || $message === NULL) {
			var_dump($message);
		} else {
			print $message . chr(10);
		}
	}

	
}

?>