<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Ingmar Schlecht
 *  (c) 2011 Nico de Haen
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
namespace TYPO3\PackageBuilder\Service\TYPO3;

use TYPO3\PackageBuilder\Domain\Model as Model;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Builds an extension object based on the buildConfiguration
 *
 * @package PackageBuilder
 * @FLOW3\Scope("singleton")
 *
 */
class PackageFactory extends \TYPO3\PackageBuilder\Service\AbstractPackageFactory {

	/**
	 * @var \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager
	 *
	 * @FLOW3\Inject
	 */
	protected $configurationManager;



	/**
	 * @var \TYPO3\PackageBuilder\Service\TYPO3\DomainObjectFactory
	 *
	 * @FLOW3\Inject
	 *
	 */
	protected $domainObjectFactory;


	/**
	 * @param array $extensionBuildConfiguration
	 * @return \TYPO3\PackageBuilder\Domain\Model\Extension $extension
	 */
	public function create(array $extensionBuildConfiguration) {
		$extension = new Model\Extension();
		$globalProperties = $extensionBuildConfiguration['properties'];
		if (!is_array($globalProperties)) {
			$this->logger->log('Error: Extension properties not submitted! ');
			throw new \TYPO3\PackageBuilder\Exception('Extension properties not submitted!');
		}
		$this->setExtensionProperties($extension, $globalProperties);
		foreach ($globalProperties['persons'] as $personValues) {
			$person = $this->buildPerson($personValues);
			$extension->addPerson($person);
		}
		foreach ($globalProperties['plugins'] as $pluginValues) {
			$plugin = $this->buildPlugin($pluginValues);
			$extension->addPlugin($plugin);
		}
		foreach ($globalProperties['backendModules'] as $backendModuleValues) {
			$backendModule = $this->buildBackendModule($backendModuleValues);
			$extension->addBackendModule($backendModule);
		}
		// classes
		if (isset($extensionBuildConfiguration['domainObjects']) && is_array($extensionBuildConfiguration['domainObjects'])) {
			foreach ($extensionBuildConfiguration['domainObjects'] as $singleModule) {
				$domainObject = $this->domainObjectFactory->create($singleModule['value']);
				if (FALSE && $domainObject->isSubClass() && !$domainObject->isMappedToExistingTable()) {
					// we try to get the table from Extbase configuration
					$classSettings = $this->configurationManager->getExtbaseClassConfiguration($domainObject->getParentClass());
					//t3lib_div::devlog('!isMappedToExistingTable:' . strtolower($domainObject->getParentClass()), 'extension_builder', 0, $classSettings);
					if (isset($classSettings['tableName'])) {
						$tableName = $classSettings['tableName'];
					} else {
						// we use the default table name
						$tableName = strtolower($domainObject->getParentClass());
					}
					/**
					if (!isset($GLOBALS['TCA'][$tableName])) {
						throw new \TYPO3\PackageBuilder\Exception(('Table definitions for table ' . $tableName) . ' could not be loaded. You can only map to tables with existing TCA or extend classes of installed extensions!');
					}
					 */
					$domainObject->setMapToTable($tableName);
				}
				$extension->addDomainObject($domainObject);
			}
			$classHierarchy = $extension->getClassHierarchy();
			foreach ($extension->getDomainObjects() as $domainObject) {
				if (isset($classHierarchy[$domainObject->getClassName()])) {
					foreach ($classHierarchy[$domainObject->getClassName()] as $directChild) {
						$domainObject->addChildObject($directChild);
					}
				}
			}
		}
		// relations
		if (isset($extensionBuildConfiguration['wires']) && is_array($extensionBuildConfiguration['wires'])) {
			$this->setExtensionRelations($extensionBuildConfiguration, $extension);
		}
		return $extension;
	}

