<?php

namespace Prado\Test\Unit\Data\SqlMap;


class TDateTime
{
	private $_datetime;

	public function __construct($datetime = null)
	{
		if (null !== $datetime) {
			$this->setDatetime($datetime);
		}
	}

	public function getTimestamp()
	{
		return strtotime($this->getDatetime());
	}

	public function getDateTime()
	{
		return $this->_datetime;
	}

	public function setDateTime($value)
	{
		$this->_datetime = $value;
	}
}
