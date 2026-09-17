<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class FooBehavior extends TBehavior implements FooInterface
{
	private $_propertyA = 'default';

	public $detached = 0;

	public function detach($obj)
	{
		$this->detached++;
		parent::detach($obj);
	}

	public function faaEverMore($laa, $sol)
	{
		return true;
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
