<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TAttributeCollection;
use Prado\Exceptions\TInvalidOperationException;

class TAttributeCollectionWriteOnlyFixture extends TAttributeCollection
{
	public $written;

	public function setWriteOnly($value)
	{
		$this->written = $value;
	}
}

class TAttributeCollectionTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testCanGetProperty()
	{
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->Property);
		self::assertEquals(true, $collection->canGetProperty('Property'));
	}

	public function testCanNotGetUndefinedProperty()
	{
		$collection = new TAttributeCollection([], true);
		self::assertEquals(false, $collection->canGetProperty('Property'));
		self::expectException(TInvalidOperationException::class);
		$value = $collection->Property;
	}

	public function testCanSetProperty()
	{
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->itemAt('Property'));
		self::assertEquals(true, $collection->canSetProperty('Property'));
	}

	public function testCanNotSetPropertyIfReadOnly()
	{
		$collection = new TAttributeCollection([], true);
		self::expectException(TInvalidOperationException::class);
		$collection->Property = 'value';
	}

	public function testGetCaseSensitive()
	{
		$collection = new TAttributeCollection();
		$collection->setCaseSensitive(false);
		self::assertEquals(false, $collection->getCaseSensitive());
		$collection->setCaseSensitive(true);
		self::assertEquals(true, $collection->getCaseSensitive());
	}

	public function testSetCaseSensitive()
	{
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		$collection->setCaseSensitive(false);
		self::assertEquals('value', $collection->itemAt('property'));
	}

	public function testItemAt()
	{
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->itemAt('Property'));
	}

	public function testAdd()
	{
		$collection = new TAttributeCollection();
		self::assertEquals('property', $collection->add('Property', 'value'));
		self::assertEquals('value', $collection->itemAt('Property'));
	}

	public function testRemove()
	{
		$collection = new TAttributeCollection();
		$collection->add('Property', 'value');
		$collection->remove('Property');
		self::assertEquals(0, count($collection));
	}

	public function testContains()
	{
		$collection = new TAttributeCollection();
		self::assertEquals(false, $collection->contains('Property'));
		$collection->Property = 'value';
		self::assertEquals(true, $collection->contains('Property'));
	}

	public function testHasProperty()
	{
		$collection = new TAttributeCollection();
		self::assertEquals(false, $collection->hasProperty('Property'));
		$collection->Property = 'value';
		self::assertEquals(true, $collection->hasProperty('Property'));
	}

	public function testCaseSensitiveRemoveMatchesExactKeyOnly()
	{
		$collection = new TAttributeCollection();
		$collection->setCaseSensitive(true);
		$collection->add('Property', 'value');
		self::assertFalse($collection->contains('property'));
		self::assertTrue($collection->contains('Property'));
		self::assertNull($collection->remove('property'));
		self::assertEquals(1, count($collection));
		self::assertEquals('value', $collection->remove('Property'));
		self::assertEquals(0, count($collection));
	}

	public function testGetFallsBackToComponentPropertyWhenKeyIsAbsent()
	{
		$collection = new TAttributeCollection();
		self::assertFalse($collection->CaseSensitive);
		$collection->add('CaseSensitive', 'stored');
		self::assertEquals('stored', $collection->CaseSensitive);
	}

	public function testHasPropertyForComponentGetterAndSetterOnlyProperties()
	{
		$collection = new TAttributeCollectionWriteOnlyFixture();
		self::assertTrue($collection->hasProperty('CaseSensitive'));
		self::assertTrue($collection->hasProperty('WriteOnly'));
		self::assertFalse($collection->hasProperty('Undefined'));
		self::assertEquals(0, count($collection));
	}

	public function testSerializeKeepsCaseSensitive()
	{
		$collection = new TAttributeCollection();
		$collection->setCaseSensitive(true);
		$collection->add('Key', 'value');
		$copy = unserialize(serialize($collection));
		self::assertTrue($copy->getCaseSensitive());
		self::assertEquals(['Key' => 'value'], $copy->toArray());
		self::assertNull($copy->itemAt('key'));
	}

	public function testSerializeOmitsDefaultCaseInsensitivity()
	{
		$collection = new TAttributeCollection();
		$collection->add('Key', 'value');
		$copy = unserialize(serialize($collection));
		self::assertFalse($copy->getCaseSensitive());
		self::assertEquals('value', $copy->itemAt('KEY'));
	}
}
