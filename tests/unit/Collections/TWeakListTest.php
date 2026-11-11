<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\IWeakRetainable;
use Prado\Collections\TWeakList;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventHandler;
use ArrayIterator;
use ArrayObject;
use IteratorAggregate;
use Traversable;
use WeakReference;

class TWeakListUnit extends TWeakList
{
	use TListResetTrait;
	
	public function getWeakCount(): ?int
	{
		return $this->weakCount();
	}
	
	public function getWeakObjectCount($obj): ?int
	{
		return $this->weakObjectCount($obj);
	}
	
	public function resetDiscardInvalid(bool $value): void
	{
		$this->setDiscardInvalid($value);
	}
}

class WeakListInvokableItem // implements IWeakRetainable
{
	public $data;
	public $calledData;
	public function __construct($data = null)
	{
		$this->data = $data;
	}
	public function __invoke($sender, $param, $data = null)
	{
		$this->calledData = $data;
	}
	
	public function myHandler($sender, $param, $data = null)
	{
		$this->calledData = $data;
	}
}

class WeakListListItem extends PriorityListItem
{
	public $calledData;
	
	public function myHandler($sender, $param, $data = null)
	{
		$this->calledData = $data;
	}
}

/** A Traversable item without ArrayAccess: not a container, so it is weakened and tracked. */
class WeakListTraversableItem implements IteratorAggregate
{
	public function getIterator(): Traversable
	{
		return new ArrayIterator([]);
	}
}

/** A Traversable item without ArrayAccess that the list stores strongly and tracks. */
class WeakListRetainedTraversableItem extends WeakListTraversableItem implements IWeakRetainable
{
}

/**
 *	All Test cases for the TList are here.  The TPriorityList should act just like a TList when used exactly like a TList
 *
 * The TPriority List should start behaving differently when using the class outside of the standard TList Function calls
 */
class TWeakListTest extends TListTest
{
	protected $list;
	protected $item1;
	protected $item2;
	protected $item3;
	protected $item4;

	protected function newList()
	{
		return  TWeakListUnit::class;
	}
	protected function newListItem()
	{
		return WeakListListItem::class;
	}
	protected function getCanAddNull()
	{
		return true;
	}

	//*****************************************************************
	//*******  start test cases for TList operations
	//*******		TWeakList should act exactly like a TList if objects are all retained.
	
	// These tests are inherited from TListTest

	//*******  end test cases for TList operations
	//*****************************************************************

	/*
	Methodical Method Unit Tests for TWeakList
	
	Key:	- is TList, 
			* is TWeakList [TWL], (main functionality is from parent)
			& is full custom implementation in TWL 
			~ is scrub the list for invalid weak references, 
			= is IO filtering
			# is a unit test
	
	methods:
	-*	 #	__Construct
	-*& 	getIterator (calls $this->toArray to scrub)
	-* ~ #	getCount [scrub WeakRef]
	-* ~=#	itemAt [scrub WeakRef]
	-* ~=#	add, return index  [scrub WeakRef, check add/incr WeakMpa]
	-* ~=#	insertAt [scrub WeakRef, check add/incr WeakMap]
	-*& =#	remove (calls $this->indexOf to scrub), return index  [scrub WeakRef, check rm/deincr WeakMap]
	-* ~=#	removeAt [scrub WeakRef, check rm/deincr WeakMap]
	-*&  #	clear [scrub WeakRef, check for WeakMap count = 0]
	-*& =	contains, return bool
	-* ~=#	indexOf, return index  [scrub WeakRef]
	-*& =#	insertBefore, return index (calls $this->indexOf to scrub)[scrub WeakRef, check add/incr WeakMap]
	-*& =#	insertAfter, return index (calls $this->indexOf to scrub)[scrub WeakRef, check add/incr WeakMap]
	-*&~=#	toArray, return array [scrub WeakRef]
	-*& =#	copyFrom [parent::insertAt, objects are add/incr WeakMap]
	-*& =#	mergeWith [parent::insertAt, objects are add/incr WeakMap]
	-*& 	offsetExists (calls $this->getCount to scrub)
	-		offsetGet (calls $this->itemAt)
	-*&~=#	offsetSet [scrub WeakRef, object add/incr WeakMap, old object deincr]
	-		offsetUnset (calls $this->removeAt)
	-*	 #	_getZappableSleepProps
	
		 #	Test Closures are not made WeakReference (they are not lost if the only anonymous function).
		 #	Test arrays as items have their objects made WeakReference and back.
		 #	Test changing [set]DiscardInvalid by children, adding objects to WeakMap and scrubbing.
	
	*/
	
