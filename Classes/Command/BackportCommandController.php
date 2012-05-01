<?php
namespace TYPO3\PackageBuilder\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3,
	TYPO3\FLOW3\Utility\Files as Files;

/**
 * Backport command controller for the TYPO3.PackageBuilder package
 *
 * @FLOW3\Scope("singleton")
 */
class BackportCommandController extends \TYPO3\Ice\Command\BackportCommandController {

	/**
	 * @var string
	 */
	protected $sourcePackageKey = 'TYPO3.PackageBuilder';

	/**
	 * @var string
	 */
	protected $extensionKey = 'extension_builder';

	/**
	 * @var string
	 */
	protected $moduleKey = 'extensionbuilder';

	/**
	 * @return void
	 */
	protected function processClassFiles() {
		$this->backporter->setReplacePairs(array(
			'* This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *' => '* This script belongs to the TYPO3 extension "extension_builder".        *',
			'for the TYPO3.PackageBuilder package' => 'for the extension_builder package',
			"CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Ice'" => "CONFIGURATION_TYPE_SETTINGS, 'ice', 'tx_ice'",
			'TYPO3.Ice' => 'tx_ice',
			'ConfigurationManager' => 'Tx_Extbase_Configuration_ConfigurationManager',
			'\\TYPO3\\FLOW3\\Utility\\Arrays::arrayMergeRecursiveOverrule' => 't3lib_div::array_merge_recursive_overrule',
			'\\TYPO3\\FLOW3\\MVC\\View\\NotFoundView' => 'Tx_Extbase_MVC_View_NotFoundView',
		));

		$this->backporter->setIncludeFilePatterns(array(
			'#^Classes/Controller/.*$#',
			'#^Classes/ViewHelpers/.*$#',
			'#^Classes/Service/.*php$#',
		));

		$this->backporter->setExcludeFilePatterns(array(
		));

		$this->backporter->setFileSpecificReplacePairs(array(
		));

		$this->backporter->processFiles(
			$this->packageManager->getPackage('TYPO3.PackageBuilder')->getPackagePath(),
			$this->settings['backport']['targetFolder']
		);

		$afterFileProcessing = array(
			$this->settings['backport']['targetFolder'] . 'Classes/Controller/StandardController.php' => array(
				'extends Tx_ExtensionBuilder_Controller_StandardController' => 'extends Tx_Ice_Controller_StandardController',
			)
		);
		foreach ($afterFileProcessing as $file => $replaces) {
			$this->afterProcessingStringReplaceInFiles($file, $replaces);
		}


//		\TYPO3\PackageBuilder\Utility\Extension::createAutoloadRegistryForExtension(
//			'extension_builder',
//			$this->settings['backport']['extensionBuilder']['targetFolder']
//		);
////		$this->createExtAutoloadFile($this->settings['backport']['extensionBuilder']['targetFolder'], 'extension_builder');
	}

	protected function copyResourceFolders() {
		Files::createDirectoryRecursively($this->settings['backport']['targetFolder'] . 'Resources/Private');
		Files::createDirectoryRecursively($this->settings['backport']['targetFolder'] . 'Resources/Public');
	}


}

?>