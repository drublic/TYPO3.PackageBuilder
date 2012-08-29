<?php
namespace TYPO3\PackageBuilder\Tests;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nico de Haen
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
if(!class_exists('\\TYPO3\\PackageBuilder\\Tests\\BaseTest')) {
	require_once('../BaseTest.php');
}

class PrinterTest extends BaseTest {


	/**
	 * @test
	 */
	public function printSimplePropertyClass() {
		$this->assertTrue(is_writable($this->testDir), 'Directory not writable: Tests/Fixtures/tmp. Can\'t compare rendered files');
		$fileName = 'SimpleProperty.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}

	/**
	 * @test
	 */
	public function printClassWithMultipleProperties() {
		$fileName = 'ClassWithMultipleProperties.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}

	/**
	 * @test
	 */
	public function printSimpleClassMethodWithManyParameter() {
		$fileName = 'ClassMethodWithManyParameter.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}

	/**
	 * @test
	 */
	public function printSimpleClassMethodWithMissingParameterTypeHint() {
		$fileName = 'ClassMethodWithMissingParameterTypeHint.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$reflectedClass = $this->compareClasses($classFileObject, $this->testDir . $fileName);
		$parameters = $reflectedClass->getMethod('testMethod')->getParameters();
		//$this->assertEquals($parameters[1]->getTypeHint());
	}

	/**
	 * @test
	 */
	public function printSimpleClassMethodWithMissingParameterTag() {
		$fileName = 'ClassMethodWithMissingParameterTag.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$reflectedClass = $this->compareClasses($classFileObject, $this->testDir . $fileName);
		// No way to detect the typeHint with Reflection...

	}

	/**
	 * @test
	 */
	public function printClassWithIncludeStatement() {
		$fileName = 'ClassWithIncludeStatement.php';
		$this->assertTrue(copy($this->fixturesPath.'DummyIncludeFile1.php',$this->testDir.'DummyIncludeFile1.php'));
		$this->assertTrue(copy($this->fixturesPath.'DummyIncludeFile2.php',$this->testDir.'DummyIncludeFile2.php'));
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);

	}

	/**
	 * @test
	 */
	public function printClassWithPreStatements() {
		$fileName = 'ClassWithPreStatements.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
		$this->assertEquals(TX_PHPPARSER_TEST_FOO,'BAR');
		$this->assertEquals('FOO',TX_PHPPARSER_TEST_BAR);
	}

	/**
	 * @test
	 *
	 */
	public function printClassWithPostStatements() {
		$fileName = 'ClassWithPostStatements.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
		$this->assertEquals(TX_PHPPARSER_TEST_FOO_POST,'BAR');
		$this->assertEquals('FOO',TX_PHPPARSER_TEST_BAR_POST);
	}

	/**
	 * @test
	 *
	 */
	public function printClassWithPreAndPostStatements() {
		$fileName = 'ClassWithPreAndPostStatements.php';
		$classFileObject = $this->parseAndWrite($fileName);
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
		$this->assertEquals(TX_PHPPARSER_TEST_FOO_PRE2,'BAR');
		$this->assertEquals('FOO',TX_PHPPARSER_TEST_BAR_POST2);
	}


	/**
	 * @test
	 */
	public function printSimpleNamespacedClass() {
		$fileName = 'SimpleNamespace.php';
		$classFileObject = $this->parseAndWrite($fileName,'Namespaces/');
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}


	/**
	 * @test
	 */
	public function printSimpleNamespaceWithUseStatement() {
		$fileName = 'SimpleNamespaceWithUseStatement.php';
		$classFileObject = $this->parseAndWrite($fileName,'Namespaces/');
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}

	/**
	 * @test
	 */
	public function printMultipleNamespacedClass() {
		$fileName = 'MultipleNamespaces.php';
		$classFileObject = $this->parseAndWrite($fileName,'Namespaces/');
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}


	/**
	 * @test
	 */
	public function printMultipleBracedNamespacedClass() {
		$fileName = 'MultipleBracedNamespaces.php';
		$classFileObject = $this->parseAndWrite($fileName,'Namespaces/');
		$this->compareClasses($classFileObject, $this->testDir . $fileName);
	}


	protected function parseAndWrite($fileName, $subFolder = '') {
		$classFilePath = $this->packagePath . 'Tests/Fixtures/' . $subFolder . $fileName;
		$this->assertTrue(file_exists($classFilePath));
		$classFileObject = $this->parser->parseFile($classFilePath);
		$newClassFilePath = $this->testDir . $fileName;
		file_put_contents($newClassFilePath,"<?php\n\n" . $this->printer->renderFileObject($classFileObject) . "\n?>");
		return $classFileObject;
	}

	/**
	 * @test
	 */
	function printFileWithFunction() {
		$fileObject = $this->parseFile('FunctionsWithoutClasses.php');
		$newFilePath = $this->testDir . 'FunctionsWithoutClasses.php';
		file_put_contents($newFilePath,"<?php\n\n" . $this->printer->renderFileObject($fileObject) . "\n?>");
		$this->markTestSkipped(
		  'Ignorables in PHPParser are not yet printed correct'
		);
		$this->assertEquals(file_get_contents($this->fixturesPath . 'FunctionsWithoutClasses.php'), file_get_contents($newFilePath));
	}

	/**
	 * @test
	 */
	function printFileWithNamespacedFunction() {
		$fileObject = $this->parseFile('Namespaces/FunctionsWithoutClasses.php');
		$newFilePath = $this->testDir . 'NamespacedFunctions.php';
		file_put_contents($newFilePath,"<?php\n\n" . $this->printer->renderFileObject($fileObject) . "\n?>");
		$this->markTestSkipped(
		  'Ignorables in PHPParser are not yet printed correct'
		);
		$this->assertEquals(file_get_contents($this->fixturesPath . 'Namespaces/FunctionsWithoutClasses.php'), file_get_contents($newFilePath));
	}

}

?>
