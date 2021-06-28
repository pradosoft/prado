<?php

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

class TMapTest_MapItem
{
	public $data = 'data';
}

class TMapTestBehavior extends TBehavior implements IDynamicMethods
{
	public $method;
	public $args;
	public function __dycall($method, $args)
	{
		$this->method = $method;
		$this->args = $args;
	}
}
class TMapTestNoItemBehavior extends TBehavior
{
	public function dyNoItem($returnValue, $key, $callchain)
	{
		if($key == 'key3') {
			$returnValue = new TMapTest_MapItem;
			$returnValue->data = 'value';
		}
		return $callchain->dyNoItem($returnValue, $key);
	}
}

class TMapTest extends PHPUnit\Framework\TestCase
{
	protected const BEHAVIOR_NAME = 'catcher';
	protected $map;
	protected $item1;
	protected $item2;
	protected $item3;

	protected function setUp(): void
	{
		$this->map = new TMap;
		$this->item1 = new TMapTest_MapItem;
		$this->item2 = new TMapTest_MapItem;
		$this->item3 = new TMapTest_MapItem;
		$this->map->add('key1', $this->item1);
		$this->map->add('key2', $this->item2);
	}

	protected function tearDown(): void
	{
		$this->map = null;
		$this->item1 = null;
		$this->item2 = null;
		$this->item3 = null;
	}

	public function testConstruct()
	{
		$a = [1, 2, 'key3' => 3];
		$map = new TMap($a);
		$this->assertEquals(3, $map->getCount());
		$map2 = new TMap($this->map);
		$this->assertEquals(2, $map2->getCount());
	}

	public function testGetReadOnly()
	{
		$map = new TMap(null, true);
		self::assertEquals(true, $map->getReadOnly(), 'List is not read-only');
		$map = new TList(null, false);
		self::assertEquals(false, $map->getReadOnly(), 'List is read-only');
	}

	public function testGetCount()
	{
		$this->assertEquals(2, $this->map->getCount());
	}

	public function testGetKeys()
	{
		$keys = $this->map->getKeys();
		$this->assertTrue(count($keys) === 2);
		$this->assertTrue($keys[0] === 'key1');
		$this->assertTrue($keys[1] === 'key2');
	}

	public function testAdd()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestBehavior);
		
		$this->map->add('key3', $this->item3);
		$this->assertTrue($this->map->getCount() == 3);
		$this->assertTrue($this->map->contains('key3'));
		
		$this->assertEquals('dyAddItem', $b->method);
		$this->assertEquals('key3', $b->args[0]);
		$this->assertEquals($this->item3, $b->args[1]);
	}

	public function testCanNotAddWhenReadOnly()
	{
		$map = new TMap([], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$map->add('key', 'value');
	}

	public function testRemove()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestBehavior);
		
		$this->assertTrue($this->map->remove('unknown key') === null);
		
		$this->assertEquals('dyAttachBehavior', $b->method);
		$this->assertEquals($b, $b->args[1]);
		
		$this->assertEquals($this->item1, $this->map->remove('key1'));
		
		$this->assertTrue($this->map->getCount() == 1);
		$this->assertFalse($this->map->contains('key1'));
		
		$this->assertEquals('dyRemoveItem', $b->method);
		$this->assertEquals('key1', $b->args[0]);
		$this->assertEquals($this->item1, $b->args[1]);
	}

	public function testCanNotRemoveWhenReadOnly()
	{
		$map = new TMap(['key' => 'value'], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$map->remove('key');
	}

	public function testClear()
	{
		$this->map->clear();
		$this->assertTrue($this->map->getCount() == 0);
		$this->assertFalse($this->map->contains('key1'));
		$this->assertFalse($this->map->contains('key2'));
	}

	public function testContains()
	{
		$this->assertTrue($this->map->contains('key1'));
		$this->assertTrue($this->map->contains('key2'));
		$this->assertFalse($this->map->contains('key3'));
	}

	public function testCopyFrom()
	{
		$array = ['key3' => $this->item3, 'key4' => $this->item1];
		$this->map->copyFrom($array);
		$this->assertTrue($this->map->getCount() == 2);
		$this->assertTrue($this->map['key3'] === $this->item3);
		$this->assertTrue($this->map['key4'] === $this->item1);
		self::expectException('Prado\\Exceptions\\TInvalidDataTypeException');
		$this->map->copyFrom($this);
		$this->fail('no exception raised when copying a non-traversable object');
	}

	public function testMergeWith()
	{
		$array = ['key2' => $this->item1, 'key3' => $this->item3];
		$this->map->mergeWith($array);
		$this->assertTrue($this->map->getCount() == 3);
		$this->assertTrue($this->map['key2'] === $this->item1);
		$this->assertTrue($this->map['key3'] === $this->item3);
		self::expectException('Prado\\Exceptions\\TInvalidDataTypeException');
		$this->map->mergeWith($this);
	}

	public function testArrayRead()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestBehavior);
		
		$this->assertTrue($this->map['key1'] === $this->item1);
		$this->assertTrue($this->map['key2'] === $this->item2);
		
		$this->assertEquals('dyAttachBehavior', $b->method);
		$this->assertEquals($b, $b->args[1]);
		
		$this->assertEquals(null, $this->map['key3']);
		
		$this->assertEquals('dyNoItem', $b->method);
		$this->assertNull($b->args[0]);
		$this->assertEquals('key3', $b->args[1]);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME);
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestNoItemBehavior);
		
		$this->assertInstanceOf('TMapTest_MapItem', $item3 = $this->map['key3']);
		
		$this->assertEquals('value', $item3->data);
	}

	public function testArrayWrite()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestBehavior);
		
		$this->map['key3'] = $this->item3;
		$this->assertEquals('dyAddItem', $b->method);
		$this->assertEquals('key3', $b->args[0]);
		$this->assertEquals($this->item3, $b->args[1]);
		$this->assertTrue($this->map['key3'] === $this->item3);
		$this->assertTrue($this->map->getCount() === 3);
		$this->map['key1'] = $this->item3;
		$this->assertEquals('dyAddItem', $b->method);
		$this->assertEquals('key1', $b->args[0]);
		$this->assertEquals($this->item3, $b->args[1]);
		$this->assertTrue($this->map['key1'] === $this->item3);
		$this->assertTrue($this->map->getCount() === 3);
		unset($this->map['key2']);
		$this->assertEquals('dyRemoveItem', $b->method);
		$this->assertEquals('key2', $b->args[0]);
		$this->assertEquals($this->item2, $b->args[1]);
		$this->assertTrue($this->map->getCount() === 2);
		$this->assertFalse($this->map->contains('key2'));
	}

	public function testArrayForeach()
	{
		$n = 0;
		$found = 0;
		foreach ($this->map as $index => $item) {
			$n++;
			if ($index === 'key1' && $item === $this->item1) {
				$found++;
			}
			if ($index === 'key2' && $item === $this->item2) {
				$found++;
			}
		}
		$this->assertTrue($n == 2 && $found == 2);
	}

	public function testArrayMisc()
	{
		$this->assertEquals($this->map->Count, count($this->map));
		$this->assertTrue(isset($this->map['key1']));
		$this->assertFalse(isset($this->map['unknown key']));
	}

	public function testToArray()
	{
		$map = new TMap(['key' => 'value']);
		self::assertEquals(['key' => 'value'], $map->toArray());
	}
}
