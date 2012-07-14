<?php
namespace TYPO3\PackageBuilder\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
 *                                                                        *
 *                                                                        */


/**
 *
 */
interface CodeGeneratorInterface  {

	/**
	 * @abstract
	 * @param AbstractPackage $package
	 * @return mixed
	 */
	public function build( $package);


}
