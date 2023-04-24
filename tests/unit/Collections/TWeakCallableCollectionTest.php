<?php

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TPhpErrorException;
use Prado\TComponent;

class TWeakCallableCollectionUnit extends TWeakCallableCollection
{
	// NOTE: "TWCC" is the short name for "TWeakCallableCollection"
	
	public $_scrubCount = 0;
	public $_scrubError = false;
	public int $_containWith_fd = 0;
	public int $_containWithout_fd = 0;
	
	protected function scrubWeakReferences()
	{
		$this->_scrubCount++;
		if(!is_int($this->_scrubError) && $this->_scrubError === true) {
			throw new TInvalidOperationException("Not allowed to scrubWeakReferences()");
		} elseif (is_int($this->_scrubError) && $this->_scrubCount != 1) {
			throw new TInvalidOperationException("Only allowed to scrub once");
		}
		parent::scrubWeakReferences();
	}
	
	public function setScrubError($value)
	{
		if($value === false && $this->_scrubError === 1 && $this->_scrubCount === 0) {
			throw new TInvalidOperationException("Did not scrub where it should have");
		}
		$this->_scrubError = $value;
		$this->_scrubCount = 0;
	}
	
	public function contains($item): bool
	{
		if ($this->_fd !== null)
			$this->_containWith_fd++;
		else
			$this->_containWithout_fd++;
		return parent::contains($item);
	}	
	
	// Accessor functions to protected methods.
	public function getWeakChanged(): bool
	{
		return $this->weakChanged();
	}
	public function getWeakCount(): ?int
	{
		return $this->weakCount();
	}
	
	public function getWeakObjectCount($obj): ?int
	{
		return $this->weakObjectCount($obj);
	}
	
	public function _setDiscardInvalid(bool $value): void
	{
		$this->setDiscardInvalid($value);
	}
}

class CallableListItem
{
	public $data = 'data';
	public function __construct($d = null)
	{
		if ($d !== null)
			$this->data = $d;
	}
	public function eventHandler($sender, $param)
	{
	}
	public function __invoke($sender, $param)
	{
	}
	public static function staticHandler($sender, $param)
	{
	}
}

class CallableListItemChild extends CallableListItem
{
	public static function staticHandler($sender, $param)
	{
	}
}

/**
 *	All Test cases for the TList are here.  The TWeakCallableCollection should act just like a TList when used exactly like a TList
 *
 * The TPriority List should start behaving differently when using the class outside of the standard TList Function calls
 */
class TWeakCallableCollectionTest extends TPriorityListTest
{

	protected function newList()
	{
		return  TWeakCallableCollectionUnit::class;
	}
	protected function newListItem()
	{
		return CallableListItem::class;
	}
	protected function getCanAddNull()
	{
		return false;
	}
	//*****************************************************************
	//******* start test cases for TList operations
	//*******	TWeakCallableCollection should act exactly like a TList if objects are 
	//			all retained.
	// These tests are inherited from TListTest
	//******* end test cases for TList operations
	//*****************************************************************
	
	
	//*****************************************************************
	//******* start test cases for TPriorityList operations
	//*******	TWeakCallableCollection should act exactly like a TPriorityList 
	//			if objects are all retained.
	// These tests are inherited from TPriorityListTest
	//******* end test cases for TPriorityList operations
	//*****************************************************************
	
