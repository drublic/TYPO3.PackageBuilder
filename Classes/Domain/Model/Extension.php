<?php
namespace TYPO3\PackageBuilder\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\PackageBuilder\Annotations as PackageBuilder;

/**
 * A Extension
 *
 * @PackageBuilder\Model
 */
class Extension extends AbstractPackage{


	/**
	 * default settings for em_conf
	 * @var array
	 */
	protected $emConfDefaults = array('dependencies' => 'cms,extbase,fluid', 'category' => 'plugin');

	/**
	 * @var string
	 */
	protected $priority = '';

	/**
	 * @var boolean
	 */
	protected $shy = FALSE;


	/**
	 * @var boolean
	 */
	protected $supportVersioning = TRUE;


	/**
	 * Is an upload folder required for this extension
	 *
	 * @var boolean
	 */
	protected $needsUploadFolder = FALSE;

	/**
	 *
	 * an array keeping all md5 hashes of all files in the extension to detect modifications
	 *
	 * @var array
	 */
	protected $md5Hashes = array();


	/**
	 * plugins
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\Plugin>
	 * @ORM\OneToMany
	 */
	private $plugins;

	/**
	 * backend modules
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\BackendModule>
	 * @ORM\OneToMany
	 */
	private $backendModules;

	/**
	 * was the extension renamed?
	 * @var boolean
	 */
	private $renamed = FALSE;

	/**
	 *
	 * @return string
	 */
	public function getExtensionKey() {
		return $this->identifier;
	}

	/**
	 *
	 * @param string $extensionKey
	 */
	public function setExtensionKey($extensionKey) {
		$this->identifier = $extensionKey;
	}

	/**
	 *
	 * @return string
	 */
	public function getOriginalExtensionKey() {
		return $this->originalExtensionKey;
	}

	/**
	 *
	 * @param string $extensionKey
	 */
	public function setOriginalExtensionKey($extensionKey) {
		$this->originalExtensionKey = $extensionKey;
	}



	/**
	 *
	 *
	 * @return array settings for Extension Manager
	 */
	public function getEmConf() {

		if (isset($this->settings['emConf'])) {
			return $this->settings['emConf'];
		}
		else return $this->emConfDefaults;
	}

	/**
	 *
	 * @return string
	 */
	public function getExtensionDir() {
		if (empty($this->packageDir)) {
			if (empty($this->identifier)) {
				throw new \TYPO3\PackageBuilder\Exception('ExtensionDir can only be created if an extensionKey is defined first');
			}
			$this->packageDir = PATH_typo3conf . 'ext/' . $this->identifier . '/';
		}
		return $this->packageDir;
	}

	/**
	 *
	 * @param string $extensionDir
	 */
	public function setExtensionDir($packageDir) {
		$this->packageDir = $packageDir;
	}




	/**
	 * An array of domain objects for which a controller should be built.
	 * This is done in the following two cases:
	 * - Domain Objects which are aggregate roots
	 * - Actions defined for these domain objects
	 *
	 * @return array
	 */
	public function getDomainObjectsForWhichAControllerShouldBeBuilt() {
		$domainObjects = array();
		foreach ($this->domainObjects as $domainObject) {
			if (count($domainObject->getActions()) || $domainObject->isAggregateRoot()) {
				$domainObjects[] = $domainObject;
			}
		}
		return $domainObjects;
	}

	/**
	 * get all domainobjects that are mapped to existing tables
	 * @return array|null
	 */
	public function getDomainObjectsThatNeedMappingStatements() {
		$domainObjectsThatNeedMappingStatements = array();
		foreach ($this->domainObjects as $domainObject) {
			if ($domainObject->getNeedsMappingStatement()) {
				$domainObjectsThatNeedMappingStatements[] = $domainObject;
			}
		}
		if (!empty($domainObjectsThatNeedMappingStatements)) {
			return $domainObjectsThatNeedMappingStatements;
		} else {
			return NULL;
		}
	}

