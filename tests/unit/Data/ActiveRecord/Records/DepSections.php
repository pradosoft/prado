<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class DepSections extends TActiveRecord
{
	public $department_id;
	public $section_id;
	public $order;

	const TABLE = 'department_sections';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
