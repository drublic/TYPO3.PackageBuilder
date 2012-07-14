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
	protected $name;

	/**
	 * The package key
	 * @var string
	 */
	protected $identifier;

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
	 * The extension's state. One of the STATE_* constants.
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
	 * @var array<Tx_ExtensionBuilder_Domain_Model_DomainObject>
	 */
	protected $domainObjects = array();

	/**
	 * The Persons working on the Extension
	 * @var array<Tx_ExtensionBuilder_Domain_Model_Person>
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


	public function getDomainObjects(){}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}
?>