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
 * A Domain Object Relation
 *
 * @PackageBuilder\Model
 */
abstract class Relation extends Property {

	/**
	 * The schema of the foreign class
	 * @var \TYPO3\PackageBuilder\Domain\Model\Package
	 */
	protected $foreignModel;

	/**
	 * The schema of the foreign class
	 * @var string
	 */
	protected $foreignClassName;

	/**
	 * @var string
	 */
	protected $foreignDatabaseTableName;

	/**
	 * If this flag is set to TRUE the relation is rendered as IRRE field (Inline Relational Record Editing). Default is FALSE.
	 * @var boolean
	 */
	protected $inlineEditing = FALSE;

	/**
	 * If this flag is set to TRUE the relation will be lazy loading. Default is FALSE
	 * @var bool;
	 */
	protected $lazyLoading = FALSE;

	/**
	 * @var bool;
	 */
	protected $relatedToExternalModel = FALSE;

	public function setRelatedToExternalModel($relatedToExternalModel) {
		$this->relatedToExternalModel = $relatedToExternalModel;
	}

	public function getRelatedToExternalModel() {
		return $this->relatedToExternalModel;
	}

	/**
	 *
	 * @return Tx_ExtensionBuilder_Domain_Model_DomainObject The foreign class
	 */
	public function getForeignModel() {
		return $this->foreignModel;
	}

	/**
	 * @return string
	 */
	public function getForeignDatabaseTableName() {
		if(is_object($this->foreignModel)) {
			return $this->foreignModel->getDatabaseTableName();
		} else {
			return $this->foreignDatabaseTableName;
		}
	}

	/**
	 * @param string
	 */
	public function setForeignDatabaseTableName( $foreignDatabaseTableName) {
		$this->foreignDatabaseTableName = $foreignDatabaseTableName;
	}

	/**
	 *
	 * @return string The foreign class
	 */
	public function getForeignClassName() {
		if(isset($this->foreignClassName)) {
			return $this->foreignClassName;
		}
		if(is_object($this->foreignModel)) {
			return $this->foreignModel->getClassName();
		}
		return NULL;
	}

	public function getForeignModelName() {
		if(is_object($this->foreignModel)) {
			return $this->foreignModel->getName();
		}
		$parts = explode('_Domain_Model_', $this->foreignClassName);
		return $parts[1];
	}

	/**
	 *
	 * @param Tx_ExtensionBuilder_Domain_Model_DomainObject $foreignClass Set the foreign class of the relation
	 */
	public function setForeignModel(Tx_ExtensionBuilder_Domain_Model_DomainObject $foreignClass) {
		$this->foreignModel = $foreignClass;
	}

	/**
	 *
	 * @param string  Set the foreign class nsme of the relation
	 */
	public function setForeignClassName( $foreignClassName) {
		$this->foreignClassName = $foreignClassName;
	}

	/**
	 * Sets the flag, if the relation should be rendered as IRRE field.
	 *
	 * @param bool $inlineEditing
	 * @return void
	 **/
	public function setInlineEditing($inlineEditing) {
		$this->inlineEditing = (bool)$inlineEditing;
	}

	/**
	 * Returns the state of the flag, if the relation should be rendered as IRRE field.
	 *
	 * @return bool TRUE if the field shopuld be rendered as IRRE field; FALSE otherwise
	 **/
	public function getInlineEditing() {
		return (bool)$this->inlineEditing;
	}

	/**
	 * Sets the lazyLoading flag
	 *
	 * @param  $lazyLoading
	 * @return void
	 */
	public function setLazyLoading($lazyLoading) {
		$this->lazyLoading = $lazyLoading;
	}

	/**
	 * Gets the lazyLoading flag
	 *
	 * @return bool
	 */
	public function getLazyLoading() {
		return $this->lazyLoading;
	}

	public function getSqlDefinition() {
		return $this->getFieldName() . " int(11) unsigned DEFAULT '0' NOT NULL,";
	}

	public function getIsDisplayable() {
		return FALSE;
	}


}

?>
