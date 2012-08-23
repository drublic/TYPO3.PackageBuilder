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
	#protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 * @FLOW3\Inject
	 */
	protected $packageManager;


	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 * @FLOW3\Inject
	 *
	 */
	protected $logger;


	/**
	 * @var \TYPO3\PackageBuilder\Service\CodeGeneratorInterface
	 */

	protected $codeGenerator;

	/**
	 * Initialize action, and merge settings if needed
	 *
	 * @return void
	 */
	public function initializeAction() {
		if (!isset($this->settings['codeGeneration']['frameWork']) OR $this->settings['codeGeneration']['frameWork'] == 'FLOW3') {
			$this->settings['codeGeneration'] = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
				$this->settings['codeGeneration'],
				$this->settings['codeGeneration']['FLOW3']
			);
		} else {
			$this->settings['codeGeneration']['frameWork'] = 'TYPO3';
		}
		if (!empty($this->settings['extendIceSettings'])) {
			$this->settings = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
				$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Ice'),
				$this->settings
			);
			$this->codeGenerator = $this->objectManager->get('\TYPO3\PackageBuilder\Service\Flow3CodeGenerator');
		}
		$this->codeGenerator->injectSettings($this->settings);

	}



	/**
	 * Shows the Interface to create a new package
	 */
	public function newAction() {
		$this->indexAction();
		$this->forward('create');

	}

	/**
	 * list all packages
	 */
	public function listAction() {

	}

	/**
	 * create a new package based on the configuration
	 */
	public function createAction() {
		$packageKey = 'MyApp.TestPackage';
		$this->settings['packageConfiguration'] = $this->packageConfigurationManager->getPackageConfiguration($packageKey);
		$this->codeGenerator->injectSettings($this->settings);
		//$this->codeGenerator->injectLogger($this->logger);
		$this->logger->log('Test 123');
		if($this->settings['codeGeneration']['frameWork'] == 'TYPO3') {

		} else {
			$testPackage = $this->objectManager->get('TYPO3\PackageBuilder\Domain\Model\Package');
			$testPackage->setTitle('My Test Package');
			$testPackage->setDependencies(
				array(
					array(
						'minVersion' => '1.1',
						'maxVersion' => '9.9',
						'key' => 'TYPO3.Test'
					)
				)

			);
			$testPackage->setKey($packageKey);
			$this->codeGenerator->build($testPackage);
		}

	}

}

?>