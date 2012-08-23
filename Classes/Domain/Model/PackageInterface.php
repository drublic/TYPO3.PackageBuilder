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

use TYPO3\PackageBuilder\Annotations as PackageBuilder;

/**
 * A PackageInterface
 *
 * @PackageBuilder\Model
 */
interface PackageInterface {

	public function getDomainObjects();


	/**
	 * @param string $name
	 */
	public function setName($name);


	/**
	 * @return string
	 */
	public function getName();
}