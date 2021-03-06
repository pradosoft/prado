<?php

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

class CallableListItem
{
	public $data = 'data';
	public function __construct($d)
	{
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
		return  'Prado\\Collections\\TWeakCallableCollection';
	}
	protected function newListItem()
	{
		return 'CallableListItem';
	}
	protected function getCanAddNull()
	{
		return false;
	}

	//*******  end test cases for TWeakCallableCollection operations
	//*****************************************************************
	

	public function testGetWeakReferenceEnabledTWeakCallableCollection()
	{
		self::assertEquals('Prado\Collections\TWeakCallableCollection', get_class($this->list));
		self::assertEquals(class_exists('\WeakReference'), $this->list->getWeakReferenceEnabled());
		self::assertEquals(class_exists('\WeakReference'), TWeakCallableCollection::getWeakReferenceEnabled());
	}
	
	public function testToPriorityArrayWeakTWeakCallableCollection()
	{
		if(!TWeakCallableCollection::getWeakReferenceEnabled()) {
			self::markTestSkipped('The WeakReferences are only available in PHP 7.4+. You are running PHP version ' . phpversion());
			return;
		}
			
		$list = new TWeakCallableCollection();
		$component = new TComponent;
		
		//Test for that only callables can be inserted into the collection
		$item1 = $list[] = 'foo';
		$item2 = $list[] = ['CallableListItem', 'staticHandler'];
		$item3 = $list[] = [$this->item1, 'eventHandler'];
		$item4 = $list[] = 'CallableListItem::staticHandler';
		$item5 = $list[] = ['CallableListItemChild','parent::staticHandler'];
		$item6 = $list[] = $this->item2;
		
		// Check that callables that have proper syntax but do not exist
		self::assertEquals(6, count($list));
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
		} catch(TInvalidDataValueException $e){}
		try {
			$list[] = $component;
			$this->fail('TInvalidDataValueException object without  __invocke did not throw error');
		} catch(TInvalidDataValueException $e){}
		
		//There should still only be 6 items in the list
		self::assertEquals(6, count($list));
		
		$p = $list->toPriorityArrayWeak();
		
		// The two objects in the list should be converted into WeakReference
		$priority = $list->getDefaultPriority();
		$this->assertTrue(is_a($p[$priority][2][0], 'WeakReference'));
		$this->assertTrue(is_a($p[$priority][5], 'WeakReference'));
		
		//The WeakReference should refer to the proper objects
		$this->assertEquals($p[$priority][0], 'foo');
		$this->assertEquals($p[$priority][1], ['CallableListItem', 'staticHandler']);
		$this->assertEquals($p[$priority][2][0]->get(), $this->item1);
		$this->assertEquals($p[$priority][2][1], 'eventHandler');
		$this->assertEquals($p[$priority][3], 'CallableListItem::staticHandler');
		$this->assertEquals($p[$priority][4], ['CallableListItemChild','parent::staticHandler']);
		$this->assertEquals($p[$priority][5]->get(), $this->item2);
		
	}
	
}
