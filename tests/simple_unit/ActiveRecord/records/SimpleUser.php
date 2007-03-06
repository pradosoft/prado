<?php
class SimpleUser extends TActiveRecord
{
	public $username;

	const TABLE='simple_users';

	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}
}

?>