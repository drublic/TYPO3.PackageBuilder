<?php
 namespace TYPO3\PackageBuilder\Persistence;

 /*                                                                        *
  * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
  *                                                                        *
  *                                                                        */

 use TYPO3\FLOW3\Annotations as FLOW3;

 /**
  *
  *
  */
 interface PersistenceManagerInterface {
	 /**
	 * Load the PackageBuilder configuration for a certain $packageKey, and return it
	 *
	 * @param string $packageKey
	 * @return array
	 */
	public function load($packageKey);

	/**
	 * Save the PackageBuilder configuration identified by $packageKey
	 *
	 * @param string $packageKey
	 * @param array $packageDefinition
	 */
	public function save($packageKey, array $packageDefinition);

	/**
	 * List all form definitions which can be loaded through this form persistence
	 * manager.
	 *
	 * Returns an associative array with each item containing the keys 'name' (the human-readable name of the form)
	 * and 'persistenceIdentifier' (the unique identifier for the Form Persistence Manager e.g. the path to the saved form definition).
	 *
	 * @return array in the format array(array('name' => 'PackageName', 'persistenceIdentifier' => 'package key'), array( .... ))
	 */
	public function listPackages();
 }