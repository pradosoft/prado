<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TPhpErrorException;
use Prado\Test\Unit\PradoUnit;
use Prado\TComponent;
use Prado\TEventHandler;
use Prado\Collections\IWeakRetainable;
use Prado\Collections\TPriorityList;
use Prado\Collections\TPriorityMap;

class TWeakCallableCollectionUnit extends TWeakCallableCollection
{
	use TListResetTrait;
	
	// NOTE: "TWCC" is the short name for "TWeakCallableCollection"
	
	public $_scrubCount = 0;
	public $_scrubError = false;
	public $_afterScrub = null;
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
		if ($this->_afterScrub) {
			$afterScrub = $this->_afterScrub;
			$this->_afterScrub = null;
			$afterScrub();
		}
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
	
	public function resetDiscardInvalid(bool $value): void
	{
		$this->setDiscardInvalid($value);
	}
	public function resetDefaultPriority($value)
	{
		$this->setDefaultPriority($value);
	}
	
	public function resetPrecision($value)
	{
		$this->setPrecision($value);
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

class RetainableCallableListItem extends CallableListItem implements IWeakRetainable
{
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
		$component = new TComponent();
		$object = new CallableListItem();
		
		//Test for that only callables can be inserted into the collection
		$item1 = $list[] = 'foo';
		$item2 = $list[] = [CallableListItem::class, 'staticHandler'];
		$item3 = $list[] = [$item3 = [$this->item1, 'eventHandler'], $item4 = CallableListItem::class . '::staticHandler'];
		$item5 = $list[] = [CallableListItemChild::class,'staticHandler'];
		$item6 = $list[] = $this->item2;
		$item7 = $list[] = function($n) { return $n + $n; };  //Test retaining of Closure
		self::assertNotEquals(6, count($list), "Closure is wrongly being WeakReferenced.");
		$list[] = new TEventHandler($handler = [$object, 'eventHandler'], 3); // Test Retaining of IWeakRetainable
		self::assertNotEquals(7, count($list), "TEventHandler/IWeakRetainable is wrongly being WeakReferenced.");
		$item8 = $list[7];
		$list[] = $item9 = new TEventHandler($item8);
		$list[] = [new CallableListItem(), 'eventHandler']; // immediately dropped because it's not retainable.
		$list[] = [$retainObject= new RetainableCallableListItem(), 'eventHandler'];
		self::assertEquals(10, count($list));
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item2));
		self::assertEquals(1, $list->getWeakObjectCount($item7));
		self::assertEquals(2, $list->getWeakObjectCount($object));
		self::assertEquals(1, $list->getWeakObjectCount($retainObject));
		self::assertEquals(5, $list->getWeakCount(), "Weak Callable Objects adding too many to the WeakMap");
		$retainObject = null;
		self::assertEquals(10, count($list));
		
