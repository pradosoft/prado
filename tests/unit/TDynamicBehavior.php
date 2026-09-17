<?php

namespace Prado\Test\Unit;

use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

class TDynamicBehavior extends TBehavior implements IDynamicMethods
{
	private $_dyMethod;
	public function getLastBehaviorDynamicMethodCalled()
	{
		return $this->_dyMethod;
	}
	public function __dycall($method, $args)
	{
		$this->_dyMethod = $method;
		if ($method == 'dyTestDynamicBehaviorMethod') {
			return $args[0] / $args[1];
		}
	}
	public function dyTestIntraEvent($param1, $param2, $chain)
	{
		return $chain->dyTestIntraEvent($param1 * 2 * $param2, $param2);
	}
	public function TestBehaviorMethod($param1, $param2)
	{
		return $param1 * $param2;
	}
}