	public function testConstructTWeakList()
	{
		$this->list = new $this->_baseClass();
		self::assertTrue($this->list->getDiscardInvalid());
			
		$this->list = new $this->_baseClass(null, false);
		self::assertTrue($this->list->getDiscardInvalid());
		
		$this->list = new $this->_baseClass(null, false, true);
		self::assertTrue($this->list->getDiscardInvalid());
		
		$this->list = new $this->_baseClass(null, false, false);
		self::assertFalse($this->list->getDiscardInvalid());
		
		$this->list = new $this->_baseClass(null, true);
		self::assertFalse($this->list->getDiscardInvalid());
		
		$this->list = new $this->_baseClass(null, true, true);
		self::assertTrue($this->list->getDiscardInvalid());
		
		$this->list = new $this->_baseClass(null, true, false);
		self::assertFalse($this->list->getDiscardInvalid());
		
		$eventHandler1 = new TEventHandler([$this->item3, 'myHandler'], 77);
		$eventHandler2 = new TEventHandler($eventHandler1, 88);
		$list = new $this->_baseClass([$this->item1, $this->item2, $this->item3, $this->item4, $eventHandler1, $eventHandler2], true);
		$this->item2 = null;
		$this->item3 = null;
		self::assertEquals(6, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertNull($list[1]);
		self::assertNull($list[2]);
		self::assertEquals($this->item4, $list[3]);
		self::assertNull($list[4]);
		self::assertNull($list[5]);
		
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$eventHandler1 = new TEventHandler([$this->item3, 'myHandler'], 70);
		$eventHandler2 = new TEventHandler($eventHandler1, 71);
		$list = new $this->_baseClass([$this->item1, $this->item2, $this->item3, $this->item4, $eventHandler1, $eventHandler2], false);
		$this->item2 = null;
		$this->item3 = null;
		self::assertEquals(2, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertEquals($this->item4, $list[1]);
		
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$list = new $this->_baseClass([$this->item1, $this->item2, $this->item3, $this->item4], null);
		$this->item2 = null;
		$this->item3 = null;
		self::assertEquals(2, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertEquals($this->item4, $list[1]);
	}
	
	public function testGetIteratorTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		unset($this->item2);
		unset($this->item3);
		
		$iter = $this->list->getIterator();
		self::assertEquals(2, $this->list->getWeakCount());
		self::assertEquals($this->item1, $iter->current());
		self::assertEquals(0, $iter->key());
		$iter->next();
		self::assertEquals($this->item4, $iter->current());
		self::assertEquals(1, $iter->key());
		$iter->next();
		self::assertFalse($iter->valid());
	}
	
	public function testGetCountTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		unset($this->item2);
		unset($this->item3);
		
		self::assertEquals(2, $this->list->getCount());
		self::assertEquals(2, $this->list->getWeakCount());
		
		// Test the read only and not discard invalid aspect of scrubWeakReferences
		
		// Read only with scrubbing.
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$this->list = new $this->_baseClass([$this->item1, $this->item2, $this->item3, $this->item4], true, true);
		self::assertTrue($this->list->getDiscardInvalid());
		unset($this->item2);
		unset($this->item3);
		self::assertEquals([$this->item1, $this->item4], $this->list->toArray());
			
		// Read only without scrubbing.
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$this->list = new $this->_baseClass([$this->item1, $this->item2, $this->item3, $this->item4], true, false);
		self::assertFalse($this->list->getDiscardInvalid());
		unset($this->item2);
		unset($this->item3);
		self::assertEquals([$this->item1, null, null, $this->item4], $this->list->toArray());
		
		// mutable without scrubbing
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$this->list = new $this->_baseClass(null, false, false);
		self::assertFalse($this->list->getDiscardInvalid());
		$this->list->add($this->item1);
		$this->list->add($this->item2);
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		unset($this->item2);
		unset($this->item3);
		
		self::assertEquals(4, $this->list->getCount());
		self::assertNull($this->list->itemAt(1));
		self::assertNull($this->list->itemAt(2));
	}
	
	public function testItemAtTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		unset($this->item2);
		unset($this->item3);
		
		try {
			self::assertEquals($this->item1, $this->list->itemAt(2));
		} catch(TInvalidDataValueException $e) {
		}
		self::assertEquals($this->item4, $this->list->itemAt(1));
		self::assertEquals($this->item1, $this->list->itemAt(0));
		self::assertEquals(2, $this->list->getWeakCount());
	}
	
	public function testAddTWeakList()
	{
		self::assertEquals(2, $this->list->add($this->item3));
		
		unset($this->item2);
		unset($this->item3);
		
		self::assertEquals(1, $this->list->add($this->item4));
		self::assertEquals(2, $this->list->add($this->item4));
		
		self::assertEquals(2, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(2, $this->list->getWeakObjectCount($this->item4));
	}
	
	public function testAddTWeakList_TEventHandler()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list->add($eventHandler1 = new TEventHandler($handler1, 13));
		$this->list->add($eventHandler2 = new TEventHandler($eventHandler1, 21));
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(2, $this->list->getWeakObjectCount($object1));
		self::assertEquals($eventHandler1, $this->list[0]);
		self::assertEquals($eventHandler2, $this->list[1]);
	}
	
