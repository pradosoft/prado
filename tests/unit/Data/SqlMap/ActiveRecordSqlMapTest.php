<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordManager;

class ActiveRecordSqlMapTest extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::initSqlMap();
		TActiveRecordManager::getInstance()->setDbConnection(self::getConnection());
		self::initScript('account-init.sql');
	}

	public function testLoadWithSqlMap_SaveWithActiveRecord()
	{
		$record = self::$sqlmap->queryForObject('GetActiveRecordAccounts');
		$record->Account_FirstName = "Testing 123";

		$this->assertTrue($record->save());

		$check1 = self::$sqlmap->queryForObject('GetActiveRecordAccounts');
		$finder = ActiveAccount::finder();
		$check2 = $finder->findByAccount_FirstName($record->Account_FirstName);


		$this->assertSameAccount($record,$check1);
		$this->assertSameAccount($record,$check2);

		$this->initScript('account-init.sql');
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