	/**
	 * get all domainobjects that are mapped to existing tables
	 * @return array|null
	 */
	public function getClassHierarchy() {
		$classHierarchy = array();
		foreach ($this->domainObjects as $domainObject) {
			if ($domainObject->isSubclass()) {
				if (!is_array($classHierarchy[$domainObject->getParentClass()])) {
					$classHierarchy[$domainObject->getParentClass()] = array();
				}
				$classHierarchy[$domainObject->getParentClass()][] = $domainObject;
			}
		}
		if (!empty($classHierarchy)) {
			return $classHierarchy;
		} else {
			return NULL;
		}
	}

	public function getDomainObjectsInHierarchicalOrder() {
		$domainObjects = $this->getDomainObjects();
		$classHierarchy = $this->getClassHierarchy();
		for ($i = 0; $i < count($domainObjects); $i++) {
			for ($j = 0; $j < count($domainObjects); $j++) {
				if (isParentOf($domainObjects[$i], $domainObjects[$j], $classHierarchy)) {
					$tmp = $domainObjects[$j];
					$domainObjects[$j] = $domainObjects[$i];
					$domainObjects[$i] = $tmp;
				}
			}
		}
	}

	protected function isParentOf($domainObject1, $domainObject2, $classHierarchy) {
		if (isset($classHierarchy[$domainObject1->getClassName()])) {
			foreach ($classHierarchy[$domainObject1->getClassName()] as $subClass) {
				if ($subClass->getClassName() == $domainObject2->getClassName()) {
					// $domainObject2 is parent of $domainObject1
					return TRUE;
				} else {
					if ($this->isParentOf($subClass, $domainObject2, $classHierarchy)) {
						// if a subclass of object1 is a parent class
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Add a domain object to the extension. Creates the reverse link as well.
	 * @param Tx_ExtensionBuilder_Domain_Model_DomainObject $domainObject
	 */
	public function addDomainObject(Tx_ExtensionBuilder_Domain_Model_DomainObject $domainObject) {
		$domainObject->setExtension($this);
		if (in_array($domainObject->getName(), array_keys($this->domainObjects))) {
			throw new \TYPO3\PackageBuilder\Exception('Duplicate domain object name "' . $domainObject->getName() . '".', Tx_ExtensionBuilder_Domain_Validator_ExtensionValidator::ERROR_DOMAINOBJECT_DUPLICATE);
		}
		if ($domainObject->getNeedsUploadFolder()) {
			$this->needsUploadFolder = TRUE;
		}
		$this->domainObjects[$domainObject->getName()] = $domainObject;
	}

	/**
	 *
	 * @param string $domainObjectName
	 * @return Tx_ExtensionBuilder_Domain_Model_DomainObject
	 */
	public function getDomainObjectByName($domainObjectName) {
		if (isset($this->domainObjects[$domainObjectName])) {
			return $this->domainObjects[$domainObjectName];
		}
		return NULL;
	}

	/**
	 * returns the extension key a prefix tx_  and without underscore
	 */
	public function getShortExtensionKey() {
		return 'tx_' . str_replace('_', '', $this->getExtensionKey());
	}

	/**
	 * Returns the Persons
	 *
	 * @return array<Tx_ExtensionBuilder_Domain_Model_Person>
	 */
	public function getPersons() {
		return $this->persons;
	}

	/**
	 * Sets the Persons
	 *
	 * @param array<Tx_ExtensionBuilder_Domain_Model_Person> $persons
	 * @return void
	 */
	public function setPersons($persons) {
		$this->persons = $persons;
	}

	/**
	 * Adds a Person to the end of the current Set of Persons.
	 *
	 * @param Tx_ExtensionBuilder_Domain_Model_Person $person
	 * @return void
	 */
	public function addPerson($person) {
		$this->persons[] = $person;
	}

	/**
	 * Setter for plugin
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_ExtensionBuilder_Domain_Model_Plugin> $plugins
	 * @return void
	 */
	public function setPlugins(Tx_Extbase_Persistence_ObjectStorage $plugins) {
		$this->plugins = $plugins;
	}

	/**
	 * Getter for $plugin
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_ExtensionBuilder_Domain_Model_Plugin>
	 */
	public function getPlugins() {
		return $this->plugins;
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasPlugins() {
		if (count($this->plugins) > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Add $plugin
	 *
	 * @param Tx_ExtensionBuilder_Domain_Model_Plugin
	 * @return void
	 */
	public function addPlugin(Tx_ExtensionBuilder_Domain_Model_Plugin $plugin) {
		$this->plugins[] = $plugin;
	}

	/**
	 * Setter for backendModule
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_ExtensionBuilder_Domain_Model_BackendModule> $backendModules
	 * @return void
	 */
	public function setBackendModules(Tx_Extbase_Persistence_ObjectStorage $backendModules) {
		$this->backendModules = $backendModules;
	}

	/**
	 * Getter for $backendModule
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_ExtensionBuilder_Domain_Model_Plugin>
	 */
	public function getBackendModules() {
		return $this->backendModules;
	}

	/**
	 * Add $backendModule
	 *
	 * @param Tx_ExtensionBuilder_Domain_Model_BackendModule
	 * @return void
	 */
	public function addBackendModule(Tx_ExtensionBuilder_Domain_Model_BackendModule $backendModule) {
		$this->backendModules[] = $backendModule;
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasBackendModules() {
		if (count($this->backendModules) > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getReadableState() {
		switch ($this->getState()) {
			case self::STATE_ALPHA:
				return 'alpha';
			case self::STATE_BETA:
				return 'beta';
			case self::STATE_STABLE:
				return 'stable';
			case self::STATE_EXPERIMENTAL:
				return 'experimental';
			case self::STATE_TEST:
				return 'test';
		}
		return '';
	}


	public function getCssClassName() {
		return 'tx-' . str_replace('_', '-', $this->getExtensionKey());
	}

	public function isModified($filePath) {
		if (is_file($filePath) && isset($this->md5Hashes[$filePath])) {
			if (md5_file($filePath) != $this->md5Hashes[$filePath]) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * setter for md5 hashes
	 * @return void
	 */
	public function setMD5Hashes($md5Hashes) {
		$this->md5Hashes = $md5Hashes;
	}

	/**
	 * getter for md5 hashes
	 * @return array $md5Hashes
	 */
	public function getMD5Hashes() {
		return $this->md5Hashes;
	}

	/**
	 * calculates all md5 hashes
	 * @return
	 */
	public function setMD5Hash($filePath) {
		$this->md5Hashes[$filePath] = md5_file($filePath);

	}

	/**
	 * Get the previous extension directory
	 * if the extension was renamed it is different from $this->extensionDir
	 *
	 * @return void
	 */
	public function getPreviousExtensionDirectory() {
		if ($this->isRenamed()) {
			$originalExtensionKey = $this->getOriginalExtensionKey();
			$this->previousExtensionDirectory = PATH_typo3conf . 'ext/' . $originalExtensionKey . '/';
			$this->previousExtensionKey = $originalExtensionKey;
			return $this->previousExtensionDirectory;
		}
		else {
			return $this->extensionDir;
		}
	}

	/**
	 *
	 * @return boolean
	 */
	public function isRenamed() {
		$originalExtensionKey = $this->getOriginalExtensionKey();
		if (!empty($originalExtensionKey) && $originalExtensionKey != $this->getExtensionKey()) {
			$this->renamed = TRUE;
		}
		return $this->renamed;
	}

	/**
	 * Getter for $needsUploadFolder
	 *
	 * @return boolean $needsUploadFolder
	 */
	public function getNeedsUploadFolder() {
		if ($this->needsUploadFolder) {
			return 1;
		}
		else {
			return 0;
		}
	}

	/**
	 *
	 * @return string $uploadFolder
	 */
	public function getUploadFolder() {
		return 'uploads/' . $this->getShortExtensionKey();
	}

	/**
	 * @return string
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @param string $priority
	 */
	public function setPriority($priority) {
		$this->priority = $priority;
	}

	/**
	 * @return boolean
	 */
	public function getShy() {
		return $this->shy;
	}

	/**
	 * @param boolean $shy
	 * @return void
	 */
	public function setShy($shy) {
		$this->shy = $shy;
	}

	/**
	 * @param boolean $supportVersioning
	 */
	public function setSupportVersioning($supportVersioning) {
		$this->supportVersioning = $supportVersioning;
	}

	/**
	 * @return boolean
	 */
	public function getSupportVersioning() {
		return $this->supportVersioning;
	}

}

?>