	public function testInsertAtTWeakList()
	{
		$this->list->insertAt(0, $this->item3);
		
		unset($this->item2);
		unset($this->item3);
		
		$this->list->insertAt(0, $this->item4);
		$this->list->insertAt(2, $this->item4);
		
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(2, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(2, $this->list->getWeakCount());
	}
	
	public function testInsertAtTWeakList_TEventHandler()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list->insertAt(0, $eventHandler1 = new TEventHandler($handler1, 13));
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($object1));
		self::assertEquals($eventHandler1, $this->list[0]);
	}
	
	
	public function testRemoveTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		$this->list->add($this->item1);
		$this->list->add($this->item4);
		
		self::assertEquals(2, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(2, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(4, $this->list->getWeakCount());
		
		unset($this->item1);
		unset($this->item2);
		
		self::assertEquals(2, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->remove($this->item4));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $this->list->remove($this->item4));
		self::assertNull($this->list->getWeakObjectCount($this->item4));
		
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakCount());
	}
	
	public function testRemoveTWeakList_TEventHandler()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list[] = $eventHandler1 = new TEventHandler($handler1, 13);
		$this->list[] = $eventHandler2 = new TEventHandler($eventHandler1, 21);
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(2, $this->list->getWeakObjectCount($object1));
		
		self::assertEquals(0, $this->list->remove($eventHandler1));
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($object1));
			
		self::assertEquals(0, $this->list->remove($eventHandler2));
		self::assertEquals(0, $this->list->getWeakCount());
		
		$this->list[] = $eventHandler1;
		self::assertEquals(0, $this->list->remove($handler1));
			
		$this->list[] = $eventHandler2;
		self::assertEquals(0, $this->list->remove($handler1));
	}
	
	
	public function testRemoveAtTWeakList()
	{
		$item5 = new $this->_baseItemClass(5);
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		$this->list->add($this->item1);
		$this->list->add($item5);
		$this->list->add($this->item4);
		self::assertEquals(5, $this->list->getWeakCount());
		
		unset($this->item1);
		unset($this->item2);
		
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals($this->item4, $this->list->removeAt(1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals($this->item4, $this->list->removeAt(2));
		self::assertNull($this->list->getWeakObjectCount($this->item4));
		self::assertEquals(2, $this->list->getWeakCount());
		
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($item5));
		self::assertEquals(2, $this->list->getWeakCount());
	}
	
	public function testRemoveAtTWeakList_TEventHandler()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list[] = $eventHandler1 = new TEventHandler($handler1, 13);
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($object1));
		
		$this->list->removeAt(0);
		self::assertEquals(0, $this->list->getWeakCount());
	}
	
	
	public function testClearTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		$this->list->add($this->item1);
		$this->list->add($this->item4);
		
		unset($this->item1);
		unset($this->item2);
		self::assertEquals(2, $this->list->getWeakCount());
		
		$this->list->clear();
		self::assertEquals(0, $this->list->getWeakCount());
	}
	
	
	public function testIndexOfTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		
		unset($this->item2);
		unset($this->item3);
		
		self::assertEquals(1, $this->list->indexOf($this->item4));
		self::assertEquals(0, $this->list->indexOf($this->item1));
	}
	
	
	public function testIndexOfTWeakList_TEventHandler()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$handler2 = [$object2 = new WeakListInvokableItem(2), 'myHandler'];
		$handler3 = [$object3 = new WeakListInvokableItem(3), 'myHandler'];
		$handler4 = [$object4 = new WeakListInvokableItem(5), 'myHandler'];
		
		$eventHandler5 = new TEventHandler($handler4, 55);
		
		$handler6 = [$object6 = new WeakListInvokableItem(8), 'myHandler'];
		$eventHandler6 = new TEventHandler($handler6, 14);
		$eventHandler7 = new TEventHandler($eventHandler6, 15);
		$eventHandler8 = new TEventHandler($eventHandler7, 16);
		
		$this->list[] = $handler1;
		$this->list[] = $handler2;
		$this->list[] = $eventHandler1 = new TEventHandler($handler1, 13);
		$this->list[] = $eventHandler3 = new TEventHandler($handler3, 21);
		$this->list[] = $eventHandler4 = new TEventHandler($handler4, 34);
		$this->list[] = $eventHandler8;
		$this->list[] = $handler4;
		
		self::assertEquals(0, $this->list->indexOf($handler1));
		self::assertEquals(1, $this->list->indexOf($handler2));
		self::assertEquals(3, $this->list->indexOf($handler3), "Handler, not directly in the list, but in TEventHandler should be found");
		self::assertEquals(6, $this->list->indexOf($handler4), "Raw Handler takes precedence over being found in TEventHandler");
		self::assertEquals(5, $this->list->indexOf($handler6), "Nested TEventHandler didn't match the callable.");
		
		self::assertEquals(2, $this->list->indexOf($eventHandler1));
		self::assertEquals(3, $this->list->indexOf($eventHandler3));
		self::assertEquals(4, $this->list->indexOf($eventHandler4));
			
		self::assertEquals(5, $this->list->indexOf($eventHandler6));
		self::assertEquals(5, $this->list->indexOf($eventHandler7));
		self::assertEquals(5, $this->list->indexOf($eventHandler8));
		
		self::assertEquals(-1, $this->list->indexOf($eventHandler5));
	}
	
	
	public function testInsertBeforeTWeakList()
	{
		unset($this->item1);
		
		self::assertEquals(0, $this->list->insertBefore($this->item2, $this->item3));
	}
	
	
	public function testInsertBeforeTWeakList_TEventHandler()
	{
		self::assertEquals(1, $this->list->insertBefore($this->item2, new TEventHandler($object = new WeakListInvokableItem())));
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $this->list->getWeakObjectCount($object));
	}
	
	
	public function testInsertAfterTWeakList()
	{
		unset($this->item1);
		
		self::assertEquals(1, $this->list->insertAfter($this->item2, $this->item3));
	}
	
	
	public function testInsertAfterTWeakList_TEventHandler()
	{
		self::assertEquals(1, $this->list->insertAfter($this->item1, new TEventHandler($object = new WeakListInvokableItem())));
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $this->list->getWeakObjectCount($object));
	}
	
	
	public function testToArrayTWeakList()
	{
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		
		unset($this->item2);
		unset($this->item3);
		
		self::assertEquals([$this->item1, $this->item4], $this->list->toArray($this->item4));
		self::assertEquals(2, $this->list->getWeakCount());
	}
	
	
	public function testCopyFromTWeakList()
	{
		self::assertEquals(2, $this->list->getWeakCount());
		unset($this->item2);
		self::assertEquals(1, $this->list->getWeakCount());
		$this->list->copyFrom([$this->item3, $this->item4, new TEventHandler($object = new WeakListInvokableItem())]);
		
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertNull($this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $this->list->getWeakObjectCount($object));
	}
	
	
	public function testMergeWithTWeakList()
	{
		$this->list->mergeWith([$this->item3, $this->item4, new TEventHandler($object = new WeakListInvokableItem())]);
		
		self::assertEquals(5, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $this->list->getWeakObjectCount($object));
	}
	
	
	public function testOffsetSetTWeakList()
	{
		unset($this->item2);
		$this->list[1] = $this->item3;
		$this->list[] = $this->item4;
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		
		$item5 = new $this->_baseItemClass(5);
		$this->list[1] = $item5;
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertNull($this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($item5));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
	}
	
	
	
	public function testOffsetSetTWeakList_TEventHandler()
	{
		$item1 = new WeakListInvokableItem(1);
		$item2 = new WeakListInvokableItem(2);
		$this->item2 = new TEventHandler($item1, 2);
		$this->item3 = new TEventHandler($item2, 3);
		self::assertEquals(1, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		
		$this->list[] = $this->item2; // [1]
		self::assertEquals(2, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($item1));
		
		$this->list[1] = $this->item3;
		self::assertEquals(2, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($item2));
	}
	
	public function testClosureTWeakList()
	{
		$this->list->add(function ($param1) {
			return $param1 + $param1;
		});
		unset($this->item2);
		try {
			$closure = $this->list->itemAt(1);
		} catch (\Exception $e) {
			self::fail("Closure was put under WeakReference when it shouldn't have been, resulting in:\n" . $e->getMessage());
		}
		self::assertInstanceOf(\Closure::class, $closure);
	}
	
	public function testArrayAsItemTWeakList()
	{
		$this->list->add([$this->item3, $this->item4]);
		self::assertEquals([$this->item1, $this->item2, [$this->item3, $this->item4]], $this->list->toArray());
		unset($this->item2);
		unset($this->item3);
		self::assertEquals([$this->item1, [null, $this->item4]], $this->list->toArray());
	}
	
	public function testTEventHandlerAsItemTWeakList()
	{
		$this->list->clear();
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list[] = $eventHandler1 = new TEventHandler($handler1, 13);
		$this->list[] = new TEventHandler([$object2 = new WeakListInvokableItem(2), 'myHandler'], 13);
		
		self::assertEquals(2, $this->list->getCount(), "TEventHandler is an IWeakRetainable that is not being retained.");
		
		$object2 = null;
		
		self::assertEquals(1, $this->list->getCount());
	}
	
	public function testDiscardInvalid()
	{
		$list = new $this->_baseClass();
		$list->setDiscardInvalid(true);
		self::assertTrue($list->getDiscardInvalid());
		$list->resetDiscardInvalid(false);
		self::assertFalse($list->getDiscardInvalid());
		
		$this->list->add($this->item3);
		$this->list->add($this->item4);
		
		$handler1 = [$object1 = new WeakListInvokableItem(1), 'myHandler'];
		$this->list[] = $eventHandler1 = new TEventHandler($handler1, 13);
		$handler2 = [$object2 = new WeakListInvokableItem(2), 'myHandler'];
		$this->list[] = $eventHandler2 = new TEventHandler($handler2, 13);
		
		self::assertTrue($this->list->getDiscardInvalid());
		
		self::assertEquals(6, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $this->list->getWeakObjectCount($object1));
		self::assertEquals(1, $this->list->getWeakObjectCount($object2));
		
		$this->list->resetDiscardInvalid(false);
		self::assertNull($this->list->weakCount());
		self::assertFalse($this->list->getDiscardInvalid());
		
		unset($this->item2);
		unset($this->item3);
		$eventHandler2 = $handler2 = $object2 = null;
		
		$this->list->resetDiscardInvalid(true);
		self::assertTrue($this->list->getDiscardInvalid());
		self::assertEquals(3, $this->list->getWeakCount());
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $this->list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $this->list->getWeakObjectCount($object1));
		
		self::expectException(TInvalidOperationException::class);
		$list->setDiscardInvalid(true);
	}

	public function testAddEventHandlerWithoutObject_TWeakList()
	{
		$list = new TWeakList();
		$handler = new TEventHandler('strlen');

		self::assertSame(0, $list->add($handler));
		self::assertSame(1, $list->getCount());
		self::assertSame($handler, $list->itemAt(0));
		self::assertTrue($list->contains('strlen'));
		self::assertSame(0, $list->indexOf('strlen'));

		self::assertSame(0, $list->remove('strlen'));
		self::assertSame(0, $list->getCount());
		self::assertFalse($list->contains('strlen'));

		$list = new TWeakList([$handler]);
		self::assertSame(1, $list->getCount(), 'Turning DiscardInvalid on keeps a TEventHandler without an object');
		self::assertSame([$handler], $list->toArray());
	}

	public function testAddEventHandlerWithCollectedObject_TWeakList()
	{
		$object = new WeakListInvokableItem();
		$handler = new TEventHandler([$object, 'myHandler']);
		$object = null;
		self::assertFalse($handler->hasHandler());

		$list = new TWeakList();
		self::assertSame(0, $list->add($handler));
		self::assertSame(0, $list->getCount(), 'A TEventHandler collected before it is added is scrubbed');
		self::assertSame([], $list->toArray());

		$list = new TWeakList(null, null, false);
		$list->add($handler);
		self::assertSame(1, $list->getCount());
		self::assertSame([null], $list->toArray());
		self::assertSame(0, $list->remove($handler));
		self::assertSame(0, $list->getCount());
	}

	public function testTraversableArrayAccessItemIsWeakenedAsCopy_TWeakList()
	{
		$object = new WeakListInvokableItem();
		$container = new \ArrayObject(['x' => $object]);
		$list = new TWeakList();
		$list->add($container);

		self::assertSame($object, $container['x'], 'The caller\'s container keeps its object');

		$item = $list->itemAt(0);
		self::assertInstanceOf(\ArrayObject::class, $item);
		self::assertNotSame($container, $item);
		self::assertSame($object, $item['x']);
		$list->toArray();
		$item = null;

		$weak = \WeakReference::create($object);
		$object = $container = null;
		gc_collect_cycles();

		self::assertNull($weak->get(), 'The list releases objects in a stored container after reads');
		self::assertSame(1, $list->getCount());
		self::assertNull($list->itemAt(0)['x']);
	}

	public function testFilterItemTraversableArrayAccess_TWeakList()
	{
		$object = new WeakListInvokableItem();
		$container = new \ArrayObject(['x' => $object, 'y' => 1]);
		$item = $container;

		TWeakList::filterItemForInput($item);
		self::assertNotSame($container, $item);
		self::assertSame($object, $container['x']);
		self::assertInstanceOf(\WeakReference::class, $item['x']);
		self::assertSame(1, $item['y']);

		$stored = $item;
		TWeakList::filterItemForOutput($item);
		self::assertNotSame($stored, $item);
		self::assertSame($object, $item['x']);
		self::assertInstanceOf(\WeakReference::class, $stored['x'], 'The stored container keeps its WeakReference');
	}

	public function testExplicitWeakReferenceTracksReferent_TWeakList()
	{
		$list = new TWeakListUnit();
		$object = new WeakListInvokableItem();
		$list->add(\WeakReference::create($object));

		self::assertSame(1, $list->getWeakObjectCount($object));
		self::assertSame($object, $list->itemAt(0));

		$object = null;
		self::assertSame(0, $list->getCount(), 'A collected referent is scrubbed');
		self::assertSame([], $list->toArray());
	}

	public function testExplicitWeakReferenceSharesTrackingWithItsObject_TWeakList()
	{
		$list = new TWeakListUnit();
		$object = new WeakListInvokableItem();
		$list->add($object);
		$list->add(\WeakReference::create($object));
		self::assertSame(2, $list->getWeakObjectCount($object));

		$list->removeAt(0);
		self::assertSame(1, $list->getWeakObjectCount($object));

		$object = null;
		self::assertSame(0, $list->getCount());
		self::assertSame([], $list->toArray());
	}

	public function testExplicitWeakReferenceCollectedBeforeAdd_TWeakList()
	{
		$object = new WeakListInvokableItem();
		$weak = \WeakReference::create($object);
		$object = null;

		$list = new TWeakListUnit();
		$list->add($weak);
		self::assertSame(0, $list->getCount(), 'A referent collected before it is added is scrubbed');
		self::assertSame([], $list->toArray());

		$list = new TWeakListUnit(null, null, false);
		$list->add($weak);
		self::assertSame(1, $list->getCount(), 'DiscardInvalid false keeps the entry');
		self::assertSame([null], $list->toArray());
	}

	public function testExplicitWeakReferenceToEventHandler_TWeakList()
	{
		$list = new TWeakListUnit();
		$target = new WeakListInvokableItem();
		$wrapped = new TEventHandler([$target, 'myHandler']);
		$list->add(\WeakReference::create($wrapped));
		self::assertSame(1, $list->getWeakObjectCount($wrapped), 'The referent is tracked, not its handler object');
		self::assertNull($list->getWeakObjectCount($target));

		$list->removeAt(0);
		self::assertNull($list->getWeakObjectCount($wrapped));

		$item = new WeakListInvokableItem();
		$list->add(new TEventHandler([$item, 'myHandler']));
		self::assertSame(0, $list->indexOf([$item, 'myHandler']), 'The removal keeps the event handler search on');
		$list->clear();

		$list->add(\WeakReference::create($wrapped));
		$wrapped = null;
		self::assertSame(0, $list->getCount(), 'The entry ends with the referenced handler while its target lives');
	}

	public function testExplicitWeakReferenceToContainer_TWeakList()
	{
		$list = new TWeakListUnit();
		$container = new \ArrayObject();
		$list->add(\WeakReference::create($container));
		self::assertSame(1, $list->getWeakObjectCount($container));

		$list->removeAt(0);
		self::assertNull($list->getWeakObjectCount($container));

		$list->add(\WeakReference::create($container));
		$container = null;
		self::assertSame(0, $list->getCount());
	}

	public function testClearResetsBookkeeping_TWeakList()
	{
		$list = new TWeakListUnit();
		$item = new WeakListInvokableItem();
		$list->add(new TEventHandler('strlen'));
		$list->add($item);
		$list->clear();

		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertSame(0, PradoUnit::getProp($list, '_weakCount'));
		self::assertNull($list->getWeakObjectCount($item));
	}

	public function testClearReadOnly_TWeakList()
	{
		$item = new WeakListInvokableItem();
		$list = new TWeakListUnit([$item], true, true);
		$item = null;
		$list->clear();
		self::assertSame(0, $list->getCount(), 'A read-only list holding only collected objects clears without error');
		self::assertSame([], $list->toArray());

		$item = new WeakListInvokableItem();
		$list = new TWeakListUnit([$item], true, true);
		try {
			$list->clear();
			self::fail('A read-only list with a live item must not clear');
		} catch (TInvalidOperationException $e) {
			self::assertSame('list_readonly', $e->getErrorCode());
		}
		self::assertSame([$item], $list->toArray());
	}

	public function testFailedInsertLeavesNoBookkeeping_TWeakList()
	{
		$item = new WeakListInvokableItem();

		$list = new TWeakListUnit(null, true, true);
		$inserts = [
			fn () => $list->add($item),
			fn () => $list->insertAt(0, $item),
			fn () => $list->mergeWith([$item]),
			fn () => $list->copyFrom([$item]),
			fn () => $list->offsetSet(null, $item),
		];
		foreach ($inserts as $insert) {
			try {
				$insert();
				self::fail('Inserting into a read-only list must throw');
			} catch (TInvalidOperationException $e) {
				self::assertSame('list_readonly', $e->getErrorCode());
			}
			self::assertNull($list->getWeakObjectCount($item));
		}

		$list = new TWeakListUnit(null, true, false);
		$this->expectFailure(TInvalidOperationException::class, fn () => $list->add(new TEventHandler('strlen')));
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));

		$list = new TWeakListUnit();
		$this->expectFailure(TInvalidDataValueException::class, fn () => $list->insertAt(9, $item));
		self::assertSame(0, $list->getCount());
		self::assertNull($list->getWeakObjectCount($item));
		self::assertSame(0, PradoUnit::getProp($list, '_weakCount'));

		$this->expectFailure(TInvalidDataValueException::class, fn () => $list->insertAt(9, new TEventHandler('strlen')));
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
	}

	private function expectFailure(string $class, callable $call): void
	{
		try {
			$call();
		} catch (\Throwable $e) {
			self::assertInstanceOf($class, $e);
			return;
		}
		self::fail("Expected $class");
	}

	public function testCloneTWeakListKeepsIndependentWeakMap()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1);
		$list->add($this->item2);
		$clone = clone $list;
		$clone->add($this->item3);

		self::assertSame(2, $list->getWeakCount());
		self::assertSame(3, $clone->getWeakCount());
		self::assertNull($list->getWeakObjectCount($this->item3));

		$this->item2 = null;

		self::assertSame([$this->item1], $list->toArray());
		self::assertSame([$this->item1, $this->item3], $clone->toArray());
		self::assertSame(1, $list->getWeakCount());
		self::assertSame(2, $clone->getWeakCount());
	}

	public function testSerializeTWeakListWakesEmptyAndRestartsTracking()
	{
		$list = new $this->_baseClass([$this->item1, 'scalar']);
		self::assertTrue($list->getDiscardInvalid());

		$serialized = serialize($list);

		self::assertStringContainsString('_discardInvalid', $serialized);
		self::assertStringNotContainsString('_weakMap', $serialized);
		self::assertSame([$this->item1, 'scalar'], $list->toArray(), 'Sleeping must not change the source list.');

		$restored = unserialize($serialized);

		self::assertSame(0, $restored->getCount());
		self::assertSame([], $restored->toArray());
		self::assertTrue($restored->getDiscardInvalid());
		self::assertSame(0, $restored->getWeakCount());

		$restored->add($this->item3);
		self::assertSame(1, $restored->getWeakCount());
		$this->item3 = null;
		self::assertSame(0, $restored->getCount());
	}

	public function testSerializeTWeakListKeepsDiscardInvalidFalse()
	{
		$list = new $this->_baseClass([$this->item1], false, false);

		$restored = unserialize(serialize($list));

		self::assertFalse($restored->getDiscardInvalid());
		self::assertNull($restored->getWeakCount());
		$restored->add($this->item3);
		$this->item3 = null;
		self::assertSame(1, $restored->getCount());
		self::assertNull($restored->itemAt(0));
	}

	public function testSerializeTWeakListLeavesUnresolvedDiscardInvalidLazy()
	{
		$list = new $this->_baseClass(null, true);

		$serialized = serialize($list);

		self::assertStringNotContainsString('_discardInvalid', $serialized);
		$restored = unserialize($serialized);
		self::assertTrue($restored->getReadOnly());
		self::assertFalse($restored->getDiscardInvalid());
	}

	public function testFilterItemForInputWeakensTraversableContainer()
	{
		$closure = fn () => 1;
		$container = new ArrayObject(['obj' => $this->item3, 'num' => 5, 'fn' => $closure, 'nested' => [$this->item4]]);

		TWeakList::filterItemForInput($container);

		self::assertInstanceOf(ArrayObject::class, $container);
		self::assertInstanceOf(WeakReference::class, $container['obj']);
		self::assertSame($this->item3, $container['obj']->get());
		self::assertSame(5, $container['num']);
		self::assertSame($closure, $container['fn']);
		self::assertInstanceOf(WeakReference::class, $container['nested'][0]);
		self::assertSame($this->item4, $container['nested'][0]->get());
	}

	public function testRemoveAtCollectedEntryWithoutDiscardInvalidTWeakList()
	{
		$list = new $this->_baseClass(null, false, false);
		$list->add($this->item3);
		$this->item3 = null;

		self::assertNull($list->removeAt(0));
		self::assertSame(0, $list->getCount());
	}

	public function testRemoveAtStoredContainerResolvesACopyTWeakList()
	{
		$list = new $this->_baseClass();
		$list->add(new ArrayObject(['x' => $this->item3]));

		$removed = $list->removeAt(0);

		self::assertInstanceOf(ArrayObject::class, $removed);
		self::assertSame($this->item3, $removed['x']);
		self::assertSame(0, $list->getCount());
		self::assertNull($list->getWeakObjectCount($removed));
	}

	public function testDiscardInvalidTransitionKeepsStoredContainerTWeakList()
	{
		$list = new $this->_baseClass(null, false, false);
		$list->add(new ArrayObject(['x' => $this->item3]));

		$list->resetDiscardInvalid(true);

		self::assertTrue($list->getDiscardInvalid());
		self::assertSame(1, $list->getCount());
		self::assertSame($this->item3, $list->itemAt(0)['x']);
	}

	public function testFilterItemForOutputResolvesTraversableContainer()
	{
		$dying = new $this->_baseItemClass('dying');
		$container = new ArrayObject([
			'live' => WeakReference::create($this->item3),
			'dead' => WeakReference::create($dying),
			'num' => 5,
			'nested' => [WeakReference::create($this->item4)],
		]);
		$dying = null;

		TWeakList::filterItemForOutput($container);

		self::assertSame($this->item3, $container['live']);
		self::assertNull($container['dead']);
		self::assertSame(5, $container['num']);
		self::assertSame([$this->item4], $container['nested']);
	}

	public function testMergeWithTraversableAndScalarItemsTWeakList()
	{
		$list = new $this->_baseClass();

		$list->mergeWith(new ArrayIterator([$this->item3]));
		$list->mergeWith(new ArrayIterator(['scalar']));
		$list->mergeWith(['text']);
		$list->mergeWith([]);
		$list->mergeWith(new ArrayIterator([]));
		$list->mergeWith(null);

		self::assertSame([$this->item3, 'scalar', 'text'], $list->toArray());
		self::assertSame(1, $list->getWeakCount());
		$this->item3 = null;
		self::assertSame(['scalar', 'text'], $list->toArray());

		try {
			$list->mergeWith('not iterable');
			self::fail('mergeWith did not throw TInvalidDataTypeException for a non-iterable.');
		} catch (TInvalidDataTypeException $e) {
			self::assertSame('list_data_not_iterable', $e->getErrorCode());
		}
	}

	public function testScalarInsertAndRemoveSkipWeakTrackingTWeakList()
	{
		$list = new $this->_baseClass([$this->item1]);

		$list->insertAt(0, 'first');
		self::assertSame(1, $list->insertBefore($this->item1, 'before'));
		self::assertSame(1, $list->insertAfter('first', 'after'));
		self::assertSame(['first', 'after', 'before', $this->item1], $list->toArray());
		self::assertSame(1, $list->getWeakCount());

		self::assertSame('after', $list->removeAt(1));
		self::assertSame(['first', 'before', $this->item1], $list->toArray());
		self::assertSame(1, $list->getWeakObjectCount($this->item1));
	}

	public function testOffsetExistsRejectsNegativeOffsetTWeakList()
	{
		self::assertFalse(isset($this->list[-1]));
		self::assertTrue(isset($this->list[0]));
	}

	public function testClearEmptyTWeakList()
	{
		$list = new $this->_baseClass();

		$list->clear();

		self::assertSame(0, $list->getCount());
		self::assertSame([], $list->toArray());
	}

	public function testOffsetSetScalarAndObjectReplacementsTWeakList()
	{
		$list = new $this->_baseClass([$this->item1, 'b']);

		$list[2] = 'c';
		self::assertSame([$this->item1, 'b', 'c'], $list->toArray());

		$list[0] = 'a';
		self::assertNull($list->getWeakObjectCount($this->item1));
		self::assertSame(0, $list->getWeakCount());

		$list[1] = $this->item3;
		$list[2] = 'z';

		self::assertSame(['a', $this->item3, 'z'], $list->toArray());
		self::assertSame(1, $list->getWeakCount());
		self::assertSame(1, $list->getWeakObjectCount($this->item3));
	}

	public function testRemoveScalarSkipsWeakTrackingTWeakList()
	{
		$list = new $this->_baseClass(['a', $this->item1]);

		self::assertSame(0, $list->remove('a'));

		self::assertSame([$this->item1], $list->toArray());
		self::assertSame(1, $list->getWeakObjectCount($this->item1));
	}

	public function testTraversableItemWithoutArrayAccessIsWeakenedAndTrackedTWeakList()
	{
		$list = new $this->_baseClass();
		$item = new WeakListTraversableItem();
		$weak = WeakReference::create($item);

		$list->add($item);

		self::assertSame(1, $list->getWeakObjectCount($item));
		self::assertSame($item, $list->itemAt(0));

		$item = null;

		self::assertNull($weak->get());
		self::assertSame(0, $list->getCount());
	}

	public function testRemoveTraversableItemClearsWeakTrackingTWeakList()
	{
		$list = new $this->_baseClass();
		$item = new WeakListTraversableItem();
		$list->add($item);

		self::assertSame(0, $list->remove($item));

		self::assertNull($list->getWeakObjectCount($item));
		self::assertSame(0, $list->getWeakCount());
		self::assertSame(0, $list->getCount());
	}

	public function testRemoveRetainedTraversableItemClearsWeakTrackingTWeakList()
	{
		$list = new $this->_baseClass();
		$item = new WeakListRetainedTraversableItem();
		$weak = WeakReference::create($item);
		$list->add($item);
		self::assertSame(1, $list->getWeakObjectCount($item));

		$item = null;
		self::assertInstanceOf(WeakListRetainedTraversableItem::class, $weak->get());

		self::assertSame($weak->get(), $list->removeAt(0));

		self::assertSame(0, $list->getWeakCount());
		self::assertNull($weak->get());
	}

	public function testRemoveClosureClearsWeakTrackingTWeakList()
	{
		$list = new $this->_baseClass();
		$closure = static function () {
		};
		$list->add($closure);
		self::assertSame(1, $list->getWeakObjectCount($closure));

		self::assertSame(0, $list->remove($closure));

		self::assertNull($list->getWeakObjectCount($closure));
		self::assertSame(0, $list->getWeakCount());
	}

	public function testRemoveAtExplicitReferenceToContainerReturnsContainerTWeakList()
	{
		$list = new $this->_baseClass();
		$container = new ArrayObject(['x' => 1]);
		$list->add(WeakReference::create($container));

		self::assertSame($container, $list->removeAt(0));
		self::assertSame(0, $list->getCount());
		self::assertSame(['x' => 1], $container->getArrayCopy());
	}

	public function testWeakCustomRemoveOfReferenceToContainerIsNotTrackedTWeakList()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1);
		$container = new ArrayObject();

		self::assertSame(0, PradoUnit::invoke($list, 'weakCustomRemove', WeakReference::create($container)));
		self::assertSame(1, $list->getWeakCount());
	}

	public function testRemoveAtExplicitReferenceToEventHandlerReturnsHandlerTWeakList()
	{
		$list = new $this->_baseClass();
		$withoutObject = new TEventHandler('strlen');
		$target = new WeakListInvokableItem();
		$withObject = new TEventHandler([$target, 'myHandler']);
		$list->add(WeakReference::create($withoutObject));
		$list->add(WeakReference::create($withObject));

		self::assertSame($withObject, $list->removeAt(1));
		self::assertSame($withoutObject, $list->removeAt(0));
		self::assertSame(0, $list->getCount());
	}

	/**
	 * Without DiscardInvalid there is no WeakMap; clear() empties the list, zeroes
	 * the event handler count, and creates no WeakMap.
	 */
	public function testClearWithoutDiscardInvalidCreatesNoWeakMapTWeakList()
	{
		$handlerObject = new WeakListInvokableItem();
		$list = new $this->_baseClass([new TEventHandler($handlerObject), $this->item1], false, false);
		self::assertFalse($list->getDiscardInvalid());
		self::assertNull($list->getWeakCount());
		self::assertSame(1, PradoUnit::getProp($list, '_eventHandlerCount'));

		$list->clear();

		self::assertSame(0, $list->getCount());
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertNull($list->getWeakCount());
	}
}
