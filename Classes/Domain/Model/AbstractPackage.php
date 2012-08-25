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
 * A Package
 *
 * @PackageBuilder\Model
 */
class AbstractPackage extends AbstractModel{

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * The package key
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $nameSpace;

	/**
	 * the directory where the packages/extensions are stored
	 *
	 * @var string
	 */
	protected $baseDir;

	/**
	 * Package dir
	 * @var string
	 */
	protected $packageDir;

	/**
	 * version
	 * @var string
	 */
	protected $version;

	/**
	 *
	 * @var string
	 */
	protected $description;


	/**
	 *
	 * @var array
	 */
	protected $settings = array();


	/**
	 * @var string
	 */
	protected $category;


	/**
	 * The package's state. One of the STATE_* constants.
	 * @var integer
	 */
	protected $state = 0;

	const STATE_ALPHA = 0;
	const STATE_BETA = 1;
	const STATE_STABLE = 2;
	const STATE_EXPERIMENTAL = 3;
	const STATE_TEST = 4;

	/**
	 * All domain objects
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\DomainObject>
	 */
	protected $domainObjects = array();

	/**
	 * The Persons working on the Extension
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\Person>
	 */
	protected $persons = array();

	/**
	 * was the extension renamed?
	 * @var boolean
	 */
	private $renamed = FALSE;

	/**
	 * @var array
	 */
	private $dependencies = array();


	/**
	 *
	 */
	public function getDomainObjects(){
        return $this->domainObjects();
	}


	/**
	 * @param array $dependencies
	 */
	public function setDependencies($dependencies) {
		$this->dependencies = $dependencies;
	}

	/**
	 * @return array
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * @param float $targetVersion
	 */
	public function setTargetVersion($targetVersion) {
		$this->targetVersion = $targetVersion;
	}

	/**
	 * @return float
	 */
	public function getTargetVersion() {
		return $this->targetVersion;
	}

	/**
	 * @param string $category
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	/**
	 * @return string
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $packageDir
	 */
	public function setPackageDir($packageDir) {
		$this->packageDir = $packageDir;
	}

	/**
	 * @return string
	 */
	public function getPackageDir() {
		if(empty($this->packageDir)) {
			if(empty($this->baseDir)) {
				throw new \TYPO3\PackageBuilder\Exception\ConfigurationError('No base package dir configured');
			}
			$this->packageDir = $this->baseDir . $this->key;
		}
		return $this->packageDir;
	}

	/**
	 * @param array $persons
	 */
	public function setPersons($persons) {
		$this->persons = $persons;
	}

	/**
	 * @return array
	 */
	public function getPersons() {
		return $this->persons;
	}

	/**
	 * @param boolean $renamed
	 */
	public function setRenamed($renamed) {
		$this->renamed = $renamed;
	}

	/**
	 * @return boolean
	 */
	public function getRenamed() {
		return $this->renamed;
	}

	/**
	 * @param array $settings
	 */
	public function setSettings($settings) {
		$this->settings = $settings;
	}

	/**
	 * @return array
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * @param int $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param string $nameSpace
	 */
	public function setNameSpace($nameSpace) {
		$this->nameSpace = $nameSpace;
	}

	/**
	 * @return string
	 */
	public function getNameSpace() {
		if(empty($this->nameSpace) && !empty($this->key)) {
			$this->nameSpace = str_replace('.', '\\', $this->key);
		}
		return $this->nameSpace;
	}

	/**
	 * @param string $baseDir
	 */
	public function setBaseDir($baseDir) {
		$this->baseDir = $baseDir;
	}

	/**
	 * @return string
	 */
	public function getBaseDir() {
		return $this->baseDir;
	}

}
?>