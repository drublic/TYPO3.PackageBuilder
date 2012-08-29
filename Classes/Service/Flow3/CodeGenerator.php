<?php
namespace TYPO3\PackageBuilder\Service\FLOW3;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 *
 * @FLOW3\Scope("singleton")
 */
class CodeGenerator extends \TYPO3\PackageBuilder\Service\CodeGenerator implements \TYPO3\PackageBuilder\Service\CodeGeneratorInterface {

	/**
	 * @var string
	 */
	protected $metaDirectory;

	/**
	 * @param \TYPO3\PackageBuilder\Domain\Model\PackageInterface $package
	 * @throws \TYPO3\PackageBuilder\Exception
	 * @return mixed|void
	 */
	public function build(\TYPO3\PackageBuilder\Domain\Model\PackageInterface $package) {
		$this->package = $package;

		$packageDir = $this->package->getPackageDir();
		if (empty($packageDir)) {
			$this->package->setPackageDir($this->settings['codeGeneration']['packagesDir'] . '/' . $this->package->getKey() . '/');
		}
			// \TYPO3\FLOW3\var_dump($this->settings);

			// Base directory already exists at this point
		$this->packageDirectory = $this->package->getPackageDir();

		\TYPO3\PackageBuilder\Utility\Tools::replaceConstantsInConfiguration($this->settings);

		if (!is_dir($this->packageDirectory)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->packageDirectory);
		}

		$this->createDirectoryRecursively($this->packageDirectory . 'Log/');
		if (!file_exists($this->packageDirectory . 'Log/PackageBuilder.log')) {
			$this->writeFile($this->packageDirectory . 'Log/PackageBuilder.log', 'Log vom ' . date('Y-m-d H:i') . chr(10));
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

		$this->createDirectoryRecursively($this->privateResourcesDirectory);

		die('Success');

	}

	protected function renderMetaFiles() {
		$metaFileContent = $this->renderFluidTemplate('Meta/Package.xmlt', array());
		$this->writeFile($this->packageDirectory . 'Meta/Package.xml', $metaFileContent);
		$packageFileContent = $this->renderPackageClass('Classes/Package.php', array());
		$this->writeFile($this->packageDirectory . 'Classes/Package.php', $packageFileContent);
	}

	protected function renderPackageClass() {
		$packageClassFileObject = $this->parser->parseFile($this->codeTemplateRootPath . 'Classes/Package.php');
		$packageClassFileObject->getNamespace()->setName($this->package->getNameSpace(), TRUE);
		$classComments = $packageClassFileObject->getFirstClass()->getAllComments();
		foreach($classComments as $line => $classComment) {
			$classComment = $this->renderFluidTemplateSource($classComment->toString(), array());
			$packageClassFileObject->getFirstClass()->replaceComment($classComment,$line);
		}
		$docComment = $packageClassFileObject->getFirstClass()->getDocComment()->getDescription();
		$docComment = $this->renderFluidTemplateSource($docComment, array());
		\TYPO3\FLOW3\var_dump($packageClassFileObject->getFirstClass()->getComment(),__FILE__ . __LINE__);
		$packageClassFileObject->getFirstClass()->setDescription($docComment);
		return $this->printer->renderFileObject($packageClassFileObject, TRUE);
	}

}
