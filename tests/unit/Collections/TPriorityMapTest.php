<?php

use Prado\Collections\IPriorityItem;
use Prado\Collections\TPriorityMap;
use Prado\Collections\TPriorityList;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

class TPriorityMapTest_MapItem extends TMapTest_MapItem
{
}

class AutoPriorityMapItem extends TPriorityMapTest_MapItem implements IPriorityItem
{
	public $priority;
	
	public function getPriority()
	{
		return $this->priority;
	}
}

class CapturePropertyMapItem extends TPriorityMapTest_MapItem implements IPriorityCapture
{
	public $priority;
	
	public function getPriority()
	{
		return $this->priority;
	}
	
	public function setPriority($value)
	{
		$this->priority = $value;
	}
}

class PriorityPropertyMapItem extends TPriorityMapTest_MapItem implements IPriorityProperty
{
	public $priority;
	
	public function getPriority()
	{
		return $this->priority;
	}
	
	public function setPriority($value)
	{
		$this->priority = $value;
	}
}

class TPriorityMapTestBehavior extends TBehavior implements IDynamicMethods
{
	public $method;
	public $args;
	public function __dycall($method, $args)
	{
		$this->method = $method;
		$this->args = $args;
	}
}
class TPriorityMapTestNoItemBehavior extends TBehavior
{
	public function dyNoItem($returnValue, $key, $callchain)
	{
		if($key == 'key3') {
			$returnValue = new TPriorityMapTest_MapItem;
			$returnValue->data = 'value';
		}
		return $callchain->dyNoItem($returnValue, $key);
	}
}

class TPriorityMapUnit extends TPriorityMap
{
	public function _setPrecision($value)
	{
		$this->setPrecision($value);
	}
	public function _setReadOnly($value)
	{
		$this->setReadOnly($value);
	}
}


class TPriorityMapTest extends TMapTest
{
	protected const BEHAVIOR_NAME = 'catcher';
	
	protected $item4;
	protected $item5;

	protected function newList()
	{
		return  TPriorityMapUnit::class;
	}
	protected function newListItem()
	{
		return TPriorityMapTest_MapItem::class;
	}
	
	protected function setUp(): void
	{
		parent::setUp();
		
		// test that TPriorityMap complies with TMap
		$this->item4 = new $this->_baseItemClass(4);
		$this->item5 = new $this->_baseItemClass(5);

		//Test the priority capabilities
	}

	public function setUpPriorities()
	{
		$this->map->add('key3', $this->item3, 0);
		$this->map->add('key4', $this->item4, 100);
		$this->map->add('key5', $this->item5, 1);
	}

	protected function tearDown(): void
	{
		parent::tearDown();
	}

	public function testConstruct()
	{
		$a = [1, 2, 'key3' => 3];
		$map = new $this->_baseClass($a);
		$this->assertEquals(3, $map->getCount());
		$map2 = new $this->_baseClass($this->map);
		$this->assertEquals(2, $map2->getCount());

		/* Test the priority functionality of TPriorityMap  */

		$map3 = new $this->_baseClass($this->map, false, 100, -1);
		$this->assertEquals(100, $map3->getDefaultPriority());
		$this->assertEquals(-1, $map3->getPrecision());
	}

	/* Test that TPriorityMap complies with TMap   */

	public function testGetReadOnly()
	{
		$map = new $this->_baseClass(null, true);
		self::assertEquals(true, $map->getReadOnly(), 'List is not read-only');
		$map = new $this->_baseClass(null, false);
		self::assertEquals(false, $map->getReadOnly(), 'List is read-only');
		
		$map->_setReadOnly(true);
		self::assertEquals(true, $map->getReadOnly(), 'List is not converted to Read Only');
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
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TPriorityMapTestBehavior);
		
		$this->map->add('key3', $this->item3);
		$this->assertTrue($this->map->getCount() == 3);
		$this->assertTrue($this->map->contains('key3'));
		
		$this->assertEquals('dyAddItem', $b->method);
		$this->assertEquals('key3', $b->args[0]);
		$this->assertEquals($this->item3, $b->args[1]);
		
