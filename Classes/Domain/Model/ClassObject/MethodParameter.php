<?php
/***************************************************************
 *  Copyright notice
 *
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
namespace TYPO3\PackageBuilder\Domain\Model\ClassObject;

/**
 * parameter representing a method parameterin the context of software development
 *
 * @package PackageBuilder
 * @version $ID:$
 */
class MethodParameter {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $varType;

	/**
	 * @var mixed
	 */
	protected $typeHint = NULL;

	/**
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * @var int
	 */
	protected $position;

	/**
	 * @var boolean
	 */
	protected $optional;

	/**
	 * @var boolean
	 */
	protected $passedByReference;

	/**
	 * @param $propertyName
	 * @param $propertyReflection (optional)
	 * @return void
	 */
	public function __construct($parameterName) {
		$this->name = $parameterName;
	}

	/**
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns $varType.
	 */
	public function getVarType() {
		return $this->varType;
	}

	/**
	 * Sets $varType.
	 *
	 * @param object $varType
	 */
	public function setVarType($varType) {
		$this->varType = $varType;
	}

	/**
	 * @return int $position
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * setter for position
	 *
	 * @param int $position
	 * @return void
	 */
	public function setPosition($position) {
		$this->position = $position;
	}

	/**
	 * getter for defaultValue
	 *
	 * @return mixed
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}

	/**
	 * setter for defaultValue
	 *
	 * @param $defaultValue
	 * @return void
	 */
	public function setDefaultValue($defaultValue = NULL) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function isOptional() {
		return $this->optional;
	}

	/**
	 * @param $optional
	 * @return void
	 */
	public function setOptional($optional) {
		$this->optional = $optional;
	}

	/**
	 * @return boolean
	 */
	public function isPassedByReference() {
		return $this->passedByReference;
	}

	/**
	 * @return
	 */
	public function getTypeHint() {
		return $this->typeHint;
	}

	/**
	 * Sets $typeHint.
	 *
	 * @param object $typeHint
	 * @see Tx_ExtensionBuilder_Domain_Model_Class_MethodParameter::$typeHint
	 */
	public function setTypeHint($typeHint) {
		$this->typeHint = $typeHint;
	}

}

