<?php

require_once(__DIR__ . '/BaseCase.php');

class PropertyAccessTest extends BaseCase
{
	public function testGetPublicProperty()
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

		$this->assertSame(10, TPropertyAccess::get($account, 'Id'));
		$this->assertSame(12, TPropertyAccess::get($account, 'More.Id'));
		$this->assertSame(6, TPropertyAccess::get($account, 'More.More.Id'));
	}

	public function testSetPublicProperty()
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

		$this->assertSame(10, TPropertyAccess::get($account, 'Id'));
		$this->assertSame(12, TPropertyAccess::get($account, 'More.Id'));
		$this->assertSame(6, TPropertyAccess::get($account, 'More.More.Id'));

		$this->assertSame(
			'hahaha',
			TPropertyAccess::get($account, 'More.More.EmailAddress')
		);
	}

	public function testArrayAccessProperty()
	{
		$account = new AccountBis();
		$things['more'] = 1;
		$things['accounts'] = $this->NewAccount6();
		$account->More = $things;

		$this->assertSame(6, TPropertyAccess::get($account, 'More.accounts.ID'));

		TPropertyAccess::set($account, 'More.accounts.EmailAddress', 'adssd');
		$this->assertSame('adssd', TPropertyAccess::get($account, 'More.accounts.EmailAddress'));

		$this->assertSame(1, TPropertyAccess::get($things, 'more'));
	}
}
