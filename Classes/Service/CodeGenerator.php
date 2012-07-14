<?php
namespace TYPO3\PackageBuilder\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @FLOW3\Scope("singleton")
 */
class CodeGenerator {

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\PackageBuilder\Service\ClassBuilder
	 * @FLOW3\Inject
	 *
	 */
	protected $classBuilder;

	/**
	 * @var \TYPO3\PackageBuilder\Domain\Model\Package
	 */
	protected $package;

	static public $defaultActions = array(
		'createAction',
		'deleteAction',
		'editAction',
		'listAction',
		'newAction',
		'showAction',
		'updateAction'
	);

	/**
	 * alle file types where a split token makes sense
	 * @var array
	 */
	protected $filesSupportingSplitToken = array(
		'php', //ext_tables, tca, localconf
		'sql',
		'txt' // Typoscript/**
	);




}
