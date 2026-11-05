<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class NewComponentStaticBehavior extends TBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value + $value;
	}
}
