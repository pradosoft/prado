<?php
Prado::using('System.Data.ActiveRecord.TActiveRecordStateRegistry');
Prado::using('System.Data.ActiveRecord.Exceptions.*');

class StateTestObject
{
	public $propA = 'a';
	public $propB;
}

class ActiveRecordRegistryTestCase extends UnitTestCase
{
	function test_new_object()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();

		$this->assertTrue($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));
	}

	function test_clean_object_registers_without_error()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();
		$registry->registerClean($obj);

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertTrue($registry->isCleanObject($obj));
	}

	function test_clean_object_becomes_dirty_when_changed()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();

		$registry->registerClean($obj);

		$obj->propB='b';

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertTrue($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));
	}

	function test_removed_object_must_register_as_clean_first()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();

		try
		{
			$registry->registerRemoved($obj);
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}

	function test_removed_object_registers_without_error()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();
		$registry->registerClean($obj);

		$registry->registerRemoved($obj);

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertTrue($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));
	}


	function test_removed_object_can_not_become_clean()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();
		$registry->registerClean($obj);

		$registry->registerRemoved($obj);

		try
		{
			$registry->registerClean($obj);
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertTrue($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));
	}

	function test_remove_dirty_object()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();

		$registry->registerClean($obj);

		$obj->propB='b';

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertTrue($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));

		$registry->registerRemoved($obj);

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertTrue($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));

		try
		{
			$registry->registerClean($obj);
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}

	function test_clean_dirty_clean_object()
	{
		$obj = new StateTestObject();
		$registry = new TActiveRecordStateRegistry();

		$registry->registerClean($obj);

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertTrue($registry->isCleanObject($obj));

		$obj->propB='b';

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertTrue($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertFalse($registry->isCleanObject($obj));

		$registry->registerClean($obj);

		$this->assertFalse($registry->isNewObject($obj));
		$this->assertFalse($registry->isDirtyObject($obj));
		$this->assertFalse($registry->isRemovedObject($obj));
		$this->assertTrue($registry->isCleanObject($obj));
	}

}

?>