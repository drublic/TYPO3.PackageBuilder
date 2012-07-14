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
class PackageController extends \TYPO3\Ice\Controller\StandardController {

	/**
	 * @var \TYPO3\PackageBuilder\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $packageConfigurationManager;

	/**
	 * @var \TYPO3\PackageBuilder\Persistence\PersistenceManagerInterface
	 * @FLOW3\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var CodeGeneratorInterface
	 */

	protected $codeGenerator;

	/**
	 * Initialize action, and merge settings if needed
	 *
	 * @return void
	 */
	public function initializeAction() {
		if (defined('TYPO3_MODE')) {
			// we are running in TYPO3
			$this->settings['frameWork'] = 'TYPO3';
		} else {
			$this->settings['frameWork'] = 'FLOW3';
			if (!empty($this->settings['extendIceSettings'])) {
				$this->settings = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
					$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Ice'),
					$this->settings
				);
			}
		}
	}



	/**
	 * Shows the Interface to create a new package
	 */
	public function newAction() {
		$this->indexAction();

	}

	/**
	 * list all packages
	 */
	public function listAction() {

	}

	/**
	 * create a new package based on the configuration
	 */
	public function create() {
		if($this->settings['frameWork'] == 'TYPO3') {

		} else {
			$testConfig = $this->persistenceManager->load('test');
			$testModel = $this->objectManager->get('TYPO3\PackageBuilder\Domain\Model');
			$this->codeGenerator->build($testModel);
		}

	}

}

?>