<?php
namespace TYPO3\PackageBuilder\Configuration\FLOW3;
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
 * @package
 * @author Nico de Haen
 */

class ConfigurationManager extends \TYPO3\PackageBuilder\Configuration\AbstractConfigurationManager{

	/**
	 * This is mainly copied from DataMapFactory
	 *
	 * @param string $className
	 * @return array with configuration values
	 */
	public function getExtbaseClassConfiguration($className) {
		$classConfiguration = array();
		$frameworkConfiguration = $this->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$classSettings = $frameworkConfiguration['persistence']['classes'][$className];
		if ($classSettings !== NULL) {
			if (isset($classSettings['subclasses']) && is_array($classSettings['subclasses'])) {
				$classConfiguration['subclasses'] = $classSettings['subclasses'];
			}
			if (isset($classSettings['mapping']['recordType']) && strlen($classSettings['mapping']['recordType']) > 0) {
				$classConfiguration['recordType'] = $classSettings['mapping']['recordType'];
			}
			if (isset($classSettings['mapping']['tableName']) && strlen($classSettings['mapping']['tableName']) > 0) {
				$classConfiguration['tableName'] = $classSettings['mapping']['tableName'];
			}
			/**
			$classHierachy = array_merge(array($className), class_parents($className));
			foreach ($classHierachy as $currentClassName) {
			if (in_array($currentClassName, array('Tx_Extbase_DomainObject_AbstractEntity', 'Tx_Extbase_DomainObject_AbstractValueObject'))) {
			break;
			}
			$currentClassSettings = $frameworkConfiguration['persistence']['classes'][$currentClassName];
			if ($currentClassSettings !== NULL) {
			if (isset($currentClassSettings['mapping']['columns']) && is_array($currentClassSettings['mapping']['columns'])) {
			$columnMapping = t3lib_div::array_merge_recursive_overrule($columnMapping, $currentClassSettings['mapping']['columns'], 0, FALSE); // FALSE means: do not include empty values form 2nd array
			}
			}
			}
			 */
		}
		return $classConfiguration;
	}

	/**
	 * get the file name and path of the settings file
	 * @param string $extensionKey
	 * @return string path
	 */
	public function getSettingsFile($extensionKey) {
		$extensionDir = PATH_typo3conf . 'ext/' . $extensionKey . '/';
		$settingsFile = $extensionDir . self::SETTINGS_DIR . 'settings.yaml';
		if (!file_exists($settingsFile) && file_exists($extensionDir . self::OLD_SETTINGS_DIR . 'settings.yaml')) {
			// upgrade from an extension that was built with the extbase_kickstarter
			mkdir($extensionDir . self::SETTINGS_DIR);
			copy($extensionDir . self::OLD_SETTINGS_DIR . 'settings.yaml', $extensionDir . self::SETTINGS_DIR . 'settings.yaml');
			$settingsFile = $extensionDir . self::SETTINGS_DIR . 'settings.yaml';
		}
		return $settingsFile;
	}
}