	/*
		Key:	- is TList, 
				+ is TPriorityList, 
				* is TWeakCallableCollection [TWCC],
				& is custom implementation in TWCC 
				~ is scrubbing the list for out of date weak references, 
				= is IO filtering
				# is unit test
		Methods:
		-+*	  #	Construct
		 +* ~	protected: flattenPriorities (called by TPriorityList::toArray and getIterator)
		  *	~	protected: scrubWeakReferences
		 +* ~ #	getPriorities
		 +* ~ #	getPriorityCount
		-+*& =#	getIterator (calls $this->flattenPriorities for ~)
		- * ~ #	getCount
		-+*&~=#	itemAt
		 +* ~=#	itemsAtPriority
		 +*	~=#	itemAtIndexInPriority
		-+	  #	add (calls insertAtIndexInPriority)
		-+*&~ #	insertAt (calls parent::PriorityAt and internalInsertAtIndexInPriority for =)
		 +* ~ #	insertAtIndexInPriority (calls internalInsertAtIndexInPriority for =)
		-+*&~=#	remove (calls priorityOf)
		-+*#~ #	removeAt (calls parent::priorityAt then internalRemoveAtIndexInPriority for =)
		 +* ~=#	removeAtIndexInPriority
		-+*&  #	clear
		- *&~=#	contains (calls indexOf)
		-+*&~=#	indexOf
		 +* ~=#	priorityOf
		 +* ~ #	priorityAt (calls getCount for ~)
		-+*&~=#	insertBefore (calls priorityOf for ~ then internalInsertAtIndexInPriority for =)
		-+*&~=#	insertAfter (calls priorityOf for ~ then internalInsertAtIndexInPriority for =)
		-+*  =#	toArray (calls $this->flattenPriorities for ~)
		 +* ~=#	toPriorityArray
		  *&~ #	toPriorityArrayWeak
		 +* ~=#	toArrayBelowPriority
		 +* ~=#	toArrayAbovePriority
		-+  ~ #	offsetExists (calls getCount)
		-+*&  #	copyFrom
		-+*&  # mergeWith
		-   ~=#	offsetGet (calls itemAt)
		-+*&~=#	offsetSet (calls getCount, parent::priorityAt, internalRemoveAtIndexInPriority [if in list], and internalInsertAtIndexInPriority)
		-   ~=#	offsetUnset (calls removeAt)
		-*	  	protected: _getZappableSleepProps
		
		# Test callable types and Closure.
		# test DiscardInvalid
		
	*/
	
