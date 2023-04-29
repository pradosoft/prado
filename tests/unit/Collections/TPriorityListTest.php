<?php

use Prado\Collections\IPriorityItem;
use Prado\Collections\TPriorityList;
use Prado\Collections\TPriorityMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

class PriorityListItem
{
	public $data = 'data';
	public function __construct($d)
	{
		$this->data = $d;
	}
}

class AutoPriorityListItem extends PriorityListItem implements IPriorityItem
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
	
	public function __invoke()
	{
	}
}

class CapturePriorityListItem extends PriorityListItem implements IPriorityCapture
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
	
	public function __invoke()
	{
	}
}

class PriorityPropertyListItem extends PriorityListItem implements IPriorityProperty
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

	public function __invoke()
	{
	}
}

class TPriorityListUnit extends TPriorityList
{
	use TListResetTrait;
	
	public function resetDefaultPriority($value)
	{
		$this->setDefaultPriority($value);
	}
	
	public function resetPrecision($value)
	{
		$this->setPrecision($value);
	}
}

/**
 *	All Test cases for the TList are here.  The TPriorityList should act just like a TList when used exactly like a TList
 *
 * The TPriority List should start behaving differently when using the class outside of the standard TList Function calls
 */
class TPriorityListTest extends TListTest
{
	protected $plist;
	protected $pfirst;
	protected $pitem1;
	protected $pitem2;
	protected $pitem3;
	protected $pitem4;
	protected $pitem5;

	protected function newList()
	{
		return  TPriorityListUnit::class;
	}
	protected function newListItem()
	{
		return PriorityListItem::class;
	}
	protected function getCanAddNull()
	{
		return true;
	}

	protected function setUp(): void
	{
		$this->_baseClass = $this->newList();
		$this->_baseItemClass = $this->newListItem();
		
		parent::setUp();

		// ****  start the setup for non-TList things
		$this->plist = new $this->_baseClass();
		$this->pfirst = new $this->_baseItemClass(5);
		$this->pitem1 = new $this->_baseItemClass(6);
		$this->pitem2 = new $this->_baseItemClass(7);
		$this->pitem3 = new $this->_baseItemClass(8);
		$this->pitem4 = new $this->_baseItemClass(9);
		$this->pitem5 = new $this->_baseItemClass(0);
		$this->plist->add($this->pitem1);
		$this->plist->add($this->pitem3, 100);
		$this->plist->add($this->pitem2);
		$this->plist->add($this->pfirst, -10000000);
		// 4 and 5 are not inserted
		// ending setup: pfirst @ -10000000[0], pitem1 @ 10[0], pitem2 @ 10[1], pitem3 @ 100[0]
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		// ****  start the setup for non-TList things
		$this->plist = null;
		$this->pfirst = null;
		$this->pitem1 = null;
		$this->pitem2 = null;
		$this->pitem3 = null;
		$this->pitem4 = null;
		$this->pitem5 = null;
	}

	//*****************************************************************
	//*******  start test cases for TList operations
	//*******		TPriorityList should act exactly like a TList if no special functions are used
	
	// These tests are inherited from the parent test

	//*******  end test cases for TList operations
	//*****************************************************************


	//*******  start test cases for TPriorityList operations
	//*****************************************************************


	public function testConstructTPriorityList()
	{
		$a = [
			'a' => $this->item1, '0.5' => $this->item2, 9 => $this->item3
		];

		$list = new $this->_baseClass($a);
		$this->assertEquals(3, $list->getCount());

		$list2 = new $this->_baseClass($this->plist);
		// validate that the elements were copied
		$this->assertEquals(4, $list2->getCount());
		$this->assertEquals(-10000000, $list2->priorityOf($this->pfirst));
		$this->assertEquals(100, $list2->priorityOf($this->pitem3));
		$this->assertEquals(-10000000, $list2->priorityAt(0));
		$this->assertEquals($list2->DefaultPriority, $list2->priorityAt(2));
		$this->assertEquals(100, $list2->priorityAt(3));
	}
	
	public function testDefaultPriority()
	{
		$this->assertEquals(10, $this->list->getDefaultPriority());
		
		$list = new $this->_baseClass();
		$this->assertEquals(10, $list->getDefaultPriority());
		
		$list = new $this->_baseClass();
		$list->setDefaultPriority(21);
		$this->assertEquals(21, $list->getDefaultPriority());
		$list->resetDefaultPriority(15);
		$this->assertEquals(15, $list->getDefaultPriority());
		
		self::expectException(TInvalidOperationException::class);
		$list->setDefaultPriority(20);
	}
	
