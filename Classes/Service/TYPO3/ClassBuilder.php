<?php
namespace TYPO3\PackageBuilder\Service\TYPO3;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */
use TYPO3\PackageBuilder\Domain\Model;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @property mixed settings
 * @FLOW3\Scope("singleton")
 */
class ClassBuilder extends \TYPO3\PackageBuilder\Service\AbstractClassBuilder {


	/**
	 * @var \TYPO3\PackageBuilder\Service\TYPO3\RoundTrip
	 * @FLOW3\Inject
	 *
	 */
	protected $roundTripService;

	/**
	 * @var \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager
	 * @FLOW3\Inject
	 *
	 */
	protected $packageConfigurationManager;


	/**
	 *
	 * @var \TYPO3\PackageBuilder\Service\TYPO3\CodeGenerator
	 * @FLOW3\Inject
	 *
	 */
	protected $codeGenerator;

	/**
	 * This line is added to the constructor if there are storage objects to initialize
	 * @var string
	 */
	protected $initStorageObjectCall = "//Do not remove the next line: It would break the functionality\n\$this->initStorageObjects();";


	/**
	 *
	 * @param Model\Extension $extension
	 * @param boolean roundtrip enabled?
	 *
	 * @return void
	 */
	public function initialize(Model\Extension $extension, $roundTripEnabled) {
		$this->package = $extension;
		$settings = $extension->getSettings();
		if ($roundTripEnabled) {
			$this->roundTripService->initialize($this->package);
		}
		if (isset($settings['classBuilder'])) {
			$this->settings = $settings['classBuilder'];
		}
			// $this->packageDirectory = $this->package->getExtensionDir();
		$this->packageNamespace = $this->package->getNamespace();
	}

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
	public function generateModelClassObject($domainObject, $mergeWithExistingClass) {
		$this->logger->log(' generateModelClassObject: ' . $domainObject->getName());
			// reference to the resulting class file
		$this->classObject = NULL;
		$className = $domainObject->getClassName();

		if ($mergeWithExistingClass) {
			try {
				$this->classObject = $this->roundTripService->getDomainModelClass($domainObject);
			} catch (\Exception $e) {
				$this->logger->log('Class ' . $className . ' could not be imported: ' . $e->getMessage(), 2);
			}
		}

		if ($this->classObject == NULL) {
			$this->classObject = new Model\ClassObject\ClassObject($className);
			if ($domainObject->isEntity()) {
				$parentClass = $domainObject->getParentClass();
				if (empty($parentClass)) {
					$parentClass = $this->packageConfigurationManager->getParentClassForEntityObject($this->package->getKey());
				}
			} else {
				$parentClass = $this->packageConfigurationManager->getParentClassForValueObject($this->package->getKey());
			}
			$this->classObject->setParentClass($parentClass);
			$this->classObject->setNameSpace($this->package->getNameSpace() . '\\Domain\\Model');
		}

		if (!$this->classObject->hasDescription()) {
			$this->classObject->setDescription($domainObject->getDescription());
		}

		$this->addInitStorageObjectCalls($domainObject);

			// TODO the following part still needs some enhancement:
			// what should be obligatory in existing properties and methods
		foreach ($domainObject->getProperties() as $domainProperty) {
			$propertyName = $domainProperty->getName();
				// add the property to class Object (or update an existing class Object property)
			if ($this->classObject->propertyExists($propertyName)) {
				$classProperty = $this->classObject->getProperty($propertyName);
				// $classPropertyTags = $classProperty->getTags();
				// $this->logger->log('Property found: ' . $propertyName . ':' . $domainProperty->getTypeForComment(), 'extension_builder', 1, (array)$classProperty);
			} else {
				$classProperty = new Model\ClassObject\Property($propertyName);
				$classProperty->setTag('var', $domainProperty->getTypeForComment());
				$classProperty->addModifier('protected');
				// $this->logger->log('New property: ' . $propertyName . ':' . $domainProperty->getTypeForComment(), 'extension_builder', 1);
			}

			$classProperty->setAssociatedDomainObjectProperty($domainProperty);

			if ($domainProperty->getRequired()) {
				if (!$classProperty->isTaggedWith('validate')) {
					$validateTag = explode(' ', trim($domainProperty->getValidateAnnotation()));
					$classProperty->setTag('validate', $validateTag[1]);
				}
			}

			if ($domainProperty->isRelation() && $domainProperty->getLazyLoading()) {
				if (!$classProperty->isTaggedWith('lazy')) {
					$classProperty->setTag('lazy', '');
				}
			}

			if ($domainProperty->getHasDefaultValue()) {
				$classProperty->setDefault($domainProperty->getDefaultValue());
			}

			$this->classObject->setProperty($classProperty);

			if ($domainProperty->isNew()) {
				$this->setPropertyRelatedMethods($domainProperty);
			}
		}
			// $this->logger->log('Methods before sorting','extension_builder',0,array_keys($this->classObject->getMethods()));
			// $this->sortMethods($domainObject);
		return $this->classObject;
	}

