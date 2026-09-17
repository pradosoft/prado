<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class dy1TextReplace extends TBehavior
{
	protected $_called = false;
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("..", '__', $callchain->dyTextFilter($text));
	}
	public function isCalled()
	{
		return $this->_called;
	}
	public function dyPowerFunction($x, $y, $callchain)
	{
		return pow($x / $callchain->dyPowerFunction($x, $y), $y);
	}
}
