<?php

class DepartmentRecord extends TActiveRecord
{
	public $department_id;
	public $name;
	public $description;
	public $active;
	public $order;

	private static $_tablename = 'departments';

	public static function finder()
	{
		return self::getRecordFinder('DepartmentRecord');
	}
}

?>