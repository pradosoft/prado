<?php

namespace Prado\Test\Unit;

use Prado\Util\TClassBehavior;

class OwnerVisibleMethodsClassBehavior extends TClassBehavior
{
	public function visibleClassMethod($obj)
	{
		return 'visibleClass';
	}
	public function hiddenClassMethod($obj)
	{
		return 'hiddenClass';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return 'visibleClassMethod';
	}
}
