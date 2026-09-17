<?php

namespace Prado\Test\Unit\Data\SqlMap\Domain;

use Prado\Collections\TMap;

class Complex
{
	private $_map;

	public function getMap()
	{
		return $this->_map;
	}
	public function setMap(TMap $map)
	{
		$this->_map = $map;
	}
}