	/**
	 * @param Model\DomainObject $domainObject
	 * @return void
	 */
	protected function addInitStorageObjectCalls(Model\DomainObject $domainObject) {
		$anyToManyRelationProperties = $domainObject->getAnyToManyRelationProperties();

		if (count($anyToManyRelationProperties) > 0) {
			if (!$this->classObject->methodExists('__construct')) {
				$constructorMethod = new Model\ClassObject\Method('__construct');
					// $constructorMethod->setDescription('The constructor of this '.$domainObject->getName());
				if (count($anyToManyRelationProperties) > 0) {
					$constructorMethod->setBody($this->codeGenerator->getDefaultMethodBody($domainObject, NULL, 'Model', '', 'construct'));
				}
				$constructorMethod->addModifier('public');
				$constructorMethod->setTag('return', 'void');
				$this->classObject->addMethod($constructorMethod);
			}
			$constructorMethod = $this->classObject->getMethod('__construct');
			if (preg_match('/\$this->initStorageObjects()/', $constructorMethod->getBody()) < 1) {
				$this->logger->log('Constructor method in Class ' . $this->classObject->getName() . ' was overwritten since the initStorageObjectCall was missing', 2);
				$constructorMethod->setBody($this->initStorageObjectCall);
				$this->classObject->setMethod($constructorMethod);
			}
				// initStorageObjects
			$initStorageObjectsMethod = new Model\ClassObject\Method('initStorageObjects');
			$initStorageObjectsMethod->setDescription('Initializes all ObjectStorage properties.');
			$methodBody = "/**\n* Do not modify this method!\n* It will be rewritten on each save in the extension builder\n* You may modify the constructor of this class instead\n*/\n";
			foreach ($anyToManyRelationProperties as $relationProperty) {
				$methodBody .= '\$this->' . $relationProperty->getName() . ' = new \\TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage();' . PHP_EOL;
			}
			$initStorageObjectsMethod->setBody($this->codeGenerator->getDefaultMethodBody($domainObject, NULL, 'Model', '', 'initStorageObjects'));
			$initStorageObjectsMethod->addModifier('protected');
			$initStorageObjectsMethod->setTag('return', 'void');
			$this->classObject->setMethod($initStorageObjectsMethod);
		} elseif ($this->classObject->methodExists('initStorageObjects')) {
			$this->classObject->getMethod('initStorageObjects')->setBody('// empty');
		}
	}

