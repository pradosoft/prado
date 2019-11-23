<?php


class BaseRecordTest extends TActiveRecord
{
}

class BaseActiveRecordTest extends PHPUnit\Framework\TestCase
{
	public function test_finder_returns_same_instance()
	{
		$obj1 = TActiveRecord::finder('BaseRecordTest');
		$obj2 = TActiveRecord::finder('BaseRecordTest');
		$this->assertSame($obj1, $obj2);
	}
}
