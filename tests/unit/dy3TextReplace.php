<?php

namespace Prado\Test\Unit;


class dy3TextReplace extends dy1TextReplace
{
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("!!", '??', $callchain->dyTextFilter($text));
	}
}
