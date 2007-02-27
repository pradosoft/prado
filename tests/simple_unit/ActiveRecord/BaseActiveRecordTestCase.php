<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');

class BaseRecordTest extends TActiveRecord
{

}

class BaseActiveRecordTestCase extends UnitTestCase
{
	function test_finder_returns_same_instance()
	{
		$obj1 = TActiveRecord::finder('BaseRecordTest');
		$obj2 = TActiveRecord::finder('BaseRecordTest');
		$this->assertIdentical($obj1,$obj2);
	}

	function test_finder_throw_exception_when_save()
	{
		$obj = TActiveRecord::finder('BaseRecordTest');
		try
		{
			$obj->save();
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}
}

?>