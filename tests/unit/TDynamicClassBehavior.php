<?php

namespace Prado\Test\Unit;

use Prado\Util\IDynamicMethods;
use Prado\Util\TClassBehavior;

class TDynamicClassBehavior extends TClassBehavior implements IDynamicMethods
{
	private $_dyMethod;
	public function getLastBehaviorDynamicMethodCalled()
	{
		return $this->_dyMethod;
	}
	//Dynamic Calls within class behaviors contain the main object as the first parameter within the args
	public function __dycall($method, $args)
	{
		$this->_dyMethod = $method;
		$object = array_shift($args);
		if ($method == 'dyTestDynamicClassBehaviorMethod') {
			return $args[0] / $args[1];
		}
	}
	public function dyTestIntraEvent($object, $param1, $param2, $chain)
	{
		return $chain->dyTestIntraEvent($param1 * 2 * $param2, $param2);
	}
	public function TestBehaviorMethod($object, $param1, $param2)
	{
		return $param1 * $param2;
	}
}