	public function testVariousCallable_TWCC()
	{
		$list = new $this->_baseClass();
		$component = new TComponent;
		
		//Test for that only callables can be inserted into the collection
		$item1 = $list[] = 'foo';
		$item2 = $list[] = [CallableListItem::class, 'staticHandler'];
		$item3 = $list[] = [$this->item1, 'eventHandler'];
		$item4 = $list[] = CallableListItem::class . '::staticHandler';
		$item5 = $list[] = [CallableListItemChild::class,'staticHandler'];
		$item6 = $list[] = $this->item2;
		$item7 = $list[] = function($n) { return $n + $n; };
		self::assertNotEquals(6, count($list), "Closure is wrongly being WeakReferenced.");
		self::assertEquals(7, count($list));
		self::assertEquals(3, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $list->getWeakObjectCount($item7));
		
		// Check callables that have proper syntax but error because they aren't referencing
		//   Valid callables/objects/methods.
		try {
			$list[] = 'notAFunctionCallable';
			$this->fail('TInvalidDataValueException string that is not a function did not throw error');
		} catch(TInvalidDataValueException $e){}
		try {
			$list[] = ['CallableListItem', 'noStaticMethod'];
			$this->fail('TInvalidDataValueException [valid static object, \'noStaticMethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		try {
			$list[] = [$this->item1, 'noMethod'];
			$this->fail('TInvalidDataValueException [valid  object, \'noMethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		try {
			$list[] = 'CallableListItem::noStaticMethod';
			$this->fail('TInvalidDataValueException string of \'object::nostaticmethod\' that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		try {
			$list[] = ['CallableListItemChild','parent::noMethod'];
			$this->fail('TInvalidDataValueException string of [valid static object, \'parent::nostaticmethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e) {// Catch PHP 8.1
		} catch(TPhpErrorException $e) {} // Catch PHP 8.2+
		try {
			$list[] = $component;
			$this->fail('TInvalidDataValueException object without  __invocke did not throw error');
		} catch(TInvalidDataValueException $e){}
		
		//There should still only be 6 items in the list
		self::assertEquals(7, count($list));
		
		$p = $list->toPriorityArrayWeak();
		
		// The two objects in the list should be converted into WeakReference
		$priority = $list->getDefaultPriority();
		$this->assertInstanceOf(WeakReference::class, $p[$priority][5]);
		
		//The WeakReference should refer to the proper objects
		$this->assertEquals('foo', $p[$priority][0]);
		$this->assertEquals(['CallableListItem', 'staticHandler'], $p[$priority][1]);
		$this->assertInstanceOf(WeakReference::class, $p[$priority][2][0]);
		$this->assertEquals($this->item1, $p[$priority][2][0]->get());
		$this->assertEquals('eventHandler', $p[$priority][2][1]);
		$this->assertEquals('CallableListItem::staticHandler', $p[$priority][3]);
		//$this->assertEquals($p[$priority][4], ['CallableListItemChild','parent::staticHandler']);
		$this->assertEquals($this->item2, $p[$priority][5]->get());
		$this->assertEquals($item7, $p[$priority][6]);
	}
	
	public function testConstructTWCC()
	{
		$this->list = new $this->_baseClass([$this->item1, $this->item2]);
		self::assertEquals(0, $this->list->_scrubCount, "Scrubbed when not needed.");
	}
	
	public function testScrubInvalidWeakReference_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add($closure = function ($n) { return $n + $n; });
		self::assertEquals(5, $list->getWeakCount());
		$this->item2 = null;
		$this->item3 = null;
		self::assertEquals(3, $list->getWeakCount());
		self::assertEquals(3, $list->getCount());
		self::assertEquals([$this->item1, $closure, [$this->item4, 'eventHandler']], $list->toArray());
		self::assertEquals(5, $list->priorityOf($this->item1));
		self::assertEquals(10, $list->priorityOf($closure));
		self::assertEquals(20, $list->priorityOf([$this->item4, 'eventHandler']));
		self::assertEquals(false, $list->priorityOf($this->item2));
		self::assertEquals(false, $list->priorityOf([$this->item3, 'eventHandler']));
		
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		
		$list = new $this->_baseClass([$this->item1, $this->pitem2, $this->pitem3, $this->item4], true);
		$this->pitem2 = null;
		$this->pitem3 = null;
		self::assertEquals(4, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertTrue($list[1] === null);
		self::assertTrue($list[2] === null);
		self::assertEquals($this->item4, $list[3]);
	}
	
	public function testDiscardInvalid_TWCC()
	{
		$list = new $this->_baseClass();
		self::assertEquals(0, $list->getWeakCount());
		$list->_setDiscardInvalid(false);
		self::assertFalse($list->getDiscardInvalid());
		self::assertTrue($list->getWeakCount() === null);
		$list->add($this->item2, 10);
		$list->add($this->item1, 5);
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($closure = function ($n) {return $n + $n;});
		
		$this->item2 = null;
		$this->item3 = null;
		
		self::assertEquals(5, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertTrue($list[1] === null);
		self::assertEquals($closure, $list[2]);
		self::assertTrue($list[3] === null);
		self::assertEquals([$this->item4, 'eventHandler'], $list[4]);
		
		$list->setScrubError(true);
		$list->_setDiscardInvalid(true);
		$list->setScrubError(false);
		
		self::assertTrue($list->getDiscardInvalid());
		self::assertEquals(3, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($closure));
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item4));
		
		self::assertEquals(3, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertEquals($closure, $list[1]);
		self::assertEquals([$this->item4, 'eventHandler'], $list[2]);
	}
	
	public function testGetPriorities_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$this->item2 = null;
		self::assertTrue($list->getWeakChanged(), "TWeakCallableCollection invokable objects not being tracked properly");
		
		$list->setScrubError(1);
		self::assertEquals([5, 15, 20], $list->getPriorities(), "Did not scrub of invalid WeakReference");
		$list->setScrubError(false);
		
		unset($this->item3);
		self::assertTrue($list->getWeakChanged(), "TWeakCallableCollection array callable not being tracked properly");
		self::assertEquals([5, 20], $list->getPriorities());
	}
	
	public function testGetPriorityCount_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		unset($this->item2);
		unset($this->item3);
		
		$list->setScrubError(1);
		self::assertEquals(1, $list->getPriorityCount(5));
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->getPriorityCount(10));
		self::assertEquals(0, $list->getPriorityCount(15));
	}
	
	public function testGetIteratorTPriorityList_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		unset($this->item2);
		unset($this->item3);
		
		$list->setScrubError(1);
		$iter = $list->getIterator();
		$list->setScrubError(false);
			
		self::assertEquals(0, $iter->key());
		self::assertEquals($this->item1, $iter->current(), "Iterator is returning WeakReference rather than the objects.");
		$iter->next();
		self::assertTrue($iter->valid());
		self::assertEquals(1, $iter->key());
		self::assertEquals([$this->item4, 'eventHandler'], $iter->current());
		$iter->next();
		self::assertFalse($iter->valid());
	}
	
