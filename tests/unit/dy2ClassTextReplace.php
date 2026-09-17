<?php

namespace Prado\Test\Unit;


class dy2ClassTextReplace extends dy1ClassTextReplace
{
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("||", '++', $callchain->dyTextFilter($text));
	}
}
