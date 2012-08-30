<?php
namespace TYPO3\PackageBuilder\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @FLOW3\Scope("singleton")
 */
abstract class AbstractConfigurationManager  {

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $configurationManager;

	const FLOW3 = 1;
	const PHOENIX = 2;
	const TYPO3 = 3;

	/**
	 * @param string $packageKey
	 * @return array
	 */
	public function getPackageConfiguration($packageKey) {
		return array(
			'enableRoundtrip' => FALSE
		);
	}

	const SETTINGS_DIR = 'Configuration/ExtensionBuilder/';
	const OLD_SETTINGS_DIR = 'Configuration/Kickstarter/';
	const EXTENSION_BUILDER_SETTINGS_FILE = 'PackageBuilder.json';
	const PACKAGE_BUILDER_SETTINGS_FILE = 'PackageBuilder.json';

	/**
	 *
	 * @var array
	 */
	private $inputData = array();

	/**
	 * wrapper for file_get_contents('php://input')
	 */
	public function injectInputData($jsonString) {
		$this->inputData = json_decode($jsonString, TRUE);
	}

	/**
	 * reads the configuration from this->inputData
	 * and returns it as array
	 *
	 */
	public function getConfigurationFromModeler() {
		if (empty($this->inputData)) {
			throw new \TYPO3\PackageBuilder\Exception\ConfigurationError('No inputData!');
		}
		//$extensionConfigurationJSON = json_decode($this->inputData, TRUE);
		//$extensionConfigurationJSON = $this->reArrangeRelations($extensionConfigurationJSON);
		return $this->inputData;
	}

	public function getConfigurationFromFile($filePath) {
		$jsonString = file_get_contents($filePath);
		$this->injectInputData($jsonString);
		return $this->getConfigurationFromModeler();

	}


	public function getSubActionFromRequest() {
		$subAction = $this->inputData['method'];
		return $subAction;
	}

	/**
	 * get settings from various sources:
	 * settings configured in module.extension_builder typoscript
	 * Module settings configured in the extension manager
	 *
	 * @param array $typoscript (optional)
	 */
	public function getSettings($typoscript = NULL) {
		if ($typoscript == NULL) {
			$typoscript = $this->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		}
		$settings = $typoscript['module.']['extension_builder.']['settings.'];
		if (empty($settings['codeTemplateRootPath'])) {
			$settings['codeTemplateRootPath'] = 'EXT:extension_builder/Resources/Private/CodeTemplates/Extbase/';
		}
		$settings['codeTemplateRootPath'] = self::substituteExtensionPath($settings['codeTemplateRootPath']);
		$settings['extConf'] = $this->getExtensionBuilderSettings();
		return $settings;
	}

