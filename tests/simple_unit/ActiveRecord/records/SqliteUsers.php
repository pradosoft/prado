<?php

class SqliteUsers extends TActiveRecord
{
	public $username;
	public $password;
	public $email;

	private static $_tablename='users';

	public static function finder()
	{
		return self::getRecordFinder('SqliteUsers');
	}
}

?>