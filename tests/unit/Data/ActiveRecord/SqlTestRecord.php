<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;

class SqlTestRecord extends TActiveRecord
{
	public $category;
	public $item;

	const TABLE = 'items';
}
