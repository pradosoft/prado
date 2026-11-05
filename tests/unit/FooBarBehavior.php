<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class FooBarBehavior extends TBehavior
{
	public function moreFunction($laa, $sol)
	{
		return $laa * $sol * $sol;
	}
}
