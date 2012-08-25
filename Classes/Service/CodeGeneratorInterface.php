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
	 * @param \TYPO3\PackageBuilder\Domain\Model\PackageInterface $package
	 * @return mixed
	 */
	public function build(\TYPO3\PackageBuilder\Domain\Model\PackageInterface $package);

	/**
	 * @abstract
	 * @param array $settings
	 * @return mixed
	 */
	public function injectSettings(array $settings);
}