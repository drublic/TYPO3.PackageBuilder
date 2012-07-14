<?php
namespace TYPO3\PackageBuilder\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @FLOW3\Scope("singleton")
 */
class Flow3CodeGenerator extends CodeGenerator{

	/**
	 * @param \TYPO3\PackageBuilder\Domain\Model\Package $package
	 */
	public function build(\TYPO3\PackageBuilder\Domain\Model\Package $package) {
		$this->package = $package;
		if ($this->settings['extConf']['enableRoundtrip'] == 1) {
			$this->roundTripEnabled = TRUE;
		}
		
		if (isset($this->settings['codeTemplateRootPath'])) {
			$this->codeTemplateRootPath = $this->settings['codeTemplateRootPath'];
		} else {
			throw new \TYPO3\PackageBuilder\Exception('No codeTemplateRootPath configured');
		}

		// Base directory already exists at this point
		$this->packageDirectory = $this->package->getExtensionDir();
		if (!is_dir($this->packageDirectory)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->packageDirectory);
		}

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->packageDirectory, 'Configuration');

		$this->configurationDirectory = $this->packageDirectory . 'Configuration/';

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->packageDirectory, 'Resources/Private');

		$this->privateResourcesDirectory = $this->packageDirectory . 'Resources/Private/';

	}

}
