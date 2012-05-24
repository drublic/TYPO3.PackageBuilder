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

class Person {

	/**
	 * TODO make that work
	 * This Array contains all valid values for the role of a person.
	 * Extend here and in the locallang (mlang_Tx_ExtensionBuilder_domain_model_person_[rolekey from array]) to add new Roles.
	 *
	 * @var array
	 */
	protected static $ROLES = array('developer', 'product_manager');

	/**
	 * The Persons name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * TODO validation?
	 * The Persons role.
	 *
	 * @var string
	 * @see Tx_ExtensionBuilder_Domain_Model_Person::ROLES
	 */
	protected $role = '';

	/**
	 * The Emailadress.
	 *
	 * @var string
	 */
	protected $email = '';

	/**
	 * The Persons company.
	 *
	 * @var string
	 */
	protected $company = '';

	/**
	 * Gets the Name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the Name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Gets the role.
	 *
	 * @return string
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * Sets the role.
	 *
	 * @param string $role
	 * @return void
	 */
	public function setRole($role) {
		$this->role = $role;
	}

	/**
	 * Gets the email
	 *
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Sets the email
	 *
	 * @param string $email
	 * @return void
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Gets the company
	 *
	 * @return string
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * Sets the company
	 *
	 * @param string $company
	 * @return void
	 */
	public function setCompany($company) {
		$this->company = $company;
	}
}

?>
