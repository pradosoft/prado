<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Collections.TAttributeCollection');

/**
 * @package System.Collections
 */
class TAttributeCollectionTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
	}

	public function tearDown() {
	}

	public function testCanGetProperty() {
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->Property);
		self::assertEquals(true, $collection->canGetProperty('Property'));
	}
	
	public function testCanNotGetUndefinedProperty() {
		$collection = new TAttributeCollection(array(), true);
		self::assertEquals(false, $collection->canGetProperty('Property'));
		try {
			$value = $collection->Property;
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

	public function testCanSetProperty() {
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->itemAt('Property'));
		self::assertEquals(true, $collection->canSetProperty('Property'));
	}
	
	public function testCanNotSetPropertyIfReadOnly() {
		$collection = new TAttributeCollection(array(), true);
		try {
			$collection->Property = 'value';
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}
	
	public function testGetCaseSensitive() {
		$collection = new TAttributeCollection();
		$collection->setCaseSensitive(false);
		self::assertEquals(false, $collection->getCaseSensitive());
		$collection->setCaseSensitive(true);
		self::assertEquals(true, $collection->getCaseSensitive());
	}
	
	public function testSetCaseSensitive() {
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		$collection->setCaseSensitive(false);
		self::assertEquals('value', $collection->itemAt('property'));
	}
	
	public function testItemAt() {
		$collection = new TAttributeCollection();
		$collection->Property = 'value';
		self::assertEquals('value', $collection->itemAt('Property'));
	}
	
	public function testAdd() {
		$collection = new TAttributeCollection();
		$collection->add('Property', 'value');
		self::assertEquals('value', $collection->itemAt('Property'));
	}
	
	public function testRemove() {
		$collection = new TAttributeCollection();
		$collection->add('Property', 'value');
		$collection->remove('Property');
		self::assertEquals(0, count($collection));
	}
	
	public function testContains() {
		$collection = new TAttributeCollection();
		self::assertEquals(false, $collection->contains('Property'));
		$collection->Property = 'value';
		self::assertEquals(true, $collection->contains('Property'));
	}
	
	public function testHasProperty() {
		$collection = new TAttributeCollection();
		self::assertEquals(false, $collection->hasProperty('Property'));
		$collection->Property = 'value';
		self::assertEquals(true, $collection->hasProperty('Property'));
	}

}

?>
