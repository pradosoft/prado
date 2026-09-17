<?php

namespace Prado\Test\Unit\Data\SqlMap\Domain;

class Book extends Document
{
	private $_PageNumber = '';

	public function getPageNumber()
	{
		return $this->_PageNumber;
	}
	public function setPageNumber($value)
	{
		$this->_PageNumber = $value;
	}
}
