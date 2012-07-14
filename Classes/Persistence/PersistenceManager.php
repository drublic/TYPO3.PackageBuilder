<?php
namespace TYPO3\PackageBuilder\Persistence;

/*                                                                        *
  * This script belongs to the FLOW3 package "TYPO3.PackageBuilder".       *
  *                                                                        *
  *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use \TYPO3\FLOW3\Configuration\ConfigurationManager as ConfigurationManager;

/**
 *
 *
 * @FLOW3\Scope("singleton")
 */
class PersistenceManager implements PersistenceManagerInterface {
	/**
	 * @var string
	 */
	protected $savePath;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['Persistence']['savePath'])) {
			$this->savePath = $settings['Persistence']['savePath'];
			if (!is_dir($this->savePath)) {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->savePath);
			}
		}
	}

	/**
	 * Load the array package defintion identified by $persistenceIdentifier, and return it
	 *
	 * @param string $persistenceIdentifier
	 * @return array
	 * @throws \TYPO3\PackageBuilder\Exception\PersistenceManagerException
	 */
	public function load($persistenceIdentifier) {
		if($persistenceIdentifier == 'test') {
			return array(
				'DomainObjects' => array(
					'U895MH56H3SD163' => array(
						'name' => 'TestModel'
					)
				)
			);
		}
		if (!$this->exists($persistenceIdentifier)) {
			throw new \TYPO3\PackageBuilder\Exception\PersistenceManagerException(sprintf('The package with key "%s" could not be loaded.', $persistenceIdentifier), 1329307034);
		}
		$packagePathAndFilename = $this->getPackagePathAndFilename($persistenceIdentifier);
		return json_decode(file_get_contents($packagePathAndFilename));
	}

	/**
	 * Save the array $packageDefinition identified by $persistenceIdentifier
	 *
	 * @param string $persistenceIdentifier
	 * @param array $packageDefinition
	 */
	public function save($persistenceIdentifier, array $packageDefinition) {
		$packagePathAndFilename = $this->getFormPathAndFilename($persistenceIdentifier);
		file_put_contents($packagePathAndFilename, json_encode($packageDefinition));
	}

	/**
	 * Check whether a package with the specified $persistenceIdentifier exists
	 *
	 * @param string $persistenceIdentifier
	 * @return boolean TRUE if a package with the given $persistenceIdentifier can be loaded, otherwise FALSE
	 */
	public function exists($persistenceIdentifier) {
		return is_file($this->getFormPathAndFilename($persistenceIdentifier));
	}

	/**
	 * List all package definitions which can be loaded through this package persistence
	 * manager.
	 *
	 * Returns an associative array with each item containing the keys 'name' (the human-readable name of the package)
	 * and 'persistenceIdentifier' (the unique identifier for the Form Persistence Manager e.g. the path to the saved package definition).
	 *
	 * @return array in the format array(array('name' => 'Form 01', 'persistenceIdentifier' => 'path1'), array( .... ))
	 */
	public function listPackages() {
		$packages = array();
		$directoryIterator = new \DirectoryIterator($this->savePath);

		foreach ($directoryIterator as $fileObject) {
			if (!$fileObject->isFile()) {
				continue;
			}
			$fileInfo = pathinfo($fileObject->getFilename());
			if (strtolower($fileInfo['extension']) !== 'json') {
				continue;
			}
			$persistenceIdentifier = $fileInfo['filename'];
			$package = $this->load($persistenceIdentifier);
			$packages[] = array(
				'identifier' => $package['identifier'],
				'name' => isset($package['label']) ? $package['label'] : $package['identifier'],
				'persistenceIdentifier' => $persistenceIdentifier
			);
		}
		return $packages;
	}

	/**
	 * Returns the absolute path and filename of the package with the specified $persistenceIdentifier
	 * Note: This (intentionally) does not check whether the file actually exists
	 *
	 * @param string $persistenceIdentifier
	 * @return string the absolute path and filename of the package with the specified $persistenceIdentifier
	 */
	protected function getPackagePathAndFilename($persistenceIdentifier) {
		$packageFileName = sprintf('%s.json', $persistenceIdentifier);
		return \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->savePath, $packageFileName));
	}
}
