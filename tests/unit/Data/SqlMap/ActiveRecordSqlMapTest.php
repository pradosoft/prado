<?php

require_once(__DIR__ . '/BaseCase.php');

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

class ActiveRecordSqlMapTest extends BaseCase
{
	public function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
		TActiveRecordManager::getInstance()->setDbConnection($this->getConnection());

		//$this->initScript('account-init.sql');
	}

	public function testLoadWithSqlMap_SaveWithActiveRecord()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$record = $this->sqlmap->queryForObject('GetActiveRecordAccounts');
				$record->Account_FirstName = "Testing 123";

				$this->assertTrue($record->save());

				$check1 = $this->sqlmap->queryForObject('GetActiveRecordAccounts');
				$finder = ActiveAccount::finder();
				$check2 = $finder->findByAccount_FirstName($record->Account_FirstName);


				$this->assertSameAccount($record,$check1);
				$this->assertSameAccount($record,$check2);

				$this->initScript('account-init.sql');
		*/
	}

	public function assertSameAccount($account1, $account2)
	{
		$props = ['Account_Id', 'Account_FirstName', 'Account_LastName',
						'Account_Email', 'Account_Banner_Option', 'Account_Cart_Option'];
		foreach ($props as $prop) {
			$this->assertEquals($account1->{$prop}, $account2->{$prop});
		}
	}
}
