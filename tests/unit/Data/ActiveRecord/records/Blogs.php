<?php

namespace Prado\Test\Unit\Data\ActiveRecord\records;

use Prado\Data\ActiveRecord\TActiveRecord;

class Blogs extends TActiveRecord
{
	/**
	 * Returns the short class name as the table name; the namespaced class name is not a table.
	 * @return string the table name.
	 */
	public function table()
	{
		return strtolower((new \ReflectionClass($this))->getShortName());
	}

	public $blog_id;
	public $blog_name;
	public $blog_author;

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
