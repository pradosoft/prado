<?php

namespace Prado\Test\Unit;

use Prado\Util\TClassBehavior;

class NewComponentStaticClassBehavior extends TClassBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value * $value;
	}
}
