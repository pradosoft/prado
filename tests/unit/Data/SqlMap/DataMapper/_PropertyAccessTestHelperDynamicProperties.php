<?php

namespace Prado\Test\Unit\Data\SqlMap\DataMapper;

class _PropertyAccessTestHelperDynamicProperties
{
	private $_a = 1;
	private $_b = 2;

	public function __set($name, $value)
	{
		switch (strToLower($name)) {
			case 'a':
				$this -> _a = $value;
			break;
			case 'b':
				$this -> _b = $value;
			break;
		}
	}

	public function __get($name)
	{
		switch (strToLower($name)) {
			case 'a':
				return $this -> _a;
			break;
			case 'b':
				return $this -> _b;
			break;
			default:
				return null;
			break;
		}
	}
}