	/**
	 * add all setter/getter/add/remove etc. methods
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return void
	 */
	protected function setPropertyRelatedMethods($domainProperty) {
		$this->logger->log('setPropertyRelatedMethods:' . $domainProperty->getName(), 'extension_builder', 0, (array)$domainProperty);
		if (is_subclass_of($domainProperty, 'Model\\DomainObject\\Relation\\AnyToManyRelation')) {
			$addMethod = $this->buildAddMethod($domainProperty);
			$removeMethod = $this->buildRemoveMethod($domainProperty);
			$this->classObject->setMethod($addMethod);
			$this->classObject->setMethod($removeMethod);
		}
		$getMethod = $this->buildGetterMethod($domainProperty);
		$setMethod = $this->buildSetterMethod($domainProperty);
		$this->classObject->setMethod($getMethod);
		$this->classObject->setMethod($setMethod);
		if ($domainProperty->getTypeForComment() == 'boolean') {
			$isMethod = $this->buildIsMethod($domainProperty);
			$this->classObject->setMethod($isMethod);
		}
	}


	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildGetterMethod($domainProperty) {

		// add (or update) a getter method
		$getterMethodName = $this->getMethodName($domainProperty, 'get');
		if ($this->classObject->methodExists($getterMethodName)) {
			$getterMethod = $this->classObject->getMethod($getterMethodName);
			//$getterMethodTags = $getterMethod->getTags();
			//$this->logger->log('Existing getterMethod imported:' . $getterMethodName, 'extension_builder', 0, array('methodBody' => $getterMethod->getBody()));
		} else {
			$getterMethod = new Model\ClassObject\Method($getterMethodName);
			$this->logger->log('new getMethod:' . $getterMethodName, 'extension_builder', 0);
			// default method body
			$getterMethod->setBody($this->codeGenerator->getDefaultMethodBody(NULL, $domainProperty, 'Model', 'get', ''));
			$getterMethod->setTag('return', $domainProperty->getTypeForComment() . ' $' . $domainProperty->getName());
			$getterMethod->addModifier('public');
		}
		if (!$getterMethod->hasDescription()) {
			$getterMethod->setDescription('Returns the ' . $domainProperty->getName());
		}
		return $getterMethod;
	}

	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildSetterMethod($domainProperty) {

		$propertyName = $this->getParameterName($domainProperty, 'set');
		// add (or update) a setter method
		$setterMethodName = $this->getMethodName($domainProperty, 'set');
		if ($this->classObject->methodExists($setterMethodName)) {
			$setterMethod = $this->classObject->getMethod($setterMethodName);
			//$setterMethodTags = $setterMethod->getTags();
			//$this->logger->log('Existing setterMethod imported:' . $setterMethodName, 'extension_builder', 0, array('methodBody' => $setterMethod->getBody()));
		} else {
			//$this->logger->log('new setMethod:' . $setterMethodName, 'extension_builder', 0);
			$setterMethod = new Model\ClassObject\Method($setterMethodName);
			// default method body
			$setterMethod->setBody($this->codeGenerator->getDefaultMethodBody(NULL, $domainProperty, 'Model', 'set', ''));
			$setterMethod->setTag('param', $this->getParamTag($domainProperty, 'set'));
			$setterMethod->setTag('return', 'void');
			$setterMethod->addModifier('public');
		}
		if (!$setterMethod->hasDescription()) {
			$setterMethod->setDescription('Sets the ' . $propertyName);
		}
		$setterParameters = $setterMethod->getParameterNames();
		if (!in_array($propertyName, $setterParameters)) {
			$setterParameter = new Model\ClassObject\MethodParameter($propertyName);
			$setterParameter->setVarType($domainProperty->getTypeForComment());
			if (is_subclass_of($domainProperty, 'Model\\DomainObject\\Relation\\AbstractRelation')) {
				$setterParameter->setTypeHint($domainProperty->getTypeHint());
			}
			$setterMethod->setParameter($setterParameter);
		}
		return $setterMethod;
	}


	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildAddMethod($domainProperty) {

		$propertyName = $domainProperty->getName();

		$addMethodName = $this->getMethodName($domainProperty, 'add');

		if ($this->classObject->methodExists($addMethodName)) {
			$addMethod = $this->classObject->getMethod($addMethodName);
			//$this->logger->log('Existing addMethod imported:' . $addMethodName, 'extension_builder', 0, array('methodBody' => $addMethod->getBody()));
		} else {
			//$this->logger->log('new addMethod:' . $addMethodName, 'extension_builder', 0);
			$addMethod = new Model\ClassObject\Method($addMethodName);
			// default method body
			$addMethod->setBody($this->codeGenerator->getDefaultMethodBody(NULL, $domainProperty, 'Model', 'add', ''));
			$addMethod->setTag('param', $this->getParamTag($domainProperty, 'add'));

			$addMethod->setTag('return', 'void');
			$addMethod->addModifier('public');
		}
		$addParameters = $addMethod->getParameterNames();

		if (!in_array(Tx_ExtensionBuilder_Utility_Inflector::singularize($propertyName), $addParameters)) {
			$addParameter = new Model\ClassObject\MethodParameter($this->getParameterName($domainProperty, 'add'));
			$addParameter->setVarType($domainProperty->getForeignClassName());
			$addParameter->setTypeHint($domainProperty->getForeignClassName());
			$addMethod->setParameter($addParameter);
		}
		if (!$addMethod->hasDescription()) {
			$addMethod->setDescription('Adds a ' . $domainProperty->getForeignModelName());
		}
		return $addMethod;
	}

	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildRemoveMethod($domainProperty) {

		$propertyName = $domainProperty->getName();

		$removeMethodName = $this->getMethodName($domainProperty, 'remove');

		if ($this->classObject->methodExists($removeMethodName)) {
			$removeMethod = $this->classObject->getMethod($removeMethodName);
			//$this->logger->log('Existing removeMethod imported:' . $removeMethodName, 'extension_builder', 0, array('methodBody' => $removeMethod->getBody()));
		} else {
			//$this->logger->log('new removeMethod:' . $removeMethodName, 'extension_builder', 0);
			$removeMethod = new Model\ClassObject\Method($removeMethodName);
			// default method body
			$removeMethod->setBody($this->codeGenerator->getDefaultMethodBody(NULL, $domainProperty, 'Model', 'remove', ''));
			$removeMethod->setTag('param', $this->getParamTag($domainProperty, 'remove'));
			$removeMethod->setTag('return', 'void');
			$removeMethod->addModifier('public');
		}

		$removeParameters = $removeMethod->getParameterNames();

		if (!in_array($this->getParameterName($domainProperty, 'remove'), $removeParameters)) {
			$removeParameter = new Model\ClassObject\MethodParameter($this->getParameterName($domainProperty, 'remove'));
			$removeParameter->setVarType($domainProperty->getForeignClassName());
			$removeParameter->setTypeHint($domainProperty->getForeignClassName());
			$removeMethod->setParameter($removeParameter);
		}

		if (!$removeMethod->hasDescription()) {
			$removeMethod->setDescription('Removes a ' . $domainProperty->getForeignModelName());
		}
		return $removeMethod;
	}

