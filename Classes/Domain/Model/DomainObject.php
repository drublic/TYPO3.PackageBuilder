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
 * A Domain Object
 *
 * @PackageBuilder\Model
 */
class DomainObject extends AbstractModel{

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var array<\TYPO3\PackageBuilder\Domain\Model\Property>
	 * @ORM\OneToMany
	 */
	protected $properties = array();

	/**
	 * @var \TYPO3\PackageBuilder\Domain\Model\Package
	 * @ORM\ManyToOne
	 */
	protected $package;

	/**
	 * @param array $children
	 */
	public function setProperties($properties) {
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}


	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param \TYPO3\PackageBuilder\Domain\Model\Package $package
	 */
	public function setPackage($package) {
		$this->package = $package;
	}

	/**
	 * @return \TYPO3\PackageBuilder\Domain\Model\Package
	 */
	public function getPackage() {
		return $this->package;
	}
}
?>