	/**
	 * Get the extension_builder configuration (ext_template_conf)
	 *
	 * @return array
	 */
	public function getExtensionBuilderSettings() {
		$settings = array();
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extension_builder'])) {
			$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extension_builder']);
		}
		return $settings;
	}

	/**
	 *
	 * @param string $extensionKey
	 * @return array settings
	 */
	public function getExtensionSettings($extensionKey) {
		$settings = array();
		$settingsFile = $this->getSettingsFile($extensionKey);
		if (file_exists($settingsFile)) {
			$yamlParser = new \TYPO3\FLOW3\Configuration\Source\YamlSource();
			$settings = $yamlParser->load($settingsFile);
		}
		return $settings;
	}

	/**
	 * reads the stored configuration  (i.e. the extension model etc.)
	 *
	 * @param string $extensionKey
	 * @param boolean $prepareForModeler (should the advanced settings be mapped to the subform?)
	 * @return array extension configuration
	 */
	public function getExtensionBuilderConfiguration($extensionKey, $prepareForModeler = TRUE) {

		$oldJsonFile = PATH_typo3conf . 'ext/' . $extensionKey . '/kickstarter.json';
		$jsonFile = PATH_typo3conf . 'ext/' . $extensionKey . '/' . self::EXTENSION_BUILDER_SETTINGS_FILE;
		if (file_exists($oldJsonFile)) {
			rename($oldJsonFile, $jsonFile);
		}

		if (file_exists($jsonFile)) {
			// compatibility adaptions for configurations from older versions
			$extensionConfigurationJSON = json_decode(file_get_contents($jsonFile), TRUE);
			$extensionConfigurationJSON = $this->fixExtensionBuilderJSON($extensionConfigurationJSON, $prepareForModeler);
			$extensionConfigurationJSON['properties']['originalExtensionKey'] = $extensionKey;
			//t3lib_div::writeFile($jsonFile, json_encode($extensionConfigurationJSON));
			return $extensionConfigurationJSON;
		} else {
			return NULL;
		}
	}





	/**
	 *
	 * @param Tx_ExtensionBuilder_Domain_Model_Extension $extension
	 * @param string $codeTemplateRootPath
	 */
	public function createInitialSettingsFile($extension, $codeTemplateRootPath) {
		t3lib_div::mkdir_deep($extension->getExtensionDir(), self::SETTINGS_DIR);
		$settings = file_get_contents($codeTemplateRootPath . 'Configuration/ExtensionBuilder/settings.yamlt');
		$settings = str_replace('{extension.extensionKey}', $extension->getExtensionKey(), $settings);
		$settings = str_replace('<f:format.date>now</f:format.date>', date('Y-m-d H:i'), $settings);
		t3lib_div::writeFile($extension->getExtensionDir() . self::SETTINGS_DIR . 'settings.yaml', $settings);
	}

	/**
	 * Replace the EXT:extkey prefix with the appropriate path
	 * @param string $encodedTemplateRootPath
	 */
	static public function substituteExtensionPath($encodedTemplateRootPath) {
		if (t3lib_div::isFirstPartOfStr($encodedTemplateRootPath, 'EXT:')) {
			list($extKey, $script) = explode('/', substr($encodedTemplateRootPath, 4), 2);
			if ($extKey && TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded($extKey)) {
				return TYPO3\CMS\Core\Extension\ExtensionManager::extPath($extKey) . $script;
			}
		} else if (t3lib_div::isAbsPath($encodedTemplateRootPath)) {
			return $encodedTemplateRootPath;
		} else {
			return PATH_site . $encodedTemplateRootPath;
		}
	}

	/**
	 * performs various compatibility modifications
	 * and fixes/workarounds for wireit limitations
	 *
	 * @param array $extensionConfigurationJSON
	 * @param boolean $prepareForModeler
	 *
	 * @return array the modified configuration
	 */
	public function fixExtensionBuilderJSON($extensionConfigurationJSON, $prepareForModeler) {
		$extBuilderVersion = tx_em_Tools::renderVersion($extensionConfigurationJSON['log']['extension_builder_version']);
		$extensionConfigurationJSON['modules'] = $this->mapOldRelationTypesToNewRelationTypes($extensionConfigurationJSON['modules']);
		$extensionConfigurationJSON['modules'] = $this->generateUniqueIDs($extensionConfigurationJSON['modules']);
		$extensionConfigurationJSON['modules'] = $this->resetOutboundedPositions($extensionConfigurationJSON['modules']);
		$extensionConfigurationJSON['modules'] = $this->mapAdvancedMode($extensionConfigurationJSON['modules'], $prepareForModeler);
		$extensionConfigurationJSON['modules'] = $this->mapOldActions($extensionConfigurationJSON['modules']);
		if ($extBuilderVersion['version_int'] < 2000100) {
			$extensionConfigurationJSON = $this->importExistingActionConfiguration($extensionConfigurationJSON);
		}
		$extensionConfigurationJSON = $this->reArrangeRelations($extensionConfigurationJSON);
		return $extensionConfigurationJSON;
	}

	/**
	 * enable unique IDs to track modifications of models, properties and relations
	 * this method sets unique IDs to the JSON array, if it was created
	 * with an older version of the extension builder
	 *
	 * @param $jsonConfig
	 * @return array $jsonConfig with unique IDs
	 */
	protected function generateUniqueIDs($jsonConfig) {
		//  generate unique IDs
		foreach ($jsonConfig as &$module) {

			if (empty($module['value']['objectsettings']['uid'])) {
				$module['value']['objectsettings']['uid'] = md5(microtime() . $module['propertyName']);
			}

			for ($i = 0; $i < count($module['value']['propertyGroup']['properties']); $i++) {
				// don't save empty properties
				if (empty($module['value']['propertyGroup']['properties'][$i]['propertyName'])) {
					unset($module['value']['propertyGroup']['properties'][$i]);
				} else if (empty($module['value']['propertyGroup']['properties'][$i]['uid'])) {
					$module['value']['propertyGroup']['properties'][$i]['uid'] = md5(microtime() . $module['value']['propertyGroup']['properties'][$i]['propertyName']);
				}
			}
			for ($i = 0; $i < count($module['value']['relationGroup']['relations']); $i++) {
				// don't save empty relations
				if (empty($module['value']['relationGroup']['relations'][$i]['relationName'])) {
					unset($module['value']['relationGroup']['relations'][$i]);
				} else if (empty($module['value']['relationGroup']['relations'][$i]['uid'])) {
					$module['value']['relationGroup']['relations'][$i]['uid'] = md5(microtime() . $module['value']['relationGroup']['relations'][$i]['relationName']);
				}
			}
		}
		return $jsonConfig;
	}

	/**
	 * Check if the confirm was send with input data
	 *
	 * @return boolean
	 */
	public function isConfirmed($identifier) {
		if (isset($this->inputData['params'][$identifier]) &&
				$this->inputData['params'][$identifier] == 1
		) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 *
	 * enables compatibility with JSON from older versions of the extension builder
	 * old relation types are mapped to new types according to this scheme:
	 *
	 * zeroToMany
	 *         inline == 1 => zeroToMany
	 *         inline == 0 => manyToMany
	 * zeroToOne
	 *         inline == 1 => zeroToOne
	 *         inline == 0 => manyToOne
	 * ManyToMany
	 *         inline == 1 => oneToMany
	 *         inline == 0 => manyToMany
	 *
	 * @param array $jsonConfig
	 */
	protected function mapOldRelationTypesToNewRelationTypes($jsonConfig) {
		foreach ($jsonConfig as &$module) {
			for ($i = 0; $i < count($module['value']['relationGroup']['relations']); $i++) {
				if (isset($module['value']['relationGroup']['relations'][$i]['advancedSettings']['inlineEditing'])) {
					// the json config was created with an older version of the kickstarter
					if ($module['value']['relationGroup']['relations'][$i]['advancedSettings']['inlineEditing'] == 1) {
						if ($module['value']['relationGroup']['relations'][$i]['advancedSettings']['relationType'] == 'manyToMany') {
							// inline enabled results in a zeroToMany
							$module['value']['relationGroup']['relations'][$i]['relationType'] = 'zeroToMany';
						}
					} else {
						if ($module['value']['relationGroup']['relations'][$i]['advancedSettings']['relationType'] == 'zeroToMany') {
							// inline disabled results in a manyToMany
							$module['value']['relationGroup']['relations'][$i]['relationType'] = 'manyToMany';
						}
						if ($module['value']['relationGroup']['relations'][$i]['advancedSettings']['relationType'] == 'zeroToOne') {
							// inline disabled results in a manyToOne
							$module['value']['relationGroup']['relations'][$i]['relationType'] = 'manyToOne';
						}
					}
				}
				unset($module['value']['relationGroup']['relations'][$i]['advancedSettings']['inlineEditing']);
				unset($module['value']['relationGroup']['relations'][$i]['inlineEditing']);
			}
		}
		return $jsonConfig;
	}

	/**
	 * copy values from simple mode fieldset to advanced fieldset
	 *
	 * enables compatibility with JSON from older versions of the extension builder
	 *
	 * @param array $jsonConfig
	 * @param boolean $prepareForModeler
	 *
	 * @return array modified json
	 */
	protected function mapAdvancedMode($jsonConfig, $prepareForModeler) {
		$fieldsToMap = array('relationType', 'propertyIsExcludeField', 'propertyIsExcludeField', 'lazyLoading', 'relationDescription', 'foreignRelationClass');
		foreach ($jsonConfig as &$module) {
			for ($i = 0; $i < count($module['value']['relationGroup']['relations']); $i++) {
				if ($prepareForModeler) {
					if (empty($module['value']['relationGroup']['relations'][$i]['advancedSettings'])) {
						$module['value']['relationGroup']['relations'][$i]['advancedSettings'] = array();
						foreach ($fieldsToMap as $fieldToMap) {
							$module['value']['relationGroup']['relations'][$i]['advancedSettings'][$fieldToMap] = $module['value']['relationGroup']['relations'][$i][$fieldToMap];
						}

						$module['value']['relationGroup']['relations'][$i]['advancedSettings']['propertyIsExcludeField'] = $module['value']['relationGroup']['relations'][$i]['propertyIsExcludeField'];
						$module['value']['relationGroup']['relations'][$i]['advancedSettings']['lazyLoading'] = $module['value']['relationGroup']['relations'][$i]['lazyLoading'];
						$module['value']['relationGroup']['relations'][$i]['advancedSettings']['relationDescription'] = $module['value']['relationGroup']['relations'][$i]['relationDescription'];
						$module['value']['relationGroup']['relations'][$i]['advancedSettings']['foreignRelationClass'] = $module['value']['relationGroup']['relations'][$i]['foreignRelationClass'];
					}
				} else if (isset($module['value']['relationGroup']['relations'][$i]['advancedSettings'])) {
					foreach ($fieldsToMap as $fieldToMap) {
						$module['value']['relationGroup']['relations'][$i][$fieldToMap] = $module['value']['relationGroup']['relations'][$i]['advancedSettings'][$fieldToMap];
					}
					unset($module['value']['relationGroup']['relations'][$i]['advancedSettings']);
				}
			}
		}
		return $jsonConfig;
	}

	/**
	 * just a temporary workaround until the new UI is available
	 *
	 * @param array $jsonConfig
	 */
	protected function resetOutboundedPositions($jsonConfig) {
		foreach ($jsonConfig as &$module) {
			if ($module['config']['position'][0] < 0) {
				$module['config']['position'][0] = 10;
			}
			if ($module['config']['position'][1] < 0) {
				$module['config']['position'][1] = 10;
			}
		}
		return $jsonConfig;
	}

	/**
	 * This is a workaround for the bad design in WireIt
	 * All wire terminals are only identified by a simple index,
	 * that does not reflect deleting of models and relations
	 *
	 * @param array $jsonConfig
	 */
	protected function reArrangeRelations($jsonConfig) {
		foreach ($jsonConfig['wires'] as &$wire) {
			$parts = explode('_', $wire['src']['terminal']); // format: relation_1
			$supposedRelationIndex = $parts[1];
			$supposedModuleIndex = $wire['src']['moduleId'];
			$uid = $wire['src']['uid'];
			$wire['src'] = self::findModuleIndexByRelationUid($wire['src']['uid'], $jsonConfig['modules'], $wire['src']['moduleId'], $supposedRelationIndex);
			$wire['src']['uid'] = $uid;

			$supposedModuleIndex = $wire['tgt']['moduleId'];
			$uid = $wire['tgt']['uid'];
			$wire['tgt'] = self::findModuleIndexByRelationUid($wire['tgt']['uid'], $jsonConfig['modules'], $wire['tgt']['moduleId']);
			$wire['tgt']['uid'] = $uid;
		}
		return $jsonConfig;
	}

	/**
	 *
	 * @param int $uid
	 * @param array $modules
	 * @param int $supposedModuleIndex
	 * @param int $supposedRelationIndex
	 */
	protected function findModuleIndexByRelationUid($uid, $modules, $supposedModuleIndex, $supposedRelationIndex = NULL) {
		$result = array(
			'moduleId' => $supposedModuleIndex
		);
		if ($supposedRelationIndex == NULL) {
			$result['terminal'] = 'SOURCES';
			if ($modules[$supposedModuleIndex]['value']['objectsettings']['uid'] == $uid) {
				return $result; // everything as expected
			} else {
				$moduleCounter = 0;
				foreach ($modules as $module) {
					if ($module['value']['objectsettings']['uid'] == $uid) {
						$result['moduleId'] = $moduleCounter;
						return $result;
					}
				}
			}
		} else if ($modules[$supposedModuleIndex]['value']['relationGroup']['relations'][$supposedRelationIndex]['uid'] == $uid) {
			$result['terminal'] = 'relationWire_' . $supposedRelationIndex;
			return $result; // everything as expected
		} else {
			$moduleCounter = 0;
			foreach ($modules as $module) {
				$relationCounter = 0;
				foreach ($module['value']['relationGroup']['relations'] as $relation) {
					if ($relation['uid'] == $uid) {
						$result['moduleId'] = $moduleCounter;
						$result['terminal'] = 'relationWire_' . $relationCounter;
						return $result;
					}
					$relationCounter++;
				}
				$moduleCounter++;
			}
		}
	}

	/**
	 * this method should adapt the changes in action configuration
	 * 1. version: list with dropdowns
	 * 2. version: checkboxes for default actions and list with textfields for custom actions
	 * 3. version: prefix for default actions to enable sorting
	 * @param $modules
	 * @return mixed
	 */
	protected function mapOldActions($modules) {
		$newActionNames = array('list' => '_default0_list', 'show' => '_default1_show', 'new_create' => '_default2_new_create', 'edit_update' => '_default3_edit_update', 'delete' => '_default4_delete');
		foreach ($modules as &$module) {
			if (isset($module['value']['actionGroup']['actions'])) {
				foreach ($newActionNames as $defaultAction) {
					$module['value']['actionGroup'][$defaultAction] = FALSE;
				}
				if (empty($module['value']['actionGroup']['actions'])) {
					if ($module['value']['objectsettings']['aggregateRoot']) {
						foreach ($newActionNames as $defaultAction) {
							$module['value']['actionGroup'][$defaultAction] = TRUE;
						}
					}
				} else {

					foreach ($module['value']['actionGroup']['actions'] as $oldActionName) {
						if ($oldActionName == 'create') {
							$module['value']['actionGroup']['new_create'] = TRUE;
						} else if ($oldActionName == 'update') {
							$module['value']['actionGroup']['edit_update'] = TRUE;
						} else {
							$module['value']['actionGroup'][$oldActionName] = TRUE;
						}
					}
				}
				unset($module['value']['actionGroup']['actions']);
			}
			//foreach($module['value']['actionGroup'] as $actionName => $value) {
			foreach ($newActionNames as $oldActionKey => $newActionKey) {
				if (isset($module['value']['actionGroup'][$oldActionKey])) {
					$module['value']['actionGroup'][$newActionKey] = $module['value']['actionGroup'][$oldActionKey];
					unset($module['value']['actionGroup'][$oldActionKey]);
				} else if (!isset($module['value']['actionGroup'][$newActionKey])) {
					$module['value']['actionGroup'][$newActionKey] = FALSE;
				}
			}
		}
		return $modules;
	}

	/**
	 * Enable the import of actions configuration of installed extensions
	 * by importing the settings from $TYPO3_CONF_VARS
	 * @param array $extensionConfigurationJSON
	 * @return array
	 */
	protected function importExistingActionConfiguration(array $extensionConfigurationJSON) {
		if (isset($extensionConfigurationJSON['properties']['plugins'])) {
			$extKey = $extensionConfigurationJSON['properties']['extensionKey'];
			if (TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded($extKey)) {
				foreach ($extensionConfigurationJSON['properties']['plugins'] as &$pluginJSON) {
					if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][t3lib_div::underscoredToUpperCamelCase($extKey)]['plugins'][ucfirst($pluginJSON['key'])]['controllers'])) {
						$controllerActionCombinationsConfig = "";
						$nonCachableActionConfig = "";
						$pluginJSON['actions'] = array();
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][t3lib_div::underscoredToUpperCamelCase($extKey)]['plugins'][ucfirst($pluginJSON['key'])]['controllers'] as $controllerName => $controllerConfig) {
							if (isset($controllerConfig['actions'])) {
								$controllerActionCombinationsConfig .= $controllerName . '=>' . implode(',', $controllerConfig['actions']) . LF;
							}
							if (isset($controllerConfig['nonCacheableActions'])) {
								$nonCachableActionConfig .= $controllerName . '=>' . implode(',', $controllerConfig['nonCacheableActions']) . LF;
							}
						}
						if (!empty($controllerActionCombinationsConfig)) {
							$pluginJSON['actions']['controllerActionCombinations'] = $controllerActionCombinationsConfig;
						}
						if (!empty($nonCachableActionConfig)) {
							$pluginJSON['actions']['noncacheableActions'] = $nonCachableActionConfig;
						}
					}
				}
			}
		}
		return $extensionConfigurationJSON;
	}

	public function getParentClassForValueObject($extensionKey) {
		$settings = self::getExtensionSettings($extensionKey);
		if (isset($settings['classBuilder']['Model']['AbstractValueObject']['parentClass'])) {
			$parentClass = $settings['classBuilder']['Model']['AbstractValueObject']['parentClass'];
		} else {
			$parentClass = 'Tx_Extbase_DomainObject_AbstractValueObject';
		}
		return $parentClass;
	}

	public function getParentClassForEntityObject($extensionKey) {
		$settings = self::getExtensionSettings($extensionKey);
		if (isset($settings['classBuilder']['Model']['AbstractEntity']['parentClass'])) {
			$parentClass = $settings['classBuilder']['Model']['AbstractEntity']['parentClass'];
		} else {
			$parentClass = 'Tx_Extbase_DomainObject_AbstractEntity';
		}
		return $parentClass;
	}

}

?>