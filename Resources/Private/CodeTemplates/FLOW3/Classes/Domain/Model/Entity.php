<?php
namespace MyPackage;

use TYPO3\FLOW3\Annotations as FLOW3;
use Doctrine\ORM\Mapping as ORM;

class MyModel {

	/**
	 * @var
	 */
	protected $myProperty;

	/**
	 * @param  $myProperty
	 */
	public function setMyProperty($myProperty) {
		$this->myProperty = $myProperty;
	}

	/**
	 * @return
	 */
	public function getMyProperty() {
		return $this->myProperty;
	}
}
