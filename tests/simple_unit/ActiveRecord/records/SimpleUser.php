<?php

class SimpleUser extends TActiveRecord
{
	public $username;

	public static $_tablename='simple_users';

	public static function finder()
	{
		return self::getRecordFinder('SimpleUser');
	}
}

?>