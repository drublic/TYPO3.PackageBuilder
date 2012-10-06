<?php
namespace TYPO3\PackageBuilder\Service\TYPO3;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */
use TYPO3\PackageBuilder\Domain\Model;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @FLOW3\Scope("singleton")
 */
class CodeGenerator extends \TYPO3\PackageBuilder\Service\AbstractCodeGenerator {

	/**
	 * @var Model\Extension
	 */
	protected $extension;

	/**
	 * @var string
	 */
	protected $extensionDirectory;

	protected $locallangFileFormat = 'xlf';

	/**
	 * @var \TYPO3\PackageBuilder\Service\TYPO3\ClassBuilder
	 * @FLOW3\Inject
	 */
	protected $classBuilder;

	/**
	 * The entry point to the class
	 *
	 * @param Model\PackageInterface $extension
	 * @return void
	 */
	public function build(Model\PackageInterface $extension) {
		$this->logger->log('Build started');
		$this->extension = $extension;
			// Base directory already exists at this point
		$this->setExtensionDirectory($this->extension->getExtensionDir() . '/');
		if (!is_dir($this->extensionDirectory)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->extensionDirectory);
			$this->logger->log('Extension Dir created: ' . $this->extensionDirectory);
		}

		$this->configurationDirectory = $this->extensionDirectory . 'Configuration/';

