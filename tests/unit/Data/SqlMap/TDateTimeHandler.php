<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class TDateTimeHandler extends TSqlMapTypeHandler
{
	public function getType()
	{
		return 'date';
	}

	public function getResult($string)
	{
		$time = new TDateTime($string);
		return $time;
	}

	public function getParameter($parameter)
	{
		if ($parameter instanceof TDateTime) {
			return $parameter->getTimestamp();
		} else {
			return $parameter;
		}
	}

	public function createNewInstance($data = null)
	{
		return new TDateTime;
	}
}
