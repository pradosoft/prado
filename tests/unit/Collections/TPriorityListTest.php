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

	public function setUp()
	{
		$this->list = new TPriorityList;
		$this->item1 = new PriorityListItem(1);
		$this->item2 = new PriorityListItem(2);
		$this->item3 = new PriorityListItem(3);
		$this->item4 = new PriorityListItem(4);
		$this->list->add($this->item1);
		$this->list->add($this->item2);

		// ****  start the setup for non-TList things
		$this->plist = new TPriorityList;
		$this->pfirst = new PriorityListItem(5);
		$this->pitem1 = new PriorityListItem(6);
		$this->pitem2 = new PriorityListItem(7);
		$this->pitem3 = new PriorityListItem(8);
		$this->pitem4 = new PriorityListItem(9);
		$this->pitem5 = new PriorityListItem(0);
		$this->plist->add($this->pitem1);
		$this->plist->add($this->pitem3, 100);
		$this->plist->add($this->pitem2);
		$this->plist->add($this->pfirst, -10000000);
		// 4 and 5 are not inserted
		// ending setup: pfirst @ -10000000[0], pitem1 @ 10[0], pitem2 @ 10[1], pitem3 @ 100[0]
	}

	public function tearDown()
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
			1, 2, 3
		];
		$list = new TPriorityList($a);
		$this->assertEquals(3, $list->getCount());
		$list2 = new TPriorityList($this->list);
		$this->assertEquals(2, $list2->getCount());
	}

	public function testGetReadOnlyTList()
	{
		$list = new TPriorityList(null, true);
		self::assertEquals(true, $list->getReadOnly(), 'List is not read-only');
		$list = new TPriorityList(null, false);
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
	}

	public function testAddTList()
	{
		$this->assertEquals(2, $this->list->add(null));
		$this->assertEquals(3, $this->list->add($this->item3));
		$this->assertEquals(4, $this->list->getCount());
		$this->assertEquals(3, $this->list->indexOf($this->item3));
	}

	public function testCanNotAddWhenReadOnlyTList()
	{
		$list = new TPriorityList([], true);
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
		$list = new TPriorityList([], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAt(1, 2);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAt(0, 2);
	}

	public function testInsertBeforeTList()
	{
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->insertBefore($this->item4, $this->item3);

		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(0, $this->list->insertBefore($this->item1, $this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(0, $this->list->indexOf($this->item3));
		$this->assertEquals(1, $this->list->indexOf($this->item1));
		$this->assertEquals(2, $this->list->indexOf($this->item2));
	}

	public function testCanNotInsertBeforeWhenReadOnlyTList()
	{
		$list = new TPriorityList([
			5
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertBefore(5, 6);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertBefore(8, 6);
	}

	public function testInsertAfterTList()
	{
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$this->list->insertAfter($this->item4, $this->item3);

		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(2, $this->list->insertAfter($this->item2, $this->item3));
		$this->assertEquals(3, $this->list->getCount());
		$this->assertEquals(0, $this->list->indexOf($this->item1));
		$this->assertEquals(1, $this->list->indexOf($this->item2));
		$this->assertEquals(2, $this->list->indexOf($this->item3));
	}

	public function testCanNotInsertAfterWhenReadOnlyTList()
	{
		$list = new TPriorityList([
			5
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAfter(5, 6);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAfter(8, 6);
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
		$list = new TPriorityList([
			1, 2, 3
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->remove(2);

		$list = new TPriorityList([
			1, 2, 3
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
		$list = new TPriorityList([
			1, 2, 3
		], true);

		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->removeAt(2);

		$list = new TPriorityList([
			1, 2, 3
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
		$list = new TPriorityList([
			1, 2, 3
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
		$list = new TPriorityList([
			1, 2, 3
		]);
		$list->offsetSet(null, 4);
		self::assertEquals([
			1, 2, 3, 4
		], $list->toArray());
	}

	public function testOffsetSetReplaceTList()
	{
		$list = new TPriorityList([
			1, 2, 3
		]);
		$list->offsetSet(1, 4);
		self::assertEquals([
			1, 4, 3
		], $list->toArray());
	}

	public function testOffsetUnsetTList()
	{
		$list = new TPriorityList([
			1, 2, 3
		]);
		$list->offsetUnset(1);
		self::assertEquals([
			1, 3
		], $list->toArray());
	}

	//*******  end test cases for TList operations
	//*****************************************************************


	//*******  end test cases for TList operations
	//*****************************************************************


	public function testConstructTPriorityList()
	{
		$a = [
			'a' => 1, '0.5' => 2, 9 => 8
		];

		$list = new TPriorityList($a);
		$this->assertEquals(3, $list->getCount());

		$list2 = new TPriorityList($this->plist);
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
		$plist = new TPriorityList($this->plist);

		$plist->add($this->pitem3, 200);
		$this->assertEquals(200, $plist->priorityAt(4));

		// try a negative precision and a different default priority
		$list = new TPriorityList(null, false, 256, -1);

		$this->assertEquals(260, $list->getDefaultPriority());
		$this->assertEquals(-1, $list->getPrecision());
		$list->add(-10);
		$list->add(-11, 255);
		$list->add(-12, 250);
		$list->add(-13, 201);
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
		$list = new TPriorityList(null, false, 0, 4);

		$this->assertEquals(0, $list->getDefaultPriority());
		$this->assertEquals(4, $list->getPrecision());
		$list->add(-10);
		$list->add(-11, 0.0001);
		$list->add(-12, 0.00001);
		$list->add(-13, 0.001);
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
		$plist = new TPriorityList($this->plist);
		$this->assertNull($plist->insertAt(0, $this->pitem3));
		$this->assertEquals(-10000000, $plist->priorityAt(0));

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->insertAt(5, $this->pitem3);

		$this->assertEquals(100, $plist->priorityAt(4));
	}

	public function testInsertBeforeTPriorityList()
	{
		$plist = new TPriorityList($this->plist);

		$this->assertEquals(4, $plist->getCount());
		$plist->insertBefore($this->pitem3, $this->pitem4);
		$this->assertEquals(100, $plist->priorityOf($this->pitem4));
	}

	public function testInsertAfterTPriorityList()
	{
		$plist = new TPriorityList($this->plist);

		$this->assertEquals(4, $plist->getCount());
		$plist->insertAfter($this->pfirst, $this->pitem4);
		$this->assertEquals(-10000000, $plist->priorityOf($this->pitem4));
	}

	public function testRemoveTPriorityList()
	{
		$plist = new TPriorityList($this->plist);

		$this->assertEquals(2, $plist->remove($this->pitem2));
		$this->assertEquals(1, $plist->getPriorityCount());

		$plist = new TPriorityList($this->plist);

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->remove($this->pitem5);

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->remove($this->pitem3, null);

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->remove($this->pitem1, 100);

		$plist->insertBefore($this->pitem3, $this->pitem4);
		$this->assertEquals(4, $plist->remove($this->pitem3, 100));
	}

	public function testRemoveAtTPriorityList()
	{
		$plist = new TPriorityList($this->plist);

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
		$plist = new TPriorityList();

		$plist->insertAtIndexInPriority(3);
		$this->assertEquals([
			3
		], $plist->toArray());

		$plist->insertAtIndexInPriority(4, false);
		$this->assertEquals([
			3, 4
		], $plist->toArray());
		$plist->insertAtIndexInPriority(5, 2);
		$this->assertEquals([
			3, 4, 5
		], $plist->toArray());

		$plist->insertAtIndexInPriority(6, false, null);
		$this->assertEquals([
			3, 4, 5, 6
		], $plist->toArray());
		$plist->insertAtIndexInPriority(7, 5, null);
		$this->assertEquals([
			3, 4, 5, 6, 7
		], $plist->toArray());

		$plist->insertAtIndexInPriority(8, false, 10);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8
		], $plist->toArray());
		$plist->insertAtIndexInPriority(9, 7, 10);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9
		], $plist->toArray());

		$plist->insertAtIndexInPriority(10, false, 100);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10
		], $plist->toArray());
		$plist->insertAtIndexInPriority(11, 1, 100);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10, 11
		], $plist->toArray());

		$plist = new TPriorityList();

		$plist->insertAtIndexInPriority(3, false, null, true);
		$this->assertEquals([
			3
		], $plist->toArray());

		$plist->insertAtIndexInPriority(4, false, null, true);
		$this->assertEquals([
			3, 4
		], $plist->toArray());
		$plist->insertAtIndexInPriority(5, 2, null, true);
		$this->assertEquals([
			3, 4, 5
		], $plist->toArray());

		$plist->insertAtIndexInPriority(6, false, null, true);
		$this->assertEquals([
			3, 4, 5, 6
		], $plist->toArray());
		$plist->insertAtIndexInPriority(7, 5, null, true);
		$this->assertEquals([
			3, 4, 5, 6, 7
		], $plist->toArray());

		$plist->insertAtIndexInPriority(8, false, 10, true);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8
		], $plist->toArray());
		$plist->insertAtIndexInPriority(9, 7, 10, true);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9
		], $plist->toArray());

		$plist->insertAtIndexInPriority(10, false, 100, true);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10
		], $plist->toArray());
		$plist->insertAtIndexInPriority(11, 1, 100, true);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10, 11
		], $plist->toArray());

		$plist = new TPriorityList();

		$plist->insertAtIndexInPriority(3, false, null, false);
		$this->assertEquals([
			3
		], $plist->toArray());

		$plist->insertAtIndexInPriority(4, false, null, false);
		$this->assertEquals([
			3, 4
		], $plist->toArray());
		$plist->insertAtIndexInPriority(5, 2, null, false);
		$this->assertEquals([
			3, 4, 5
		], $plist->toArray());

		$plist->insertAtIndexInPriority(6, false, null, false);
		$this->assertEquals([
			3, 4, 5, 6
		], $plist->toArray());
		$plist->insertAtIndexInPriority(7, 5, null, false);
		$this->assertEquals([
			3, 4, 5, 6, 7
		], $plist->toArray());

		$plist->insertAtIndexInPriority(8, false, 10, false);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8
		], $plist->toArray());
		$plist->insertAtIndexInPriority(9, 7, 10, false);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9
		], $plist->toArray());

		$plist->insertAtIndexInPriority(10, false, 100, false);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10
		], $plist->toArray());
		$plist->insertAtIndexInPriority(11, 1, 100, false);
		$this->assertEquals([
			3, 4, 5, 6, 7, 8, 9, 10, 11
		], $plist->toArray());
	}

	public function testCanNotInsertAtIndexInPriorityWhenReadOnlyTList()
	{
		$list = new TPriorityList([], true);
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$list->insertAtIndexInPriority(1);
	}


	public function testRemoveAtIndexInPriorityTPriorityList()
	{
		$plist = new TPriorityList($this->plist);
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->removeAtIndexInPriority(1, 100);

		$this->assertEquals($this->pitem2, $plist->removeAtIndexInPriority(1));
		$this->assertEquals($this->pitem3, $plist->removeAtIndexInPriority(0, 100));
		$this->assertEquals($this->pitem1, $plist->removeAtIndexInPriority(0, 10));

		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
		$plist->removeAtIndexInPriority(0, 200);

		$this->assertEquals($this->pfirst, $plist->removeAtIndexInPriority(0, -10000000));
		$this->assertEquals(0, $plist->getCount());
	}

	public function testCanNotRemoveAtIndexInPriorityWhenReadOnlyTList()
	{
		$plist = new TPriorityList($this->plist, true);
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
		$plist = new TPriorityList($this->plist);
		$plist->clear();
		$this->assertEquals(0, $plist->getCount());
		$this->assertEquals(-1, $plist->indexOf($this->pitem1));
		$this->assertEquals(-1, $plist->indexOf($this->pitem3));
	}

	public function testContainTPriorityLists()
	{
		$plist = new TPriorityList($this->plist);
		$this->assertTrue($plist->contains($this->pfirst));
		$this->assertTrue($plist->contains($this->pitem1));
		$this->assertTrue($plist->contains($this->pitem2));
		$this->assertTrue($plist->contains($this->pitem3));
		$this->assertFalse($plist->contains($this->pitem5));
	}

	public function testIndexOfTPriorityList()
	{
		$plist = new TPriorityList($this->plist);
		$this->assertEquals(0, $plist->indexOf($this->pfirst));
		$this->assertEquals(1, $plist->indexOf($this->pitem1));
		$this->assertEquals(2, $plist->indexOf($this->pitem2));
		$this->assertEquals(3, $plist->indexOf($this->pitem3));
		$this->assertEquals(-1, $plist->indexOf($this->pitem4));
	}

	public function testCopyFromTPriorityList()
	{
		$plist = new TPriorityList();
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
		$plist = new TPriorityList([
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
		$list = new TPriorityList();
		$list->add(2);
		$list->add(1, 5);
		$list->add(3, 15);
		$list->offsetSet(null, 4); // Appending like this, items get the default priority; not linear behavior
		self::assertEquals([
			1, 2, 4, 3
		], $list->toArray());
	}

	public function testOffsetSetReplaceTPriorityList()
	{
		$list = new TPriorityList();
		$list->add(2);
		$list->add(1, 5);
		$list->add(3, 15);
		$list->offsetSet(1, 4);
		self::assertEquals([
			1, 4, 3
		], $list->toArray());
	}

	public function testOffsetSetAppendTPriorityList()
	{
		$list = new TPriorityList();
		$list->add(2);
		$list->add(1, 5);
		$list->add(3, 15);
		$list->offsetSet(3, 4);
		self::assertEquals([
			1, 2, 3, 4
		], $list->toArray());
	}

	public function testOffsetUnsetTPriorityList()
	{
		$list = new TPriorityList();
		$list->add(2);
		$list->add(1, 5);
		$list->add(3, 15);
		$list->offsetUnset(1);
		self::assertEquals([
			1, 3
		], $list->toArray());
	}
}
