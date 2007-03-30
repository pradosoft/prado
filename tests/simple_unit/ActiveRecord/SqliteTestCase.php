<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/SqliteUsers.php');

class SqliteTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('sqlite2:'.dirname(__FILE__).'/ar_test.db');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_finder()
	{
		$finder = SqliteUsers::finder();
		$user = $finder->findByPk('test');
		$this->assertNotNull($user);
	}

	function test_get_pk()
	{
		$meta = TActiveRecordManager::getInstance()->getMetaData('SqliteUsers');
		$this->assertEqual(array('username'), $meta->PrimaryKeys);
	}
}

?>