	/**
	 * Builds a method that checks the current boolean state of a property
	 *
	 * @param Model\DomainObject\AbstractProperty $domainProperty
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildIsMethod($domainProperty) {

		$isMethodName = $this->getMethodName($domainProperty, 'is');

		if ($this->classObject->methodExists($isMethodName)) {
			$isMethod = $this->classObject->getMethod($isMethodName);
			//$this->logger->log('Existing isMethod imported:' . $isMethodName, 'extension_builder', 0, array('methodBody' => $isMethod->getBody()));
		} else {
			//$this->logger->log('new isMethod:' . $isMethodName, 'extension_builder', 1);
			$isMethod = new Model\ClassObject\Method($isMethodName);
			// default method body
			$isMethod->setBody($this->codeGenerator->getDefaultMethodBody(NULL, $domainProperty, 'Model', 'is', ''));
			$isMethod->setTag('return', 'boolean');
			$isMethod->addModifier('public');
		}

		if (!$isMethod->hasDescription()) {
			$isMethod->setDescription('Returns the boolean state of ' . $domainProperty->getName());
		}
		return $isMethod;
	}

	/**
	 *
	 * @param Model\DomainObject\Action $action
	 * @param Model\DomainObject $domainObject
	 *
	 * @return Model\ClassObject\Method
	 */
	protected function buildActionMethod(Model\DomainObject\Action $action, Model\DomainObject $domainObject) {
		$actionName = $action->getName();
		$actionMethodName = $actionName . 'Action';
		$actionMethod = new Model\ClassObject\Method($actionMethodName);
		$actionMethod->setDescription('action ' . $action->getName());
		$actionMethod->setBody($this->codeGenerator->getDefaultMethodBody($domainObject, NULL, 'Controller', '', $actionMethodName));
		$actionMethod->addModifier('public');
		if (in_array($actionName, array('show', 'edit', 'create', 'new', 'update', 'delete'))) {
			// these actions need a parameter
			if (in_array($actionName, array('create', 'new'))) {
				$parameterName = 'new' . $domainObject->getName();
			} else {
				$parameterName = \TYPO3\PackageBuilder\Utility\Tools::lcfirst($domainObject->getName());
			}
			$parameter = new Model\ClassObject\MethodParameter($parameterName);
			$parameter->setTypeHint('\\' . $this->package->getNameSpace() . '\\Domain\\Model\\' . $domainObject->getClassName());
			$parameter->setVarType('\\' .$this->package->getNameSpace() . '\\Domain\\Model\\' . $domainObject->getClassName());
			$parameter->setPosition(0);
			if ($actionName == 'new') {
				$parameter->setOptional(TRUE);
				$actionMethod->setTag('dontvalidate', '$' . $parameterName);
			}
			$actionMethod->setParameter($parameter);
		}
		$actionMethod->setTag('return', 'void');

		return $actionMethod;
	}

	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $property
	 * @param string $methodType (get,set,add,remove,is)
	 * @return string method name
	 */
	public function getMethodName($domainProperty, $methodType) {
		$propertyName = $domainProperty->getName();
		switch ($methodType) {
			case 'set'        :
				return 'set' . ucfirst($propertyName);

			case 'get'        :
				return 'get' . ucfirst($propertyName);

			case 'add'        :
				return 'add' . ucfirst(\Sho_Inflect::singularize($propertyName));

			case 'remove'    :
				return 'remove' . ucfirst(\Sho_Inflect::singularize($propertyName));

			case 'is'        :
				return 'is' . ucfirst($propertyName);
		}
	}

