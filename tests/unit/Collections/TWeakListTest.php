<?php

use Prado\Collections\IWeakRetainable;
use Prado\Collections\TWeakList;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

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
		} catch (Exception $e) {
			self::fail("Closure was put under WeakReference when it shouldn't have been, resulting in:\n" . $e->getMessage());
		}
		self::assertInstanceOf(Closure::class, $closure);
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
}