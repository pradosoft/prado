@@ -0,0 +1,682 @@
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
	use TListResetTrait;
	
	public function _setDefaultPriority($value)
	{
		$this->setDefaultPriority($value);
	}
	public function _setPrecision($value)
	{
		$this->setPrecision($value);
	}
	public function _setReadOnly($value)
	{
		$this->setReadOnly($value);
	}
}

/** ***********************************************
 *   Test Class
 */

class TPriorityMapTest extends TMapTest
{
	protected const BEHAVIOR_NAME = 'catcher';
	
	protected $item4;
	protected $item5;
	protected $item6;

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
		$this->item6 = new $this->_baseItemClass(6);

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

	public function testConstructTPriorityMap()
	{
		/* Test the priority functionality of TPriorityMap  */

		$map3 = new $this->_baseClass($this->map, false, 100, -1);
		$this->assertEquals(100, $map3->getDefaultPriority());
		$this->assertEquals(-1, $map3->getPrecision());
	}
	
	public function testGetKeysTPriorityMap()
	{
		$this->map[] = $this->item2;
		$this->map->add(null, $this->item5, 15);
		$this->map[] = $this->item3;
		$this->map->add(null, $this->item1, 5);
		$this->map->add(3, $this->item4, 10);
		$this->map->add(10, $this->item6, 10);
		$this->assertEquals(['key1', 'key2', 0, 2, 3, 10, 1], $this->map->getKeys());
	}
	
	public function testGetPriorityCountTPriorityMap()
	{
		$this->map[] = $this->item2;
		$this->map->add(null, $this->item5, 15);
		$this->map[] = $this->item3;
		$this->map->add(null, $this->item1, 5);
		$this->map->add(3, $this->item4, 10); // replace item1 at [3]
		$this->map->add(10, $this->item6, 10);
		$this->assertEquals(6, $this->map->getPriorityCount(10));
		$this->assertEquals(0, $this->map->getPriorityCount(12));
		$this->assertEquals(1, $this->map->getPriorityCount(15));
	}
	
	public function testItemAtTPriorityMap()
	{
		$this->map[] = $this->item2;
		$this->map->add(null, $this->item4, 15);
		$this->map[] = $this->item3;
		$this->map->add(null, $this->item1, 5);
		$this->map->add(3, $this->item5, 10); // replace item3 item with item5
		$this->map->add(10, $this->item6, 10);
		
		$this->assertEquals($this->item2, $this->map->itemAt(0));
		$this->assertEquals($this->item1, $this->map->itemAt('key1'));
		$this->assertEquals($this->item2, $this->map->itemAt('key2'));
		$this->assertEquals($this->item4, $this->map->itemAt(1));
		$this->assertEquals($this->item3, $this->map->itemAt(2));
		$this->assertEquals($this->item5, $this->map->itemAt(3));
		$this->assertNull($this->map->itemAt(4));
		$this->assertEquals($this->item6, $this->map->itemAt(10));
		
		$this->assertEquals($this->item2, $this->map->itemAt(0, 10));
		$this->assertEquals($this->item3, $this->map->itemAt(2, 10));
		$this->assertEquals($this->item5, $this->map->itemAt(3, 10));
		$this->assertEquals($this->item6, $this->map->itemAt(10, 10));
		$this->assertEquals($this->item4, $this->map->itemAt(1, 15));
	}
	
	public function testPriorityAtTPriorityMap()
	{
		$this->map[] = $this->item2;
		$this->map->add(null, $this->item4, 15);
		$this->map[] = $this->item3;
		$this->map->add(null, $this->item1, 5);
		
		$this->assertEquals(10, $this->map->priorityAt(0));
		$this->assertEquals(15, $this->map->priorityAt(1));
		$this->assertEquals(10, $this->map->priorityAt(2));
		$this->assertEquals(5, $this->map->priorityAt(3));
	}
	
	public function testContainsTPriorityMap()
	{
		$this->map[] = $this->item2;
		$this->map->add(null, $this->item4, 15);
		$this->map[] = $this->item3;
		$this->map->add(null, $this->item1, 5);
		
		$this->map[10] = $this->item5;
		
		$this->assertTrue($this->map->contains(0));
		$this->assertTrue($this->map->contains('key1'));
		$this->assertTrue($this->map->contains('key2'));
		$this->assertTrue($this->map->contains(1));
		$this->assertTrue($this->map->contains(2));
		$this->assertTrue($this->map->contains(3));
		$this->assertTrue($this->map->contains(10));
	}
	
	public function testKeyOfTPriorityMap()
	{
		$this->map->add(null, $this->item2, 5);
		$this->map->add(null, $this->item4, 15);
		$this->map[] = $this->item3;
		$this->map[] = $this->item1;
		
		$this->map[10] = $this->item5;
		
		$this->assertEquals('key1', $this->map->keyOf($this->item1, false), "keyOf did not find the first instance of item1 properly.");
		$this->assertEquals(['key1' => $this->item1, 3 => $this->item1], $this->map->keyOf($this->item1), "keyOf did not find the all of item1 properly.");
		$this->assertEquals(0, $this->map->keyOf($this->item2, false), "keyOf did not find the first _priority_ instance of item2 properly.");
		$this->assertEquals(2, $this->map->keyOf($this->item3, false));
		$this->assertEquals([2 => $this->item3], $this->map->keyOf($this->item3, true));
		$this->assertEquals([2 => $this->item3], $this->map->keyOf($this->item3));
		$this->assertEquals(1, $this->map->keyOf($this->item4, false));
		$this->assertEquals(10, $this->map->keyOf($this->item5, false));
		$this->assertFalse($this->map->keyOf($this, false));
	}
	
