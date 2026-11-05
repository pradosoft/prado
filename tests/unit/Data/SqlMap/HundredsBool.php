<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class HundredsBool extends TSqlMapTypeHandler
{
	public function getResult($string)
	{
		$value = (int) $string;
		if ($value == 100) {
			return true;
		}
		if ($value == 200) {
			return false;
		}
		//throw new Exception('unexpected value '.$value);
	}

	public function getParameter($parameter)
	{
		if ($parameter) {
			return 100;
		} else {
			return 200;
		}
	}

	public function createNewInstance($data = null)
	{
		throw new \TDataMapperException('can not create');
	}
}
