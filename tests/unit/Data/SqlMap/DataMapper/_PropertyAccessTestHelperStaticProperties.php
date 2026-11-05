<?php

namespace Prado\Test\Unit\Data\SqlMap\DataMapper;

class _PropertyAccessTestHelperStaticProperties
{
	private $_a = 1;
	private $_b = 2;

	public function getA()
	{
		return $this -> _a;
	}

	public function setA($value)
	{
		$this -> _a = $value;
	}

	public function getB()
	{
		return $this -> _b;
	}

	public function setB($value)
	{
		$this -> _b = $value;
	}
}
