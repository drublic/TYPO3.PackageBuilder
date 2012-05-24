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

class Plugin {

	/**
	 * @var array
	 */
	protected static $TYPES = array('list_type', 'CType');

	/**
	 * The plugin name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * The type
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * The plugin key
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * array with configuration arrays
	 * array('controller' => 'MyController', 'actions' => 'action1,action2')
	 *
	 * @var array
	 */
	protected $controllerActionCombinations;

	/**
	 * array with configuration arrays
	 * array('controller' => 'MyController', 'actions' => 'action1,action2')
	 * @var array
	 */
	protected $noncacheableControllerActions;

	/**
	 * @var array
	 */
	protected $switchableControllerActions;

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
	 * Setter for type
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Getter for type
	 *
	 * @return string type
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Setter for key
	 *
	 * @param string $key
	 * @return void
	 */
	public function setKey($key) {
		$this->key = strtolower($key);
	}

	/**
	 * Getter for key
	 *
	 * @return string key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param array $controllerActionCombinations
	 */
	public function setControllerActionCombinations(array $controllerActionCombinations) {
		$this->controllerActionCombinations = $controllerActionCombinations;
	}

	/**
	 * @return array
	 */
	public function getControllerActionCombinations() {
		return $this->controllerActionCombinations;
	}

	/**
	 * @param array $noncacheableControllerActions
	 */
	public function setNoncacheableControllerActions(array $noncacheableControllerActions) {
		$this->noncacheableControllerActions = $noncacheableControllerActions;
	}

	/**
	 * @return array
	 */
	public function getNoncacheableControllerActions() {
		return $this->noncacheableControllerActions;
	}

	/**
	 * @param array $switchableControllerActions
	 */
	public function setSwitchableControllerActions($switchableControllerActions) {
		$this->switchableControllerActions = $switchableControllerActions;
	}

	/**
	 * @return boolean
	 */
	public function getSwitchableControllerActions() {
		return $this->switchableControllerActions;
	}
}

?>