	/**
	 *
	 * @param Model\DomainObject\AbstractProperty $property
	 * @param string $methodType (set,add,remove)
	 * @return string method body
	 */
	public function getParameterName($domainProperty, $methodType) {

		$propertyName = $domainProperty->getName();

		switch ($methodType) {

			case 'set'            :
				return $propertyName;

			case 'add'            :
				return \Sho_Inflect::singularize($propertyName);

			case 'remove'        :
				return \Sho_Inflect::singularize($propertyName) . 'ToRemove';
		}
	}

	public function getParamTag($domainProperty, $methodType) {

		switch ($methodType) {
			case 'set'        :
				return $domainProperty->getTypeForComment() . ' $' . $domainProperty->getName();

			case 'add'        :
				$paramTag = $domainProperty->getForeignClassName();
				$paramTag .= ' $' . $this->getParameterName($domainProperty, 'add');
				return $paramTag;

			case 'remove'    :
				$paramTag = $domainProperty->getForeignClassName();
				$paramTag .= ' $' . $this->getParameterName($domainProperty, 'remove');
				$paramTag .= ' The ' . $domainProperty->getForeignModelName() . ' to be removed';
				return $paramTag;
		}
	}

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
	public function generateControllerClassObject($domainObject, $mergeWithExistingClass) {
		$this->logger->log('generateControllerClassObject(' . $domainObject->getName() . ')');

		$this->classObject = NULL;
		$className = $domainObject->getControllerName();

		if ($mergeWithExistingClass) {
			try {
				$this->classObject = $this->roundTripService->getControllerClass($domainObject);
			} catch (Exception $e) {
				$this->logger->log('Class ' . $className . ' could not be imported: ' . $e->getMessage());
			}
		}

		if ($this->classObject == NULL) {
			$this->classObject = new Model\ClassObject\ClassObject($className);
			if (isset($this->settings['Controller']['parentClass'])) {
				$parentClass = $this->settings['Controller']['parentClass'];
			} else {
				$parentClass = '\\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController';
			}
			$this->classObject->setParentClass($parentClass);
			$this->classObject->setNameSpace($this->package->getNameSpace() . '\\Controller');
		}

		if ($domainObject->isAggregateRoot()) {
			$propertyName = \TYPO3\PackageBuilder\Utility\Tools::lcfirst($domainObject->getName()) . 'Repository';
			// now add the property to class Object (or update an existing class Object property)
			if (!$this->classObject->propertyExists($propertyName)) {
				$classProperty = new Model\ClassObject\Property($propertyName);
				$classProperty->setTag('var', $domainObject->getDomainRepositoryClassName());
				$classProperty->addModifier('protected');
				$this->classObject->setProperty($classProperty);
			}

			$injectMethodName = 'inject' . $domainObject->getName() . 'Repository';
			if (!$this->classObject->methodExists($injectMethodName)) {
				$repositoryVarName = \TYPO3\PackageBuilder\Utility\Tools::lcfirst($domainObject->getName()) . 'Repository';
				$repositoryQualifiedClassName = '\\' . $this->package->getNameSpace() . '\\Domain\\Repository\\' . $domainObject->getDomainRepositoryClassName();
				$injectMethod = new Model\ClassObject\Method($injectMethodName);
				$injectMethod->setBody('$this->' . $repositoryVarName . ' = $' . $repositoryVarName . ';');
				$injectMethod->setTag('param',$repositoryQualifiedClassName . ' $' . $repositoryVarName);
				$injectMethod->setTag('return', 'void');
				$injectMethod->addModifier('public');
				$parameter = new Model\ClassObject\MethodParameter($repositoryVarName);
				$parameter->setVarType($repositoryQualifiedClassName);
				$parameter->setTypeHint($repositoryQualifiedClassName);
				$parameter->setPosition(0);
				$injectMethod->setParameter($parameter);
				$this->classObject->addMethod($injectMethod);
			}
		}
		foreach ($domainObject->getActions() as $action) {
			$actionMethodName = $action->getName() . 'Action';
			if (!$this->classObject->methodExists($actionMethodName)) {
				$actionMethod = $this->buildActionMethod($action, $domainObject);
				$this->classObject->addMethod($actionMethod);
			}
		}
		return $this->classObject;
	}

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
	public function generateRepositoryClassObject($domainObject, $mergeWithExistingClass) {
		$this->logger->log('generateRepositoryClassObject(' . $domainObject->getName() . ')');

		$this->classObject = NULL;
		$className = $domainObject->getDomainRepositoryClassName();
		if ($mergeWithExistingClass) {
			try {
				$this->classObject = $this->roundTripService->getRepositoryClass($domainObject);
			} catch (Exception $e) {
				$this->logger->log('Class ' . $className . ' could not be imported: ' . $e->getMessage());
			}
		}

		if ($this->classObject == NULL) {
			$this->classObject = new Model\ClassObject\ClassObject($className);

			if (isset($this->settings['Repository']['parentClass'])) {
				$parentClass = $this->settings['Repository']['parentClass'];
			} else {
				$parentClass = '\\TYPO3\\CMS\\Extbase\\Persistence\\Repository';
			}
			$this->classObject->setParentClass($parentClass);
			$this->classObject->setNameSpace($this->package->getNameSpace() . '\\Domain\\Repository');
		}

		return $this->classObject;
	}

