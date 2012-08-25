<?php
namespace MyPackage\MyNameSpace;

use TYPO3\FLOW3\Package\Package as BasePackage;
use TYPO3\FLOW3\Annotations as FLOW3;

/***************************************************************
 *  Copyright notice
 *
 *  (c) <f:format.date format="Y">now</f:format.date> <f:for each="{package.persons}" as="person">{person.name} <f:if condition="{person.email}"><{person.email}></f:if><f:if condition="{person.company}">, {person.company}</f:if>
  *  </f:for>
 *  All rights reserved
 *
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


/**
 * Package base class of the {package.key} package.
 *
 * @FLOW3\Scope("singleton")
 */
class Package extends BasePackage {

	/**
	 * SOme text property
	 * @var string
	 */
	protected $test;
}
?>