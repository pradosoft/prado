<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class OwnerHiddenAllBehavior extends TBehavior
{
	protected $_dyCalled = false;
	public function visibleMethod()
	{
		return 'visible';
	}
	public function dyTextFilter($text, $callchain)
	{
		$this->_dyCalled = true;
		return str_replace('a', 'b', $callchain->dyTextFilter($text));
	}
	public function isDyCalled()
	{
		return $this->_dyCalled;
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return [];
	}
}
