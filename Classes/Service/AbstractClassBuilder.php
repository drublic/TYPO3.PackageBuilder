<?php
namespace TYPO3\PackageBuilder\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */
use TYPO3\PackageBuilder\Domain\Model;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @FLOW3\Scope("singleton")
 */
abstract class AbstractClassBuilder {


	/**
	 * The current class object
	 * @var Model\ClassObject\ClassObject
	 */
	protected $classObject = NULL;

	/**
	 * @var \TYPO3\ParserApi\Service\Parser
	 */
	protected $classParser;

	/**
	 * @var Model\AbstractPackage
	 */
	protected $package;

	/**
	 * @var string
	 */
	protected $packageNamespace;

	/**
	 * @var string
	 */
	protected $packageDirectory;

	/**
	 * @var \TYPO3\PackageBuilder\Log\FileLogger
	 */
	protected $logger;

	/**
	 *
	 * @param Model\Extension
	 * @param boolean $roundTripEnabled
	 *
	 * @internal param \TYPO3\PackageBuilder\Service\AbstractCodeGenerator $codeGenerator
	 * @return void
	 */
	abstract public function initialize(Model\Extension $extension, $roundTripEnabled);

	/**
	 * This method generates the class schema object, which is passed to the template
	 * it keeps all methods and properties including user modified method bodies and comments
	 * needed to create a domain object class file
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 *
	 * @return Model\ClassObject\ClassObject
	 */
	public abstract function generateModelClassObject($domainObject, $mergeWithExistingClass);

	/**
	 * This method generates the class object, which is passed to the template
	 * it keeps all methods and properties including user modified method bodies and
	 * comments that are required to create a controller class file
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 *
	 * @return Model\ClassObject\ClassObject
	 */
	abstract public function generateControllerClassObject($domainObject, $mergeWithExistingClass);

	/**
	 * This method generates the repository class object, which is passed to the template
	 * it keeps all methods and properties including user modified method bodies and comments
	 * needed to create a repository class file
	 *
	 * @param Model\DomainObject $domainObject
	 * @param boolean $mergeWithExistingClass
	 *
	 * @return Model\ClassObject\ClassObject
	 */
	abstract public function generateRepositoryClassObject($domainObject, $mergeWithExistingClass);

	/**
	 * @param \TYPO3\FLOW3\Log\Logger $logger
	 */
	public function injectLogger(\TYPO3\FLOW3\Log\Logger $logger) {
		$this->logger = $logger;
	}

}

?>