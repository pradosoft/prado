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
		$criteria->OnSelect = array($this, 'logger');
		$user1 = UserRecord::finder()->find($criteria);
		//var_dump($user1);
	}

	function logger($sender, $param)
	{
		var_dump($param->Command->Text);
	}
}

?>