		// Check callables that have proper syntax but error because they aren't referencing
		//   Valid callables/objects/methods.
		try {
			$list[] = 'notAFunctionCallable';
			$this->fail('TInvalidDataValueException string that is not a function did not throw error');
		} catch(TInvalidDataValueException $e){}
		self::assertEquals(10, count($list));
		try {
			$list[] = [CallableListItem::class, 'noStaticMethod'];
			$this->fail('TInvalidDataValueException [valid static object, \'noStaticMethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		self::assertEquals(10, count($list));
		try {
			$list[] = [$this->item1, 'noMethod'];
			$this->fail('TInvalidDataValueException [valid  object, \'noMethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		self::assertEquals(10, count($list));
		try {
			$list[] = CallableListItem::class . '::noStaticMethod';
			$this->fail('TInvalidDataValueException string of \'object::nostaticmethod\' that is not a method did not throw error');
		} catch(TInvalidDataValueException $e){}
		self::assertEquals(10, count($list));
		try {
			$list[] = [CallableListItemChild::class,'parent::noMethod'];
			$this->fail('TInvalidDataValueException string of [valid static object, \'parent::nostaticmethod\'] that is not a method did not throw error');
		} catch(TInvalidDataValueException $e) {// Catch PHP 8.1
		} catch(TPhpErrorException $e) {} // Catch PHP 8.2+
		self::assertEquals(10, count($list));
		try {
			$list[] = $component;
			$this->fail('TInvalidDataValueException object without  __invocke did not throw error');
		} catch(TInvalidDataValueException $e){}
		
		//There should still only be 6 items in the list
		self::assertEquals(10, count($list));
		
		$p = $list->toPriorityArrayWeak();
		
		// The two objects in the list should be converted into WeakReference
		$priority = $list->getDefaultPriority();
		$this->assertInstanceOf(\WeakReference::class, $p[$priority][5]);
		
		//The WeakReference should refer to the proper objects
		$this->assertEquals('foo', $p[$priority][0]);
		$this->assertEquals([CallableListItem::class, 'staticHandler'], $p[$priority][1]);
		$this->assertInstanceOf(\WeakReference::class, $p[$priority][2][0]);
		$this->assertEquals($this->item1, $p[$priority][2][0]->get());
		$this->assertEquals('eventHandler', $p[$priority][2][1]);
		$this->assertEquals(CallableListItem::class . '::staticHandler', $p[$priority][3]);
		//$this->assertEquals($p[$priority][4], ['CallableListItemChild','parent::staticHandler']);
		$this->assertEquals($this->item2, $p[$priority][5]->get());
		$this->assertEquals($item7, $p[$priority][6]);
		$this->assertEquals($item8, $p[$priority][7]);
		$this->assertEquals($item9, $p[$priority][8]);
	}
	
	public function testConstructTWCC()
	{
		$this->list = new $this->_baseClass([$this->item1, $this->item2]);
		self::assertEquals(0, $this->list->_scrubCount, "Scrubbed when not needed.");
	}
	
	public function testScrubInvalidWeakReference_TWCC()
	{
		$object1 = new CallableListItem();
		$object2 = new CallableListItem();
		
		$list = new $this->_baseClass();
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add($this->item1, 5);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($this->item2, 10);
		$list->add(function ($n) { return $n + $n; });
		self::assertEquals(5, $list->getWeakCount());
		
		$item1 = $list[] = new TEventHandler($handler1 = [$object1, 'eventHandler'], 3);
		$item2 = $list[] = new TEventHandler($handler2 = [$object2, 'eventHandler'], 4); 
		$item3 = $list[] = new TEventHandler($item2, 5);  // Scrub nested TEventHandler.
		
		self::assertEquals(8, $list->getCount());
		self::assertEquals(7, $list->getWeakCount());
			
		$this->item2 = null;
		$this->item3 = null;
		$handler2 = $object2 = null;
		
		self::assertInstanceof(\Closure::class, $closure = $list[1]);
		self::assertEquals(4, $list->getWeakCount());
		self::assertEquals(4, $list->getCount());
		self::assertEquals([$this->item1, $closure, $item1, [$this->item4, 'eventHandler']], $list->toArray());
		self::assertEquals(5, $list->priorityOf($this->item1));
		self::assertEquals(10, $list->priorityOf($item1));
		self::assertEquals(10, $list->priorityOf($closure));
		self::assertEquals(20, $list->priorityOf([$this->item4, 'eventHandler']));
		self::assertEquals(false, $list->priorityOf($this->item2));
		self::assertEquals(false, $list->priorityOf([$this->item3, 'eventHandler']));
		
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$object2 = new CallableListItem();
		$item2 = new TEventHandler($handler2 = [$object2, 'eventHandler'], 4); 
		$item3 = new TEventHandler($item2, 5);  // Scrub nested TEventHandler.
		
		$list = new $this->_baseClass([$this->item1, $this->pitem2, $this->pitem3, $this->item4, $item1, $item3], true);
		$this->pitem2 = null;
		$this->pitem3 = null;
		$handler1 = $object1 = null;
		$handler2 = $object2 = null;
		self::assertEquals(6, $list->getCount(), "Read only list incorrectly scrubbed.");
		self::assertEquals($this->item1, $list[0]);
		self::assertNull($list[1]);
		self::assertNull($list[2]);
		self::assertEquals($this->item4, $list[3]);
		self::assertNull($list[4]); // TEventHandler is removed if the callable is invalidated.
		self::assertNull($list[5]); // Nested TEventHandler is removed if the callable is invalidated.
	}
	
	public function testDiscardInvalid_TWCC()
	{
		$object1 = new CallableListItem();
		$object2 = new CallableListItem();
		
		$list = new $this->_baseClass();
		self::assertEquals(0, $list->getWeakCount());
		$list->resetDiscardInvalid(false);
		self::assertFalse($list->getDiscardInvalid());
		self::assertTrue($list->getWeakCount() === null);
		$list->add($this->item2, 10);
		$list->add($this->item1, 5);
		$list->add([$this->item4, 'eventHandler'], 20);
		$list->add([$this->item3, 'eventHandler'], 15);
		$list->add($closure = function ($n) {return $n + $n;});
		$eventHandler1 = new TEventHandler($handler1 = [$object1, 'eventHandler'], 3); // Test Retaining of IWeakRetainable
		$eventHandler2 = new TEventHandler($handler2 = [$object2, 'eventHandler'], 4); // Test Retaining of IWeakRetainable
		$list->add($eventHandler1);
		$list->add($eventHandler2);
		
		$this->item2 = null;
		$this->item3 = null;
		$handler2 = $object2 = null;
		
		self::assertEquals(7, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertNull($list[1]);
		self::assertEquals($closure, $list[2]);
		self::assertEquals($eventHandler1, $list[3]);
		self::assertNull($list[4]);
		self::assertNull($list[5]);
		self::assertEquals([$this->item4, 'eventHandler'], $list[6]);
		
		$list->setScrubError(true);
		$list->resetDiscardInvalid(true);
		$list->setScrubError(false);
		
		self::assertTrue($list->getDiscardInvalid());
		self::assertEquals(4, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($closure));
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals(1, $list->getWeakObjectCount($this->item4));
		self::assertEquals(1, $list->getWeakObjectCount($object1));
		
		self::assertEquals(4, $list->getCount());
		self::assertEquals($this->item1, $list[0]);
		self::assertEquals($closure, $list[1]);
		self::assertEquals($eventHandler1, $list[2]);
		self::assertEquals([$this->item4, 'eventHandler'], $list[3]);
		
		$list = new $this->_baseClass();
		$list->setDiscardInvalid(true);
		self::assertTrue($list->getDiscardInvalid());
		$list->resetDiscardInvalid(false);
		self::assertFalse($list->getDiscardInvalid());
		
		self::expectException(TInvalidOperationException::class);
		$list->setDiscardInvalid(true);
	}
	
	/**
	 * A weak listener freed between the scrub and the output filter (the cycle
	 * collector can run there) is dropped from the output instead of returned as null.
	 */
	public function testOutputDropsReferentFreedAfterScrub_TWCC()
	{
		$outputs = [
			'toArray' => fn ($list) => $list->toArray(),
			'toArrayBelowPriority' => fn ($list) => $list->toArrayBelowPriority(100),
			'toArrayAbovePriority' => fn ($list) => $list->toArrayAbovePriority(0),
			'getIterator' => fn ($list) => iterator_to_array($list->getIterator()),
			'itemsAtPriority' => fn ($list) => $list->itemsAtPriority(10),
		];
		foreach ($outputs as $name => $output) {
			$retained = new CallableListItem();
			$dying = new CallableListItem();
			$list = new $this->_baseClass();
			$list->add([$retained, 'eventHandler'], 10);
			$list->add([$dying, 'eventHandler'], 10);
			$list->add($dying, 10);
			$list->_afterScrub = function () use (&$dying) {
				$dying = null;
			};
			self::assertSame([[$retained, 'eventHandler']], $output($list), $name);
			self::assertNull($dying, $name);
			self::assertEquals(1, $list->getCount(), $name);
		}

		$retained = new CallableListItem();
		$dying = new CallableListItem();
		$list = new $this->_baseClass();
		$list->add([$retained, 'eventHandler'], 10);
		$list->add([$dying, 'eventHandler'], 20);
		$list->_afterScrub = function () use (&$dying) {
			$dying = null;
		};
		self::assertSame(['10' => [[$retained, 'eventHandler']]], $list->toPriorityArray());

		$dying = new CallableListItem();
		$list = new $this->_baseClass();
		$list->add($dying, 20);
		$list->_afterScrub = function () use (&$dying) {
			$dying = null;
		};
		self::assertNull($list->itemsAtPriority(20));
	}

	/**
	 * Lists that keep invalid WeakReferences (DiscardInvalid false) keep null
	 * placeholders in the output so indices match {@see TWeakCallableCollection::itemAt}.
	 */
	public function testOutputKeepsNullWithoutDiscardInvalid_TWCC()
	{
		$retained = new CallableListItem();
		$dying = new CallableListItem();
		$list = new $this->_baseClass([[$retained, 'eventHandler'], [$dying, 'eventHandler']], true);
		$dying = null;
		self::assertSame([[$retained, 'eventHandler'], null], $list->toArray());
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

	public function testRemoveWithPriorityFindsWrappingEventHandler_TWCC()
	{
		$list = new $this->_baseClass();
		$callable = [$this->item1, 'eventHandler'];
		$list->add([$this->item2, 'eventHandler'], 10);
		$list->add($handler = new TEventHandler($callable, 'data'), 10);
		$list->add([$this->item3, 'eventHandler'], 5);

		self::assertEquals(2, $list->indexOf($callable, 10));
		self::assertEquals(2, $list->remove($callable, 10));
		self::assertEquals(2, $list->getCount());
		self::assertEquals(-1, $list->indexOf($callable));
		self::assertEquals(0, $list->getWeakObjectCount($this->item1));

		$list->add($handler, 10);
		try {
			$list->remove($callable, 5);
			self::fail('A wrapping TEventHandler at another priority must not be removed.');
		} catch (TInvalidDataValueException $e) {
			self::assertEquals('list_item_inexistent', $e->getErrorCode());
		}
		self::assertEquals(3, $list->getCount());
	}

	public function testRemoveWithPriorityPrefersExactMatch_TWCC()
	{
		$list = new $this->_baseClass();
		$callable = [$this->item1, 'eventHandler'];
		$list->add(new TEventHandler($callable, 'data'), 10);
		$list->add($callable, 10);

		self::assertEquals(1, $list->remove($callable, 10));
		self::assertEquals(1, $list->getCount());
		self::assertInstanceOf(TEventHandler::class, $list[0]);
	}

	public function testDetachEventHandlerWithPriorityFindsWrappingEventHandler_TWCC()
	{
		$component = new TComponent();
		$callable = [$this->item1, 'eventHandler'];
		$component->attachEventHandler('fxRemovePriorityTest', new TEventHandler($callable, 'data'), 10);

		self::assertFalse($component->detachEventHandler('fxRemovePriorityTest', $callable, 5));
		self::assertTrue($component->detachEventHandler('fxRemovePriorityTest', $callable, 10));
		self::assertCount(0, $component->getEventHandlers('fxRemovePriorityTest'));
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
	
	public function testInsert_RemoveAtIndexInPriority_TEventHandler_TWCC() 
	{
		$list = new $this->_baseClass();
		$list->insertAtIndexInPriority($refHandler = new TEventHandler($this->item1), 0, 5);
		self::assertEquals(1, $list->getWeakCount());
		self::assertEquals(1, $list->getWeakObjectCount($this->item1));
		self::assertEquals($refHandler, $list->removeAtIndexInPriority(0, 5));
		self::assertEquals(0, $list->getWeakCount());
		self::assertEquals(0, $list->getWeakObjectCount($this->item1));
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
		
		$list->setScrubError(1);
		self::assertTrue($list->contains($this->item1));
		$list->setScrubError(false);
		
		self::assertEquals(1, $list->_containWithout_fd);
		self::assertEquals(0, $list->_containWith_fd);
		self::assertTrue($list->contains([$this->item4, 'eventHandler']));
			
		$list->toArray();
		
		$list->_containWithout_fd = 0;
		$list->_containWith_fd = 0;
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
	
	public function testIndexOf_TEventHandler_TWCC()
	{
		$this->list->clear();
		
		$handler1 = [$this->item1, 'eventHandler'];
		$handler2 = [$this->item2, 'eventHandler'];
		$handler3 = [$this->item3, 'eventHandler'];
		$handler4 = [$this->item4, 'eventHandler'];
		
		$eventHandler5 = new TEventHandler($handler4, 55);
		
		$handler6 = [$object6 = new CallableListItem(), 'eventHandler'];
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
	
	public function testPriorityOf_TEventHandler_TWCC()
	{
		$this->list->clear();
		
		$handler1 = [$this->item1, 'eventHandler'];
		$handler2 = [$this->item2, 'eventHandler'];
		$handler3 = [$this->item3, 'eventHandler'];
		$handler4 = [$this->item4, 'eventHandler'];
		
		$eventHandler5 = new TEventHandler($handler4, 55);
		
		$handler6 = [$object6 = new CallableListItem(), 'eventHandler'];
		$eventHandler6 = new TEventHandler($handler6, 14);
		$eventHandler7 = new TEventHandler($eventHandler6, 15);
		$eventHandler8 = new TEventHandler($eventHandler7, 16);
		
		$this->list->add($handler1, 1);
		$this->list->add($handler2, 2);
		$this->list->add($eventHandler1 = new TEventHandler($handler1, 13), 3);
		$this->list->add($eventHandler3 = new TEventHandler($handler3, 21), 5);
		$this->list->add($eventHandler4 = new TEventHandler($handler4, 34), 8);
		$this->list->add($eventHandler8, 11);
		$this->list->add($handler4, 13);
		$eventHandler5 = new TEventHandler($handler4, 55);
		
		self::assertEquals(1, $this->list->priorityOf($handler1));
		self::assertEquals(2, $this->list->priorityOf($handler2));
		self::assertEquals(5, $this->list->priorityOf($handler3), "Handler, not directly in the list, but in TEventHandler should be found");
		self::assertEquals(13, $this->list->priorityOf($handler4), "Raw Handler takes precedence over being found in TEventHandler");
		self::assertEquals(11, $this->list->priorityOf($handler6), "Nested TEventHandler didn't match the callable.");
		
		self::assertEquals(3, $this->list->priorityOf($eventHandler1));
		self::assertEquals(5, $this->list->priorityOf($eventHandler3));
		self::assertEquals(8, $this->list->priorityOf($eventHandler4));
		
		self::assertEquals(11, $this->list->priorityOf($eventHandler6));
		self::assertEquals(11, $this->list->priorityOf($eventHandler7));
		self::assertEquals(11, $this->list->priorityOf($eventHandler8));
		
		self::assertEquals(-1, $this->list->indexOf($eventHandler5));
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
		
		self::assertInstanceOf(\WeakReference::class, $array[5][0]);
		self::assertInstanceOf(\WeakReference::class, $array[10][0][0]);
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

	public function testConstructorResolvesDiscardInvalid_TWCC()
	{
		$item = new CallableListItem();
		$data = [[$item, 'eventHandler']];

		self::assertTrue((new TWeakCallableCollection($data))->getDiscardInvalid());
		self::assertTrue((new TWeakCallableCollection($data, false))->getDiscardInvalid());
		self::assertFalse((new TWeakCallableCollection($data, true))->getDiscardInvalid());
		self::assertFalse((new TWeakCallableCollection($data, 1))->getDiscardInvalid());
		self::assertFalse((new TWeakCallableCollection($data, null, null, null, false))->getDiscardInvalid());
		self::assertTrue((new TWeakCallableCollection($data, true, null, null, true))->getDiscardInvalid());
		self::assertTrue((new TWeakCallableCollection())->getDiscardInvalid());

		$list = new TWeakCallableCollection([[new CallableListItem(), 'eventHandler'], 'strlen']);
		gc_collect_cycles();
		self::assertSame(1, $list->getCount());
		self::assertSame(['strlen'], $list->toArray());
	}

	public function testEventHandlerWithoutObject_TWCC()
	{
		$list = new TWeakCallableCollection();
		$handler = new TEventHandler('strlen');
		$list->add($handler);

		self::assertSame(1, $list->getCount());
		self::assertTrue($list->contains('strlen'));
		self::assertSame(0, $list->indexOf('strlen'));
		self::assertEquals(10, $list->priorityOf('strlen'));
		self::assertSame(0, $list->remove('strlen'));
		self::assertSame(0, $list->getCount());
		self::assertFalse($list->contains('strlen'));
	}

	public function testEventHandlerWithCollectedObject_TWCC()
	{
		$item = new CallableListItem();
		$handler = new TEventHandler([$item, 'eventHandler']);
		$item = null;
		self::assertFalse($handler->hasHandler());

		$list = new TWeakCallableCollection();
		$list->add($handler);
		self::assertSame([], $list->toArray());
		self::assertSame(0, $list->getCount(), 'A TEventHandler collected before it is added is scrubbed');

		$list = new TWeakCallableCollection(null, null, null, null, false);
		$list->add($handler);
		self::assertSame(1, $list->getCount());
		self::assertSame([null], $list->toArray());
	}

	public function testDiscardInvalidTransitionKeepsEventHandlerWithoutObject_TWCC()
	{
		$list = new TWeakCallableCollectionUnit(null, null, null, null, false);
		$item = new CallableListItem();
		$handler = new TEventHandler('strlen');
		$list->add($handler);
		$list->add($objectHandler = new TEventHandler([$item, 'eventHandler']));

		$list->resetDiscardInvalid(true);

		self::assertSame([$handler, $objectHandler], $list->toArray());
		self::assertSame(1, $list->getWeakObjectCount($item));
		self::assertSame(1, $list->indexOf([$item, 'eventHandler']));
	}

	public function testReadOnlyDiscardInvalidTransitionRemovesCollected_TWCC()
	{
		$list = new TWeakCallableCollectionUnit(null, null, null, null, false);
		$dead = new CallableListItem();
		$deadHandler = new CallableListItem();
		$live = new CallableListItem();
		$list->add([$dead, 'eventHandler'], 5);
		$list->add(new TEventHandler([$deadHandler, 'eventHandler']), 10);
		$list->add([$live, 'eventHandler'], 10);
		$list->resetReadOnly(true);
		$dead = $deadHandler = null;

		$list->resetDiscardInvalid(true);

		self::assertTrue($list->getReadOnly());
		self::assertSame(1, $list->getCount());
		self::assertSame([[$live, 'eventHandler']], $list->toArray());
		self::assertEquals([10], $list->getPriorities(), 'A priority left empty is removed');
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertSame(1, $list->getWeakObjectCount($live));
	}

	public function testRemoveCollectedEventHandlerUncounts_TWCC()
	{
		$list = new TWeakCallableCollectionUnit(null, null, null, null, false);
		$item = new CallableListItem();
		$list->add(new TEventHandler([$item, 'eventHandler']));
		$item = null;

		self::assertNull($list->removeAt(0));
		self::assertSame(0, $list->getCount());
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
	}

	public function testClearResetsBookkeeping_TWCC()
	{
		$list = new TWeakCallableCollectionUnit();
		$item = new CallableListItem();
		$list->add(new TEventHandler('strlen'));
		$list->add([$item, 'eventHandler']);
		$list->clear();

		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertSame(0, PradoUnit::getProp($list, '_weakCount'));
		self::assertFalse($list->getWeakChanged());
		self::assertNull($list->getWeakObjectCount($item));
	}

	public function testFailedInsertLeavesNoBookkeeping_TWCC()
	{
		$item = new CallableListItem();

		$list = new TWeakCallableCollectionUnit();
		try {
			$list->add([$item, 'noSuchMethod']);
			self::fail('A non-callable must not be added');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('weakcallablecollection_callable_required', $e->getErrorCode());
		}
		self::assertSame(0, $list->getCount());
		self::assertNull($list->getWeakObjectCount($item));
		self::assertSame(0, PradoUnit::getProp($list, '_weakCount'));

		try {
			$list->insertAtIndexInPriority([$item, 'eventHandler'], 9, 5);
			self::fail('An index past the priority must throw');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('prioritylist_index_invalid', $e->getErrorCode());
		}
		self::assertSame(0, $list->getCount());
		self::assertNull($list->getWeakObjectCount($item));

		$list = new TWeakCallableCollectionUnit(null, true, null, null, true);
		try {
			$list->insertAtIndexInPriority(new TEventHandler([$item, 'eventHandler']));
			self::fail('A read-only list must not insert');
		} catch (TInvalidOperationException $e) {
			self::assertSame('list_readonly', $e->getErrorCode());
		}
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertNull($list->getWeakObjectCount($item));
	}

	public function testConstructDiscardInvalidArgument_TWCC()
	{
		$dying = new CallableListItem();
		$list = new $this->_baseClass([[$dying, 'eventHandler'], $this->item1], null, null, null, false);
		$dying = null;

		self::assertFalse($list->getReadOnly());
		self::assertFalse($list->getDiscardInvalid());
		self::assertNull($list->getWeakCount());
		self::assertSame(2, $list->getCount());
		self::assertNull($list[0]);

		$dying = new CallableListItem();
		$list = new $this->_baseClass([[$dying, 'eventHandler'], $this->item1], true, null, null, true);
		self::assertSame(2, $list->getWeakCount());
		$dying = null;

		self::assertTrue($list->getReadOnly());
		self::assertTrue($list->getDiscardInvalid());
		self::assertSame([$this->item1], $list->toArray());
		self::assertSame(1, $list->getCount());
	}

	public function testClone_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item1, 'eventHandler']);
		$list->add($this->item2);
		$clone = clone $list;
		$clone->add($this->item3);

		self::assertSame(2, $list->getWeakCount());
		self::assertSame(3, $clone->getWeakCount());
		self::assertNull($list->getWeakObjectCount($this->item3));

		$this->item2 = null;

		self::assertSame([[$this->item1, 'eventHandler']], $list->toArray());
		self::assertSame([[$this->item1, 'eventHandler'], $this->item3], $clone->toArray());
		self::assertSame(1, $list->getWeakCount());
		self::assertSame(2, $clone->getWeakCount());
	}

	public function testSerializeWakesEmptyAndRestartsTracking_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add([$this->item1, 'eventHandler'], 5);
		$list->add($this->item2);
		self::assertSame([[$this->item1, 'eventHandler'], $this->item2], $list->toArray());

		$serialized = serialize($list);

		self::assertStringContainsString('_discardInvalid', $serialized);
		self::assertStringNotContainsString('_weakMap', $serialized);
		self::assertSame(2, $list->getCount(), 'Sleeping must not change the source list.');
		self::assertSame([[$this->item1, 'eventHandler'], $this->item2], $list->toArray());

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

	public function testSerializeKeepsDiscardInvalidFalse_TWCC()
	{
		$list = new $this->_baseClass([$this->item1], true);
		self::assertFalse($list->getDiscardInvalid());

		$restored = unserialize(serialize($list));

		self::assertTrue($restored->getReadOnly());
		self::assertFalse($restored->getDiscardInvalid());
		self::assertNull($restored->getWeakCount());
		self::assertSame(0, $restored->getCount());
	}

	public function testSerializeLeavesUnresolvedDiscardInvalidLazy_TWCC()
	{
		$list = new $this->_baseClass();

		$serialized = serialize($list);

		self::assertStringNotContainsString('_discardInvalid', $serialized);
		$restored = unserialize($serialized);
		self::assertTrue($restored->getDiscardInvalid());
	}

	public function testTEventHandlerWithoutHandlerObject_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1);
		$eh = new TEventHandler('strlen', 5);

		$list->add($eh, 5);

		self::assertSame(2, $list->getCount());
		self::assertSame(1, $list->getWeakCount());
		self::assertSame([$eh, $this->item1], $list->toArray());
		self::assertSame(0, $list->indexOf($eh));

		self::assertSame($eh, $list->removeAt(0));

		self::assertSame(1, $list->getWeakCount());
		self::assertSame([$this->item1], $list->toArray());
	}

	public function testInsertArrayOfCallablesReturnsEachIndex_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1);
		self::assertSame([$this->item1], $list->toArray());

		$result = $list->insertAtIndexInPriority([[$this->item2, 'eventHandler'], [$this->item3, 'eventHandler'], $this->item4]);

		self::assertSame([1, 2, 3], $result);
		self::assertSame([$this->item1, [$this->item2, 'eventHandler'], [$this->item3, 'eventHandler'], $this->item4], $list->toArray());
		self::assertSame(4, $list->getWeakCount());
	}

	public function testIndexOfWithPriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1, 5);
		$list->add($this->item2, 10);
		$list->add($eh = new TEventHandler([$this->item3, 'eventHandler']), 10);
		$list->add($this->item4, 20);

		self::assertSame(2, $list->indexOf($eh, 10));
		self::assertSame(2, $list->indexOf([$this->item3, 'eventHandler'], 10));
		self::assertSame(-1, $list->indexOf($this->item4, 10));
		self::assertSame(-1, $list->indexOf($this->item1, 7));
		self::assertSame(-1, $list->indexOf($this->item4, 30));

		$empty = new $this->_baseClass();
		self::assertSame(-1, $empty->indexOf($this->item1, 10));
	}

