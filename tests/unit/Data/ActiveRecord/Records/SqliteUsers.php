<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class SqliteUsers extends TActiveRecord
{
	public $username;
	public $password;
	public $email;

	const TABLE = 'users';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