	/**
	 * @param array $extensionBuildConfiguration
	 * @param Model\Extension $extension
	 * @throws Exception
	 */
	protected function setExtensionRelations($extensionBuildConfiguration, &$extension) {
		$existingRelations = array();
		foreach ($extensionBuildConfiguration['wires'] as $wire) {
			if ($wire['tgt']['terminal'] !== 'SOURCES') {
				if ($wire['src']['terminal'] == 'SOURCES') {
					// this happens if a relation wire was drawn from child to parent
					// swap the two arrays
					$tgtModuleId = $wire['src']['moduleId'];
					$wire['src'] = $wire['tgt'];
					$wire['tgt'] = array('moduleId' => $tgtModuleId, 'terminal' => 'SOURCES');
				} else {
					throw new \TYPO3\PackageBuilder\Exception('A wire has always to connect a relation with a model, not with another relation');
				}
			}
			$srcModuleId = $wire['src']['moduleId'];
			$relationId = substr($wire['src']['terminal'], 13);
			// strip "relationWire_"
			$relationJsonConfiguration = $extensionBuildConfiguration['domainObjects'][$srcModuleId]['value']['relationGroup']['relations'][$relationId];
			if (!is_array($relationJsonConfiguration)) {
				//\t3lib_div::devlog('Error in JSON relation configuration!', 'extension_builder', 3, $extensionBuildConfiguration);
				$errorMessage = 'Missing relation config in domain object: ' . $extensionBuildConfiguration['domainObjects'][$srcModuleId]['value']['name'];
				throw new \TYPO3\PackageBuilder\Exception($errorMessage);
			}
			$foreignModelName = $extensionBuildConfiguration['domainObjects'][$wire['tgt']['moduleId']]['value']['name'];
			$localModelName = $extensionBuildConfiguration['domainObjects'][$wire['src']['moduleId']]['value']['name'];
			//$relation = $this->objectSchemaBuilder->buildRelation($relationJsonConfiguration);
			if (!isset($existingRelations[$localModelName])) {
				$existingRelations[$localModelName] = array();
			}
			$domainObject = $extension->getDomainObjectByName($localModelName);
			$relation = $domainObject->getPropertyByName($relationJsonConfiguration['relationName']);
			if (!$relation) {
				//\t3lib_div::devlog((('Relation not found: ' . $localModelName) . '->') . $relationJsonConfiguration['relationName'], 'extension_builder', 2, $relationJsonConfiguration);
				throw new \TYPO3\PackageBuilder\Exception((('Relation not found: ' . $localModelName) . '->') . $relationJsonConfiguration['relationName']);
			}
			// get unique foreign key names for multiple relations to the same foreign class
			if (in_array($foreignModelName, $existingRelations[$localModelName])) {
				if (is_a($relation, '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\Relation\\ZeroToManyRelation')) {
					$relation->setForeignKeyName(strtolower($localModelName) . count($existingRelations[$localModelName]));
				}
				if (is_a($relation, '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\Relation\\AnyToManyRelation')) {
					$relation->setUseExtendedRelationTableName(TRUE);
				}
			}
			$existingRelations[$localModelName][] = $foreignModelName;
			$relation->setForeignModel($extension->getDomainObjectByName($foreignModelName));
		}
	}

