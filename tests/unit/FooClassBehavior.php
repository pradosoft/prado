<?php

namespace Prado\Test\Unit;

use Prado\Util\TClassBehavior;

class FooClassBehavior extends TClassBehavior
{
	private $_propertyA = 'default';
	private $_baseObject;
	public $_config;
	public const NULL_CONFIG = "null-class-config";

	public function init($config)
	{
		if ($config == null) {
			$config = self::NULL_CONFIG;
		}
		$this->_config = $config;
	}

	public function faaEverMore($object, $laa, $sol)
	{
		$this->_baseObject = $object;
		return $laa * $sol;
	}
	public function getLastClassObject()
	{
		return $this->_baseObject;
	}
	public function getPropertyA()
	{
		return $this->_propertyA;
	}
	public function setPropertyA($value)
	{
		$this->_propertyA = $value;
	}
}