	public function testPrecision()
	{
		$this->assertEquals(8, $this->list->getPrecision());
		
		$list = new $this->_baseClass();
		$this->assertEquals(8, $list->getPrecision());
		
		$list = new $this->_baseClass();
		$list->setPrecision(4);
		$this->assertEquals(4, $list->getPrecision());
		$list->resetPrecision(5);
		$this->assertEquals(5, $list->getPrecision());
		
		self::expectException(TInvalidOperationException::class);
		$list->setPrecision(6);
	}

	public function testGetPriorityCountTPriorityList()
	{
		$this->assertEquals(2, $this->plist->getPriorityCount());
		$this->assertEquals(2, $this->plist->getPriorityCount(null));
		$this->assertEquals(1, $this->plist->getPriorityCount(100));
		$this->assertEquals(1, $this->plist->getPriorityCount(-10000000));
	}

	public function testItemsAtPriorityTPriorityList()
	{
		$items = $this->plist->itemsAtPriority();

		$this->assertEquals(2, count($items));
		$this->assertEquals($this->pitem2, $items[1]);


		$items = $this->plist->itemsAtPriority(100);

		$this->assertEquals(1, count($items));
		$this->assertEquals($this->pitem3, $items[0]);
	}

	public function testItemAtTPriorityList()
	{
		$this->assertTrue($this->plist->itemAt(0) === $this->pfirst);
		$this->assertTrue($this->plist->itemAt(1) === $this->pitem1);
		$this->assertTrue($this->plist->itemAt(2) === $this->pitem2);
		$this->assertTrue($this->plist->itemAt(3) === $this->pitem3);
	}