	/**
	 * @param Model\Extension $extension
	 * @param array $propertyConfiguration
	 * @return void
	 */
	protected function setExtensionProperties(&$extension, $propertyConfiguration) {
		// name
		$extension->setName(trim($propertyConfiguration['name']));
		// description
		$extension->setDescription($propertyConfiguration['description']);
		// extensionKey
		$extension->setKey(trim($propertyConfiguration['extensionKey']));
		if ( isset($propertyConfiguration['emConf']['disableVersioning']) && $propertyConfiguration['emConf']['disableVersioning']) {
			$extension->setSupportVersioning(FALSE);
		}
		// various extension properties
		$extension->setVersion($propertyConfiguration['emConf']['version']);
		if (!empty($propertyConfiguration['emConf']['dependsOn'])) {
			$dependencies = array();
			$lines = \TYPO3\FLOW3\Utility\Arrays::trimExplode('
', $propertyConfiguration['emConf']['dependsOn']);
			foreach ($lines as $line) {
				if (strpos($line, '=>')) {
					list($extensionKey, $version) = \TYPO3\FLOW3\Utility\Arrays::trimExplode('=>', $line);
					$dependencies[$extensionKey] = $version;
				}
			}
			$extension->setDependencies($dependencies);
		}
		if (!empty($propertyConfiguration['emConf']['targetVersion'])) {
			$extension->setTargetVersion(floatval($propertyConfiguration['emConf']['targetVersion']));
		}
		if (!empty($propertyConfiguration['emConf']['custom_category'])) {
			$category = $propertyConfiguration['emConf']['custom_category'];
		} else {
			$category = $propertyConfiguration['emConf']['category'];
		}
		$extension->setCategory($category);
		$extension->setShy($propertyConfiguration['emConf']['shy']);
		$extension->setPriority($propertyConfiguration['emConf']['priority']);
		// state
		$state = 0;
		switch ($propertyConfiguration['emConf']['state']) {
		case 'alpha':
			$state = Model\Extension::STATE_ALPHA;
			break;
		case 'beta':
			$state = Model\Extension::STATE_BETA;
			break;
		case 'stable':
			$state = Model\Extension::STATE_STABLE;
			break;
		case 'experimental':
			$state = Model\Extension::STATE_EXPERIMENTAL;
			break;
		case 'test':
			$state = Model\Extension::STATE_TEST;
			break;
		}
		$extension->setState($state);
		if (!empty($propertyConfiguration['originalExtensionKey'])) {
			// handle renaming of extensions
			// original extensionKey
			$extension->setOriginalExtensionKey($propertyConfiguration['originalExtensionKey']);
			$this->logger->log('Extension setOriginalExtensionKey:' . $extension->getOriginalExtensionKey());
		}
		if (!empty($propertyConfiguration['originalExtensionKey']) && $extension->getOriginalExtensionKey() != $extension->getExtensionKey()) {
			$settings = $this->configurationManager->getExtensionSettings($extension->getOriginalExtensionKey());
			// if an extension was renamed, a new extension dir is created and we
			// have to copy the old settings file to the new extension dir
			//copy($this->configurationManager->getSettingsFile($extension->getOriginalExtensionKey()), $this->configurationManager->getSettingsFile($extension->getExtensionKey()));
		} else {
			$settings = $this->configurationManager->getExtensionSettings($extension->getExtensionKey());
		}
		if (!empty($settings)) {
			$extension->setSettings($settings);
			//\t3lib_div::devlog('Extension settings:' . $extension->getExtensionKey(), 'extbase', 0, $extension->getSettings());
		}
	}

	/**
	 * @param array $personValues
	 * @return Tx_ExtensionBuilder_Domain_Model_Person
	 */
	protected function buildPerson($personValues) {
		$person = new Model\Person;
		$person->setName($personValues['name']);
		$person->setRole($personValues['role']);
		$person->setEmail($personValues['email']);
		$person->setCompany($personValues['company']);
		return $person;
	}

	/**
	 * @param array $pluginValues
	 * @return Tx_ExtensionBuilder_Domain_Model_Plugin
	 */
	protected function buildPlugin($pluginValues) {
		$plugin = new Model\Plugin;
		$plugin->setName($pluginValues['name']);
		if (isset($pluginValues['type'])) {
			$plugin->setType($pluginValues['type']);
		}
		$plugin->setKey($pluginValues['key']);
		if (!empty($pluginValues['actions']['controllerActionCombinations'])) {
			$controllerActionCombinations = array();
			$lines = \TYPO3\FLOW3\Utility\Arrays::trimExplode('
', $pluginValues['actions']['controllerActionCombinations'], TRUE);
			foreach ($lines as $line) {
				list($controllerName, $actionNames) = \TYPO3\FLOW3\Utility\Arrays::trimExplode('=>', $line);
				$controllerActionCombinations[$controllerName] = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $actionNames);
			}
			$plugin->setControllerActionCombinations($controllerActionCombinations);
		}
		if (!empty($pluginValues['actions']['noncacheableActions'])) {
			$noncacheableControllerActions = array();
			$lines = \TYPO3\FLOW3\Utility\Arrays::trimExplode('
', $pluginValues['actions']['noncacheableActions'], TRUE);
			foreach ($lines as $line) {
				list($controllerName, $actionNames) = \TYPO3\FLOW3\Utility\Arrays::trimExplode('=>', $line);
				$noncacheableControllerActions[$controllerName] = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $actionNames);
			}
			$plugin->setNoncacheableControllerActions($noncacheableControllerActions);
		}
		if (!empty($pluginValues['actions']['switchableActions'])) {
			$switchableControllerActions = array();
			$lines = \TYPO3\FLOW3\Utility\Arrays::trimExplode('
', $pluginValues['actions']['switchableActions'], TRUE);
			$switchableAction = array();
			foreach ($lines as $line) {
				if (strpos($line, '->') === FALSE) {
					if (isset($switchableAction['label'])) {
						// start a new array
						$switchableAction = array();
					}
					$switchableAction['label'] = trim($line);
				} else {
					$switchableAction['actions'] = \TYPO3\FLOW3\Utility\Arrays::trimExplode(';', $line, TRUE);
					$switchableControllerActions[] = $switchableAction;
				}
			}
			$plugin->setSwitchableControllerActions($switchableControllerActions);
		}
		return $plugin;
	}

	/**
	 * @param array $backendModuleValues
	 * @return Tx_ExtensionBuilder_Domain_Model_BackendModule
	 */
	protected function buildBackendModule($backendModuleValues) {
		$backendModule = new Model\BackendModule;
		$backendModule->setName($backendModuleValues['name']);
		$backendModule->setMainModule($backendModuleValues['mainModule']);
		$backendModule->setTabLabel($backendModuleValues['tabLabel']);
		$backendModule->setKey($backendModuleValues['key']);
		$backendModule->setDescription($backendModuleValues['description']);
		if (!empty($backendModuleValues['actions']['controllerActionCombinations'])) {
			$controllerActionCombinations = array();
			$lines = \TYPO3\FLOW3\Utility\Arrays::trimExplode('
', $backendModuleValues['actions']['controllerActionCombinations'], TRUE);
			foreach ($lines as $line) {
				list($controllerName, $actionNames) = \TYPO3\FLOW3\Utility\Arrays::trimExplode('=>', $line);
				$controllerActionCombinations[$controllerName] = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $actionNames);
			}
			$backendModule->setControllerActionCombinations($controllerActionCombinations);
		}
		return $backendModule;
	}

}

