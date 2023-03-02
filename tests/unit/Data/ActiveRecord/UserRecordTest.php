<?php

require_once(__DIR__ . '/records/UserRecord.php');

class UserRecordTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	public function testFindByPk()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);
	}

	public function test_same_data_returns_different_instance()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);

		$user2 = UserRecord::finder()->findByPk('admin');
		$this->assertFalse($user1 === $user2);
	}

	public function testFindByPk_returns_null()
	{
		$user = UserRecord::finder()->findByPk('me');
		$this->assertNull($user);
	}

	public function test_Create_new_user_returns_true()
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

	public function assertSameUser($user, $check)
	{
		$props = ['username', 'password', 'email', 'first_name', 'last_name', 'job_title',
						'work_phone', 'work_fax', 'active', 'department_id', 'salutation',
						'hint_question', 'hint_answer'];
		foreach ($props as $prop) {
			$this->assertEquals($user->$prop, $check->$prop);
		}
	}
}
