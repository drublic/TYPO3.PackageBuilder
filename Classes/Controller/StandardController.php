<?php
namespace TYPO3\PackageBuilder\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use \TYPO3\FLOW3\Configuration\ConfigurationManager as ConfigurationManager;

/**
 * Standard controller for the TYPO3.PackageBuilder package
 *
 * @FLOW3\Scope("singleton")
 */
class StandardController extends \TYPO3\Ice\Controller\StandardController {

	/**
	 * Initialize action, and merge settings if needed
	 *
	 * @return void
	 */
	public function initializeAction() {
		if (!empty($this->settings['extendIceSettings'])) {
			$this->settings = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
				$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Ice'),
				$this->settings
			);
		}
	}

}

?>