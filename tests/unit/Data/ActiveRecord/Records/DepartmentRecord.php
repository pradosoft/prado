<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class DepartmentRecord extends TActiveRecord
{
	public $department_id;
	public $name;
	public $description;
	public $active;
	public $order;

	const TABLE = 'departments';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
