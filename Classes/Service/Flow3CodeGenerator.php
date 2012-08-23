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
class Flow3CodeGenerator extends CodeGenerator implements CodeGeneratorInterface{

	/**
	 * @param \TYPO3\PackageBuilder\Domain\Model\PackageInterface $package
	 */
	public function build(\TYPO3\PackageBuilder\Domain\Model\PackageInterface $package) {
		$this->package = $package;

		if ($this->settings['packageConfiguration']['enableRoundtrip'] == 1) {
			$this->editModeEnabled = TRUE;
		}
		
		if (isset($this->settings['codeGeneration']['codeTemplateRootPath'])) {
			$this->codeTemplateRootPath = $this->settings['codeGeneration']['codeTemplateRootPath'];
		} else {
			throw new \TYPO3\PackageBuilder\Exception('No codeTemplateRootPath configured');
		}

		$packageDir = $this->package->getPackageDir();
		if(empty($packageDir)) {
			$this->package->setPackageDir($this->settings['codeGeneration']['packagesDir'] . '/' . $package->getKey() . '/');
		}
		//\TYPO3\FLOW3\var_dump($this->settings);

		// Base directory already exists at this point
		$this->packageDirectory = $this->package->getPackageDir();

		\TYPO3\PackageBuilder\Utility\Tools::replaceConstantsInConfiguration($this->settings);

		if (!is_dir($this->packageDirectory)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->packageDirectory);
		}

		$this->createDirectoryRecursively($this->packageDirectory . 'Log/');
		if(!file_exists($this->packageDirectory . 'Log/PackageBuilder.log')) {
			$this->writeFile($this->packageDirectory . 'Log/PackageBuilder.log','Log vom ' . date('Y-m-d H:i') . chr(10));
		}

		$logBackend = new \TYPO3\FLOW3\Log\Backend\FileBackend();
		$logBackend->setLogFileURL($this->packageDirectory . 'Log/PackageBuilder.log');
		$this->logger->addBackend($logBackend);

		$this->configurationDirectory = $this->packageDirectory . 'Configuration/';

		$this->privateResourcesDirectory = $this->packageDirectory . 'Resources/Private/';

		$this->classesDirectory = $this->packageDirectory . 'Classes/';
		$this->createDirectoryRecursively($this->classesDirectory);

		$this->metaDirectory = $this->packageDirectory . 'Meta/';
		$this->createDirectoryRecursively($this->metaDirectory);

		$this->renderMetaFiles();

		$this->createDirectoryRecursively($this->configurationDirectory);

		die('Success');

	}

	protected function renderMetaFiles() {
		$metaFileContent = $this->renderTemplate('Meta/Package.xmlt',array());
		$this->writeFile($this->packageDirectory  . 'Meta/Package.xml',$metaFileContent);
		$packageFileContent = $this->renderTemplate('Classes/Package.phpt',array());
		$this->writeFile($this->packageDirectory  . 'Classes/Package.php',$packageFileContent);
	}

}
