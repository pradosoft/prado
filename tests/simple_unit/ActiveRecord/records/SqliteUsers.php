<?php
class SqliteUsers extends TActiveRecord
{
	public $username;
	public $password;
	public $email;

	public static $_tablename='users';

	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}
}

?>