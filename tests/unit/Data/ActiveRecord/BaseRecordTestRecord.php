<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;

class BaseRecordTestRecord extends TActiveRecord
{
	/**
	 * Returns the short class name as the table name; the namespaced class name is not a table.
	 * @return string the table name.
	 */
	public function table()
	{
		return strtolower((new \ReflectionClass($this))->getShortName());
	}

}
