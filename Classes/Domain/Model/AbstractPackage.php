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
	 * The development state. One of the STATE_* constants.
	 * @var integer
	 */
	protected $state = 0;

	const STATE_ALPHA = 0;
	const STATE_BETA = 1;
	const STATE_STABLE = 2;
	const STATE_EXPERIMENTAL = 3;
	const STATE_TEST = 4;

	/**
	 * The package key
	 * @var string
	 */
	protected $key;

	/**
	 * All domain objects
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\DomainObject>
	 * @ORM\OneToMany
	 */
	protected $domainObjects = array();

	/**
	 * The Persons working on the Extension
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\Person>
	 * @ORM\OneToMany
	 */
	protected $persons = array();

	/**
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The original package key (if a package was renamed)
	 * @var string
	 */
	protected $originalKey;

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
	 * @param string $originalKey
	 */
	public function setOriginalKey($originalKey) {
		$this->originalKey = $originalKey;
	}

	/**
	 * @return string
	 */
	public function getOriginalKey() {
		return $this->originalKey;
	}
}
?>