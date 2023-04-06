<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponent;
use Prado\Util\Behaviors\TMapRouteBehavior;


class TMapRouteBehaviorTest extends PHPUnit\Framework\TestCase
{
	public const BEHAVIOR_NAME = 'route';
	public const BEHAVIOR_NAME_ALL = 'routeall';
	protected $map;
	protected $behavior;
	protected $behaviorall;
	
	protected $_property;
	
	protected $_key;
	protected $_value;

	protected function setUp(): void
	{
		$this->map = new TMap;
		$this->behavior = new TMapRouteBehavior('key1', [$this, 'setProperty']);
		$this->behaviorall = new TMapRouteBehavior(null, [$this, 'setKeyValue']);
	}
	
	public function setProperty($value)
	{
		$this->_property = $value;
	}
	
	public function setKeyValue($key, $value)
	{
		$this->_key = $key;
		$this->_value = $value;
	}

	protected function tearDown(): void
	{
		$this->map = null;
		$this->behavior = null;
		$this->behaviorall = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TMapRouteBehavior::class, $this->behavior);
		
		try {
			$b = new TMapRouteBehavior(null, null);
			self::fail('Expected TInvalidDataTypeException not thrown');
		} catch (TInvalidDataTypeException $e) {
		}
	}
	
	public function testDyAddItem()
	{
		self::assertNull($this->_property);
		
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $this->behavior);
		$this->map->attachBehavior(self::BEHAVIOR_NAME_ALL, $this->behaviorall);
		
		self::assertNull($this->map['key1']);
		$value = $this->map['key1'] = 'value1';
		self::assertEquals($value, $this->_property);
		self::assertEquals('key1', $this->_key);
		self::assertEquals($value, $this->_value);
		$value2 = $this->map['key2'] = 'value2';
		self::assertEquals($value, $this->_property);
		self::assertEquals('key2', $this->_key);
		self::assertEquals($value2, $this->_value);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME);
		
		$value2 = $this->map['key1'] = 'value2';
		self::assertEquals($value, $this->_property);
		
		self::assertEquals('key1', $this->_key);
		self::assertEquals($value2, $this->_value);
		$this->map->detachBehavior(self::BEHAVIOR_NAME_ALL);
		
		$this->map['key3'] = 'value3';
		self::assertEquals('key1', $this->_key);
		self::assertEquals($value2, $this->_value);
	}
	
	public function testDyRemoveItem()
	{
		self::assertNull($this->_property);
		
		
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $this->behavior);
		$this->map->attachBehavior(self::BEHAVIOR_NAME_ALL, $this->behaviorall);
		
		$value = $this->map['key1'] = 'value1';
		$this->map['key2'] = 'value2';
		
		self::assertEquals($value, $this->_property);
		unset($this->map['key2']);
		self::assertEquals($value, $this->_property);
		self::assertEquals('key2', $this->_key);
		self::assertNull($this->_value);
		
		unset($this->map['key1']);
		
		self::assertNull($this->_property);
		self::assertEquals('key1', $this->_key);
		self::assertNull($this->_value);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME);
		
		$this->map['key1'] = 'value2';
		$this->_property = 1;
		$this->_key = 1;
		$this->_value = 1;
		unset($this->map['key1']);
		self::assertEquals(1, $this->_property);
		self::assertEquals('key1', $this->_key);
		self::assertNull($this->_value);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME_ALL);
		
		$this->map['key2'] = 'value3';
		$this->_property = 1;
		$this->_key = 1;
		$this->_value = 1;
		unset($this->map['key2']);
		self::assertEquals(1, $this->_property);
		self::assertEquals(1, $this->_key);
		self::assertEquals(1, $this->_value);
	}
	

	public function testParameter()
	{
		self::assertEquals('key1', $this->behavior->getParameter());
		$this->behavior->setParameter('key2');
		self::assertEquals('key2', $this->behavior->getParameter());
	}
}
