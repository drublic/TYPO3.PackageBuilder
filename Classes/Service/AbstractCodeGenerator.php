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
abstract class AbstractCodeGenerator {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var string
	 */
	protected $packageDirectory;

	/**
	 * @var string
	 */
	protected $classesDirectory;

	/**
	 * @var string
	 */
	protected $configurationDirectory;

	/**
	 * @var string
	 */
	protected $privateResourcesDirectory;

	/**
	 * @var string
	 */
	protected $codeTemplateRootPath;

	/**
	 * @var bool
	 */
	protected $editModeEnabled = FALSE;

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $configurationManager;


	/**
	 * @var	 \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @FLOW3\Inject
	 *
	 */
	protected  $objectManager;

	/**
	 * @var \TYPO3\PackageBuilder\Service\ClassBuilder
	 * @FLOW3\Inject
	 *
	 */
	protected $classBuilder;

	/**
	 * @var \TYPO3\ParserApi\Service\Parser
	 * @FLOW3\inject
	 */
	protected $parser;

	/**
	 * @var \TYPO3\ParserApi\Service\Printer
	 * @FLOW3\inject
	 */
	protected $printer;

	/**
	 * @var \TYPO3\PackageBuilder\Log\FileLogger
	 */
	protected $logger;


	/**
	 * @var \TYPO3\PackageBuilder\Domain\Model\AbstractPackage
	 */
	protected $package;

	static public $defaultActions = array(
		'createAction',
		'deleteAction',
		'editAction',
		'listAction',
		'newAction',
		'showAction',
		'updateAction'
	);

	/**
	 * alle file types where a split token makes sense
	 * @var array
	 */
	protected $filesSupportingSplitToken = array(
		'php', //ext_tables, tca, localconf
		'sql',
		'txt' // Typoscript/**
	);

