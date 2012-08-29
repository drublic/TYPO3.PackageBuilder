<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Ingmar Schlecht
 *  (c) 2010 Nico de Haen
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
 * Builder for domain objects
 *
 * @package PackageBuilder
 */
class DomainObjectFactory extends \TYPO3\PackageBuilder\Service\AbstractDomainObjectFactory {

	/**
	 * @var \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager
	 *
	 * @FLOW3\Inject
	 */
	protected $configurationManager;

	/**
	 * @param array $jsonDomainObject
	 * @return \TYPO3\PackageBuilder\Domain\Model\DomainObject $domainObject
	 */
	public function create(array $jsonDomainObject) {
		//t3lib_div::devlog('Building domain object '.$jsonDomainObject['name'],'extension_builder',0,$jsonDomainObject);
		$domainObject = new Model\DomainObject;
		$domainObject->setUniqueIdentifier($jsonDomainObject['objectsettings']['uid']);
		$domainObject->setName($jsonDomainObject['name']);
		$domainObject->setDescription($jsonDomainObject['objectsettings']['description']);
		if ($jsonDomainObject['objectsettings']['type'] === 'Entity') {
			$domainObject->setEntity(TRUE);
		} else {
			$domainObject->setEntity(FALSE);
		}
		$domainObject->setAggregateRoot($jsonDomainObject['objectsettings']['aggregateRoot']);

		if(isset($jsonDomainObject['objectsettings']['sorting'])) {
			$domainObject->setSorting($jsonDomainObject['objectsettings']['sorting']);
		}

		// extended settings
		if (!empty($jsonDomainObject['objectsettings']['mapToTable'])) {
			$domainObject->setMapToTable($jsonDomainObject['objectsettings']['mapToTable']);
		}
		if (!empty($jsonDomainObject['objectsettings']['parentClass'])) {
			$domainObject->setParentClass($jsonDomainObject['objectsettings']['parentClass']);
		}
		// properties
		foreach ($jsonDomainObject['propertyGroup']['properties'] as $jsonProperty) {
			$propertyType = $jsonProperty['propertyType'];
			$propertyClassName = '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\' . $propertyType . 'Property';
			if (!class_exists($propertyClassName)) {
				throw new \Exception(('Property of type ' . $propertyType) . ' not found');
			}
			$property = new $propertyClassName;
			$property->setUniqueIdentifier($jsonProperty['uid']);
			$property->setName($jsonProperty['propertyName']);
			$property->setDescription($jsonProperty['propertyDescription']);
			if (isset($jsonProperty['propertyIsRequired'])) {
				$property->setRequired($jsonProperty['propertyIsRequired']);
			}
			if (isset($jsonProperty['propertyIsExcludeField'])) {
				$property->setExcludeField($jsonProperty['propertyIsExcludeField']);
			}
			//t3lib_div::devlog('Adding property ' . $jsonProperty['propertyName'] . ' to domain object '.$jsonDomainObject['name'],'extension_builder',0,$jsonDomainObject);
			$domainObject->addProperty($property);
		}
		$relatedForeignTables = array();
		foreach ($jsonDomainObject['relationGroup']['relations'] as $jsonRelation) {
			$relation = $this->buildRelation($jsonRelation);
			if (!empty($jsonRelation['foreignRelationClass'])) {
				// relations without wires
				$relation->setForeignClassName($jsonRelation['foreignRelationClass']);
				$relation->setRelatedToExternalModel(TRUE);
				//$extbaseClassConfiguration = $this->configurationManager->getExtbaseClassConfiguration($jsonRelation['foreignRelationClass']);
				if (isset($extbaseClassConfiguration['tableName'])) {
					$foreignDatabaseTableName = $extbaseClassConfiguration['tableName'];
				} else {
					$foreignDatabaseTableName = strtolower($jsonRelation['foreignRelationClass']);
				}
				$relation->setForeignDatabaseTableName($foreignDatabaseTableName);
				if (is_a($relation,  '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\Relation\\ZeroToManyRelation')) {
					$foreignKeyName = strtolower($domainObject->getName());
					if (isset($relatedForeignTables[$foreignDatabaseTableName])) {
						$foreignKeyName .= $relatedForeignTables[$foreignDatabaseTableName];
						$relatedForeignTables[$foreignDatabaseTableName] += 1;
					} else {
						$relatedForeignTables[$foreignDatabaseTableName] = 1;
					}
					$relation->setForeignKeyName($foreignKeyName);
				}
			}
			//t3lib_div::devlog('Adding relation ' . $jsonRelation['relationName'] . ' to domain object '.$jsonDomainObject['name'],'extension_builder',0,$jsonRelation);
			$domainObject->addProperty($relation);
		}
		//actions
		foreach ($jsonDomainObject['actionGroup'] as $jsonActionName => $actionValue) {
			if ($jsonActionName == 'customActions' && !empty($actionValue)) {
				$actionNames = $actionValue;
			} else {
				if ($actionValue == 1) {
					$jsonActionName = preg_replace('/^_default[0-9]_*/', '', $jsonActionName);
					if ($jsonActionName == 'edit_update' || $jsonActionName == 'new_create') {
						$actionNames = explode('_', $jsonActionName);
					} else {
						$actionNames = array($jsonActionName);
					}
				} else {
					$actionNames = array();
				}
			}
			if (!empty($actionNames)) {
				foreach ($actionNames as $actionName) {
					$actionClassName = '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\Action';
					$action = new $actionClassName;
					$action->setName($actionName);
					$domainObject->addAction($action);
				}
			}
		}
		return $domainObject;
	}

	/**
	 * @param $relationJsonConfiguration
	 * @return Tx_ExtensionBuilder_Domain_Model_DomainObject_Relation_AbstractRelation
	 */
	static public function buildRelation($relationJsonConfiguration) {
		$relationSchemaClassName = '\\TYPO3\\PackageBuilder\\Domain\\Model\\DomainObject\\Relation\\' . ucfirst($relationJsonConfiguration['relationType']) . 'Relation';
		if (!class_exists($relationSchemaClassName)) {
			throw new \Exception(((('Relation of type ' . $relationSchemaClassName) . ' not found (configured in "') . $relationJsonConfiguration['relationName']) . '")');
		}
		$relation = new $relationSchemaClassName();
		$relation->setName($relationJsonConfiguration['relationName']);
		//$relation->setInlineEditing((bool)$relationJsonConfiguration['inlineEditing']);
		$relation->setLazyLoading((bool) $relationJsonConfiguration['lazyLoading']);
		$relation->setExcludeField($relationJsonConfiguration['propertyIsExcludeField']);
		$relation->setDescription($relationJsonConfiguration['relationDescription']);
		$relation->setUniqueIdentifier($relationJsonConfiguration['uid']);
		return $relation;
	}

}

