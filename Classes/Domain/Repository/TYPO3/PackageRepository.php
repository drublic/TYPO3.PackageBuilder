<?php
namespace TYPO3\PackageBuilder\Domain\Repository\TYPO3;
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Nico de Haen <mail@ndh-websolutions.de>
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
 * Repository for existing Extbase Extensions
 *
 * @package ExtensionBuilder
 */
class PackageRepository extends \TYPO3\PackageBuilder\Domain\Repository\AbstractPackageRepository {


	/**
	 * @var \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager
	 */
	protected $packageConfigurationManager;

	/**
	 * @param \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}


	public function getByIdentifier($identifier) {

	}

	/**
	 * loops through all extensions in typo3conf/ext/
	 * and searchs for a JSON file with extension builder configuration
	 * @return array
	 */
	public function findAll() {
		$result = array();
		$extensionDirectoryHandle = opendir(PATH_typo3conf . 'ext/');
		while (FALSE !== ($singleExtensionDirectory = readdir($extensionDirectoryHandle))) {
			if ($singleExtensionDirectory[0] == '.') {
				continue;
			}
			$extensionBuilderConfiguration = $this->packageConfigurationManager->getExtensionBuilderConfiguration($singleExtensionDirectory);
			//t3lib_div::devlog('Modeler Configuration: '.$singleExtensionDirectory,'extension_builder',0,$extensionBuilderConfiguration);
			if ($extensionBuilderConfiguration !== NULL) {
				$result[] = array(
					'name' => $singleExtensionDirectory,
					'working' => json_encode($extensionBuilderConfiguration)
				);
			}
		}
		closedir($extensionDirectoryHandle);

		return $result;
	}

	/**
	 * @param \TYPO3\PackageBuilder\Domain\Model\Extension
	 */
	public function saveExtensionConfiguration(\TYPO3\PackageBuilder\Domain\Model\Extension $extension) {
		$extensionBuildConfiguration = $this->packageConfigurationManager->getConfigurationFromModeler();
		$extensionBuildConfiguration['log'] = array(
			'last_modified' => date('Y-m-d h:i'),
			'extension_builder_version' => t3lib_extMgm::getExtensionVersion('extension_builder'),
			'be_user' => $GLOBALS['BE_USER']->user['realName'] . ' (' . $GLOBALS['BE_USER']->user['uid'] . ')'
		);
		file_put_contents($extension->getExtensionDir() . \TYPO3\PackageBuilder\Configuration\TYPO3\ConfigurationManager::EXTENSION_BUILDER_SETTINGS_FILE, json_encode($extensionBuildConfiguration));
	}
}

?>