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
namespace TYPO3\PackageBuilder\Utility;

/**
 * provides helper methods
 *
 * @package PackageBuilder
 * @version $ID:$
 */
class Tools {

	static public function convertJSONArrayToPHPArray($encodedArray) {
		if (strpos($encodedArray, '}') > -1) {
			$encodedArray = str_replace('{', 'array(', $encodedArray);
			$encodedArray = str_replace('}', ')', $encodedArray);
			$encodedArray = str_replace(':', ' => ', $encodedArray);
		}
		if (strpos($encodedArray, ']') > -1) {
			$encodedArray = str_replace('[', 'array(', $encodedArray);
			$encodedArray = str_replace(']', ')', $encodedArray);
		}
		return $encodedArray;
	}

	/**
	 * This function is just copied from xliff extension,
	since there is no official API yet
	 *
	 * @param SimpleXmlElement $xml
	 * @param $languageKey
	 * @return array
	 */
	static public function parseXliff($xml, $languageKey = 'default') {
		$parsedData = array();
		$bodyOfFileTag = $xml->file->body;
		foreach ($bodyOfFileTag->children() as $translationElement) {
			if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
				// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
				if ($languageKey === 'default') {
					// Default language coming from an XLIFF template (no target element)
					$parsedData[(string) $translationElement['id']][0] = array(
						'source' => (string) $translationElement->source,
						'target' => (string) $translationElement->source
					);
				} else {
					$parsedData[(string) $translationElement['id']][0] = array(
						'source' => (string) $translationElement->source,
						'target' => (string) $translationElement->target
					);
				}
			} elseif (($translationElement->getName() === 'group' && isset($translationElement['restype'])) && (string) $translationElement['restype'] === 'x-gettext-plurals') {
				// This is a translation with plural forms
				$parsedTranslationElement = array();
				foreach ($translationElement->children() as $translationPluralForm) {
					if ($translationPluralForm->getName() === 'trans-unit') {
						// When using plural forms, ID looks like this: 1[0], 1[1] etc
						$formIndex = substr((string) $translationPluralForm['id'], strpos((string) $translationPluralForm['id'], '[') + 1, -1);
						if ($languageKey === 'default') {
							// Default language come from XLIFF template (no target element)
							$parsedTranslationElement[(int) $formIndex] = array(
								'source' => (string) $translationPluralForm->source,
								'target' => (string) $translationPluralForm->source
							);
						} else {
							$parsedTranslationElement[(int) $formIndex] = array(
								'source' => (string) $translationPluralForm->source,
								'target' => (string) $translationPluralForm->target
							);
						}
					}
				}
				if (!empty($parsedTranslationElement)) {
					if (isset($translationElement['id'])) {
						$id = (string) $translationElement['id'];
					} else {
						$id = (string) $translationElement->{'trans-unit'}[0]['id'];
						$id = substr($id, 0, strpos($id, '['));
					}
					$parsedData[$id] = $parsedTranslationElement;
				}
			}
		}
		$LOCAL_LANG = array();
		$LOCAL_LANG[$languageKey] = $parsedData;
		return $LOCAL_LANG;
	}

	/**
	 * merge existing locallang (either in xlf or locallang.xml) with the required/configured new labels

	TODO: this method works currently only for 'default' language
	 *
	 * @static
	 * @param string $locallangFile
	 * @param string $newXmlString
	 * @param string fileFormat (xml or xlf
	 * @return string merged label in XML format
	 */
	static public function mergeLocallangXlf($locallangFile, $newXmlString, $fileFormat) {
		if (!file_exists($locallangFile)) {
			throw new \Exception('File not found: ' . $locallangFile);
		}
		if (pathinfo($locallangFile, PATHINFO_EXTENSION) == 'xlf') {
			$existingXml = simplexml_load_file($locallangFile, 'SimpleXmlElement', LIBXML_NOWARNING);
			$existingLabelArr = self::flattenLocallangArray(self::parseXliff($existingXml), 'xlf');
		} else {
			$existingLabelArr = self::flattenLocallangArray(\t3lib_div::xml2array(\t3lib_div::getUrl($locallangFile)), 'xml');
		}
		if ($fileFormat == 'xlf') {
			$newXml = simplexml_load_string($newXmlString, 'SimpleXmlElement', LIBXML_NOWARNING);
			$newLabelArr = self::flattenLocallangArray(self::parseXliff($newXml), 'xlf');
		} else {
			$newLabelArr = self::flattenLocallangArray(\t3lib_div::xml2array($newXmlString), 'xml');
		}
		\t3lib_div::devlog('mergeLocallang', 'extension_builder', 0, array('new' => $newLabelArr, 'existing' => $existingLabelArr));
		if (is_array($existingLabelArr)) {
			$mergedLabelArr = \t3lib_div::array_merge_recursive_overrule($newLabelArr, $existingLabelArr);
		} else {
			$mergedLabelArr = $newLabelArr;
		}
		\t3lib_div::devlog('mergeLocallang', 'extension_builder', 0, $mergedLabelArr);
		return $mergedLabelArr;
	}

	/**
	 * @static
	 * @param string $locallangFile
	 * @param string $newXmlString
	 * @return string merged label in XML format
	 */
	static public function mergeLocallangXml($locallangFile, $newXmlString) {
		$existingLabelArr = \t3lib_div::xml2array(\t3lib_div::getUrl($locallangFile));
		$newLabelArr = \t3lib_div::xml2array($newXmlString);
		if (is_array($existingLabelArr)) {
			$mergedLabelArr = \t3lib_div::array_merge_recursive_overrule($newLabelArr, $existingLabelArr);
		} else {
			$mergedLabelArr = $newLabelArr;
		}
		$xml = self::createXML($mergedLabelArr);
		return $xml;
	}

	/**
	 * reduces an array coming from t3lib_div::xml2array or parseXliff
	to a simple index => label array
	 *
	 * @static
	 * @param $array
	 * @param $format xml/xlf
	 * @return array
	 */
	static public function flattenLocallangArray($array, $format) {
		$cleanMergedLabelArray = array();
		if ($format == 'xlf') {
			foreach ($array['default'] as $index => $label) {
				$cleanMergedLabelArray[$index] = $label[0]['source'];
			}
		} else {
			$cleanMergedLabelArray = $array['data']['default'];
		}
		return $cleanMergedLabelArray;
	}

	/**
	 * @param $outputArray
	 * @return string xml test
	 */
	static public function createXML($outputArray) {
		// Options:
		$options = array(
			//'useIndexTagForAssoc'=>'key',
			'parentTagMap' => array(
				'data' => 'languageKey',
				'orig_hash' => 'languageKey',
				'orig_text' => 'languageKey',
				'labelContext' => 'label',
				'languageKey' => 'label'
			)
		);
		// Creating XML file from $outputArray:
		$XML = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . chr(10);
		$XML .= \t3lib_div::array2xml($outputArray, '', 0, 'T3locallang', 0, $options);
		return $XML;
	}

	/**
	 * @static
	 * @param array $configurations
	 */
	public static function replaceConstantsInConfiguration(array &$configurations) {
		foreach ($configurations as $key => $configuration) {
			if (is_array($configuration)) {
				self::replaceConstantsInConfiguration($configurations[$key]);
			} elseif (is_string($configuration)) {
				$matches = array();
				preg_match_all('/(?:%)([A-Z_0-9]+)(?:%)/', $configuration, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $configurations[$key] = str_replace('%' . $match . '%', constant($match), $configurations[$key]);
					}
				}
			}
		}
	}

}

