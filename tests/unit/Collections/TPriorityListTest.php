<?php

use Prado\Collections\TPriorityList;
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

/**
 *	All Test cases for the TList are here.  The TPriorityList should act just like a TList when used exactly like a TList
 *
 * The TPriority List should start behaving differently when using the class outside of the standard TList Function calls
 */
class TPriorityListTest extends PHPUnit\Framework\TestCase
{
	protected $list;
	protected $item1;
	protected $item2;
	protected $item3;
	protected $item4;

	protected $plist;
	protected $pfirst;
	protected $pitem1;
	protected $pitem2;
	protected $pitem3;
	protected $pitem4;
	protected $pitem5;

	protected function newList()
	{
		return  'TPriorityList';
	}
	protected function newListItem()
	{
		return 'PriorityListItem';
	}
	protected function getCanAddNull()
	{
		return true;
	}
	private $_baseClass;
	private $_baseItemClass;

	protected function setUp(): void
	{
		$this->_baseClass = $this->newList();
		$this->_baseItemClass = $this->newListItem();
		
		$this->list = new $this->_baseClass();
		$this->item1 = new $this->_baseItemClass(1);
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$this->item4 = new $this->_baseItemClass(4);
		$this->list->add($this->item1);
		$this->list->add($this->item2);

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
		$this->list = null;
		$this->item1 = null;
		$this->item2 = null;
		$this->item3 = null;
		$this->item4 = null;

		// ****  start the setup for non-TList things
		$this->list = null;
		$this->item1 = null;
		$this->item2 = null;
		$this->item3 = null;
		$this->item4 = null;
		$this->item5 = null;
	}

	//*****************************************************************
	//*******  start test cases for TList operations
	//*******		TPriorityList should act exactly like a TList if no special functions are used

	public function testConstructTList()
	{
		$a = [
			$this->item1, $this->item2, $this->item3
		];
		$list = new $this->_baseClass($a);
		$this->assertEquals(3, $list->getCount());
		$list2 = new $this->_baseClass($this->list);
		$this->assertEquals(2, $list2->getCount());
	}

	public function testGetReadOnlyTList()
	{
		$list = new $this->_baseClass(null, true);
		self::assertEquals(true, $list->getReadOnly(), 'List is not read-only');
		$list = new $this->_baseClass(null, false);
		self::assertEquals(false, $list->getReadOnly(), 'List is read-only');
	}

	public function testGetCountTList()
	{
		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(2, $this->list->Count);
	}

	public function testItemAt()
	{
		$this->assertTrue($this->list->itemAt(0) === $this->item1);
		$this->assertTrue($this->list->itemAt(1) === $this->item2);
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->itemAt(2);
	}

