<?php

namespace Prado\Test\Unit;

use Prado\Util\IDynamicMethods;

class DynamicCatchingComponent extends NewComponentNoListen implements IDynamicMethods
{
	use DynamicCatchingTrait;

	public function __dycall($method, $args)
	{
	}
}