	public function testGetCountTPriorityList_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add(function($n) {return $n + $n; }, 15);
		
		unset($this->item2);
		unset($this->item3);
		
		$list->setScrubError(1);
		self::assertEquals(3, $list->getCount(), "Did not scrub of invalid WeakReference");
		$list->setScrubError(false);
	}
	
	public function testItemAt_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		unset($this->item2);
		
		$list->setScrubError(1);
		try {
			$list->itemAt(3);
			self::fail("TInvalidDataValueException not thrown as assumed");
		} catch(TInvalidDataValueException $e) {
		}
		$list->setScrubError(false)
		;
		unset($this->item3);
		self::assertEquals($this->item1, $list->itemAt(0));
		self::assertEquals([$this->item4, 'eventHandler'], $list->itemAt(1));
		self::assertEquals(2, $list->getCount());
	}
	
	public function testItemsAtPriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		unset($this->item2);
		unset($this->item3);
		
		$list->setScrubError(1);
		self::assertEquals([[$this->item4, 'eventHandler']], $list->itemsAtPriority(10));
		$list->setScrubError(false);
		
		self::assertEquals([$this->item1], $list->itemsAtPriority(5));
		self::assertEquals(2, count($list->getPriorities()));
	}
	
	public function testItemAtIndexInPriority_TWCC()
	{
		$list = new $this->_baseClass();
		
		{	// Test Add here.
			$list->setScrubError(1);
			$list->add([$this->item4, 'eventHandler'], 10);
			$list->setScrubError(false);
			self::assertEquals(1, $list->getWeakCount());
			self::assertEquals(1, $list->getWeakObjectCount($this->item4));
		}
		self::assertEquals(1, $list->getWeakCount(), "Object not added for WeakMap tracking");
		self::assertEquals(1, $list->getWeakObjectCount($this->item4), "Object not added for WeakMap tracking");
		
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		self::assertEquals($this->item2, $list->itemAtIndexInPriority(1));
		self::assertEquals([$this->item3, 'eventHandler'], $list->itemAtIndexInPriority(0, 15));
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		try {
			self::assertFalse($list->itemAtIndexInPriority(1));
			self::fail("failed to throw TInvalidDataValueException on Out-Of-Range index at priority");
		} catch (TInvalidDataValueException $e) {
		}
		$list->setScrubError(false);
		
		try {
			self::assertFalse($list->itemAtIndexInPriority(0, 15));
			self::fail("failed to throw TInvalidDataValueException on Out-Of-Range index at priority");
		} catch (TInvalidDataValueException $e) {
		}
		self::assertEquals($this->item1, $list->itemAtIndexInPriority(0, 5));
		self::assertEquals([$this->item4, 'eventHandler'], $list->itemAtIndexInPriority(0, 10));
		self::assertEquals(2, count($list->getPriorities()));
	}
	// add unit test encoded into the prior test
	
	public function testInsertAt_TWCC()
	{
		$list = new $this->_baseClass();
		$list->setScrubError(1);
		$list->insertAt(0, $this->item1);
		$list->setScrubError(false);
		
		$list->insertAt(1, $this->item2);
		$list->insertAt(2, $this->item3);
		$list->insertAt(3, $this->item4);
		
		$this->item2 = null;
		$this->item3 = null;
		
		try {
			$list->insertAt(4, $this->pitem1);
			self::fail("Failed to throw TInvalidDataValueException when inserting at an Out-Of-Range index from WeakReference scrubbing.");
		} catch(TInvalidDataValueException $e) {
		}
		$list->insertAt(2, $this->pitem1);
		
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem1));
		
		$this->assertEquals([$this->item1, $this->item4, $this->pitem1], $list->toArray());
	}
	
	public function testInsertAtIndexInPriority_TWCC() 
	{
		$list = new $this->_baseClass();
		$list->setScrubError(1);
		$list->insertAtIndexInPriority($this->pitem1, 0, 5, true);
		$list->setScrubError(false);
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem1));
		
		$list->insertAtIndexInPriority([$this->item2, 'eventHandler'], 1, 5);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		$list->insertAtIndexInPriority([$this->pitem1, 'eventHandler'], 0, 5, true);
		self::assertEquals(2, $list->getWeakObjectCount($this->pitem1));
		$list->insertAtIndexInPriority($this->item3, 0, 10);
		
		
		$this->item2 = null;
		$this->item3 = null;
		
		try {//  scrubs
			$list->insertAtIndexInPriority($this->item4, 1, 10);
			self::fail("Failed to throw TInvalidDataValueException when inserting at an Out-Of-Range index from WeakReference scrubbing.");
		} catch(TInvalidDataValueException $e) {
		}
		
		self::expectException(TInvalidDataValueException::class);
		$list->insertAtIndexInPriority($this->pitem1, 4, 5, true);
	}
	
	public function testRemove_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		unset($this->item2);
		unset($this->item3);
		
		$list->setScrubError(1);
		self::assertEquals(1, $list->remove([$this->item4, 'eventHandler']));
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
	}
	
	public function testRemoveAt_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		unset($this->item2);
		unset($this->item3);
		
		try {
			self::assertEquals(1, $list->removeAt(3));
				self::fail("Failed to throw TInvalidDataValueException when removing at an Out-Of-Range index from WeakReference scrubbing.");
		} catch (TInvalidDataValueException $e) {
		}
		try {
			self::assertEquals(1, $list->removeAt(2));
				self::fail("Failed to throw TInvalidDataValueException when removing at an Out-Of-Range index from WeakReference scrubbing.");
		} catch (TInvalidDataValueException $e) {
		}
		$list->setScrubError(1);
		self::assertEquals([$this->item4, 'eventHandler'], $list->removeAt(1));
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
	}
	
	public function testRemoveAtIndexInPriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add([$this->item1, 'eventHandler'], 5);
		$this->item2 = null;
		$this->item3 = null;
		
		try {
			$list->setScrubError(1);
			self::assertEquals([$this->item3, 'eventHandler'], $list->removeAtIndexInPriority(0, 15));
			self::fail("Failed to throw TInvalidDataValueException when removing at an Out-Of-Range index from WeakReference scrubbing.");
		} catch (TInvalidDataValueException $e) {
		}
		$list->setScrubError(false);
		
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(2, $list->getWeakObjectCount($this->item1));
		self::assertEquals([$this->item1, 'eventHandler'], $list->removeAtIndexInPriority(1, 5));
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals($this->item1, $list->removeAtIndexInPriority(0, 5));
		self::assertNull($list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakCount());
	}
	
	public function testClear_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(true);
		$list->clear();
		$list->setScrubError(false);
		self::assertEquals(0, $list->getWeakCount());
	}
	
	public function testContains_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(true);
		self::assertTrue($list->contains($this->item1));
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->_containWithout_fd);
		self::assertEquals(0, $list->_containWith_fd);
		self::assertTrue($list->contains([$this->item4, 'eventHandler']));
			
		$list->toArray();
		
		$list->_containWithout_fd = 0;
		self::assertTrue($list->contains($this->item1));
		self::assertEquals(0, $list->_containWithout_fd);
		self::assertEquals(1, $list->_containWith_fd);
		self::assertTrue($list->contains([$this->item4, 'eventHandler']));
	}
	
	public function testIndexOf_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		self::assertEquals(1, $list->indexOf([$this->item4, 'eventHandler']));
		$list->setScrubError(false);
		
		self::assertEquals(0, $list->indexOf($this->item1));
	}
	
	public function testPriorityOf_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		
		$list->setScrubError(1);
		self::assertEquals([20, 0, 2, 'priority' => 20, 'index' => 0, 'absindex' => 2], $list->priorityOf([$this->item4, 'eventHandler'], true));
		$list->setScrubError(false);
		
		$this->item3 = null;
		
		$list->setScrubError(true);
		self::assertEquals(20, $list->priorityOf([$this->item4, 'eventHandler']));
		$list->setScrubError(false);
		
		self::assertEquals(5, $list->priorityOf($this->item1));
	}
	
	
	public function testPriorityAt_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		self::assertFalse($list->priorityAt(3));
		$list->setScrubError(false);
		
		$list->setScrubError(1);
		self::assertEquals(10, $list->priorityAt(2));
		$list->setScrubError(false);
	}
	
	
	public function testInsertBefore_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		self::assertEquals(1, $list->insertBefore([$this->item4, 'eventHandler'], $closure = function($n) {return $n + $n;}));
		$list->setScrubError(false);
		
		self::assertEquals(10, $list->priorityOf($closure));
	}
	
	
	public function testInsertAfter_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		self::assertEquals(2, $list->insertAfter([$this->item4, 'eventHandler'], $closure = function($n) {return $n + $n;}));
		$list->setScrubError(false);
		
		self::assertEquals(10, $list->priorityOf($closure));
	}
	
	public function testToArray_TWCC()
	{
		
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$array = $list->toArray();
		$list->setScrubError(false);
		
		self::assertEquals($this->item1, $array[0]);
		self::assertEquals($this->item4, $array[1][0]);
		self::assertEquals(2, count($array));
	}
	
	public function testToPriorityArray_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$array = $list->toPriorityArray();
		$list->setScrubError(false);
		
		self::assertEquals($this->item1, $array[5][0]);
		self::assertEquals($this->item4, $array[10][0][0]);
		self::assertEquals(2, count($array));
		self::assertEquals(1, count($array[5]));
		self::assertEquals(1, count($array[10]));
	}
	
	public function testToPriorityArrayWeak_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$array = $list->toPriorityArrayWeak();
		$list->setScrubError(false);
		
		self::assertInstanceOf(WeakReference::class, $array[5][0]);
		self::assertInstanceOf(WeakReference::class, $array[10][0][0]);
		self::assertEquals(2, count($array));
		self::assertEquals(1, count($array[5]));
		self::assertEquals(1, count($array[10]));
	}
	
	public function testToArrayBelowPriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 5);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add($closure = function($n) {return $n + $n;}, 5);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$array = $list->toArrayBelowPriority(10);
		$list->setScrubError(false);
		
		self::assertEquals($this->item1, $array[0]);
		self::assertEquals($closure, $array[1]);
		self::assertEquals(2, count($array));
	}
	
	public function testToArrayAbovePriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 5);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add($closure = function($n) {return $n + $n;}, 15);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$array = $list->toArrayAbovePriority(10);
		$list->setScrubError(false);
		
		self::assertEquals([$this->item4, 'eventHandler'], $array[0]);
		self::assertEquals($closure, $array[1]);
		self::assertEquals(2, count($array));
	}
	
	public function testCopyFrom_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->pitem1);
		$list->setScrubError(true);
		$list->copyFrom([$this->item1, $this->item2]);
		$list->setScrubError(false);
		$this->assertEquals($this->item1, $list[0]);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		
		$plist = new TPriorityList();
		$plist->add($this->item3, 10);
		$plist->add($this->item4, 5);
		$list->setScrubError(true);
		$list->copyFrom($plist);
		$list->setScrubError(false);
		$this->assertEquals($this->item4, $list[0]);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $list->getWeakObjectCount($this->item4));
		
		$pmap = new TPriorityMap();
		$pmap->add('key1', $this->pitem1, 10);
		$pmap->add('key2', $this->pitem2, 5);
		$list->setScrubError(true);
		$list->copyFrom($pmap);
		$list->setScrubError(false);
		$this->assertEquals($this->pitem2, $list[0]);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem1));
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem2));
	}
	
	public function testMergeWith_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->pitem1);
		$list->setScrubError(true);
		$list->mergeWith([$this->item1, $this->item2]);
		$list->setScrubError(false);
		self::assertEquals(3, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		
		$plist = new TPriorityList();
		$plist->add($this->item3, 10);
		$plist->add($this->item4, 5);
		$list->setScrubError(true);
		$list->mergeWith($plist);
		$list->setScrubError(false);
		self::assertEquals(5, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item3));
		self::assertEquals(1, $list->getWeakObjectCount($this->item4));
		
		$pmap = new TPriorityMap();
		$pmap->add('key1', $this->pitem1, 10);
		$pmap->add('key2', $this->pitem2, 5);
		$list->setScrubError(true);
		$list->mergeWith($pmap);
		$list->setScrubError(false);
		self::assertEquals(6, $list->getWeakCount());
		self::assertEquals(2, $list->getWeakObjectCount($this->pitem1));
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem2));
	}
	
	public function testOffsetExists_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 5);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add($closure = function($n) {return $n + $n;}, 15);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		self::assertFalse($list->offsetExists(3));
		$list->setScrubError(false);
		
		self::assertFalse($list->offsetExists(-1));
		self::assertTrue($list->offsetExists(0));
		self::assertTrue($list->offsetExists(1));
		self::assertTrue($list->offsetExists(2));
	}
	
	public function testOffsetGet_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 5);
		$list->add([$this->item4, 'eventHandler'], 10);
		$list->add($this->item1, 5);
		$list->add($closure = function($n) {return $n + $n;}, 15);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		try {
			self::assertFalse($list->offsetGet(3));
		} catch(TInvalidDataValueException $e) {
		}
		$list->setScrubError(false);
		
		self::assertEquals($this->item1, $list->offsetGet(0));
		self::assertEquals([$this->item4, 'eventHandler'], $list->offsetGet(1));
		self::assertEquals($closure, $list->offsetGet(2));
	}
	
	public function testOffsetSet_TWCC()
	{
		$list = new $this->_baseClass();
		
		$list->setScrubError(true);	// null index
		$list->offsetSet(null, $this->item1);
		$list->setScrubError(false);
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		
		$list->setScrubError(1);	// index @ count (append)
		$list->offsetSet(1, $this->item3);
		$list->setScrubError(false);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item3));
		
		$list->setScrubError(1);	// index < count (insert)
		$list->offsetSet(1, $this->item2);
		$list->setScrubError(false);
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		self::assertNull($list->getWeakObjectCount($this->item3));
			
		$list->offsetSet(null, $this->item4);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);
		$list[1] = $this->pitem1;
		$list->setScrubError(false);
		
		self::assertEquals([$this->item1, $this->pitem1], $list->toArray());
		self::assertEquals(2, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->pitem1));
	}
	
	
	public function testOffsetUnset_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1);
		$list->add($this->item2);
		$list->add($this->item3);
		$list->add($this->item4);
		
		$this->item2 = null;
		$this->item3 = null;
		
		$list->setScrubError(1);	// null index
		unset($list[1]);
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(0, $list->getWeakObjectCount($this->item4));
	}
	
}
