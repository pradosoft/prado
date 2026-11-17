<?php

namespace Prado\Test\Unit\Util;

use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\TDataFieldAccessor;

class TDataFieldAccessorTestObject
{
	public $Plain = 'plain';
	public $Child;
	private $_secret = 'secret';

	public function getName()
	{
		return 'name';
	}
}

class TDataFieldAccessorTest extends \PHPUnit\Framework\TestCase
{
	private function assertErrorCode(string $code, mixed $data, string $field): void
	{
		try {
			TDataFieldAccessor::getDataFieldValue($data, $field);
			self::fail("No exception with the error code '$code' was thrown.");
		} catch (TInvalidDataValueException $e) {
			self::assertSame($code, $e->getErrorCode());
		}
	}

	public function testArray()
	{
		self::assertSame(1, TDataFieldAccessor::getDataFieldValue(['a' => 1], 'a'));
		self::assertSame(2, TDataFieldAccessor::getDataFieldValue(['a.b' => 2, 'a' => ['b' => 3]], 'a.b'));
		self::assertSame(3, TDataFieldAccessor::getDataFieldValue(['a' => ['b' => 3]], 'a.b'));
		self::assertNull(TDataFieldAccessor::getDataFieldValue(['a' => null], 'a'));
	}

	public function testArrayAccess()
	{
		$map = new TMap(['a' => 1, 'b' => new TMap(['c' => 2])]);
		self::assertSame(1, TDataFieldAccessor::getDataFieldValue($map, 'a'));
		self::assertSame(2, TDataFieldAccessor::getDataFieldValue($map, 'b.c'));
	}

	public function testArrayMissingFieldThrows()
	{
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', ['a' => 1], 'b');
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', ['a' => ['b' => 1]], 'a.c');
	}

	public function testArrayPathThroughObjectThrows()
	{
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', ['a' => new \stdClass()], 'a.b');
	}

	public function testObject()
	{
		$object = new TDataFieldAccessorTestObject();
		self::assertSame('name', TDataFieldAccessor::getDataFieldValue($object, 'Name'));
		self::assertSame('plain', TDataFieldAccessor::getDataFieldValue($object, 'Plain'));

		$object->Child = new TDataFieldAccessorTestObject();
		$object->Child->Plain = 'child';
		self::assertSame('child', TDataFieldAccessor::getDataFieldValue($object, 'Child.Plain'));
		self::assertSame('name', TDataFieldAccessor::getDataFieldValue($object, 'Child.Name'));
	}

	public function testObjectMissingFieldThrows()
	{
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', new TDataFieldAccessorTestObject(), 'Missing');
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', new TDataFieldAccessorTestObject(), 'Child.Plain');
	}

	public function testObjectInaccessibleFieldThrows()
	{
		$this->assertErrorCode('datafieldaccessor_datafield_invalid', new TDataFieldAccessorTestObject(), '_secret');
	}

	public function testInvalidDataThrows()
	{
		$this->assertErrorCode('datafieldaccessor_data_invalid', 'string', 'a');
		$this->assertErrorCode('datafieldaccessor_data_invalid', null, 'a');
	}
}