	/**
	 * Not used right now
	 * TODO: Needs better implementation
	 * @param Model\DomainObject $domainObject
	 * @return void
	 */
	public function sortMethods($domainObject) {

		$objectProperties = $domainObject->getProperties();
		$sortedProperties = array();
		$propertyRelatedMethods = array();
		$customMethods = array();

		// sort all properties and methods according to domainObject sort order
		foreach ($objectProperties as $objectProperty) {
			if ($this->classObject->propertyExists($objectProperty->getName())) {
				$sortedProperties[$objectProperty->getName()] = $this->classObject->getProperty($objectProperty->getName());
				$methodPrefixes = array('get', 'set', 'add', 'remove', 'is');
				foreach ($methodPrefixes as $methodPrefix) {
					$methodName = $this->getMethodName($objectProperty, $methodPrefix);
					if ($this->classObject->methodExists($methodName)) {
						$propertyRelatedMethods[$methodName] = $this->classObject->getMethod($methodName);
					}
				}
			}
		}

		// add the properties that were not in the domainObject
		$classProperties = $this->classObject->getProperties();
		$sortedPropertyNames = array_keys($sortedProperties);
		foreach ($classProperties as $classProperty) {
			if (!in_array($classProperty->getName(), $sortedProperties)) {
				$sortedProperties[$classProperty->getName()] = $classProperty;
			}
		}
		// add custom methods that were manually added to the class
		$classMethods = $this->classObject->getMethods();
		$propertyRelatedMethodNames = array_keys($propertyRelatedMethods);
		foreach ($classMethods as $classMethod) {
			if (!in_array($classMethod->getName(), $propertyRelatedMethodNames)) {
				$customMethods[$classMethod->getName()] = $classMethod;
			}
		}
		$sortedMethods = array_merge($customMethods, $propertyRelatedMethods);
		//$this->logger->log('Methods after sorting', 'extension_builder', 0, array_keys($sortedMethods));

		$this->classObject->setProperties($sortedProperties);
		$this->classObject->setMethods($sortedMethods);
	}

}

?>