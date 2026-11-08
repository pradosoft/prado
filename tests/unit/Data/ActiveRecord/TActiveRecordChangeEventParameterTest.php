<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;

class TActiveRecordChangeEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testIsValid_defaultsToTrueAndAcceptsBooleanStrings()
	{
		$param = new TActiveRecordChangeEventParameter();
		$this->assertTrue($param->getIsValid());
		$param->setIsValid('false');
		$this->assertFalse($param->getIsValid());
		$param->setIsValid(1);
		$this->assertTrue($param->getIsValid());
	}
}
