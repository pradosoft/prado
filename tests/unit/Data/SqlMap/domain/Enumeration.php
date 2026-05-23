<?php

/**
 * Enumeration domain class for SqlMap tests.
 *
 * Mirrors the .NET IBatisNet test Enumeration class.  Each field stores the raw
 * integer value from the database (Enum_Day, Enum_Color, Enum_Month columns).
 * Month is nullable (the fourth row has a NULL value in the Enumerations table).
 *
 * The SqlMap resultClass mapper resolves column names case-insensitively against
 * setter/getter pairs, so lower-cased column aliases returned by PostgreSQL (id,
 * day, color, month) map correctly to setId/setDay/setColor/setMonth.
 *
 * Initial integer/null values drive the type-cast the mapper applies when
 * assigning PDO string results, ensuring assertSame() integer comparisons pass.
 */
class Enumeration
{
	private $_Id = 0;
	private $_Day = 0;
	private $_Color = 0;
	private $_Month = null;

	public function getId()
	{
		return $this->_Id;
	}

	public function setId($value)
	{
		$this->_Id = (int) $value;
	}

	public function getDay()
	{
		return $this->_Day;
	}

	public function setDay($value)
	{
		$this->_Day = (int) $value;
	}

	public function getColor()
	{
		return $this->_Color;
	}

	public function setColor($value)
	{
		$this->_Color = (int) $value;
	}

	public function getMonth()
	{
		return $this->_Month;
	}

	public function setMonth($value)
	{
		$this->_Month = ($value === null) ? null : (int) $value;
	}
}
