<?php

require_once(dirname(__FILE__).'/BaseCase.php');

/**
 * @package System.DataAccess.SQLMap
 */
class PropertyAccessTest extends BaseCase
{
	function testGetPublicProperty()
	{
		$account = new AccountBis();

		$account->Id = 10;
		$account->FirstName = "Luky";
		$account->LastName = "Luke";
		$account->EmailAddress = "luly.luke@somewhere.com";

		$two = new AccountBis();
		$two->Id = 12;
		$two->FirstName = "Mini Me!";
		$account->More = $two;

		$account6 = $this->NewAccount6();
		$two->More = $account6;

		$this->assertIdentical(10, TPropertyAccess::get($account, 'Id'));
		$this->assertIdentical(12, TPropertyAccess::get($account, 'More.Id'));
		$this->assertIdentical(6, TPropertyAccess::get($account, 'More.More.Id'));
	}

	function testSetPublicProperty()
	{
		$account = new AccountBis();

		$account->Id = 10;
		$account->FirstName = "Luky";
		$account->LastName = "Luke";
		$account->EmailAddress = "luly.luke@somewhere.com";

		$two = new AccountBis();
		$two->Id = 12;
		$two->FirstName = "Mini Me!";
		TPropertyAccess::set($account, 'More', $two);

		$account6 = $this->NewAccount6();
		TPropertyAccess::set($account, 'More.More', $account6);

		TPropertyAccess::set($account, 'More.More.EmailAddress', 'hahaha');

		$this->assertIdentical(10, TPropertyAccess::get($account, 'Id'));
		$this->assertIdentical(12, TPropertyAccess::get($account, 'More.Id'));
		$this->assertIdentical(6, TPropertyAccess::get($account, 'More.More.Id'));

		$this->assertIdentical('hahaha',
				TPropertyAccess::get($account, 'More.More.EmailAddress'));
	}

	function testArrayAccessProperty()
	{
		$account = new AccountBis();
		$things['more'] = 1;
		$things['accounts']  = $this->NewAccount6();
		$account->More = $things;

		$this->assertIdentical(6, TPropertyAccess::get($account, 'More.accounts.ID'));

		TPropertyAccess::set($account, 'More.accounts.EmailAddress', 'adssd');
		$this->assertIdentical('adssd', TPropertyAccess::get($account, 'More.accounts.EmailAddress'));

		$this->assertIdentical(1, TPropertyAccess::get($things, 'more'));
	}

}


?>