	public function testAddTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);

		$plist->add($this->pitem3, 200);
		$this->assertEquals(200, $plist->priorityAt(4));

		// try a negative precision and a different default priority
		$list = new $this->_baseClass(null, false, 256, -1);

		$this->assertEquals(260, $list->getDefaultPriority());
		$this->assertEquals(-1, $list->getPrecision());
		$list->add($this->item1);
		$list->add($this->item2, 255);
		$list->add($this->item3, 250);
		$list->add($this->item4, 201);
		$this->assertEquals(200, $list->priorityAt(0));
		$this->assertEquals(250, $list->priorityAt(1));
		$this->assertEquals(260, $list->priorityAt(2));
		$this->assertEquals(260, $list->priorityAt(3));

		$priorities = $list->getPriorities();
		$this->assertEquals(3, count($priorities));
		$this->assertEquals(200, $priorities[0]);
		$this->assertEquals(250, $priorities[1]);
		$this->assertEquals(260, $priorities[2]);

		// try a negative precision and a different default priority
		$list = new $this->_baseClass(null, false, 0, 4);

		$this->assertEquals(0, $list->getDefaultPriority());
		$this->assertEquals(4, $list->getPrecision());
		$list->add($this->item1);
		$list->add($this->item2, 0.0001);
		$list->add($this->item3, 0.00001);
		$list->add($this->item4, 0.001);
		$this->assertEquals(0, $list->priorityAt(0));
		$this->assertEquals(0, $list->priorityAt(1));
		$this->assertEquals(0.0001, $list->priorityAt(2));
		$this->assertEquals(0.001, $list->priorityAt(3));

		$priorities = $list->getPriorities();
		$this->assertEquals(3, count($priorities));
		$this->assertEquals(0, $priorities[0]);
		$this->assertEquals(0.0001, $priorities[1]);
		$this->assertEquals(0.001, $priorities[2]);
	}

	public function testInsertAtTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);
		$this->assertNull($plist->insertAt(0, $this->pitem3));
		$this->assertEquals(-10000000, $plist->priorityAt(0));
		$this->assertEquals(100, $plist->priorityAt(4));
		
		$plist = new $this->_baseClass();
		$this->assertNull($plist->insertAt(0, $this->pitem1));
		$this->assertEquals($this->pitem1, $plist->itemAt(0));
		$this->assertEquals(10, $plist->priorityOf($this->pitem1));
		$plist->add($this->pitem4, 20);
		$this->assertNull($plist->insertAt(1, $this->pitem2));
		$this->assertEquals($this->pitem1, $plist->itemAt(0));
		$this->assertEquals($this->pitem2, $plist->itemAt(1));
		$this->assertEquals(20, $plist->priorityOf($this->pitem2));
		$this->assertNull($plist->insertAt(3, $this->pitem3));
		$this->assertEquals(20, $plist->priorityOf($this->pitem3));
		$this->assertEquals(20, $plist->priorityAt(3));

		self::expectException(TInvalidDataValueException::class);
		$plist->insertAt(5, $this->pitem3);
	}

	public function testInsertBeforeTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);

		$this->assertEquals(4, $plist->getCount());
		$plist->insertBefore($this->pitem3, $this->pitem4);
		$this->assertEquals(100, $plist->priorityOf($this->pitem4));
	}

	public function testInsertAfterTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);

		$this->assertEquals(4, $plist->getCount());
		$plist->insertAfter($this->pfirst, $this->pitem4);
		$this->assertEquals(-10000000, $plist->priorityOf($this->pitem4));
	}

	public function testRemoveTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);

		$this->assertEquals(2, $plist->remove($this->pitem2));
		$this->assertEquals(1, $plist->getPriorityCount());

		$plist = new $this->_baseClass($this->plist);

		try {
			$plist->remove($this->pitem5);
			$this->fail('TInvalidDataValueException not raised when removing item not in the list');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}
		
		try {
			$plist->remove($this->pitem3, null);
			$this->fail('TInvalidDataValueException not raised when removing item that is not at the default priority');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}
		
		try {
			$plist->remove($this->pitem1, 100);
			$this->fail('TInvalidDataValueException not raised when removing item that is not at assigned priority');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}

		$plist->insertBefore($this->pitem3, $this->pitem4);
		$this->assertEquals(4, $plist->remove($this->pitem3, 100));
	}

	public function testRemoveAtTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);

		$this->assertEquals(4, $plist->getCount());
		$this->assertEquals($this->pitem1, $plist->removeAt(1));
		$this->assertEquals(3, $plist->getCount());
		$this->assertEquals(-1, $plist->indexOf($this->pitem1));
		$this->assertEquals(1, $plist->indexOf($this->pitem2), 'Item 2 was not in the list.');
		$this->assertEquals(0, $plist->indexOf($this->pfirst));

		self::expectException(TInvalidDataValueException::class);
		$plist->removeAt(3);
	}

	public function testItemAtIndexPriorityTPriorityList()
	{
		$this->assertEquals($this->pitem2, $this->plist->itemAtIndexInPriority(1));
		$this->assertEquals($this->pitem1, $this->plist->itemAtIndexInPriority(0, $this->plist->getDefaultPriority()));
		$this->assertEquals($this->pfirst, $this->plist->itemAtIndexInPriority(0, -10000000));
		$this->assertEquals($this->pitem3, $this->plist->itemAtIndexInPriority(0, 100));
		
		self::expectException(TInvalidDataValueException::class);
		$this->plist->itemAtIndexInPriority(2);
	}


	public function testInsertAtIndexInPriorityTPriorityList()
	{
		// as false
		
		$plist = new $this->_baseClass();
		
		$plist->insertAtIndexInPriority($this->pfirst);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem1, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem2, 2);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem3, false, null);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());
		
		
		$plist->insertAtIndexInPriority($this->pitem5, false, 10);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem5
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->item2, false, 100);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem5,$this->item2
		], $plist->toArray());
		
		
		// as null
		$plist = new $this->_baseClass();

		$plist->insertAtIndexInPriority($this->pfirst);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem1, null);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem2, 2);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem3, null, null);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());
		
		try {	// Out of range index/priority
			$plist->insertAtIndexInPriority($this->pitem4, 5, null);
			$this->fail("failed to assert TInvalidDataValueException when inserting at index out of range for priority.");
		} catch(TInvalidDataValueException $e){
		}
		$plist->insertAtIndexInPriority($this->pitem4, 4, null);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem5, null, 10);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item1, 6, 10);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item2, null, 100);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());

		// Preserve Cache
		$plist = new $this->_baseClass();

		$plist->insertAtIndexInPriority($this->pfirst, null, null, true);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem1, null, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem2, 2, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem3, null, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem4, 4, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem5, null, 10, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item1, 6, 10, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item2, null, 100, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());


		// no preserve cache
		$plist = new $this->_baseClass();
		$plist->insertAtIndexInPriority($this->pfirst, null, null, false);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem1, null, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem2, 2, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem3, null, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem4, 4, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem5, null, 10, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item1, 6, 10, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->item2, null, 100, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());
		
		
		//test when the flattened array is empty, but an array, then add
		$plist = new $this->_baseClass();
		$plist->insertAtIndexInPriority($this->pfirst, false, null, true);
		$this->assertEquals([$this->pfirst], $plist->toArray());

		$plist->remove($this->pfirst, false);
		$this->assertEquals([], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pfirst, false, null, true);
		$this->assertEquals([$this->pfirst], $plist->toArray());
		
		
		//test when the flattened array is empty, but an array, then add
		$plist = new $this->_baseClass();
		$plist->insertAtIndexInPriority($this->pfirst, false, null, false);
		$this->assertEquals([$this->pfirst], $plist->toArray());

		$plist->remove($this->pfirst, false);
		$this->assertEquals([], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pfirst, false, null, false);
		$this->assertEquals([$this->pfirst], $plist->toArray());
		
		
		
		// Test IPriorityItem
		$aplistitem = new AutoPriorityListItem("my Data");
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority(null));
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority(null));
		
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		$this->assertEquals(null, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		
		$aplistitem->priority = 5;
		$this->assertEquals(5, $aplistitem->getPriority());
		$this->assertEquals(true, $aplistitem instanceof IPriorityItem);
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(5));
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(5));
		
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		
		
		$aplistitem->priority = 'string';
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority(10));
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority(10));
		
		
		$aplistitem->priority = '7';
		$plist->insertAtIndexInPriority($aplistitem, false, 'not_numeric');
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(7));
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(7));
	}
	

	public function testCanNotInsertAtIndexInPriorityWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([], true);
		self::expectException(TInvalidOperationException::class);
		$list->insertAtIndexInPriority($this->item1);
	}


	public function testRemoveAtIndexInPriorityTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);
		try {
			$plist->removeAtIndexInPriority(1, 100);
			$this->fail('TInvalidDataValueException cannot remove item from priority beyond its count');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}

		$this->assertEquals($this->pitem2, $plist->removeAtIndexInPriority(1));
		$this->assertEquals($this->pitem3, $plist->removeAtIndexInPriority(0, 100));
		$this->assertEquals($this->pitem1, $plist->removeAtIndexInPriority(0, 10));

		try {
			$plist->removeAtIndexInPriority(0, 200);
			$this->fail('TInvalidDataValueException cannot remove item from priority that does not exist');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}

		$this->assertEquals($this->pfirst, $plist->removeAtIndexInPriority(0, -10000000));
		$this->assertEquals(0, $plist->getCount());
	}

	public function testCanNotRemoveAtIndexInPriorityWhenReadOnlyTList()
	{
		$plist = new $this->_baseClass($this->plist, true);
		self::expectException(TInvalidOperationException::class);
		$plist->removeAtIndexInPriority(0);
	}
	
	public function testIPriorityCapture()
	{
		$plist = new $this->_baseClass();
		$plist[] = $this->pfirst;
		
		$aplistitem = new CapturePriorityListItem("my Data");
		$this->assertEquals(null, $aplistitem->getPriority());
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority(null));
		$this->assertEquals($plist->getDefaultPriority(), $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority(null));
		$this->assertEquals($plist->getDefaultPriority(), $aplistitem->getPriority());
		
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$aplistitem->priority = 5;
		$this->assertEquals(5, $aplistitem->getPriority());
		$this->assertEquals(true, $aplistitem instanceof IPriorityCapture);
		$this->assertEquals(false, $aplistitem instanceof IPriorityItem);
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority($aplistitem->getPriority()));
		$this->assertEquals($aplistitem->getPriority(), $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority($aplistitem->getPriority()));
		$this->assertEquals($aplistitem->getPriority(), $aplistitem->getPriority());
		
		$aplistitem->priority = 5;
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
	}
	
	public function testIPriorityProperty()
	{
		$plist = new $this->_baseClass();
		$plist[] = $this->pfirst;
		
		$aplistitem = new PriorityPropertyListItem("my Data");
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority(null));
		$this->assertEquals($plist->getDefaultPriority(), $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority(null));
		$this->assertEquals($plist->getDefaultPriority(), $aplistitem->getPriority());
		
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$aplistitem->priority = 5;
		$this->assertEquals(5, $aplistitem->getPriority());
		$this->assertEquals(true, $aplistitem instanceof IPriorityItem);
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(5));
		$this->assertEquals(5, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(5));
		$this->assertEquals(5, $aplistitem->getPriority());
		
		$plist->insertAtIndexInPriority($aplistitem, false, 20);
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(20));
		$this->assertEquals(20, $aplistitem->getPriority());
		
		
		$aplistitem->priority = 'string';
		$plist->insertAtIndexInPriority($aplistitem, false, null);
		$this->assertEquals([$this->pfirst, $aplistitem], $plist->itemsAtPriority(10));
		$this->assertEquals(10, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals([$this->pfirst], $plist->itemsAtPriority(10));
		$this->assertEquals(10, $aplistitem->getPriority());
		
		
		$aplistitem->priority = '7';
		$plist->insertAtIndexInPriority($aplistitem, false, 'not_numeric');
		$this->assertEquals([$aplistitem], $plist->itemsAtPriority(7));
		$this->assertEquals(7, $aplistitem->getPriority());
		
		$plist->remove($aplistitem);
		$this->assertEquals(null, $plist->itemsAtPriority(7));
		$this->assertEquals(7, $aplistitem->getPriority());
	}

	public function testPriorityOfTPriorityList()
	{
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem1));
		$this->assertEquals(100, $this->plist->priorityOf($this->pitem3));

		$priority = $this->plist->priorityOf($this->pfirst, true);

		$this->assertEquals(-10000000, $priority[0]);
		$this->assertEquals(0, $priority[1]);
		$this->assertEquals(0, $priority[2]);

		$this->assertEquals(-10000000, $priority['priority']);
		$this->assertEquals(0, $priority['index']);
		$this->assertEquals(0, $priority['absindex']);

		$priority = $this->plist->priorityOf($this->pitem2, true);

		$this->assertEquals(10, $priority[0]);
		$this->assertEquals(1, $priority[1]);
		$this->assertEquals(2, $priority[2]);

		$this->assertEquals(10, $priority['priority']);
		$this->assertEquals(1, $priority['index']);
		$this->assertEquals(2, $priority['absindex']);
	}

	public function testPriorityAtTPriorityList()
	{
		$this->assertEquals(10, $this->plist->priorityAt(2));
		$this->assertEquals(100, $this->plist->priorityAt(3));

		$priority = $this->plist->priorityAt(0, true);

		$this->assertEquals(-10000000, $priority[0]);
		$this->assertEquals(0, $priority[1]);
		$this->assertEquals(0, $priority[2]);

		$this->assertEquals(-10000000, $priority['priority']);
		$this->assertEquals(0, $priority['index']);
		$this->assertEquals(0, $priority['absindex']);

		$priority = $this->plist->priorityAt(2, true);

		$this->assertEquals(10, $priority[0]);
		$this->assertEquals(1, $priority[1]);
		$this->assertEquals(2, $priority[2]);

		$this->assertEquals(10, $priority['priority']);
		$this->assertEquals(1, $priority['index']);
		$this->assertEquals(2, $priority['absindex']);
	}

	public function testGetPrioritiesTPriorityList()
	{
		$priorities = $this->plist->getPriorities();
		$this->assertEquals(3, count($priorities));
		$this->assertEquals(-10000000, $priorities[0]);
		$this->assertEquals(10, $priorities[1]);
		$this->assertEquals(100, $priorities[2]);
	}

	public function testClearTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);
		$plist->clear();
		$this->assertEquals(0, $plist->getCount());
		$this->assertEquals(-1, $plist->indexOf($this->pitem1));
		$this->assertEquals(-1, $plist->indexOf($this->pitem3));
	}

	public function testContainTPriorityLists()
	{
		$plist = new $this->_baseClass($this->plist);
		$this->assertTrue($plist->contains($this->pfirst));
		$this->assertTrue($plist->contains($this->pitem1));
		$this->assertTrue($plist->contains($this->pitem2));
		$this->assertTrue($plist->contains($this->pitem3));
		$this->assertFalse($plist->contains($this->pitem5));
	}

	public function testIndexOfTPriorityList()
	{
		$plist = new $this->_baseClass($this->plist);
		$this->assertEquals(0, $plist->indexOf($this->pfirst));
		$this->assertEquals(1, $plist->indexOf($this->pitem1));
		$this->assertEquals(2, $plist->indexOf($this->pitem2));
		$this->assertEquals(3, $plist->indexOf($this->pitem3));
		$this->assertEquals(-1, $plist->indexOf($this->pitem4));
	}

	public function testCopyFromTPriorityList()
	{
		// Copy from TPriorityList
		$pfirst = new $this->_baseItemClass(-10000);
		$pitem1 = new $this->_baseItemClass(-1);
		$pitem2 = new $this->_baseItemClass(-2);
		$pitem3 = new $this->_baseItemClass(-3);
		
		$plist = new TPriorityList();
		$plist->add($pitem3, 100);
		$plist->add($pitem1);
		$plist->add($pfirst, -10000000);
		$plist->add($pitem2);
		
		$this->plist->copyFrom($plist);
		$this->assertEquals($plist->getCount(), $this->plist->getCount());
		$this->assertEquals(0, $this->plist->indexOf($pfirst));
		$this->assertEquals(1, $this->plist->indexOf($pitem1));
		$this->assertEquals(2, $this->plist->indexOf($pitem2));
		$this->assertEquals(3, $this->plist->indexOf($pitem3));
		$this->assertEquals(-10000000, $this->plist->priorityOf($pfirst));
		$this->assertEquals(10, $this->plist->priorityOf($pitem1));
		$this->assertEquals(10, $this->plist->priorityOf($pitem2));
		$this->assertEquals(100, $this->plist->priorityOf($pitem3));
		$this->assertEquals(-1, $this->plist->indexOf($this->pitem1));
		
		// Copy from TPriorityMap
		$map = new TPriorityMap();
		$map->add('key3', $this->pitem3, 100);
		$map->add('key1', $this->pitem1);
		$map->add('key0', $this->pfirst, -10000000);
		$map->add('key2', $this->pitem2);
		$this->plist->copyFrom($map);
		$this->assertEquals($map->getCount(), $this->plist->getCount());
		$this->assertEquals(0, $this->plist->indexOf($this->pfirst));
		$this->assertEquals(1, $this->plist->indexOf($this->pitem1));
		$this->assertEquals(2, $this->plist->indexOf($this->pitem2));
		$this->assertEquals(3, $this->plist->indexOf($this->pitem3));
		$this->assertEquals(-10000000, $this->plist->priorityOf($this->pfirst));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem2));
		$this->assertEquals(100, $this->plist->priorityOf($this->pitem3));
		$this->assertEquals(-1, $this->plist->indexOf($pitem1));
		
		// copy from Traversable
		$this->plist->copyFrom(['a' => $this->pitem1, 10 => $this->pitem2]);
		$this->assertEquals(2, $this->plist->getCount());
		$this->assertEquals(0, $this->plist->indexOf($this->pitem1));
		$this->assertEquals(1, $this->plist->indexOf($this->pitem2));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem2));
		
		self::expectException(TInvalidDataTypeException::class);
		$this->plist->copyFrom($this);
	}

	public function testMergeWithTPriorityList()
	{
		// Merge TPriorityList
		$plist = new TPriorityList();
		$plist->add($this->item3, 5);
		$plist->add($this->item1, 15);
		$this->plist->mergeWith($plist);
		$this->assertEquals(6, $this->plist->getCount());
		$this->assertEquals(0, $this->plist->indexOf($this->pfirst));
		$this->assertEquals(1, $this->plist->indexOf($this->item3));
		$this->assertEquals(2, $this->plist->indexOf($this->pitem1));
		$this->assertEquals(3, $this->plist->indexOf($this->pitem2));
		$this->assertEquals(4, $this->plist->indexOf($this->item1));
		$this->assertEquals(5, $this->plist->indexOf($this->pitem3));
		$this->assertEquals(-10000000, $this->plist->priorityOf($this->pfirst));
		$this->assertEquals(5, $this->plist->priorityOf($this->item3));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem2));
		$this->assertEquals(15, $this->plist->priorityOf($this->item1));
		$this->assertEquals(100, $this->plist->priorityOf($this->pitem3));
		$this->assertEquals(-1, $this->plist->indexOf($this->pitem4));
		
		// Merge Traversable
		$plist->mergeWith([100 => $this->pitem1, 10 => $this->pitem2]);
		$this->assertEquals(4, $plist->getCount());
		$this->assertEquals(0, $plist->indexOf($this->item3));
		$this->assertEquals(1, $plist->indexOf($this->pitem1));
		$this->assertEquals(2, $plist->indexOf($this->pitem2));
		$this->assertEquals(3, $plist->indexOf($this->item1));
		$this->assertEquals(5, $plist->priorityOf($this->item3));
		$this->assertEquals(10, $plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $plist->priorityOf($this->pitem2));
		$this->assertEquals(15, $plist->priorityOf($this->item1));
		
		// Merge TPriorityMap, drops keys and keeps priority
		$item1 = new $this->_baseItemClass(-1);
		$item2 = new $this->_baseItemClass(-2);
		$item3 = new $this->_baseItemClass(-3);
		$map = new TPriorityMap();
		$map->add('key3', $item3, 15);
		$map->add('key1', $item1, 5);
		$map->add('key2', $item2, 10);
		$this->plist->mergeWith($map);
		$this->assertEquals(9, $this->plist->getCount());
		$this->assertEquals(-10000000, $this->plist->priorityOf($this->pfirst));
		$this->assertEquals(5, $this->plist->priorityOf($this->item3));
		$this->assertEquals(5, $this->plist->priorityOf($item1));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $this->plist->priorityOf($this->pitem2));
		$this->assertEquals(10, $this->plist->priorityOf($item2));
		$this->assertEquals(15, $this->plist->priorityOf($this->item1));
		$this->assertEquals(15, $this->plist->priorityOf($item3));
		$this->assertEquals(100, $this->plist->priorityOf($this->pitem3));
		
		$this->assertEquals(0, $this->plist->indexOf($this->pfirst));
		$this->assertEquals(1, $this->plist->indexOf($this->item3));
		$this->assertEquals(2, $this->plist->indexOf($item1));
		$this->assertEquals(3, $this->plist->indexOf($this->pitem1));
		$this->assertEquals(4, $this->plist->indexOf($this->pitem2));
		$this->assertEquals(5, $this->plist->indexOf($item2));
		$this->assertEquals(6, $this->plist->indexOf($this->item1));
		$this->assertEquals(7, $this->plist->indexOf($item3));
		$this->assertEquals(8, $this->plist->indexOf($this->pitem3));
		
		$this->assertEquals(-1, $this->plist->indexOf($this->pitem4));
		
		self::expectException(TInvalidDataTypeException::class);
		$this->plist->mergeWith($this);
	}

	public function testToArrayTPriorityList()
	{
		$array = $this->plist->toArray();
		$this->assertEquals(4, count($array));
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals($this->pitem3, $array[3]);
	}

	public function testToPriorityArrayTPriorityList()
	{
		$array = $this->plist->toPriorityArray();
		$this->assertEquals(3, count($array));
		$this->assertEquals(1, count($array[-10000000]));
		$this->assertEquals($this->pfirst, $array[-10000000][0]);
		$this->assertEquals(2, count($array[10]));
		$this->assertEquals($this->pitem1, $array[10][0]);
		$this->assertEquals($this->pitem2, $array[10][1]);
		$this->assertEquals(1, count($array[100]));
		$this->assertEquals($this->pitem3, $array[100][0]);
	}

	public function testToArrayBelowPriority()
	{
		$array = $this->plist->toArrayBelowPriority(0);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals(1, count($array));

		$array = $this->plist->toArrayBelowPriority(10);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals(1, count($array));

		$array = $this->plist->toArrayBelowPriority(10, true);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals(3, count($array));

		$array = $this->plist->toArrayBelowPriority(11);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals(3, count($array));

		$array = $this->plist->toArrayBelowPriority(100);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals(3, count($array));

		$array = $this->plist->toArrayBelowPriority(100, true);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals($this->pitem3, $array[3]);
		$this->assertEquals(4, count($array));
	}

	public function testToArrayAbovePriority()
	{
		$array = $this->plist->toArrayAbovePriority(100, false);
		$this->assertEquals(0, count($array));

		$array = $this->plist->toArrayAbovePriority(100, true);
		$this->assertEquals(1, count($array));
		$this->assertEquals($this->pitem3, $array[0]);

		$array = $this->plist->toArrayAbovePriority(10, false);
		$this->assertEquals($this->pitem3, $array[0]);
		$this->assertEquals(1, count($array));

		$array = $this->plist->toArrayAbovePriority(10);
		$this->assertEquals($this->pitem1, $array[0]);
		$this->assertEquals($this->pitem2, $array[1]);
		$this->assertEquals($this->pitem3, $array[2]);
		$this->assertEquals(3, count($array));

		$array = $this->plist->toArrayAbovePriority(11);
		$this->assertEquals($this->pitem3, $array[0]);
		$this->assertEquals(1, count($array));

		$array = $this->plist->toArrayAbovePriority(0);
		$this->assertEquals($this->pitem1, $array[0]);
		$this->assertEquals($this->pitem2, $array[1]);
		$this->assertEquals($this->pitem3, $array[2]);
		$this->assertEquals(3, count($array));

		$array = $this->plist->toArrayAbovePriority(-10000000, true);
		$this->assertEquals($this->pfirst, $array[0]);
		$this->assertEquals($this->pitem1, $array[1]);
		$this->assertEquals($this->pitem2, $array[2]);
		$this->assertEquals($this->pitem3, $array[3]);
		$this->assertEquals(4, count($array));
	}

	public function testArrayReadTPriorityList()
	{
		$this->assertTrue($this->plist[0] === $this->pfirst);
		$this->assertTrue($this->plist[1] === $this->pitem1);
		$this->assertTrue($this->plist[2] === $this->pitem2);
		$this->assertTrue($this->plist[3] === $this->pitem3);

		self::expectException(TInvalidDataValueException::class);
		$a = $this->plist[4];
	}

	public function testGetIteratorTPriorityList()
	{
		$n = 0;
		$found = 0;

		foreach ($this->list as $a => $b); // test of iterator

		foreach ($this->plist as $index => $item) {
			$n++;
			if ($index === 0 && $item === $this->pfirst) {
				$found++;
			}
			if ($index === 1 && $item === $this->pitem1) {
				$found++;
			}
			if ($index === 2 && $item === $this->pitem2) {
				$found++;
			}
			if ($index === 3 && $item === $this->pitem3) {
				$found++;
			}
		}
		$this->assertTrue($n == 4, "Not 4 items in the list.");
		$this->assertTrue($found == 4, "$found of 4 items were in the list.");
	}

	public function testArrayMiscTPriorityList()
	{
		$this->assertEquals($this->plist->Count, count($this->plist));
		$this->assertTrue(isset($this->plist[0]));
		$this->assertTrue(isset($this->plist[1]));
		$this->assertTrue(isset($this->plist[2]));
		$this->assertTrue(isset($this->plist[3]));
		$this->assertFalse(isset($this->plist[4]));
	}

	public function testOffsetSetAddTPriorityList()
	{
		$list = new $this->_baseClass();
		$list->add($this->item2);
		$list->add($this->item1, 5);
		$list->add($this->item4, 15);
		$list->offsetSet(null, $this->item3); // Appending like this, items get the default priority; not linear behavior
		self::assertEquals([
			$this->item1, $this->item2, $this->item3, $this->item4
		], $list->toArray());
	}

	public function testOffsetSetReplaceTPriorityList()
	{
		$list = new $this->_baseClass();
		$list->add($this->item2);
		$list->add($this->item1, 5);
		$list->add($this->item3, 15);
		$list->offsetSet(1, $this->item4);
		self::assertEquals([
			$this->item1, $this->item4, $this->item3
		], $list->toArray());
	}

	public function testOffsetSetAppendTPriorityList()
	{
		$list = new $this->_baseClass();
		$list->add($this->item2);
		$list->add($this->item1, 5);
		$list->add($this->item3, 15);
		$list->offsetSet(3, $this->item4);
		self::assertEquals([
			$this->item1, $this->item2, $this->item3, $this->item4
		], $list->toArray());
	}

	public function testOffsetUnsetTPriorityList()
	{
		$list = new $this->_baseClass();
		$list->add($this->item2);
		$list->add($this->item1, 5);
		$list->add($this->item3, 15);
		$list->offsetUnset(1);
		self::assertEquals([
			$this->item1, $this->item3
		], $list->toArray());
	}
}