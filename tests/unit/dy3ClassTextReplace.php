<?php

namespace Prado\Test\Unit;


class dy3ClassTextReplace extends dy1ClassTextReplace
{
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("??", '^_^', $callchain->dyTextFilter($text));
	}
}
