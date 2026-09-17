<?php

namespace Prado\Test\Unit;

use Prado\Util\IDynamicMethods;

class DynamicCallComponent extends NewComponent implements IDynamicMethods
{
	public function __dycall($method, $args)
	{
		if ($method === 'dyPowerFunction') {
			return pow($args[0], $args[1]);
		}
		if ($method === 'dyDivisionFunction') {
			return $args[0] / $args[1];
		}
		if ($method === 'fxPowerFunction') {
			return 2 * pow($args[0], $args[1]);
		}
		if ($method === 'fxDivisionFunction') {
			return 2 * $args[0] / $args[1];
		}
	}
}
