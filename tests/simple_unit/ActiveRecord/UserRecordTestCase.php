<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/UserRecord.php');

class UserRecordTestCase extends UnitTestCase
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
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);

		$user2 = UserRecord::finder()->findByPk('admin');
		$this->assertTrue($user1===$user2);
	}

	function testFindByPk_returns_null()
	{
		$user = UserRecord::finder()->findByPk('me');
		$this->assertNull($user);
	}

	function test_Create_new_user_returns_true()
	{
		$user = new UserRecord;
		$user->username = 'hello';
		$user->password = md5('asd');
		$user->email = 'asdasd';
		$user->first_name = 'wei';
		$user->last_name = 'zhuo';

		$this->assertTrue($user->save());

		$user->password = md5('more');

		$this->assertTrue($user->save());

		$check = UserRecord::finder()->findByPk('hello');

		$this->assertSameUser($user, $check);

		$this->assertTrue($user->delete());
	}

	function assertSameUser($user,$check)
	{
		$props = array('username', 'password', 'email', 'first_name', 'last_name', 'job_title',
						'work_phone', 'work_fax', 'active', 'department_id', 'salutation',
						'hint_question', 'hint_answer');
		foreach($props as $prop)
			$this->assertEqual($user->$prop,$check->$prop);
	}
}

?>