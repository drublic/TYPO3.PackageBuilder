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
 * A Controler Action
 *
 * @PackageBuilder\Model
 */
class Action {

	/**
	 * The action's name
	 * @var string
	 */
	protected $name;

	/**
	 * The domain object this action belongs to.
	 * @var Tx_ExtensionBuilder_Domain_Model_DomainObject
	 */
	protected $domainObject;

	/**
	 * Is a template required for this action?
	 *
	 * @var boolean
	 */
	protected $needsTemplate = FALSE;

	/**
	 * Is a form required in the template for this action?
	 *
	 * @var boolean
	 */
	protected $needsForm = FALSE;

	/**
	 * Is a property partial required in the template for this action?
	 *
	 * @var boolean
	 */
	protected $needsPropertyPartial = FALSE;

	/**
	 * these actions do not need a template since they are never rendered
	 * @var array
	 */
	protected $actionNamesWithNoRendering = array(
		'create',
		'update',
		'delete'
	);

	/**
	 * these actions need a form
	 * @var array
	 */
	protected $actionNamesWithForm = array(
		'new',
		'edit'
	);

	/**
	 * these actions should not be cached
	 * @var array
	 */
	protected $actionNamesThatShouldNotBeCached = array(
		'create',
		'update',
		'delete'
	);

	/**
	 * flag: TRUE if the action is cacheable
	 * @var bool
	 */
	protected $cacheable;

	/**
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * DO NOT CALL DIRECTLY! This is being called by addAction() automatically.
	 * @param Tx_ExtensionBuilder_Domain_Model_DomainObject $domainObject the domain object this actions belongs to
	 */
	public function setDomainObject(Tx_ExtensionBuilder_Domain_Model_DomainObject $domainObject) {
		$this->domainObject = $domainObject;
	}

	/**
	 *
	 * @return Tx_ExtensionBuilder_Domain_Model_DomainObject
	 */
	public function getDomainObject() {
		return $this->domainObject;
	}

	/**
	 * Is a template required for this action?
	 *
	 * @return boolean
	 */
	public function getNeedsTemplate() {
		if (in_array($this->getName(), $this->actionNamesWithNoRendering)) {
			$this->needsTemplate = FALSE;
		}
		else {
			$this->needsTemplate = TRUE;
		}
		return $this->needsTemplate;
	}

	/**
	 * Is a form required to render the actions template?
	 *
	 * @return boolean
	 */
	public function getNeedsForm() {
		if (in_array($this->getName(), $this->actionNamesWithForm)) {
			$this->needsForm = TRUE;
		}
		else {
			$this->needsForm = FALSE;
		}
		return $this->needsForm;
	}

	/**
	 * Is a property partial needed to render the actions template?
	 *
	 * @return boolean
	 */
	public function getNeedsPropertyPartial() {
		if ($this->getName() == 'show') {
			$this->needsPropertyPartial = TRUE;
		}
		else {
			$this->needsPropertyPartial = FALSE;
		}
		return $this->needsPropertyPartial;
	}

	/**
	 * setter for cacheable flag
	 *
	 * @param boolean $cacheable
	 */
	public function setCacheable($cacheable) {
		$this->cacheable = $cacheable;
	}

	/**
	 * Getter for cacheable
	 *
	 * @return boolean $cacheable
	 */
	public function getCacheable() {
		return $this->isCacheable();
	}

	/**
	 * should this action be cacheable
	 *
	 * @return boolean
	 */
	public function isCacheable() {
		if (!isset($this->cacheable)) {
			$this->cacheable = !in_array($this->getName(), $this->actionNamesThatShouldNotBeCached);
		}
		return $this->cacheable;
	}
}

?>
