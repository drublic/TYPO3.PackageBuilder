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
	 * @var string
	 */
	protected $targetFramework = 'FLOW3';


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
		if ($this->request->hasArgument('frameWork')) {
			$this->targetFramework = $this->request->getArgument('frameWork');
		} elseif (!isset($this->settings['codeGeneration']['frameWork']) OR $this->settings['codeGeneration']['frameWork'] == 'FLOW3') {
			$this->targetFramework = 'FLOW3';
		} else {
			$this->targetFramework = 'TYPO3';
		}
		$this->settings['codeGeneration'] = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
			$this->settings['codeGeneration'],
			$this->settings['codeGeneration'][$this->targetFramework]
		);
		if (!empty($this->settings['extendIceSettings'])) {
			$this->settings = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule(
				$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Ice'),
				$this->settings
			);
		}
		if(!class_exists('\\TYPO3\\PackageBuilder\\Service\\' . ucfirst(strtolower($this->targetFramework)). 'CodeGenerator')) {
			throw new \TYPO3\PackageBuilder\Exception\MissingComponentException('No CodeGenerator class for target framework ' . $this->targetFramework . ' found');
		}
		$this->codeGenerator = $this->objectManager->get('\\TYPO3\\PackageBuilder\\Service\\' . ucfirst(strtolower($this->targetFramework)). 'CodeGenerator');
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
	public function createAction() {
		file_put_contents($this->settings['log']['backendOptions']['logFileURL'],'');
		$packageKey = 'MyApp.TestPackage';
		$this->settings['packageConfiguration'] = $this->packageConfigurationManager->getPackageConfiguration($packageKey);
		$this->codeGenerator->injectSettings($this->settings);
			// $this->codeGenerator->injectLogger($this->logger);
		if ($this->targetFramework == 'TYPO3') {
			$testPackage = $this->objectManager->get('TYPO3\PackageBuilder\Domain\Model\Extension');
		} else {
			/* @var \TYPO3\PackageBuilder\Domain\Model\Package $testPackage */
			$testPackage = $this->objectManager->get('TYPO3\PackageBuilder\Domain\Model\Package');
		}
		$testPackage->setTitle('My Test Package');
		$testPackage->setKey($packageKey);
		$testPackage->setBaseDir($this->settings['codeGeneration']['packagesDir']);
		$person = new \TYPO3\PackageBuilder\Domain\Model\Person();
		$person->setName('Max de Haen');
		$person->setEmail('mail@test.de');
		$testPackage->setPersons(array($person));
		$this->codeGenerator->build($testPackage);
		die('<pre>' . file_get_contents($this->settings['log']['backendOptions']['logFileURL']). '</pre>');
	}

}

?>