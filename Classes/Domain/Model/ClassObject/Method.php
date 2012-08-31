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
 * method representing a "method" in the context of software development
 *
 * @package PackageBuilder
 * @version $ID:$
 */
class Method extends \TYPO3\PackageBuilder\Domain\Model\AbstractClassObject {

	/**
	 * body
	 *
	 * @var string
	 */
	protected $body;

	public $defaultIndent = '		';

	/**
	 * @var MethodParameter[]
	 */
	protected $parameters;

	public function __construct($methodName) {
		$this->setName($methodName);
	}

	/**
	 * Setter for body
	 *
	 * @param string $body body
	 * @return void
	 */
	public function setBody($body) {
			// keep or set the indent
		if (strpos($body, $this->defaultIndent) !== 0) {
			$lines = explode('
', $body);
			$newLines = array();
			foreach ($lines as $line) {
				$newLines[] = $this->defaultIndent . $line;
			}
			$body = implode('
', $newLines);
		}
		$this->body = rtrim($body);
	}

	/**
	 * Getter for body
	 *
	 * @return string body
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * getter for parameters
	 *
	 * @return array parameters
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * getter for parameter names
	 *
	 * @return array parameter names
	 */
	public function getParameterNames() {
		$parameterNames = array();
		if (is_array($this->parameters)) {
			foreach ($this->parameters as $parameter) {
				$parameterNames[] = $parameter->getName();
			}
		}
		return $parameterNames;
	}

	/**
	 * adder for parameters
	 *
	 * @param MethodParameter[]
	 * @return void
	 */
	public function setParameters($parameters) {
		foreach ($parameters as $parameter) {
			$this->parameters[$parameter->getPosition()] = $parameter;
		}
	}

	/**
	 * setter for a single parameter
	 *
	 * @param MethodParameter
	 * @return void
	 */
	public function setParameter(MethodParameter $parameter) {
		if (!in_array($parameter->getName(), $this->getParameterNames())) {
			$this->parameters[$parameter->getPosition()] = $parameter;
		}
	}

	/**
	 * replace a single parameter, depending on position
	 *
	 * @param MethodParameter $parameter
	 * @return void
	 */
	public function replaceParameter(MethodParameter $parameter) {
		$this->parameters[$parameter->getPosition()] = $parameter;
	}

	/**
	 * removes a parameter
	 *
	 * @param string $parameterName
	 * @param int $parameterSortingIndex
	 * @return boolean TRUE (if successfull removed)
	 */
	public function removeParameter($parameterName, $parameterPosition) {
			// TODO: Not yet tested
		if (isset($this->parameters[$parameterPosition]) && $this->parameters[$parameterPosition]->getName() == $parameterName) {
			unset($this->parameters[$parameterPosition]);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @param string $parameterName
	 * @param string $parameterSortingIndex
	 * @return boolean TRUE (if successfull removed)
	 */
	public function renameParameter($oldName, $newName, $parameterPosition) {
			// TODO: Not yet tested
		if (isset($this->parameters[$parameterPosition])) {
			$parameter = $this->parameters[$parameterPosition];
			if ($parameter->getName() == $oldName) {
				$parameter->setName($newName);
				$this->parameters[$parameterPosition] = $parameter;
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * TODO: THe sorting of tags/annotations should be controlled
	 */
	public function getAnnotations() {
		$annotations = parent::getAnnotations();
		if ((is_array($this->parameters) && count($this->parameters) > 0) && !$this->isTaggedWith('param')) {
			$paramTags = array();
			foreach ($this->parameters as $parameter) {
				$varType = $parameter->getVarType();
				if (in_array(strtolower($varType), array('string', 'boolean', 'integer', 'doubler', 'float'))) {
					$varType = strtolower($varType);
				}
				$paramTags[] = (('param ' . $varType) . ' $') . $parameter->getName();
			}
			$annotations = array_merge($paramTags, $annotations);
		}
		if (!$this->isTaggedWith('return')) {
			$annotations[] = 'return';
		}
		return $annotations;
	}

}

?>