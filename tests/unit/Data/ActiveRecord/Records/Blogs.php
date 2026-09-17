<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class Blogs extends TActiveRecord
{
	const TABLE = 'blogs';

	public $blog_id;
	public $blog_name;
	public $blog_author;

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
