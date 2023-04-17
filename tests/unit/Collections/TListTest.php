<?php

use Prado\Collections\TList;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

class ListItem
{
	public $data = 'data';
	public function __construct($d)
	{
		$this->data = $d;
	}
}

class TListTest extends PHPUnit\Framework\TestCase
{
	protected $list;
	protected $item1;
	protected $item2;
	protected $item3;
	protected $item4;
	
	protected function newList()
	{
		return  'TList';
	}
	protected function newListItem()
	{
		return 'ListItem';
	}
	protected function getCanAddNull()
	{
		return true;
	}
	protected $_baseClass;
	protected $_baseItemClass;

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
	}

	protected function tearDown(): void
	{
		$this->list = null;
		$this->item1 = null;
		$this->item2 = null;
		$this->item3 = null;
		$this->item4 = null;
	}
	
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
		$list = new $this->_baseClass(null, "true");
		self::assertEquals(true, $list->getReadOnly(), 'List is not read-only');
		$list = new $this->_baseClass(null, "false");
		self::assertEquals(false, $list->getReadOnly(), 'List is read-only');
	}

	public function testGetCountTList()
	{
		$this->assertEquals(2, $this->list->getCount());
		$this->assertEquals(2, $this->list->Count);
	}

	public function testItemAtTList()
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
}
