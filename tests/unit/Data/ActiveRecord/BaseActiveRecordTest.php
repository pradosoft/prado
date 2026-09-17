<?php


namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;

class BaseRecordTest extends TActiveRecord
{
}

class BaseActiveRecordTest extends \PHPUnit\Framework\TestCase
{
	public function test_finder_returns_same_instance()
	{
		$obj1 = TActiveRecord::finder(BaseRecordTest::class);
		$obj2 = TActiveRecord::finder(BaseRecordTest::class);
		$this->assertSame($obj1, $obj2);
	}
}