	public function testAddTList()
	{
		$this->assertEquals(2, $this->list->add($this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(2, $this->list->indexOf($this->item3));
		
		if(!$this->getCanAddNull())
			self::expectException('Prado\\Exceptions\TInvalidDataValueException');
			
		$this->assertEquals(3, $this->list->add(null));
	}

	public function testCanNotAddWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->add(1);
	}

	public function testInsertAtTList()
	{
		$this->assertNull($this->list->insertAt(0, $this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(2, $this->list->indexOf($this->item2));
		$this->assertEquals(0, $this->list->indexOf($this->item3));
		$this->assertEquals(1, $this->list->indexOf($this->item1));
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->insertAt(4, $this->item3);
	}

	public function testCanNotInsertAtWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([], true);
		try {
			$list->insertAt(1, 2);
			$this->fail('TInvalidOperationException not raised when inserting into read only beyond bounds');
		} catch(Prado\Exceptions\TInvalidOperationException $e) {
		}

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAt(0, 2);
	}

	public function testInsertBeforeTList()
	{
		try {
			$this->list->insertBefore($this->item4, $this->item3);
			$this->fail('TInvalidOperationException item4 not in list');
		} catch(Prado\Exceptions\TInvalidDataValueException $e) {}
		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(0, $this->list->insertBefore($this->item1, $this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(0, $this->list->indexOf($this->item3));
		$this->assertEquals(1, $this->list->indexOf($this->item1));
		$this->assertEquals(2, $this->list->indexOf($this->item2));
	}

	public function testCanNotInsertBeforeWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([
			$this->item1
		], true);

		try {
			$list->insertBefore($this->item1, $this->item2);
			$this->fail('TInvalidOperationException not raised when insertBefore item when read only');
		} catch(Prado\Exceptions\TInvalidOperationException $e) {
		}
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertBefore(8, 6);
	}

	public function testInsertAfterTList()
	{

		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(2, $this->list->insertAfter($this->item2, $this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(0, $this->list->indexOf($this->item1));
		$this->assertEquals(1, $this->list->indexOf($this->item2));
		$this->assertEquals(2, $this->list->indexOf($this->item3));
		
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->insertAfter($this->item4, $this->item3);
	}

	public function testCanNotInsertAfterWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([
			$this->item1
		], true);

		try {
			$list->insertAfter($this->item1, 6);
			$this->fail('TInvalidOperationException cannot insertAfter on a read only list');
		} catch(Prado\Exceptions\TInvalidOperationException $e) {
		}

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAfter($this->item2, 6);
	}

	public function testRemoveTList()
	{
		$this->assertEquals(0, $this->list->remove($this->item1));
		$this->assertEquals(1, $this->list->getCount());
		$this->assertEquals(-1, $this->list->indexOf($this->item1));
		$this->assertEquals(0, $this->list->indexOf($this->item2));

		self::expectException('Exception');
		$this->list->remove($this->item1);
	}

	public function testCanNotRemoveWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		], true);

		try {
			$list->remove($this->item2);
			$this->fail('TInvalidOperationException cannot insert on a read only list');
		} catch(Prado\Exceptions\TInvalidOperationException $e) {
		}

		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->remove(10);
	}

	public function testRemoveAtTList()
	{
		$this->list->add($this->item3);
		$this->assertEquals($this->item2, $this->list->removeAt(1));
		$this->assertEquals(-1, $this->list->indexOf($this->item2));
		$this->assertEquals(1, $this->list->indexOf($this->item3));
		$this->assertEquals(0, $this->list->indexOf($this->item1));

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->removeAt(2);
	}

	public function testCanNotRemoveAtWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		], true);

		try {
			$list->removeAt(2);
			$this->fail('TInvalidDataValueException not raised when removing at index when read only');
		} catch(TInvalidOperationException $e) {}

		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->removeAt(10);
	}

	public function testClearTList()
	{
		$this->list->clear();
		$this->assertEquals(0, $this->list->getCount());
		$this->assertEquals(-1, $this->list->indexOf($this->item1));
		$this->assertEquals(-1, $this->list->indexOf($this->item2));
	}

	public function testCanNotClearWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->clear();
	}

	public function testContainTLists()
	{
		$this->assertTrue($this->list->contains($this->item1));
		$this->assertTrue($this->list->contains($this->item2));
		$this->assertFalse($this->list->contains($this->item3));
	}

	public function testIndexOfTList()
	{
		$this->assertEquals(0, $this->list->indexOf($this->item1));
		$this->assertEquals(1, $this->list->indexOf($this->item2));
		$this->assertEquals(-1, $this->list->indexOf($this->item3));
	}

	public function testCopyFromTList()
	{
		$array = [
			$this->item3, $this->item1
		];
		$this->list->copyFrom($array);
		$this->assertTrue(count($array) == 2 && $this->list[0] === $this->item3 && $this->list[1] === $this->item1);

		self::expectException('Prado\\Exceptions\\TInvalidDataTypeException');
		$this->list->copyFrom($this);
	}

	public function testMergeWithTList()
	{
		$array = [
			$this->item3, $this->item1
		];
		$this->list->mergeWith($array);
		$this->assertTrue($this->list->getCount() == 4 && $this->list[0] === $this->item1 && $this->list[3] === $this->item1);

		self::expectException('Prado\\Exceptions\\TInvalidDataTypeException');
		$this->list->mergeWith($this);
	}

	public function testToArrayTList()
	{
		$array = $this->list->toArray();
		$this->assertTrue(count($array) == 2 && $array[0] === $this->item1 && $array[1] === $this->item2);
	}

	public function testArrayReadTList()
	{
		$this->assertTrue($this->list[0] === $this->item1);
		$this->assertTrue($this->list[1] === $this->item2);

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$a = $this->list[2];
	}

	public function testGetIteratorTList()
	{
		$n = 0;
		$found = 0;
		foreach ($this->list as $index => $item) {
			foreach ($this->list as $a => $b); // test of iterator
			$n++;
			if ($index === 0 && $item === $this->item1) {
				$found++;
			}
			if ($index === 1 && $item === $this->item2) {
				$found++;
			}
		}
		$this->assertTrue($n == 2 && $found == 2);
	}

	public function testArrayMiscTList()
	{
		$this->assertEquals($this->list->Count, count($this->list));
		$this->assertTrue(isset($this->list[1]));
		$this->assertFalse(isset($this->list[2]));
	}

	public function testOffsetSetAddTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		]);
		$list->offsetSet(null, $this->item4);
		self::assertEquals([
			$this->item1, $this->item2, $this->item3, $this->item4
		], $list->toArray());
	}

	public function testOffsetSetReplaceTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		]);
		$list->offsetSet(1, $this->item4);
		self::assertEquals([
			$this->item1, $this->item4, $this->item3
		], $list->toArray());
	}

	public function testOffsetUnsetTList()
	{
		$list = new $this->_baseClass([
			$this->item1, $this->item2, $this->item3
		]);
		$list->offsetUnset(1);
		self::assertEquals([
			$this->item1, $this->item3
		], $list->toArray());
	}

	//*******  end test cases for TList operations
	//*****************************************************************


	//*******  end test cases for TList operations
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

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
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

		$this->assertEquals($this->pitem1, $plist->removeAt(1));
		$this->assertEquals(-1, $plist->indexOf($this->pitem1));
		$this->assertEquals(1, $plist->indexOf($this->pitem2));
		$this->assertEquals(0, $plist->indexOf($this->pfirst));

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->removeAt(3);
	}

	public function testItemAtIndexPriorityTPriorityList()
	{
		$this->assertEquals($this->pitem2, $this->plist->itemAtIndexInPriority(1));
		$this->assertEquals($this->pitem1, $this->plist->itemAtIndexInPriority(0, $this->plist->getDefaultPriority()));
		$this->assertEquals($this->pfirst, $this->plist->itemAtIndexInPriority(0, -10000000));
		$this->assertEquals($this->pitem3, $this->plist->itemAtIndexInPriority(0, 100));
	}


	public function testInsertAtIndexInPriorityTPriorityList()
	{
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
		$plist->insertAtIndexInPriority($this->pitem4, 5, null);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem5, false, 10);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item1, 7, 10);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item2, false, 100);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());

		$plist = new $this->_baseClass();

		$plist->insertAtIndexInPriority($this->pfirst, false, null, true);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem1, false, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem2, 2, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem3, false, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem4, 5, null, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->pitem5, false, 10, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item1, 7, 10, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());

		$plist->insertAtIndexInPriority($this->item2, false, 100, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100, true);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());

		$plist = new $this->_baseClass();

		$plist->insertAtIndexInPriority($this->pfirst, false, null, false);
		$this->assertEquals([
			$this->pfirst
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem1, false, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem2, 2, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem3, false, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->pitem4, 5, null, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->pitem5, false, 10, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item1, 7, 10, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1
		], $plist->toArray());
		
		$plist->insertAtIndexInPriority($this->item2, false, 100, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2
		], $plist->toArray());
		$plist->insertAtIndexInPriority($this->item3, 1, 100, false);
		$this->assertEquals([
			$this->pfirst, $this->pitem1, $this->pitem2, $this->pitem3, $this->pitem4, $this->pitem5, $this->item1, $this->item2, $this->item3
		], $plist->toArray());
	}

	public function testCanNotInsertAtIndexInPriorityWhenReadOnlyTList()
	{
		$list = new $this->_baseClass([], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
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
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$plist->removeAtIndexInPriority(0);
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
		$plist = new $this->_baseClass();
		$plist->copyFrom($this->plist);
		$this->assertEquals(0, $plist->indexOf($this->pfirst));
		$this->assertEquals(1, $plist->indexOf($this->pitem1));
		$this->assertEquals(2, $plist->indexOf($this->pitem2));
		$this->assertEquals(3, $plist->indexOf($this->pitem3));
		$this->assertEquals(-10000000, $plist->priorityOf($this->pfirst));
		$this->assertEquals(10, $plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $plist->priorityOf($this->pitem2));
		$this->assertEquals(100, $plist->priorityOf($this->pitem3));
		$this->assertEquals(-1, $plist->indexOf($this->pitem4));
	}

	public function testMergeWithTPriorityList()
	{
		$plist = new $this->_baseClass([
			$this->item3, $this->item1
		]);
		$plist->mergeWith($this->plist);
		$this->assertEquals(6, $plist->getCount());
		$this->assertEquals(0, $plist->indexOf($this->pfirst));
		$this->assertEquals(1, $plist->indexOf($this->item3));
		$this->assertEquals(2, $plist->indexOf($this->item1));
		$this->assertEquals(3, $plist->indexOf($this->pitem1));
		$this->assertEquals(4, $plist->indexOf($this->pitem2));
		$this->assertEquals(5, $plist->indexOf($this->pitem3));
		$this->assertEquals(-10000000, $plist->priorityOf($this->pfirst));
		$this->assertEquals(10, $plist->priorityOf($this->item3));
		$this->assertEquals(10, $plist->priorityOf($this->item1));
		$this->assertEquals(10, $plist->priorityOf($this->pitem1));
		$this->assertEquals(10, $plist->priorityOf($this->pitem2));
		$this->assertEquals(100, $plist->priorityOf($this->pitem3));
		$this->assertEquals(-1, $plist->indexOf($this->pitem4));
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

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
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
		$this->assertTrue($n == 4 && $found == 4);
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
