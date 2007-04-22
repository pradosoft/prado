<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/UserRecord.php');

class RecordEventTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function testFindByPk()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);
	}

	function test_same_data_returns_same_object()
	{
		$criteria = new TActiveRecordCriteria('username = ?', 'admin');
		$finder = new UserRecord();
		$finder->OnCreateCommand[] = array($this, 'logger');
		$finder->OnExecuteCommand[] = array($this, 'logger');
		$user1 = $finder->find($criteria);
		//var_dump($user1);

		//var_dump(UserRecord::finder()->find($criteria));
	}

	function logger($sender, $param)
	{
		//var_dump($param);
	}
}

?>