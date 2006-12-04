<?php

class DepSections extends TActiveRecord
{
	public $department_id;
	public $section_id;
	public $order;

	private static $_tablename='department_sections';

	public static function finder()
	{
		return self::getRecordFinder('DepSections');
	}
}

?>