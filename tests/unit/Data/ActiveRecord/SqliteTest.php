<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/SqliteUsers.php');

/**
 * @package System.Data.ActiveRecord
 */
class SqliteTest extends PHPUnit_Framework_TestCase
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
}
