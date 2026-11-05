<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class OuiNonBool extends TSqlMapTypeHandler
{
	const YES = "Oui";
	const NO = "Non";

	public function getResult($string)
	{
		if ($string === self::YES) {
			return true;
		}
		if ($string === self::NO) {
			return false;
		}
		//throw new Exception('unexpected value '.$string);
	}

	public function getParameter($parameter)
	{
		if ($parameter) {
			return self::YES;
		} else {
			return self::NO;
		}
	}

	public function createNewInstance($data = null)
	{
		throw new \TDataMapperException('can not create');
	}
}
