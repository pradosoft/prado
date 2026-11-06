<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * Record of the scaffold control tests, bound to the {@see ScaffoldFixture} database.
 */
class ScaffoldItemRecord extends TActiveRecord
{
	public const TABLE = 'scaffold_items';

	public $id;
	public $item_name;
	public $notes;
	public $price;

	public function getDbConnection()
	{
		return ScaffoldFixture::getConnection();
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
