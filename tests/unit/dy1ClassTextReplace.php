<?php

namespace Prado\Test\Unit;

use Prado\Util\TClassBehavior;

class dy1ClassTextReplace extends TClassBehavior
{
	protected $_called = false;
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("__", '..', $callchain->dyTextFilter($text));
	}
	public function isCalled()
	{
		return $this->_called;
	}
	public function dyPowerFunction($hostobject, $x, $y, $callchain)
	{
		return pow($x / $callchain->dyPowerFunction($x, $y), $y);
	}
}
