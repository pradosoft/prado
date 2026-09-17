<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class OwnerVisibleMethodsBehavior extends TBehavior
{
	public function visibleMethod()
	{
		return 'visible';
	}
	public function hiddenMethod()
	{
		return 'hidden';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return ['visibleMethod'];
	}
}
