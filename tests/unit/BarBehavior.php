<?php

namespace Prado\Test\Unit;

use Prado\Util\IInstanceCheck;

class BarBehavior extends PreBarBehavior implements IInstanceCheck
{
	private $_instanceReturn;

	public function moreFunction($laa, $sol)
	{
		return pow($laa + $sol + 1, 2);
	}

	public function isinstanceof($class, $instance = null)
	{
		return $this->_instanceReturn;
	}
	public function setInstanceReturn($value)
	{
		$this->_instanceReturn = $value;
	}
}
