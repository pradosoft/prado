<?php

namespace Prado\Test\Unit;

use Prado\Util\TClassBehavior;

class NewComponentStaticHiddenClassBehavior extends TClassBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value * $value;
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return [];
	}
}
