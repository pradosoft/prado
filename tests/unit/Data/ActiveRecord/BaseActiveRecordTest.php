<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');

class BaseRecordTest extends TActiveRecord
{

}

/**
 * @package System.Data.ActiveRecord
 */
class BaseActiveRecordTest extends PHPUnit_Framework_TestCase
{
	function test_finder_returns_same_instance()
	{
		$obj1 = TActiveRecord::finder('BaseRecordTest');
		$obj2 = TActiveRecord::finder('BaseRecordTest');
		$this->assertSame($obj1,$obj2);
	}
}
