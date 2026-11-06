<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * Record for the input builder tests. The builders only read and write column values,
 * so the record never touches a database.
 */
class ScaffoldInputRecord extends TActiveRecord
{
	public const TABLE = 'scaffold_input_record';

	public $id;
	public $value;
	public $first_name;
}
