<?php

namespace Prado\Test\Unit;


class dy2TextReplace extends dy1TextReplace
{
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("++", '||', $callchain->dyTextFilter($text));
	}
}
