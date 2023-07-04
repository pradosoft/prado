<?php

use Prado\Collections\TAttributeCollection;
use Prado\Exceptions\TInvalidOperationException;

class TAttributeCollectionTest extends PHPUnit\Framework\TestCase
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
}
