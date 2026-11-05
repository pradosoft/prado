<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbConnection;

abstract class MultipleFKSqliteRecord extends TActiveRecord
{
	/**
	 * Returns the short class name as the table name; the namespaced class name is not a table.
	 * @return string the table name.
	 */
	public function table()
	{
		return strtolower((new \ReflectionClass($this))->getShortName());
	}

	protected static $conn;

	public function getDbConnection()
	{
		if (self::$conn === null) {
			self::$conn = new TDbConnection('sqlite:' . __DIR__ . '/test1.sqlite');
		}
		return self::$conn;
	}
}
