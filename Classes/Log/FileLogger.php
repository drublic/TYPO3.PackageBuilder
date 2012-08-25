<?php
namespace TYPO3\PackageBuilder\Log;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nico de Haen <mail@ndh-websolutions.de>
 *  All rights reserved
 *
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
 ***************************************************************/

/**
 * @package
 * @author Nico de Haen
 */

class FileLogger extends \TYPO3\FLOW3\Log\Logger {

	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if ($packageKey === NULL) {
			$backtrace = debug_backtrace(FALSE, 2);
			$className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : NULL;
			$methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : NULL;
			$lineNumber = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : NULL;
			$message .= "       " . $className . '::' . $methodName . '() [line ' . $lineNumber . ']';
			$explodedClassName = explode('\\', $className);
				// FIXME: This is not really the package key:
			$packageKey = isset($explodedClassName[1]) ? $explodedClassName[1] : '';
		}
		foreach ($this->backends as $backend) {
			$backend->append($message, $severity, $additionalData, $packageKey);
		}
	}

}
