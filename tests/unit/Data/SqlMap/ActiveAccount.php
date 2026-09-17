<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\ActiveRecord\TActiveRecord;

class ActiveAccount extends TActiveRecord
{
	public $Account_Id;
	public $Account_FirstName;
	public $Account_LastName;
	public $Account_Email;

	public $Account_Banner_Option;
	public $Account_Cart_Option;

	const TABLE = 'Accounts';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
