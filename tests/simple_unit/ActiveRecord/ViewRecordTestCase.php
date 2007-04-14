<?php


Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/SimpleUser.php');

class ViewRecordTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_view_record()
	{
		$users = SimpleUser::finder()->findAll();
		$this->assertTrue(count($users) > 0);
	}

	function test_save_view_record_throws_exception()
	{
		$user = new SimpleUser();
		try
		{
			$user->save();
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}

	function test_update_view_record_throws_exception()
	{
		$user = SimpleUser::finder()->findByUsername('admin');
		$user->username = 'ads';
		try
		{
			$user->save();
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}

	function test_find_by_pk_throws_exception()
	{
		try
		{
			$user = SimpleUser::finder()->findByPk('admin');
			$this->fail();
		}
		catch(TDbException $e)
		{
			$this->pass();
		}
	}

	function test_delete_by_pk_throws_exception()
	{
		try
		{
			SimpleUser::finder()->deleteByPk('admin');
			$this->fail();
		}
		catch(TDbException $e)
		{
			$this->pass();
		}
	}
}
?>