	//abstract public function initialize();

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
		if(!isset($this->settings['codeGeneration']['codeTemplateRootPath'])) {
			return;
		}
		try {
			$this->codeTemplateRootPath = $this->settings['codeGeneration']['codeTemplateRootPath'];
			if ($this->settings['packageConfiguration']['enableRoundtrip'] == 1) {
				$this->editModeEnabled = TRUE;
			}
		} catch(Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception\ConfigurationError($e->getMessage());
		}
	}

	/**
	 * @param \TYPO3\FLOW3\Log\Logger $logger
	 */
	public function injectLogger(\TYPO3\FLOW3\Log\Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * Render a template with variables
	 *
	 * @param string $filePath
	 * @param array $variables
	 * @return string
	 */
	protected function renderFluidTemplate($filePath, $variables) {
		$variables['package'] = $this->package;
		$standAloneView = $this->objectManager->get('TYPO3\\Fluid\\View\\StandaloneView');
		$templatePathAndFilename = $this->codeTemplateRootPath . $filePath;
		$standAloneView->setTemplatePathAndFilename($templatePathAndFilename);
		$standAloneView->assignMultiple($variables);
		return $standAloneView->render();
	}

	/**
	 * @param string $templateSource
	 * @param array $variables
	 * @return string
	 */
	protected function renderFluidTemplateSource($templateSource, $variables) {
		$variables['package'] = $this->package;
		$standAloneView = $this->objectManager->get('TYPO3\\Fluid\\View\\StandaloneView');
		$standAloneView->setTemplateSource($templateSource);
		$standAloneView->assignMultiple($variables);
		return $standAloneView->render();
	}

	/**
	 * wrapper for t3lib_div::writeFile
	 * checks for overwrite settings
	 *
	 * @param string $targetFile the path and filename of the targetFile (relative to extension dir)
	 * @param string $fileContents
	 */
	protected function writeFile($targetFile, $fileContents) {
		if ($this->editModeEnabled) {
			$overWriteMode = \TYPO3\PackageBuilder\Service\AbstractRoundTrip::getOverWriteSettingForPath($targetFile, $this->extension);
			if ($overWriteMode == -1) {
				return; // skip file creation
			}
			if ($overWriteMode == 1 && strpos($targetFile, 'Classes') === FALSE) { // classes are merged by the class builder
				$fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
				if ($fileExtension == 'html') {
					//TODO: We need some kind of protocol to be displayed after code generation
					$this->logger->log('File ' . basename($targetFile) . ' was not written. Template files can\'t be merged!', 'extension_builder', LOG_WARNING);
					return;
				} elseif (in_array($fileExtension, $this->filesSupportingSplitToken)) {
					$fileContents = $this->insertSplitToken($targetFile, $fileContents);
				}
			}
			else if (file_exists($targetFile) && $overWriteMode == 2) {
				// keep the existing file
				return;
			}
		}

		if (empty($fileContents)) {
			//t3lib_div::devLog('No file content! File ' . $targetFile . ' had no content', 'extension_builder', 0, $this->settings);
		}
		$success = file_put_contents($targetFile, $fileContents);
		if (!$success) {
			throw new \TYPO3\PackageBuilder\Exception('File ' . $targetFile . ' could not be created!');
		}
	}

	/**
	 * Inserts the token into the file content
	 * and preserves everything below the token
	 *
	 * @param $targetFile
	 * @param $fileContents
	 * @return mixed|string
	 */
	protected function insertSplitToken($targetFile, $fileContents) {
		$customFileContent = '';
		if (file_exists($targetFile)) {

			// merge the files means append everything behind the split token
			$existingFileContent = file_get_contents($targetFile);
			if (strpos($existingFileContent, \TYPO3\PackageBuilder\Service\AbstractRoundTrip::OLD_SPLIT_TOKEN)) {
				$existingFileContent = str_replace(\TYPO3\PackageBuilder\Service\AbstractRoundTrip::OLD_SPLIT_TOKEN, \TYPO3\PackageBuilder\Service\AbstractRoundTrip::SPLIT_TOKEN, $existingFileContent);
			}
			$fileParts = explode(\TYPO3\PackageBuilder\Service\AbstractRoundTrip::SPLIT_TOKEN, $existingFileContent);
			if (count($fileParts) == 2) {
				$customFileContent = str_replace('?>', '', $fileParts[1]);
			}
		}

		$fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

		if ($fileExtension == 'php') {
			$fileContents = str_replace('?>', '', $fileContents);
			$fileContents .= \TYPO3\PackageBuilder\Service\AbstractRoundTrip::SPLIT_TOKEN;
		}
		else if ($fileExtension == $this->locallangFileFormat) {
			//$fileContents = Tx_ExtensionBuilder_Utility_Tools::mergeLocallangXml($targetFile, $fileContents, $this->locallangFileFormat);
		}
		else {
			$fileContents .= "\n" . \TYPO3\PackageBuilder\Service\AbstractRoundTrip::SPLIT_TOKEN;
		}

		$fileContents .= rtrim($customFileContent);

		if ($fileExtension == 'php') {
			$fileContents .= "\n?>";
		}
		return $fileContents;
	}

	/**
	 * wrapper for t3lib_div::writeFile
	 * checks for overwrite settings
	 *
	 * @param string $targetFile the path and filename of the targetFile
	 * @param string $fileContents
	 */
	protected function uploadCopyMove($sourceFile, $targetFile) {
		$overWriteMode = \TYPO3\PackageBuilder\Service\AbstractRoundTrip::getOverWriteSettingForPath($targetFile, $this->package);
		if ($overWriteMode === -1) {
			// skip creation
			return;
		}
		if (!file_exists($targetFile) || ($this->editModeEnabled && $overWriteMode < 2)) {
			if(is_uploaded_file($sourceFile)) {
				upload_copy_move($sourceFile, $targetFile);
			} else {
				throw new \TYPO3\PackageBuilder\Exception('Tried to copy/move a not uploaded file');
			}

		}
	}

	/**
	 * wrapper for t3lib_div::mkdir_deep
	 * checks for overwrite settings
	 *
	 * @param string $directory base path
	 * @param string $deepDirectory
	 */
	protected function createDirectoryRecursively($directory) {
		$parts = explode($this->getPackageDirectory(),$directory);
		if(count($parts)>1) {
			$directory = $parts[1];
		} else {

			return;
		}
		$subDirectories = explode('/',$directory);
		$tmpBasePath = $this->getPackageDirectory();
		foreach($subDirectories as $subDirectory) {
			$overWriteMode = \TYPO3\PackageBuilder\Service\AbstractRoundTrip::getOverWriteSettingForPath($tmpBasePath . $subDirectory, $this->package);
			//throw new Exception($directory . $subDirectory . '/' . $overWriteMode);
			if ($overWriteMode === -1) {
				// skip creation
				return;
			}
			if (!is_dir($tmpBasePath . $subDirectory) || ($this->editModeEnabled && $overWriteMode < 2)) {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($tmpBasePath . $subDirectory);
			}
			$tmpBasePath .= $subDirectory . '/';
		}
	}

	protected function log($message, $severity = 1) {
		$a = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
		$path = explode('/',$a[0]['file']);
		$info = array_pop($path).': Line '.$a[0]['line'];
		$message .= $info . "\t" . $message;
		$this->logger->log($message, $severity);
	}

	/**
	 * @param string $packageDirectory
	 */
	public function setPackageDirectory($packageDirectory) {
		$this->packageDirectory = $packageDirectory;
	}

	/**
	 * @return string
	 */
	public function getPackageDirectory() {
		return $this->packageDirectory;
	}

}

?>