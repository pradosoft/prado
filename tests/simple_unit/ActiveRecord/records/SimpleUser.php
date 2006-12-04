<?php

class SimpleUser extends TActiveRecord
{
	public $username;

	private static $_tablename='simple_users';

	public static function finder()
	{
		return self::getRecordFinder('SimpleUser');
	}
}

?>