	public function testPriorityOfTEventHandlerWrappedCallable_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1, 5);
		$list->add(new TEventHandler([$this->item2, 'eventHandler']), 15);

		self::assertEquals(15, $list->priorityOf([$this->item2, 'eventHandler']));
		self::assertSame(1, $list->priorityOf([$this->item2, 'eventHandler'], true)['absindex']);
		self::assertFalse($list->priorityOf([$this->item3, 'eventHandler']));
	}

	public function testOffsetSetGuards_TWCC()
	{
		$list = new $this->_baseClass([$this->item1], true);
		try {
			$list[0] = $this->item2;
			self::fail('offsetSet did not throw TInvalidOperationException on a read-only list.');
		} catch (TInvalidOperationException $e) {
			self::assertSame('list_readonly', $e->getErrorCode());
		}

		$list = new $this->_baseClass([$this->item1]);
		try {
			$list[-1] = $this->item2;
			self::fail('offsetSet did not throw TInvalidDataValueException on a negative offset.');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('list_index_invalid', $e->getErrorCode());
		}
		self::assertSame([$this->item1], $list->toArray());
	}

	public function testClearEmptyAndSingle_TWCC()
	{
		$list = new $this->_baseClass();
		$list->clear();
		self::assertSame(0, $list->getCount());
		self::assertSame(0, $list->getWeakCount());

		$list->add($this->item1, 5);
		$list->clear();
		self::assertSame(0, $list->getCount());
		self::assertSame([], $list->toPriorityArray());
		self::assertSame(0, $list->getWeakCount());
	}

	public function testRemoveWithPriority_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add($this->item1, 5);

		foreach ([
			'below every priority' => [$this->item1, 10],
			'not at the priority' => [$this->item2, 5],
			'above every priority' => [$this->item1, 1],
		] as $case => [$item, $priority]) {
			try {
				$list->remove($item, $priority);
				self::fail("remove did not throw TInvalidDataValueException: $case.");
			} catch (TInvalidDataValueException $e) {
				self::assertSame('list_item_inexistent', $e->getErrorCode(), $case);
			}
		}
		self::assertSame(0, $list->remove($this->item1, 5));
		self::assertSame(0, $list->getCount());

		try {
			$list->remove($this->item1, 5);
			self::fail('remove did not throw TInvalidDataValueException on an empty list.');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('list_item_inexistent', $e->getErrorCode());
		}
	}

	public function testRemoveNonObjectCallables_TWCC()
	{
		$list = new $this->_baseClass();
		$list->add('strlen');
		$list->add([CallableListItem::class, 'staticHandler']);
		$list->add($this->item1);
		self::assertSame(1, $list->getWeakCount());

		self::assertSame('strlen', $list->removeAt(0));
		self::assertSame([CallableListItem::class, 'staticHandler'], $list->removeAt(0));

		self::assertSame(1, $list->getWeakCount());
		self::assertSame([$this->item1], $list->toArray());
	}

	/**
	 * An object that is neither a WeakReference nor a TEventHandler is untracked by
	 * its own instance count; the count at 0 drops the object from the WeakMap.
	 */
	public function testWeakCustomRemoveUntracksAPlainObject_TWCC()
	{
		$list = new $this->_baseClass(null, null, null, null, true);
		$object = new CallableListItem();
		self::assertSame(1, PradoUnit::invoke($list, 'weakCustomAdd', $object));
		self::assertSame(2, PradoUnit::invoke($list, 'weakCustomAdd', $object));
		self::assertSame(1, $list->getWeakCount());

		self::assertSame(1, PradoUnit::invoke($list, 'weakCustomRemove', $object));
		self::assertSame(1, $list->getWeakCount());
		self::assertSame(0, PradoUnit::invoke($list, 'weakCustomRemove', $object));
		self::assertSame(0, $list->getWeakCount());
		self::assertSame(0, PradoUnit::invoke($list, 'weakCustomRemove', $object));
	}

	/**
	 * A Closure is stored strongly and tracked as a plain object, so removing it
	 * returns the Closure and releases its WeakMap entry.
	 */
	public function testRemoveClosureReleasesItsWeakMapEntry_TWCC()
	{
		$list = new $this->_baseClass();
		$closure = function ($n) {
			return $n + $n;
		};
		$list->add($closure);
		self::assertSame(1, $list->getWeakCount());

		self::assertSame($closure, $list->removeAt(0));
		self::assertSame(0, $list->getWeakCount());
		self::assertSame(0, $list->getCount());
	}

	public function testRemoveCollectedEntryWithoutDiscardInvalid_TWCC()
	{
		$dying = new CallableListItem();
		$list = new $this->_baseClass(null, null, null, null, false);
		$list->add([$dying, 'eventHandler']);
		$dying = null;

		self::assertSame(1, $list->getCount());
		self::assertNull($list->removeAt(0));
		self::assertSame(0, $list->getCount());
	}

	/**
	 * When the only listener of the only priority is freed after the scrub,
	 * toPriorityArray omits that priority.
	 */
	public function testToPriorityArrayOmitsPriorityFreedAfterScrub_TWCC()
	{
		$dying = new CallableListItem();
		$list = new $this->_baseClass();
		$list->add([$dying, 'eventHandler'], 20);
		$list->_afterScrub = function () use (&$dying) {
			$dying = null;
		};

		self::assertSame([], $list->toPriorityArray());
		self::assertNull($dying);
		self::assertSame(0, $list->getCount());
	}

	/**
	 * Without DiscardInvalid there is no WeakMap; clear() empties the list, zeroes
	 * the event handler count, and creates no WeakMap.
	 */
	public function testClearWithoutDiscardInvalidCreatesNoWeakMap_TWCC()
	{
		$handlerObject = new CallableListItem();
		$list = new $this->_baseClass(null, null, null, null, false);
		$list->add(new TEventHandler([$handlerObject, 'eventHandler']));
		$list->add($this->item1);
		self::assertFalse($list->getDiscardInvalid());
		self::assertNull($list->getWeakCount());
		self::assertSame(1, PradoUnit::getProp($list, '_eventHandlerCount'));

		$list->clear();

		self::assertSame(0, $list->getCount());
		self::assertSame(0, PradoUnit::getProp($list, '_eventHandlerCount'));
		self::assertNull($list->getWeakCount());
	}
}