		$this->privateResourcesDirectory = $this->extensionDirectory . 'Resources/Private/';

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->configurationDirectory);

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->privateResourcesDirectory);

		$this->generateYamlSettingsFile();

		$this->generateExtensionFiles();

		$this->generatePluginFiles();

		$this->copyStaticFiles();

		$this->generateTCAFiles();

		$this->generateTyposcriptFiles();

		$this->generateHtaccessFile();

		$this->generateLocallangFiles();

		$this->generateDocumentationFiles();

		$this->generateDomainObjectRelatedFiles();

		return $extension;

	}

	protected function generateYamlSettingsFile() {

		if (!file_exists($this->configurationDirectory . 'ExtensionBuilder/settings.yaml')) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->configurationDirectory . 'ExtensionBuilder');
			$fileContents = $this->generateYamlSettings();
			$targetFile = $this->configurationDirectory . 'ExtensionBuilder/settings.yaml';
			$this->writeFile($targetFile, $fileContents);
		}

	}

	protected function generateExtensionFiles() {
			// Generate ext_emconf.php, ext_tables.* and TCA definition
		$extensionFiles = array('ext_emconf.php', 'ext_tables.php', 'ext_tables.sql');
		foreach ($extensionFiles as $extensionFile) {
			try {
				$fileContents = $this->renderFluidTemplate(\TYPO3\PackageBuilder\Utility\Tools::underscoredToLowerCamelCase($extensionFile) . '.tmpl', array('extension' => $this->extension, 'locallangFileFormat' => $this->locallangFileFormat));
				$this->writeFile($this->extensionDirectory . $extensionFile, $fileContents);
				$this->logger->log('Generated ' . $extensionFile);
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception\WriteAccessError('Could not write ' . $extensionFile . ', error: ' . $e->getMessage());
			}
		}

	}

	protected function generatePluginFiles() {
		if ($this->extension->getPlugins()) {
			try {
				$fileContents = $this->renderFluidTemplate(\TYPO3\PackageBuilder\Utility\Tools::underscoredToLowerCamelCase('ext_localconf.php.tmpl'), array('extension' => $this->extension));
				$this->writeFile($this->extensionDirectory . 'ext_localconf.php', $fileContents);
				$this->logger->log('Generated ext_localconf.php');
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception\WriteAccessError('Could not write ext_localconf.php. Error: ' . $e->getMessage());
			}
			try {
				$currentPluginKey = '';
				foreach ($this->extension->getPlugins() as $plugin) {
					if ($plugin->getSwitchableControllerActions()) {
						if (!is_dir($this->configurationDirectory . 'FlexForms')) {
							$this->createDirectoryRecursively($this->configurationDirectory . 'FlexForms');
						}
						$currentPluginKey = $plugin->getKey();
						$fileContents = $this->renderFluidTemplate('Configuration/Flexforms/flexform.xmlt', array('plugin' => $plugin));
						$this->writeFile($this->extensionDirectory . 'Configuration/FlexForms/flexform_' . $currentPluginKey . '.xml', $fileContents);
						$this->logger->log('Generated flexform_' . $currentPluginKey . '.xml');
					}
				}
			} catch (\TYPO3\PackageBuilder\Exception\WriteAccessError $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not write  flexform_' . $currentPluginKey . '.xml. Error: ' . $e->getMessage());
			}
		}
	}

	protected function generateTCAFiles() {
		// Generate TCA
		try {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->extensionDirectory, 'Configuration/TCA');

			$domainObjects = $this->extension->getDomainObjects();

			foreach ($domainObjects as $domainObject) {
				if (!is_dir($this->configurationDirectory . 'TCA')) {
					$this->createDirectoryRecursively($this->configurationDirectory . 'TCA');
				}
				$fileContents = $this->generateTCA($domainObject);
				$this->writeFile($this->configurationDirectory . 'TCA/' . $domainObject->getName() . '.php', $fileContents);
				$this->logger->log('Generated ' . 'TCA/' . $domainObject->getName() . '.php');
			}

		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not generate Tca.php, error: ' . $e->getMessage() . $e->getFile());
		}
	}

	protected function generateLocallangFiles() {
		// Generate locallang*.xml files
		try {
			$this->languageDirectory = $this->privateResourcesDirectory . 'Language/';
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->languageDirectory);
			$fileContents = $this->generateLocallangFileContent();
			$this->writeFile($this->languageDirectory . 'locallang.' . $this->locallangFileFormat, $fileContents);
			$fileContents = $this->generateLocallangFileContent('_db');
			$this->writeFile($this->languageDirectory . 'locallang_db.' . $this->locallangFileFormat, $fileContents);
			if ($this->extension->hasBackendModules()) {
				foreach ($this->extension->getBackendModules() as $backendModule) {
					$fileContents = $this->generateLocallangFileContent('_mod', 'backendModule', $backendModule);
					$this->writeFile($this->languageDirectory . 'locallang_' . $backendModule->getKey() . '.' . $this->locallangFileFormat, $fileContents);
					$this->logger->log('Generated Resources/Private/Language/locallang_' . $backendModule->getKey() . '.' . $this->locallangFileFormat);
				}

			}
			foreach ($this->extension->getDomainObjects() as $domainObject) {
				$fileContents = $this->generateLocallangFileContent('_csh', 'domainObject', $domainObject);
				$this->writeFile($this->languageDirectory . 'locallang_csh_' . $domainObject->getDatabaseTableName() . '.' . $this->locallangFileFormat, $fileContents);
				$this->logger->log('Generated Resources/Private/Language/locallang_csh_' . $domainObject->getDatabaseTableName()  . '.' . $this->locallangFileFormat);

			}
		} catch (\TYPO3\PackageBuilder\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not generate locallang files, error: ' . $e->getMessage());
		}
	}

	protected function generateTemplateFiles($templateSubFolder = '') {
		$templateRootFolder = 'Resources/Private/' . $templateSubFolder;
		$absoluteTemplateRootFolder = $this->extensionDirectory . $templateRootFolder;

		$hasTemplates = FALSE;
		//$actionsUsingFormFieldsPartial = array('edit', 'new');
		//$actionsUsingPropertiesPartial = array('show');
		foreach ($this->extension->getDomainObjects() as $domainObject) {
			// Do not generate anyting if $domainObject is not an Entity or has no actions defined
			if (!$domainObject->getEntity() || (count($domainObject->getActions()) == 0)) {
				continue;
			}
			$domainTemplateDirectory = $absoluteTemplateRootFolder . 'Templates/' . $domainObject->getName() . '/';
			foreach ($domainObject->getActions() as $action) {
				if ($action->getNeedsTemplate()
						&& file_exists($this->codeTemplateRootPath . $templateRootFolder . 'Templates/' . $action->getName() . '.htmlt')

				) {
					$hasTemplates = TRUE;
					$this->createDirectoryRecursively($this->extensionDirectory.$templateRootFolder . 'Templates/' . $domainObject->getName());
					$fileContents = $this->generateDomainTemplate($templateRootFolder . 'Templates/', $domainObject, $action);
					$this->writeFile($domainTemplateDirectory . ucfirst($action->getName()) . '.html', $fileContents);
					// generate partials for formfields
					if ($action->getNeedsForm()) {
						$this->createDirectoryRecursively($absoluteTemplateRootFolder . 'Partials');
						$partialDirectory = $absoluteTemplateRootFolder . 'Partials/';
						$this->createDirectoryRecursively($partialDirectory . $domainObject->getName());
						$formfieldsPartial = $partialDirectory . $domainObject->getName() . '/FormFields.html';
						$fileContents = $this->generateDomainFormFieldsPartial($templateRootFolder . 'Partials/', $domainObject);
						$this->writeFile($formfieldsPartial, $fileContents);
						if (!file_exists($partialDirectory . 'FormErrors.html')) {
							$this->writeFile($partialDirectory . 'FormErrors.html', $this->generateFormErrorsPartial($templateRootFolder . 'Partials/'));
						}
					}
					// generate partials for properties
					if ($action->getNeedsPropertyPartial()) {
						$this->createDirectoryRecursively($absoluteTemplateRootFolder . 'Partials');
						$partialDirectory = $absoluteTemplateRootFolder . 'Partials/';
						$this->createDirectoryRecursively($partialDirectory . $domainObject->getName());
						$propertiesPartial = $partialDirectory . $domainObject->getName() . '/Properties.html';
						$fileContents = $this->generateDomainPropertiesPartial($templateRootFolder . 'Partials/', $domainObject);
						$this->writeFile($propertiesPartial, $fileContents);
					}
				}
			}
		}
		if ($hasTemplates) {
			// Generate Layouts directory
			$this->createDirectoryRecursively($absoluteTemplateRootFolder . 'Layouts');
			$layoutsDirectory = $absoluteTemplateRootFolder . 'Layouts/';
			$this->writeFile($layoutsDirectory . 'Default.html', $this->generateLayout($templateRootFolder . 'Layouts/'));
		}
	}

	protected function generateTyposcriptFiles() {
		if ($this->extension->hasPlugins() || $this->extension->hasBackendModules()) {
			// Generate TypoScript setup
			try {
				$this->createDirectoryRecursively($this->extensionDirectory . 'Configuration/TypoScript');
				$typoscriptDirectory = $this->extensionDirectory . 'Configuration/TypoScript/';
				$fileContents = $this->generateTyposcriptSetup();
				$this->writeFile($typoscriptDirectory . 'setup.txt', $fileContents);
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate typoscript setup, error: ' . $e->getMessage());
			}

			// Generate TypoScript constants
			try {
				$typoscriptDirectory = $this->extensionDirectory . 'Configuration/TypoScript/';
				$fileContents = $this->generateTyposcriptConstants();
				$this->writeFile($typoscriptDirectory . 'constants.txt', $fileContents);
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate typoscript constants, error: ' . $e->getMessage());
			}
		}

		// Generate Static TypoScript
		try {
			if ($this->extension->getDomainObjectsThatNeedMappingStatements()) {
				$fileContents = $this->generateStaticTyposcript();
				$this->writeFile($this->extensionDirectory . 'ext_typoscript_setup.txt', $fileContents);
			}
		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not generate static typoscript, error: ' . $e->getMessage());
		}
	}

	protected function generateDomainObjectRelatedFiles() {

		if (count($this->extension->getDomainObjects()) > 0) {
			$this->classBuilder->initialize($this->extension, $this->editModeEnabled);
				// Generate Domain Model
			try {
				$domainModelDirectory = 'Classes/Domain/Model/';
				$this->createDirectoryRecursively($this->extensionDirectory . $domainModelDirectory);

				$domainRepositoryDirectory = 'Classes/Domain/Repository/';
				$this->createDirectoryRecursively($this->extensionDirectory . $domainRepositoryDirectory);

				$domainModelTestsDirectory = $this->extensionDirectory . 'Tests/Unit/Domain/Model/';
				$this->createDirectoryRecursively($domainModelTestsDirectory);

				$this->createDirectoryRecursively($this->extensionDirectory . 'Tests/Unit/Controller');
				$crudEnabledControllerTestsDirectory = $this->extensionDirectory . 'Tests/Unit/Controller/';
				foreach ($this->extension->getDomainObjects() as $domainObject) {
					$destinationFile = $domainModelDirectory . $domainObject->getName() . '.php';
					if ($this->editModeEnabled && RoundTrip::getOverWriteSettingForPath($destinationFile, $this->extension) > 0) {
						$mergeWithExistingClass = TRUE;
					} else {
						$mergeWithExistingClass = FALSE;
					}
					$fileContents = $this->generateDomainObjectCode($domainObject, $mergeWithExistingClass);
					$this->writeFile($this->extensionDirectory . $destinationFile, $fileContents);
					$this->logger->log('Generated ' . $domainObject->getName() . '.php');
					$this->extension->setMD5Hash($this->extensionDirectory . $destinationFile);
					if ($domainObject->isAggregateRoot()) {
						$iconFileName = 'aggregate_root.gif';
					} elseif ($domainObject->isEntity()) {
						$iconFileName = 'entity.gif';
					} else {
						$iconFileName = 'value_object.gif';
					}
					$this->copy($this->codeTemplateRootPath . 'Resources/Private/Icons/' . $iconFileName, $this->iconsDirectory . $domainObject->getDatabaseTableName() . '.gif');
					if ($domainObject->isAggregateRoot()) {
						$destinationFile = $domainRepositoryDirectory . $domainObject->getName() . 'Repository.php';
						if ($this->editModeEnabled && RoundTrip::getOverWriteSettingForPath($destinationFile, $this->extension) > 0) {
							$mergeWithExistingClass = TRUE;
						} else {
							$mergeWithExistingClass = FALSE;
						}
						$fileContents = $this->generateDomainRepositoryCode($domainObject, $mergeWithExistingClass);
						$this->writeFile($this->extensionDirectory . $destinationFile, $fileContents);
						$this->logger->log('Generated ' . $domainObject->getName() . 'Repository.php', 'extension_builder', 0);
						$this->extension->setMD5Hash($this->extensionDirectory . $destinationFile);
					}
					// Generate basic UnitTests
					$fileContents = $this->generateDomainModelTests($domainObject);
					$this->writeFile($domainModelTestsDirectory . $domainObject->getName() . 'Test.php', $fileContents);
				}
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate domain model, error: ' . $e->getMessage());
			}

				// Generate Action Controller
			try {
				$this->createDirectoryRecursively($this->extensionDirectory . 'Classes/Controller');
				$controllerDirectory = 'Classes/Controller/';
				foreach ($this->extension->getDomainObjectsForWhichAControllerShouldBeBuilt() as $domainObject) {
					$destinationFile = $controllerDirectory . $domainObject->getName() . 'Controller.php';
					if ($this->editModeEnabled && RoundTrip::getOverWriteSettingForPath($destinationFile, $this->extension) > 0) {
						$mergeWithExistingClass = TRUE;
					} else {
						$mergeWithExistingClass = FALSE;
					}
					$fileContents = $this->generateActionControllerCode($domainObject, $mergeWithExistingClass);
					$this->writeFile($this->extensionDirectory . $destinationFile, $fileContents);
					$this->logger->log('Generated ' . $domainObject->getName() . 'Controller.php', 'extension_builder');
					$this->extension->setMD5Hash($this->extensionDirectory . $destinationFile);

					// Generate basic UnitTests
					$fileContents = $this->generateControllerTests($domainObject->getName() . 'Controller', $domainObject);
					$this->writeFile($crudEnabledControllerTestsDirectory . $domainObject->getName() . 'ControllerTest.php', $fileContents);
				}
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate action controller, error: ' . $e->getMessage());
			}

			// Generate Domain Templates
			try {
				if ($this->extension->getPlugins()) {
					$this->generateTemplateFiles();
				}
				if ($this->extension->getBackendModules()) {
					$this->generateTemplateFiles('Backend/');
				}
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate domain templates, error: ' . $e->getMessage());
			}

			try {
				$settings = $this->extension->getSettings();
				if (isset($settings['createAutoloadRegistry']) && $settings['createAutoloadRegistry'] == TRUE) {
					Tx_Extbase_Utility_Extension::createAutoloadRegistryForExtension($this->extension->getExtensionKey(), $this->extensionDirectory);
				}
			} catch (\Exception $e) {
				throw new \TYPO3\PackageBuilder\Exception('Could not generate ext_autoload.php, error: ' . $e->getMessage());
			}

		} else {
			$this->logger->log('No domainObjects in this extension');
		}
	}

	protected function generateHtaccessFile() {
		// Generate Private Resources .htaccess
		try {
			$fileContents = $this->generatePrivateResourcesHtaccess();
			$this->writeFile($this->privateResourcesDirectory . '.htaccess', $fileContents);
		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not create private resources folder, error: ' . $e->getMessage());
		}
	}

	protected function copyStaticFiles() {
		try {
			$this->copy($this->codeTemplateRootPath . 'Resources/Private/Icons/ext_icon.gif', $this->extensionDirectory . 'ext_icon.gif');
		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not copy ext_icon.gif, error: ' . $e->getMessage());
		}

		// insert a manual template
		try {
			if (!file_exists($this->extensionDirectory . 'doc/manual.sxw') && file_exists($this->codeTemplateRootPath . 'doc/manual.sxw')) {
				$this->createDirectoryRecursively($this->extensionDirectory . 'doc');
				$this->copy($this->codeTemplateRootPath . 'doc/manual.sxw', $this->extensionDirectory . 'doc/manual.sxw');
			}
		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('An error occurred when copying the manual template: ' . $e->getMessage() . $e->getFile());
		}

		try {
			$this->createDirectoryRecursively($this->extensionDirectory . 'Resources/Public');
			$publicResourcesDirectory = $this->extensionDirectory . 'Resources/Public/';
			$this->createDirectoryRecursively($publicResourcesDirectory . 'Icons');
			$this->iconsDirectory = $publicResourcesDirectory . 'Icons/';
			$this->copy($this->codeTemplateRootPath. 'Resources/Private/Icons/relation.gif', $this->iconsDirectory . 'relation.gif');
		} catch (\Exception $e) {
			throw new \TYPO3\PackageBuilder\Exception('Could not create public resources folder, error: ' . $e->getMessage());
		}


	}

	/**
	 * generate the folder structure for reST documentation
	 */
	protected function generateDocumentationFiles() {
		$documentationDirectory = $this->codeTemplateRootPath. 'Documentation/';
		$this->createDirectoryRecursively($this->extensionDirectory.'Documentation/');
		$docFiles = array();
		$docFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($documentationDirectory, NULL, TRUE, $docFiles);
		foreach ($docFiles as $docFile) {
			if (is_dir($docFile)) {
				$this->createDirectoryRecursively($this->extensionDirectory . 'Documentation/' . $docFile);
				$this->logger->log('Copied Documentation/' . $docFile);
			} else if (strpos($docFile, '.rstt') === FALSE) {
				$target = str_replace($documentationDirectory, $this->extensionDirectory . 'Documentation/', $docFile);
				$this->createDirectoryRecursively(dirname($target));
				$this->copy($docFile, $target);
				$this->logger->log('Copied ' . 'Documentation/' . str_replace($documentationDirectory, '', $docFile));
			}
		}
		$this->copy($this->codeTemplateRootPath . 'Readme.rst', $this->extensionDirectory . 'Readme.rst');
		$fileContents = $this->renderFluidTemplate('Documentation/Index.rstt', array('extension' => $this->extension));
		$this->writeFile($this->extensionDirectory . 'Documentation/Index.rst', $fileContents);
		$this->logger->log('Generated Documentation/Index.rst');

	}



	/**
	 * Generates the code for the controller class
	 * Either from ectionController template or from class partial
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 */
	public function generateActionControllerCode(Model\DomainObject $domainObject, $mergeWithExistingClass) {
		$controllerClassObject = $this->classBuilder->generateControllerClassObject($domainObject, $mergeWithExistingClass);
		// returns a class object if an existing class was found
		if ($controllerClassObject) {
			$classDocComment = $this->renderDocComment($controllerClassObject, $domainObject);
			$controllerClassObject->setDocComment($classDocComment);

			return $this->renderFluidTemplate('Classes/class.php.tmpl', array('domainObject' => $domainObject, 'extension' => $this->extension, 'classObject' => $controllerClassObject));
		} else {
			throw new \TYPO3\PackageBuilder\Exception('Class file for controller could not be generated');
		}
	}

	/**
	 * Generates the code for the domain model class
	 * Either from domainObject template or from class partial
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 */
	public function generateDomainObjectCode(Model\DomainObject $domainObject, $mergeWithExistingClass) {
		$modelClassObject = $this->classBuilder->generateModelClassObject($domainObject, $mergeWithExistingClass);
		if ($modelClassObject) {
			$classDocComment = $this->renderDocComment($modelClassObject, $domainObject);
			$modelClassObject->setDocComment($classDocComment);
			return $this->renderFluidTemplate('Classes/class.php.tmpl', array('domainObject' => $domainObject, 'package' => $this->package, 'classObject' => $modelClassObject));
		} else {
			throw new \TYPO3\PackageBuilder\Exception('Class file for domain object could not be generated');
		}

	}

	/**
	 * Generates the code for the repository class
	 * Either from domainRepository template or from class partial
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 */
	public function generateDomainRepositoryCode(Model\DomainObject $domainObject, $mergeWithExistingClass) {
		$repositoryClassObject = $this->classBuilder->generateRepositoryClassObject($domainObject, $mergeWithExistingClass);
		if ($repositoryClassObject) {
			$classDocComment = $this->renderDocComment($repositoryClassObject, $domainObject);
			$repositoryClassObject->setDocComment($classDocComment);

			return $this->renderFluidTemplate('Classes/class.php.tmpl', array('domainObject' => $domainObject, 'classObject' => $repositoryClassObject));
		} else {
			throw new \TYPO3\PackageBuilder\Exception('Class file for repository could not be generated');
		}
	}

	/**
	 * Generate the tests for a model
	 *
	 * @param Model\DomainObject $domainObject
	 *
	 * @return string
	 */
	public function generateDomainModelTests(Model\DomainObject $domainObject) {
		return $this->renderFluidTemplate('Tests/DomainModelTest.php.tmpl', array('extension' => $this->extension, 'domainObject' => $domainObject));
	}

	/**
	 * Generate the tests for a CRUD-enabled controller
	 *
	 * @param array $extensionProperties
	 * @param string $controllerName
	 * @param Model\DomainObject $domainObject
	 *
	 * @return string
	 */
	public function generateControllerTests($controllerName, Model\DomainObject $domainObject) {
		return $this->renderFluidTemplate('Tests/ControllerTest.php.tmpl', array('extension' => $this->extension, 'controllerName' => $controllerName, 'domainObject' => $domainObject));
	}

	/**
	 * generate a docComment for class files. Add a license header if none found
	 * @param unknown_type $classObject
	 * @param unknown_type $domainObject
	 */
	protected function renderDocComment($classObject, $domainObject) {
		if (!$classObject->hasDocComment()) {
			$docComment = $this->renderFluidTemplate('Partials/Classes/classDocComment.php.tmpl', array('domainObject' => $domainObject, 'extension' => $this->extension, 'classObject' => $classObject));
		} else {
			$docComment = $classObject->getDocComment();
		}
		$precedingBlock = $classObject->getPrecedingBlock();

		if (empty($precedingBlock) || strpos($precedingBlock, 'GNU General Public License') < 1) {

			$licenseHeader = $this->renderFluidTemplate('Partials/Classes/licenseHeader.php.tmpl', array('persons' => $this->extension->getPersons()));
			$docComment = "\n" . $licenseHeader . "\n\n\n" . $docComment;
		} else {
			$docComment = $precedingBlock . "\n" . $docComment;
		}
		return $docComment;
	}

	/**
	 * Generates the content of an Action template
	 * For some Actions default templates are provided, other Action templates will just be created emtpy
	 *
	 * @param string $templateRootFolder
	 * @param Model\DomainObject $domainObject
	 * @param Model\DomainObject\Action $action
	 * @return string The generated Template code (might be empty)
	 */
	public function generateDomainTemplate($templateRootFolder, Model\DomainObject $domainObject, Model\DomainObject\Action $action) {
		return $this->renderFluidTemplate($templateRootFolder . $action->getName() . '.htmlt', array('domainObject' => $domainObject, 'action' => $action, 'extension' => $this->extension));
	}

	public function generateDomainFormFieldsPartial($templateRootFolder, Model\DomainObject $domainObject) {
		return $this->renderFluidTemplate($templateRootFolder . 'formFields.htmlt', array('extension' => $this->extension, 'domainObject' => $domainObject));
	}

	public function generateDomainPropertiesPartial($templateRootFolder, Model\DomainObject $domainObject) {
		return $this->renderFluidTemplate($templateRootFolder . 'properties.htmlt', array('extension' => $this->extension, 'domainObject' => $domainObject));
	}

	public function generateFormErrorsPartial($templateRootFolder) {
		return $this->renderFluidTemplate($templateRootFolder . 'formErrors.htmlt', array('extension' => $this->extension));
	}

	public function generateLayout($templateRootFolder) {
		return $this->renderFluidTemplate($templateRootFolder . 'default.htmlt', array('extension' => $this->extension));
	}


	/**
	 * @param string $fileNameSuffix
	 * @param string $variableName
	 * @param null $variable
	 * @return mixed
	 */
	protected function generateLocallangFileContent($fileNameSuffix = '', $variableName = '', $variable = NULL) {
		$targetFile = 'Resources/Private/Language/locallang' . $fileNameSuffix;

		$variableArray = array('extension' => $this->extension);
		if (strlen($variableName) > 0) {
			$variableArray[$variableName] = $variable;
		}

		if ($this->editModeEnabled && RoundTrip::getOverWriteSettingForPath($targetFile . '.' . $this->locallangFileFormat, $this->extension) == 1) {
			$existingFile = NULL;
			$filenameToLookFor = $this->extensionDirectory . $targetFile;
			if ($variableName == 'domainObject') {
				$filenameToLookFor .= '_' . $variable->getDatabaseTableName();
			}
			if (file_exists($filenameToLookFor . '.xlf')) {
				$existingFile = $filenameToLookFor . '.xlf';
			} else if (file_exists($filenameToLookFor . '.xml')) {
				$existingFile = $filenameToLookFor . '.xml';
			}
			if ($existingFile != NULL) {
				$defaultFileContent = $this->renderFluidTemplate($targetFile . '.' . $this->locallangFileFormat . 't', $variableArray);
				if ($this->locallangFileFormat == 'xlf') {
					throw new \TYPO3\PackageBuilder\Exception('Merging xlf files is not yet supported. Please set overwrite settings to "keep" or "overwrite"');
					// this is prepared already but still needs some improvements
					//$labelArray = Tx_ExtensionBuilder_Utility_Tools::mergeLocallangXml($existingFile, $defaultFileContent, $this->locallangFileFormat);
					//$variableArray['labelArray'] = $labelArray;
				} else {
					return Tx_ExtensionBuilder_Utility_Tools::mergeLocallangXml($existingFile, $defaultFileContent);
				}

			}
		}
		return $this->renderFluidTemplate($targetFile . '.' . $this->locallangFileFormat . 't', $variableArray);
	}

	public function generatePrivateResourcesHtaccess() {
		return $this->renderFluidTemplate('Resources/Private/htaccess.t', array());
	}

	public function generateTCA( Model\DomainObject $domainObject) {
		return $this->renderFluidTemplate('Configuration/TCA/domainObject.php.tmpl', array('extension' => $this->extension, 'domainObject' => $domainObject, 'locallangFileFormat' => $this->locallangFileFormat));
	}

	public function generateYamlSettings() {
		return $this->renderFluidTemplate('Configuration/ExtensionBuilder/settings.yamlt', array('extension' => $this->extension));
	}


	public function generateTyposcriptSetup() {
		return $this->renderFluidTemplate('Configuration/TypoScript/setup.txtt', array('extension' => $this->extension));
	}

	public function generateTyposcriptConstants() {
		return $this->renderFluidTemplate('Configuration/TypoScript/constants.txtt', array('extension' => $this->extension));
	}

	public function generateStaticTyposcript() {
		return $this->renderFluidTemplate('ext_typoscript_setup.txtt', array('extension' => $this->extension));
	}


	/**
	 *
	 * @param Model\DomainObject $domainObject
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 * @param string $classType
	 * @param string $methodType (used for add, get set etc.)
	 * @param string $methodName (used for concrete methods like createAction, initialze etc.)
	 * @return string method body
	 */
	public function getDefaultMethodBody($domainObject, $domainProperty, $classType, $methodType, $methodName) {

		if ($classType == 'Controller' && !in_array($methodName, self::$defaultActions)) {
			return '';
		}
		if (!empty($methodType) && empty($methodName)) {
			$methodName = $methodType;
		}

		$variables = array(
			'domainObject' => $domainObject,
			'property' => $domainProperty,
			'extension' => $this->extension,
			'settings' => $this->settings
		);

		$methodBody = $this->renderFluidTemplate('Partials/Classes/' . $classType . '/Methods/' . $methodName . 'MethodBody.php.tmpl', $variables);
		return $methodBody;
	}

	/**
	 *
	 * @param string $extensionDirectory
	 * @param string $classType
	 * @return string
	 */
	public static function getFolderForClassFile($extensionDirectory, $classType, $createDirIfNotExist = TRUE) {
		$classPath = '';
		switch ($classType) {
			case 'Model'        :
				$classPath = 'Classes/Domain/Model/';
				break;

			case 'Controller'    :
				$classPath = 'Classes/Controller/';
				break;

			case 'Repository'    :
				$classPath = 'Classes/Domain/Repository/';
				break;
		}
		if (!empty($classPath)) {
			if (!is_dir($extensionDirectory . $classPath) && $createDirIfNotExist) {
				self::createDirectoryRecursively($extensionDirectory . $classPath);
			}
			if (!is_dir($extensionDirectory . $classPath) && $createDirIfNotExist) {
				throw new \TYPO3\PackageBuilder\Exception('folder could not be created:' . $extensionDirectory . $classPath);
			}
			return $extensionDirectory . $classPath;
		} else throw new \TYPO3\PackageBuilder\Exception('Unexpected classPath:' . $classPath);
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
			$overWriteMode = RoundTrip::getOverWriteSettingForPath($targetFile, $this->extension);
			if ($overWriteMode == -1) {
				return; // skip file creation
			}
			if ($overWriteMode == 1 && strpos($targetFile, 'Classes') === FALSE) { // classes are merged by the class builder
				$fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
				if ($fileExtension == 'html') {
					//TODO: We need some kind of protocol to be displayed after code generation
					$this->logger->log('File ' . basename($targetFile) . ' was not written. Template files can\'t be merged!',1);
					return;
				} elseif (in_array($fileExtension, $this->filesSupportingSplitToken)) {
					$fileContents = $this->insertSplitToken($targetFile, $fileContents);
				}
			} else if (file_exists($targetFile) && $overWriteMode == 2) {
				// keep the existing file
				return;
			}
		}

		if (empty($fileContents)) {
			$this->logger->log('No file content! File ' . $targetFile . ' had no content',1);
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
			if (strpos($existingFileContent, RoundTrip::OLD_SPLIT_TOKEN)) {
				$existingFileContent = str_replace(RoundTrip::OLD_SPLIT_TOKEN, RoundTrip::SPLIT_TOKEN, $existingFileContent);
			}
			$fileParts = explode(RoundTrip::SPLIT_TOKEN, $existingFileContent);
			if (count($fileParts) == 2) {
				$customFileContent = str_replace('?>', '', $fileParts[1]);
			}
		}

		$fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

		if ($fileExtension == 'php') {
			$fileContents = str_replace('?>', '', $fileContents);
			$fileContents .= RoundTrip::SPLIT_TOKEN;
		} else if ($fileExtension == $this->locallangFileFormat) {
			//$fileContents = Tx_ExtensionBuilder_Utility_Tools::mergeLocallangXml($targetFile, $fileContents, $this->locallangFileFormat);
		} else {
			$fileContents .= "\n" . RoundTrip::SPLIT_TOKEN;
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
	protected function copy($sourceFile, $targetFile) {
		$overWriteMode =RoundTrip::getOverWriteSettingForPath($targetFile, $this->extension);
		if ($overWriteMode === -1) {
			// skip creation
			return;
		}
		if (!file_exists($targetFile) || ($this->editModeEnabled && $overWriteMode < 2)) {
			copy($sourceFile, $targetFile);
		}
	}

	/**
	 * @param string $extensionDir
	 */
	public function setExtensionDirectory($extensionDir) {
		$this->extensionDirectory = $extensionDir;
		$this->packageDirectory = $extensionDir;
	}

	/**
	 * @return string
	 */
	public function getExtensionDiretory() {
		if(empty($this->extensionDirectory)) {
			return $this->getPackageDirectory();
		}
		return $this->extensionDirectory;
	}
}

?>