		$this->map->add('newKey', 'newValue', 'not_numeric');
		$this->assertTrue($this->map->contains('newKey'));
		$this->assertEquals(10, $this->map->priorityAt('newKey'));
	}

	public function testCanNotAddWhenReadOnly()
	{
		$map = new $this->_baseClass([], true);
		self::expectException(TInvalidOperationException::class);
		$map->add('key', 'value');
	}

	public function testRemove()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TPriorityMapTestBehavior);
		
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
		$map = new $this->_baseClass(['key' => 'value'], true);
		self::expectException(TInvalidOperationException::class);
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
		// Test Traversable
		$array = ['key3' => $this->item3, 'key4' => $this->item1];
		$this->map->copyFrom($array);
		$this->assertTrue($this->map->getCount() == 2);
		$this->assertTrue($this->map['key3'] === $this->item3);
		$this->assertTrue($this->map['key4'] === $this->item1);
		
		//Test Exception
		self::expectException(TInvalidDataTypeException::class);
		$this->map->copyFrom($this);
	}

	public function testMergeWith()
	{
		//Test Traversable
		$array = ['key2' => $this->item1, 'key3' => $this->item3];
		$this->map->mergeWith($array);
		$this->assertEquals(3, $this->map->getCount());
		$this->assertTrue($this->map['key2'] === $this->item1);
		$this->assertTrue($this->map['key3'] === $this->item3);
		
		//Test TPriorityMap
		$map = new TPriorityMap();
		$map->add('key2', $this->item2, 15);
		$map->add('key3', $this->item3, 5);
		$map->add('key4', $this->item4, 10);
		$this->map = new $this->_baseClass();
		$this->map->add('key1', $this->item1);
		$this->map->add('key2', $this->item5);
		$this->map->mergeWith($map);
		self::assertEquals([5 => ['key3' => $this->item3], 10 => ['key1' => $this->item1, 'key4' => $this->item4], 15 => ['key2' => $this->item2]], $this->map->toPriorityArray());
		
		//Test TPriorityList
		$list = new TPriorityList();
		$list->add($this->item1, 15);
		$list->add($this->item2, 5);
		$list->add($this->item3, 10);
		$this->map = new $this->_baseClass();
		$this->map->add(0, $this->item1);
		$this->map->add(5, $this->item5);
		$this->map->mergeWith($list);
		self::assertEquals([5 => [0 => $this->item2], 10 => [1 => $this->item3, 5 => $this->item5], 15 => [2 => $this->item1]], $this->map->toPriorityArray());
		
		//Test Invalid, throws exception.
		self::expectException(TInvalidDataTypeException::class);
		$this->map->mergeWith($this);
	}

	public function testArrayRead()
	{
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TPriorityMapTestBehavior);
		
		$this->assertTrue($this->map['key1'] === $this->item1);
		$this->assertTrue($this->map['key2'] === $this->item2);
		
		$this->assertEquals('dyAttachBehavior', $b->method);
		$this->assertEquals($b, $b->args[1]);
		
		$this->assertEquals(null, $this->map['key3']);
		
		$this->assertEquals('dyNoItem', $b->method);
		$this->assertNull($b->args[0]);
		$this->assertEquals('key3', $b->args[1]);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME);
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TPriorityMapTestNoItemBehavior);
		
		$this->assertInstanceOf('TPriorityMapTest_MapItem', $item3 = $this->map['key3']);
		$this->assertEquals('value', $item3->data);
		
		$this->map['key3'] = null;
		$this->assertNull($this->map['key3']);
		$this->assertNull($this->map->itemAt('key3'));
		$this->assertNull($this->map->itemAt('key3', null));
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
		$map = new $this->_baseClass(['key' => 'value']);
		self::assertEquals(['key' => 'value'], $map->toArray());
	}

	/* Test the priority functionality of TPriorityMap  */

	public function testDefaultPriorityAndPrecision()
	{
		$this->assertEquals(10, $this->map->DefaultPriority);

		$this->map->DefaultPriority = 5;
		$this->assertEquals(5, $this->map->getDefaultPriority());

		$this->assertEquals(8, $this->map->Precision);
		$this->assertEquals(5.4, $this->map->add('key1', $this->item1, 5.4));
		$this->assertEquals(5.5, $this->map->add('key2', $this->item2, 5.5));
		$this->assertEquals(5.6, $this->map->add('key3', $this->item3, 5.6));
		$this->assertEquals(5.3, $this->map->add('key4', $this->item4, 5.3));
		
		$this->map->_setPrecision(0);
		$this->assertEquals(0, $this->map->getPrecision());
		
		$this->assertEquals(5, $this->map->priorityOf($this->item1));
		$this->assertEquals(6, $this->map->priorityOf($this->item2));
		$this->assertEquals(6, $this->map->priorityOf($this->item3));
		$this->assertEquals(5, $this->map->priorityOf($this->item4));
		$this->map->clear();

		$this->assertEquals(5, $this->map->add('key3', $this->item3));
		$this->assertEquals(10, $this->map->add('key4', $this->item1, 10));
		$this->assertEquals(10, $this->map->add('key4', $this->item1, 10.01));
		$this->assertEquals(100, $this->map->add('key4', $this->item1, 100));
		$this->map->_setPrecision(1);
		$this->assertEquals(10.2, $this->map->add('key6', $this->item1, 10.15));

		$this->assertEquals(3, $this->map->getCount());
	}

	public function testAddWithPriorityAndPriorityOfAt()
	{
		$this->setUpPriorities();

		$this->assertEquals(5, $this->map->getCount());
		$this->assertEquals(10, $this->map->priorityOf($this->item1));
		$this->assertEquals(0, $this->map->priorityOf($this->item3));
		$this->assertEquals(100, $this->map->priorityOf($this->item4));
		$this->assertEquals(1, $this->map->priorityOf($this->item5));
		$this->assertEquals(false, $this->map->priorityOf(null));
		$this->assertEquals(false, $this->map->priorityOf('foo'));

		$this->assertEquals(10, $this->map->priorityAt('key1'));
		$this->assertEquals(0, $this->map->priorityAt('key3'));
		$this->assertEquals(100, $this->map->priorityAt('key4'));
		$this->assertEquals(1, $this->map->priorityAt('key5'));
		$this->assertEquals(false, $this->map->priorityAt(null));
		$this->assertEquals(false, $this->map->priorityAt('foo'));
		
		// Test IPriorityItem
		$autoItem = new AutoPriorityMapItem();
		
		$this->map->add('keyA', $autoItem);
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyA'));
		$this->map->remove('keyA');
		
		$autoItem->priority = 4;
		$this->map->add('keyB', $autoItem);
		$this->assertEquals(4, $this->map->priorityAt('keyB'));
		$this->assertEquals(4, $this->map->priorityOf($autoItem));
		$this->map->remove('keyB');
		
		$autoItem->priority = 4;
		$this->map->add('keyC', $autoItem, 'not_numeric');
		$this->assertEquals(4, $this->map->priorityAt('keyC'));
		$this->assertEquals(4, $this->map->priorityOf($autoItem));
		$this->map->remove('keyC');
		
		$autoItem->priority = 3;
		$this->map->add('keyD', $autoItem, 5);
		$this->assertEquals(5, $this->map->priorityAt('keyD'));
		$this->assertEquals(5, $this->map->priorityOf($autoItem));
		$this->map->remove('keyD');
		
		$autoItem->priority = 'not_a_numeric';
		$this->map->add('keyE', $autoItem, 'not_numeric');
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyE'));
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityOf($autoItem));
		$this->map->remove('keyE');
		
		$this->assertEquals(5, $this->map->getCount());
		
		// Test IPriorityProperty
		$this->map = new $this->_baseClass();
		$autoItem = new PriorityPropertyMapItem();
		
		$this->map->add('keyA', $autoItem);
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyA'));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyA');
		
		$autoItem->priority = 4;
		$this->map->add('keyB', $autoItem);
		$this->assertEquals(4, $this->map->priorityAt('keyB'));
		$this->assertEquals(4, $this->map->priorityOf($autoItem));
		$this->assertEquals(4, $autoItem->getPriority());
		$this->map->remove('keyB');
		
		$autoItem->priority = 4;
		$this->map->add('keyC', $autoItem, 'not_numeric');
		$this->assertEquals(4, $this->map->priorityAt('keyC'));
		$this->assertEquals(4, $this->map->priorityOf($autoItem));
		$this->assertEquals(4, $autoItem->getPriority());
		$this->map->remove('keyC');
		
		$autoItem->priority = 3;
		$this->map->add('keyD', $autoItem, 5);
		$this->assertEquals(5, $this->map->priorityAt('keyD'));
		$this->assertEquals(5, $this->map->priorityOf($autoItem));
		$this->assertEquals(5, $autoItem->getPriority());
		$this->map->remove('keyD');
		
		$autoItem->priority = 'not_a_numeric';
		$this->map->add('keyE', $autoItem, 'not_numeric');
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyE'));
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityOf($autoItem));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyE');
		
		$this->assertEquals(0, $this->map->getCount());
		
		// Test IPriorityCapture
		$this->map = new $this->_baseClass();
		$autoItem = new CapturePropertyMapItem();
		
		$this->map->add('keyA', $autoItem);
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyA'));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyA');
		
		$autoItem->priority = 4;
		$this->map->add('keyB', $autoItem);
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyB'));
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityOf($autoItem));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyB');
		
		$autoItem->priority = 4;
		$this->map->add('keyC', $autoItem, 'not_numeric');
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyC'));
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityOf($autoItem));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyC');
		
		$autoItem->priority = 3;
		$this->map->add('keyD', $autoItem, 5);
		$this->assertEquals(5, $this->map->priorityAt('keyD'));
		$this->assertEquals(5, $this->map->priorityOf($autoItem));
		$this->assertEquals(5, $autoItem->getPriority());
		$this->map->remove('keyD');
		
		$autoItem->priority = 'not_a_numeric';
		$this->map->add('keyE', $autoItem, 'not_numeric');
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityAt('keyE'));
		$this->assertEquals($this->map->getDefaultPriority(), $this->map->priorityOf($autoItem));
		$this->assertEquals($this->map->getDefaultPriority(), $autoItem->getPriority());
		$this->map->remove('keyE');
		
		$this->assertEquals(0, $this->map->getCount());
	}

	public function testRemoveWithPriorityAndItemsAtWithPriority()
	{
		$this->setUpPriorities();

		$this->assertEquals(5, $this->map->getCount());
		$this->map->remove('key6');
		$this->assertEquals(5, $this->map->getCount());
		$this->map->remove('key6', null);
		$this->assertEquals(5, $this->map->getCount());


		// key5 is at priority 1...   not the default priority defined by null...  nothing should happen here
		$this->map->remove('key5', null);
		$this->assertEquals(5, $this->map->getCount());

		// key5 is at priority 1...   not 50...  nothing should happen here
		$this->map->remove('key5', 50);
		$this->assertEquals(5, $this->map->getCount());

		$this->assertEquals(['key3' => $this->item3], $this->map->itemsAtPriority(0));
		$this->assertEquals(['key1' => $this->item1, 'key2' => $this->item2], $this->map->itemsAtPriority($this->map->DefaultPriority));

		$this->assertEquals($this->item2, $this->map->itemAt('key2'));
		$this->assertEquals($this->item2, $this->map->itemAt('key2', 10));
		$this->assertNull($this->map->itemAt('key2', 11)); //'key2' doesn't exist and priority 11...  it is only at priority 10
		$this->assertNull($this->map->itemAt('key2', 10.1)); //'key2' doesn't exist and priority 10.1...  it is only at priority 10

		$this->assertEquals($this->item4, $this->map->remove('key4'));
		$this->assertEquals(4, $this->map->getCount());

		$this->assertEquals($this->item5, $this->map->remove('key5'));
		$this->assertEquals(3, $this->map->getCount());
	}

	public function testIteratorAndArrayWithPriorities()
	{
		$this->setUpPriorities();

		// This is the primary reason for a TPriorityMap
		$array = $this->map->toArray();

		$ordered_keys = array_keys($array);
		$this->assertEquals('key3', $ordered_keys[0]);
		$this->assertEquals('key5', $ordered_keys[1]);
		$this->assertEquals('key1', $ordered_keys[2]);
		$this->assertEquals('key2', $ordered_keys[3]);
		$this->assertEquals('key4', $ordered_keys[4]);

		$ordered_values = array_values($array);
		$this->assertEquals($this->item3, $ordered_values[0]);
		$this->assertEquals($this->item5, $ordered_values[1]);
		$this->assertEquals($this->item1, $ordered_values[2]);
		$this->assertEquals($this->item2, $ordered_values[3]);
		$this->assertEquals($this->item4, $ordered_values[4]);

		$iter = $this->map->getIterator();

		$this->assertTrue($iter->valid());
		$this->assertEquals('key3', $iter->key());
		$this->assertEquals($this->item3, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key5', $iter->key());
		$this->assertEquals($this->item5, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key1', $iter->key());
		$this->assertEquals($this->item1, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key2', $iter->key());
		$this->assertEquals($this->item2, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key4', $iter->key());
		$this->assertEquals($this->item4, $iter->current());
		$iter->next();
		$this->assertFalse($iter->valid());
		$this->assertEquals(null, $iter->key());
		$this->assertEquals(null, $iter->current());
	}

	public function testGetPriorities()
	{
		$this->setUpPriorities();

		$priorities = $this->map->getPriorities();

		$this->assertEquals(0, $priorities[0]);
		$this->assertEquals(1, $priorities[1]);
		$this->assertEquals(10, $priorities[2]);
		$this->assertEquals(100, $priorities[3]);
		$this->assertEquals(false, isset($priorities[4]));
	}

	public function testCopyAndMergeWithPriorities()
	{
		//Test TPriorityMap
		$map = new TPriorityMap();
		$map->add('key2', $this->item2, 15);
		$map->add('key3', $this->item3, 5);
		$map->add('key4', $this->item4, 10);
		$this->map = new $this->_baseClass();
		$this->map->add('key1', $this->item1);
		$this->map->add('key2', $this->item5);
		$this->map->copyFrom($map);
		self::assertEquals([5 => ['key3' => $this->item3], 10 => ['key4' => $this->item4], 15 => ['key2' => $this->item2]], $this->map->toPriorityArray());
		
		//Test TPriorityList
		$list = new TPriorityList();
		$list->add($this->item1, 15);
		$list->add($this->item2, 5);
		$list->add($this->item3, 10);
		$this->map = new $this->_baseClass();
		$this->map->add(0, $this->item1);
		$this->map->add(5, $this->item5);
		$this->map->copyFrom($list);
		self::assertEquals([5 => [0 => $this->item2], 10 => [1 => $this->item3], 15 => [2 => $this->item1]], $this->map->toPriorityArray());
	}

	public function testSetPriorityAt()
	{
		$this->assertEquals(10, $this->map->priorityAt('key2'));
		$this->assertEquals(10, $this->map->setPriorityAt('key2', 1));
		$this->assertEquals(1, $this->map->priorityAt('key2'));
		$this->assertEquals(1, $this->map->setPriorityAt('key2'));
		$this->assertEquals(10, $this->map->priorityAt('key2'));
	}

	public function testToArrayBelowPriority()
	{
		$this->setUpPriorities();

		$array = $this->map->toArrayBelowPriority(1);
		$this->assertEquals(['key3' => $this->item3], $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayBelowPriority(1, true);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5], $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(2);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5], $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(10);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5], $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(10, true);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2], $array);
		$this->assertEquals(4, count($array));

		$array = $this->map->toArrayBelowPriority(100);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2], $array);
		$this->assertEquals(4, count($array));

		$array = $this->map->toArrayBelowPriority(100, true);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4], $array);
		$this->assertEquals(5, count($array));
	}

	public function testToArrayAbovePriority()
	{
		$this->setUpPriorities();

		$array = $this->map->toArrayAbovePriority(100, false);
		$this->assertEquals(0, count($array));

		$array = $this->map->toArrayAbovePriority(100, true);
		$this->assertEquals(1, count($array));
		$this->assertEquals(['key4' => $this->item4], $array);

		$array = $this->map->toArrayAbovePriority(11);
		$this->assertEquals(['key4' => $this->item4], $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayAbovePriority(10, false);
		$this->assertEquals(['key4' => $this->item4], $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayAbovePriority(10);
		$this->assertEquals(['key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4], $array);
		$this->assertEquals(3, count($array));

		$array = $this->map->toArrayAbovePriority(0);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4], $array);
		$this->assertEquals(5, count($array));
	}
}