	public function testCopyFromTPriorityMap()
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
		
		//Test TPriorityList CopyFrom
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

	public function testMergeWithTPriorityList()
	{
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
		$this->map->add(0, $this->item4);
		$this->map->add(2, $this->item5, 5);
		$this->map->mergeWith($list);
		self::assertEquals([5 => [2 => $this->item5, 3 => $this->item2], 10 => [0 => $this->item4, 4 => $this->item3], 15 => [5 => $this->item1]], $this->map->toPriorityArray());
		
		//Test Invalid, throws exception.
		self::expectException(TInvalidDataTypeException::class);
		$this->map->mergeWith($this);
	}

	public function testArrayReadTPriorityMap()
	{
		$this->assertEquals(null, $this->map['key3']);
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $b = new TMapTestNoItemBehavior);
		
		$this->assertInstanceOf('TMapTest_MapItem', $this->map['key3']);
		
		$this->map['key3'] = null;
		
		$this->assertNull($this->map->itemAt('key3', null));
	}
	
	public function testArrayWriteTPriorityMap()
	{
		$this->assertEquals(0, $this->map->getNextIntegerKey());
		$this->map[] = $this->item1;
		$this->assertEquals(1, $this->map->getNextIntegerKey());
		$this->map->add(null, $this->item2, 15);
		$this->assertEquals(2, $this->map->getNextIntegerKey());
		$this->map[] = $this->item3;
		$this->assertEquals(3, $this->map->getNextIntegerKey());
		$this->map->add(null, $this->item4, 5);
		$this->assertEquals(4, $this->map->getNextIntegerKey());
		$this->assertEquals($this->item1, $this->map[0]);
		$this->assertEquals($this->item2, $this->map[1]);
		$this->assertEquals($this->item3, $this->map[2]);
		$this->assertEquals($this->item4, $this->map[3]);
		$this->assertNull($this->map[4]);
		
		$this->map[11] = $this->item1;
		$this->assertEquals($this->item1, $this->map[11]);
		$this->assertEquals(12, $this->map->getNextIntegerKey());
	}
	
	// **    End TMap tests on TPriorityMap
	// ***********************************************


	// ***********************************************
	// **    Start TPriorityMap specific tests
	/* Test the priority functionality of TPriorityMap  */

	public function testDefaultPriorityAndPrecision()
	{
		$this->assertEquals(10, $this->map->DefaultPriority);

		$this->map->_setDefaultPriority(5);
		$this->assertEquals(5, $this->map->getDefaultPriority());

		$this->assertEquals(8, $this->map->Precision);
		$this->assertEquals('key1', $this->map->add('key1', $this->item1, 5.4));
		$this->assertEquals(11, $this->map->add(11, $this->item2, 5.5));
		$this->assertEquals('key3', $this->map->add('key3', $this->item3, 5.6));
		$this->assertEquals('key4', $this->map->add('key4', $this->item4, 5.3));
		
		$this->map->_setPrecision(0);
		$this->assertEquals(0, $this->map->getPrecision());
		
		$this->assertEquals(5, $this->map->priorityOf($this->item1));
		$this->assertEquals(6, $this->map->priorityOf($this->item2));
		$this->assertEquals(6, $this->map->priorityAt(11)); // $this->item2
		$this->assertEquals(6, $this->map->priorityOf($this->item3));
		$this->assertEquals(5, $this->map->priorityOf($this->item4));
		$this->map->clear();

		$this->assertEquals('key3', $this->map->add('key3', $this->item3));
		$this->assertEquals(5, $this->map->priorityOf($this->item3));
		$this->assertEquals('key4', $this->map->add('key4', $this->item1, 10));
		$this->assertEquals(10, $this->map->priorityOf($this->item1));
		$this->assertEquals('key4', $this->map->add('key4', $this->item1, 10.01));
		$this->assertEquals(10, $this->map->priorityOf($this->item1));
		$this->assertEquals('key4', $this->map->add('key4', $this->item1, 100));
		$this->assertEquals(100, $this->map->priorityOf($this->item1));
		$this->map->_setPrecision(1);
		$this->assertEquals('key6', $this->map->add('key6', $this->item1, 10.15));
		$this->assertEquals(10.2, $this->map->priorityOf($this->item1));

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
		
		$this->map[6] = $this->item6;

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
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 6 => $this->item6], $array);
		$this->assertEquals(5, count($array));

		$array = $this->map->toArrayBelowPriority(100);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 6 => $this->item6], $array);
		$this->assertEquals(5, count($array));

		$array = $this->map->toArrayBelowPriority(100, true);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 6 => $this->item6, 'key4' => $this->item4], $array);
		$this->assertEquals(6, count($array));
	}

	public function testToArrayAbovePriority()
	{
		$this->setUpPriorities();
		$this->map[6] = $this->item6;

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
		$this->assertEquals(['key1' => $this->item1, 'key2' => $this->item2, 6 => $this->item6, 'key4' => $this->item4], $array);
		$this->assertEquals(4, count($array));

		$array = $this->map->toArrayAbovePriority(0);
		$this->assertEquals(['key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 6 => $this->item6, 'key4' => $this->item4], $array);
		$this->assertEquals(6, count($array));
	}
}