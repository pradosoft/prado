<?php

use Prado\Collections\TArraySubscription;
use Prado\Collections\{TList, TMap, TPriorityList, TPriorityMap, TWeakCallableCollection, TWeakList};
use Prado\TEventHandler;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

class WeakCallableInvokee {
	public mixed $value = null;
	public mixed $param = null;
	public mixed $returnValue = null;
	public function __construct(mixed $v = null)
	{
		$this->value = $v;
	}
	public function __invoke(mixed $v = null)
	{
		$this->param = $v;
		return $this->returnValue;
	}
}

class TTestArraySubscription extends TArraySubscription
{
}


class TArraySubscriptionTest extends PHPUnit\Framework\TestCase
{
	public $subscription = null;

	public $_baseClass = null;

	protected function newSubscription(): string
	{
		return  TTestArraySubscription::class;
	}
	
	protected function setUp(): void
	{
		$this->_baseClass = $this->newSubscription();
		
	}

	protected function tearDown(): void
	{
		$this->subscription = null;
	}

	public function testConstruct()
	{
		$array = [];
		$item = [2 => 'item2'];
		$priority = 1.5;

		// normal construct.
		$this->subscription = new $this->_baseClass($array, 'key', $item, $priority);
		self::assertEquals(['key' => $item], $array);
		self::assertEquals(['key' => $item], $this->subscription->getArray());
		self::assertEquals('key', $this->subscription->getKey());
		self::assertEquals($item, $this->subscription->getItem());
		self::assertEquals($priority, $this->subscription->getPriority());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->getIsAssociative());
		
		// key with null item, subscribes
		$this->subscription = new $this->_baseClass($array, 'key2');
		self::assertEquals(['key2' => null], $array);
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->getIsAssociative());
		
		// key is null, item is not null, subscribes
		$item2 = [3 => 'item3'];
		$this->subscription = new $this->_baseClass($array, null, $item2);
		self::assertEquals([0 => $item2], $array);
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->getIsAssociative());
		$this->subscription->unsubscribe();
		self::assertEquals([], $array);
		
		
		// optional priority, isassociative and autosubscribe.
		$this->subscription = new $this->_baseClass($array, 'key', 3, priority: 20, isAssociative: null, autoSubscribe: false);
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals(20, $this->subscription->getPriority());
		self::assertNull($this->subscription->getIsAssociative());
		
		// optional priority, isassociative and autosubscribe.
		$this->subscription = new $this->_baseClass($array, 'key', 3, priority: 15, isAssociative: false, autoSubscribe: false);
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals(15, $this->subscription->getPriority());
		self::assertFalse($this->subscription->getIsAssociative());
		
		// only array
		$array = [];
		$this->subscription = new $this->_baseClass($array);
		self::assertFalse($this->subscription->getIsSubscribed());
		
		// subscribe with $key = null, $item = null
		$array = [];
		$this->subscription = new $this->_baseClass($array, autoSubscribe: true);
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals(0, $this->subscription->getKey());
		self::assertNull($this->subscription->getItem());
			
		// no auto subscribe to $array = null.
		$array = null;
		$this->subscription = new $this->_baseClass($array, 'key', $item);
		self::assertFalse($this->subscription->getIsSubscribed());
		
	}
	
	public function testDestruct()
	{
		$array = [];
		$item = [2 => 'item2'];
		
		// Destruct
		$this->subscription = new $this->_baseClass($array, 'key', $item);
		self::assertEquals(['key' => $item], $array);
		$this->subscription = null;
		self::assertEquals([], $array);
		
		// destruct already unsubscribed
		$this->subscription = new $this->_baseClass($array, 'key', $item);
		self::assertEquals(['key' => $item], $array);
		$this->subscription->unsubscribe();
		self::assertEquals([], $array);
		$array = ['key' => $item];
		$this->subscription = null;
		self::assertEquals(['key' => $item], $array);
		$array = [];
		
		// destruct invalidated array
		$this->subscription = new $this->_baseClass($array, 'key', $item);
		self::assertEquals(['key' => $item], $array);
		$array = null;
		$this->subscription = null; //no issue with invalid array.
	}
	
	public function testArray()
	{
		$array = [];
		
		$this->subscription = new $this->_baseClass($array);
		self::assertFalse($this->subscription->getIsSubscribed());
		
		// Get array reference.
		$a = &$this->subscription->getArray();
		$a['key'] = 5;
		self::assertEquals(['key' => 5], $array);
		unset($a['key']);
		self::assertEquals([], $array);
		
		//SetArray...
		$array2 = [];
		$this->subscription->setArray($array2);
		//	...is the new specified array.
		$b = &$this->subscription->getArray();
		$b['key'] = 6;
		self::assertEquals([], $array);
		self::assertEquals(['key' => 6], $array2);
		
		// array becomes null
		$b = null;
		self::assertNull($array2);
		
		//Set ArrayAccess...
		$map = new TMap();
		$this->subscription->setArray($map);
		
		//   is a weak reference.
		self::assertInstanceof(WeakReference::class, $this->subscription->getArray(true));
		self::assertInstanceof(WeakReference::class, $value = &$this->subscription->getArray(true));
		$value = null;
		self::assertInstanceof(WeakReference::class, $this->subscription->getArray(true));
		
		// normal is not a weak reference
		self::assertInstanceof(TMap::class, $this->subscription->getArray());
		self::assertInstanceof(TMap::class, $value = &$this->subscription->getArray());
		$value = null;
		self::assertInstanceof(TMap::class, $this->subscription->getArray());
		
		// deref weak reference drops the subscription array.
		$map = null;
		self::assertInstanceof(WeakReference::class, $this->subscription->getArray(true));
		self::assertNull($this->subscription->getArray());
		
		//error setArray changing array when subscribed.
		self::expectException(TInvalidOperationException::class);
		// Array should be [], and not set by reference.
		$this->subscription = new $this->_baseClass($array, 'key', 2);
		$this->subscription->setArray($array2);
	}
	
	public function testKey()
	{
		$array = [];
		
		$this->subscription = new $this->_baseClass($array, 'key');
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals('key', $this->subscription->getKey());
		
		$this->subscription->unsubscribe();
		self::assertEquals('key', $this->subscription->getKey());
		
		$this->subscription->setKey('key1');
		self::assertEquals('key1', $this->subscription->getKey());
		
		$this->subscription->setKey(null);
		self::assertEquals(null, $this->subscription->getKey());
		
		$this->subscription->setKey(4.5);
		self::assertEquals(4, $this->subscription->getKey());
			
		
		$this->subscription->setKey(null);
		self::assertEquals(null, $this->subscription->getKey());
		
		$this->subscription->subscribe();
		self::assertEquals(0, $this->subscription->getKey());
		unset($array[0]);
		self::assertEquals(null, $this->subscription->getKey());
		$this->subscription->unsubscribe();
		self::assertEquals(null, $this->subscription->getKey());
		
		$list = new TList();
		$this->subscription->setArray($list);
		$this->subscription->subscribe();
		self::assertEquals(0, $this->subscription->getKey());
		
		//error setArray changing array when subscribed.
		self::expectException(TInvalidOperationException::class);
		$this->subscription->setKey(2);
	}
	
	public function testItem()
	{
		$array = [];
		
		$this->subscription = new $this->_baseClass($array, 'key', );
		
		self::assertNull($this->subscription->getItem());
		$this->subscription->unsubscribe();
		self::assertNull($this->subscription->getItem());
		
		$this->subscription->setItem($reference = new stdClass());
		$reference->var = 3;
		self::assertEquals($reference, $this->subscription->getItem(true));
		
		$this->subscription->subscribe();
		self::assertEquals($reference, $this->subscription->getItem(true));
		$this->subscription->unsubscribe();

		$array = new TWeakList();
		self::assertEquals($array, $this->subscription->getArray());
		$this->subscription->setKey(null);
		$this->subscription->subscribe(); // Test ICollectionFilter::filterItemForInput and filterItemForOutput
		self::assertInstanceOf(WeakReference::class, $this->subscription->getItem(true));
		self::assertEquals($reference, $this->subscription->getItem());
		$this->subscription->unsubscribe();
		self::assertInstanceOf(stdClass::class, $this->subscription->getItem(true));
		self::assertEquals($reference, $this->subscription->getItem());
		
		$array = [];
		$this->subscription = new $this->_baseClass($array, 'key', );
		self::expectException(TInvalidOperationException::class);
		$this->subscription->subscribe();
		$this->subscription->setItem(2);
	}
	
	public function testPriority()
	{
		$array = [];
		$this->subscription = new $this->_baseClass($array, 'key', );
		self::expectException(TInvalidOperationException::class);
		$this->subscription->subscribe();
		$this->subscription->setPriority(2);
	}
	
	public function testIsAssociative()
	{
		$this->subscription = new $this->_baseClass();
		
		self::assertEquals(1, $this->subscription->getIsAssociative());
		$this->subscription->setIsAssociative(false);
		self::assertFalse($this->subscription->getIsAssociative());
		$this->subscription->setIsAssociative(null);
		self::assertNull($this->subscription->getIsAssociative());
	}
	
	public function testSubscribe()
	{
		// Array does subscribe.
		$array = [];
		$this->subscription = new $this->_baseClass($array);
		$this->subscription->setKey('key')->setItem(5);
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		
		// Can't double subscribe
		self::assertFalse($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
		
		// Can't subscribe to not (array || ArrayAccess)
		$array = 'not an array';
		self::assertNull($this->subscription->subscribe());
		self::assertFalse($this->subscription->getIsSubscribed());
		
		// ArrayAccess is acceptable.
		$array = new TMap();
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
	}
	
	public function testUnsubscribe()
	{
		// Array does subscribe.
		$list = new TWeakList();
		$this->subscription = new $this->_baseClass($list, item: $reference = new stdClass());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertInstanceOf(WeakReference::class, $this->subscription->getItem(true));
		self::assertEquals(0, $this->subscription->getKey());
		self::assertEquals([$reference], $list->toArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals([], $list->toArray());
			
		// TWeakList filters item from weak reference filterItemForOutput
		self::assertInstanceOf(stdClass::class, $this->subscription->getItem(true));
			
		// double unsubscribe returns false
		self::assertFalse($this->subscription->unsubscribe());
		
		// filterItemForOutput when array = null.
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals([$reference], $list->toArray());
		self::assertInstanceOf(WeakReference::class, $this->subscription->getItem(true));
		$list2 = $list;
		$list = null;
		self::assertEquals($list2, $this->subscription->getArray());
		$list2 = null;
		self::assertNull($this->subscription->getArray());
		self::assertNull($this->subscription->unsubscribe());
		self::assertInstanceOf(stdClass::class, $this->subscription->getItem(true));
		
		// $array = string/null, return null.
		$array = [];
		$this->subscription = new $this->_baseClass($array, key: 'key', item: $reference = new stdClass());
		self::assertTrue($this->subscription->getIsSubscribed());
		$array = 'not an array';
		self::assertNull($this->subscription->unsubscribe());
	}
	
	public function testUnSubscribe_array_associative()
	{
		$array = [2 => true];
		
		$this->subscription = new $this->_baseClass($array);
		
		//associative, $key === null, sets key
		$this->subscription->setKey(null)->setItem($reference = 'my value');
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals(3, $this->subscription->getKey());
		self::assertEquals([2 => true, 3 => $reference], $array);
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals([2 => true], $array);
		
		//associative, $key = 'key'
		$this->subscription->setKey('key')->setItem(5);
		self::assertTrue($this->subscription->getIsAssociative());
		$this->subscription->subscribe();
		self::assertEquals([2 => true, 'key' => 5], $array);
		$this->subscription->unsubscribe();
		self::assertEquals([2 => true], $array);
		
		//associative,   discovery of associative/list ($isAssociative = null)
		$this->subscription->setKey(0)->setItem(6)->setIsAssociative(null);
		self::assertNull($this->subscription->getIsAssociative());
		$this->subscription->subscribe();
		self::assertTrue($this->subscription->getIsAssociative());
		self::assertEquals([2 => true, 0 => 6], $array);
		$this->subscription->unsubscribe();
		
		// restore the original value.
		$this->subscription->setKey(2)->setItem(4);
		$this->subscription->subscribe();
		self::assertEquals([2 => 4], $array);
		$this->subscription->unsubscribe();
		self::assertEquals([2 => true], $array);
		
		// do not unset when not the item.
		$this->subscription->subscribe();
		self::assertEquals([2 => 4], $array);
		$array[2] = 3;
		$this->subscription->unsubscribe();
		self::assertEquals([2 => 3], $array);
	}
	
	
	public function testSubscribe_array_list()
	{
		$array = [0 => true];
		
		$this->subscription = new $this->_baseClass($array, isAssociative: false);
		
		//list, $key === null
		self::assertFalse($this->subscription->getIsAssociative());
		$this->subscription->setKey(null)->setItem($reference = 'my value');
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals([0 => true, 1 => $reference], $array);
		self::assertEquals(1, $this->subscription->getKey());
		$array[2] = $array[1];
		$array[1] = 42;
		self::assertEquals(2, $this->subscription->getKey());
		$this->subscription->unsubscribe();
		self::assertNull($this->subscription->getKey());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals([0 => true, 1 => 42], $array);
		unset($array[1]);
		
		//list, $key = 0
		$this->subscription->setKey(0)->setItem(5);
		$this->subscription->subscribe();
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals([0 => 5, 1 => true], $array);
		self::assertEquals(0, $this->subscription->getKey());
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertNull($this->subscription->getKey());
		self::assertEquals([0 => true], $array);
		
		//list,   discovery of associative/list ($isAssociative = null)
		$this->subscription->setKey(1)->setItem(6)->setIsAssociative(null);
		self::assertNull($this->subscription->getIsAssociative());
		$this->subscription->subscribe();
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(1, $this->subscription->getKey());
		self::assertEquals([0 => true, 1 => 6], $array);
		$array[1] = 5;
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertNull($this->subscription->getKey());
		self::assertEquals([0 => true, 1 => 5], $array);
	}
	
	public function testSubscribe_ArrayAccess()
	{
		$data = ['dkey' => 'data'];
		$handler = new TEventHandler([$this, 'assertEquals'], $data);
		$this->subscription = new $this->_baseClass($handler, 2, 5, isAssociative: false, autoSubscribe: false);
		
		self::assertEquals($data, $handler[2]);
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsAssociative());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals(5, $handler[2]);
		
		//unsubscribe restores.
		self::assertTrue($this->subscription->unsubscribe());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals($data, $handler[2]);
		
		// not unset when not the item.
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals(5, $handler[2]);
		$handler[2] = 6;
		self::assertTrue($this->subscription->unsubscribe());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals(6, $handler[2]);
		
		
		// key = null, throws TInvalidDataValueException
		self::expectException(TInvalidDataValueException::class);
		$this->subscription->setKey(null);
		$this->subscription->subscribe();
	}
	
	public function testSubscribe_TList()
	{
		$list = new TList([10, 20]);
		
		$this->subscription = new $this->_baseClass($list, item: 5, isAssociative: null);
		
		self::assertEquals([10, 20, 5], $list->toArray());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(2, $this->subscription->getKey());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals([10, 20], $list->toArray());
		self::assertFalse($this->subscription->getIsSubscribed());
		
		$this->subscription->setKey(0);
		self::assertTrue($this->subscription->subscribe());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertEquals(0, $this->subscription->getKey());
		self::assertEquals([5, 10, 20], $list->toArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals([10, 20], $list->toArray());
		self::assertFalse($this->subscription->getIsSubscribed());
	}
	
	public function testSubscribe_TPriorityList()
	{
		$list = new TPriorityList();
		
		$list->add(2, null);
		$list->add(3, 15);
		$list->add(1, 5);
		
		self::assertEquals([1, 2, 3], $list->toArray());
			
		$this->subscription = new $this->_baseClass($list, item: 1, priority: 12, isAssociative: null);
		
		self::assertEquals([1, 2, 1, 3], $list->toArray());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(2, $this->subscription->getKey());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals([1, 2, 3], $list->toArray());
		
		$this->subscription->setKey(1);
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals(1, $this->subscription->getKey());
		self::assertEquals(['5' => [1], '10' => [1, 2], '15' => [3]], $list->toPriorityArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals(['5' => [1], '10' => [2], '15' => [3]], $list->toPriorityArray());
		
	}
	
	public function testSubscribe_TWeakCallableCollection()
	{
		$list = new TWeakCallableCollection();
		
		$list->add($item2 = new WeakCallableInvokee(1), 10);
		$list->add($item3 = new WeakCallableInvokee(2), 15);
		$list->add($item1 = new WeakCallableInvokee(0), 5);
		
		$this->subscription = new $this->_baseClass($list, item: $item1, priority: 7, isAssociative: null);
		
		self::assertEquals(['5' => [$item1], '7' => [$item1], '10' => [$item2], '15' => [$item3]], $list->toPriorityArray());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(1, $this->subscription->getKey());
		self::assertInstanceOf(WeakReference::class, $this->subscription->getItem(true));
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertInstanceOf(WeakCallableInvokee::class, $this->subscription->getItem(true));
		self::assertEquals(['5' => [$item1], '10' => [$item2], '15' => [$item3]], $list->toPriorityArray());
		
		$this->subscription->setKey(1);
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals(1, $this->subscription->getKey());
		self::assertEquals(['5' => [$item1], '10' => [$item1, $item2], '15' => [$item3]], $list->toPriorityArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals(['5' => [$item1], '10' => [$item2], '15' => [$item3]], $list->toPriorityArray());

		self::assertTrue($this->subscription->subscribe());
		self::assertEquals(['5' => [$item1], '10' => [$item2, $item1], '15' => [$item3]], $list->toPriorityArray());
		$item1 = null;
		self::assertEquals(['10' => [$item2], '15' => [$item3]], $list->toPriorityArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(['10' => [$item2], '15' => [$item3]], $list->toPriorityArray());
	}
	
	public function testSubscribe_TWeakList()
	{
		$list = new TWeakList();
		
		$list->add($item1 = new WeakCallableInvokee(0));
		$list->add($item2 = new WeakCallableInvokee(1));
		$list->add($item3 = new WeakCallableInvokee(2));
		
		$this->subscription = new $this->_baseClass($list, item: $item1, priority: 7, isAssociative: null);
		
		self::assertEquals([$item1, $item2, $item3, $item1], $list->toArray());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(0, $this->subscription->getKey());
		self::assertInstanceOf(WeakReference::class, $this->subscription->getItem(true));
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals([$item2, $item3, $item1], $list->toArray());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertInstanceOf(WeakCallableInvokee::class, $this->subscription->getItem(true));
		
		$this->subscription->setKey(1);
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals(1, $this->subscription->getKey());
		self::assertEquals([$item2, $item1, $item3, $item1], $list->toArray());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(null, $this->subscription->getKey());
		self::assertEquals([$item2, $item3, $item1], $list->toArray());
		
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals([$item2, $item3, $item1, $item1], $list->toArray());
		$item1 = null;
		self::assertEquals([$item2, $item3], $list->toArray());
		self::assertTrue($this->subscription->unsubscribe());
	}
	
	public function testSubscribe_TMap()
	{
		$map = new TMap();
		
		$map->add(2, $item1 = new WeakCallableInvokee(2));
		$map->add('key', $item2 = new WeakCallableInvokee('key'));
		self::assertEquals(3, $map->add(null, $item3 = new WeakCallableInvokee(3)));
		
		$this->subscription = new $this->_baseClass($map, item: $item1, priority: 7, isAssociative: null);
		
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertTrue($this->subscription->getIsAssociative());
		self::assertEquals(4, $this->subscription->getKey());
		self::assertTrue($this->subscription->unsubscribe());
		
		$this->subscription->setKey('key');
		
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals($item1, $map->itemAt('key'));
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals($item2, $map->itemAt('key'));
	}
	
	public function testSubscribe_TPriorityMap()
	{
		$map = new TPriorityMap();
		
		$map->add('key3', $item3 = new WeakCallableInvokee(1), 10);
		$map->add('key4', $item4 = new WeakCallableInvokee(2), 15);
		$map->add('key1', $item1 = new WeakCallableInvokee(0), 5);
		$map->add(2, $item2 = new WeakCallableInvokee(0), 5);
		
		// TPriorityMap works.
		$this->subscription = new $this->_baseClass($map, item: $item1, priority: 7, isAssociative: null);
		
		self::assertTrue($this->subscription->getIsAssociative());
		self::assertEquals(3, $this->subscription->getKey());
		self::assertEquals($item1, $map[3]);
		self::assertEquals(7, $map->priorityAt(3));
		self::assertEquals($item1, $this->subscription->getItem());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals(3, $this->subscription->getKey());
		
		// unsubscribe restores prior value and priority
		$this->subscription->setPriority(null)->setKey('key4');
		self::assertEquals($item4, $map['key4']);
		self::assertEquals(15, $map->priorityAt('key4'));
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals($item1, $map['key4']);
		self::assertEquals(10, $map->priorityAt('key4'));
		self::assertEquals(10, $this->subscription->getPriority());
		self::assertEquals('key4', $this->subscription->getKey());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals('key4', $this->subscription->getKey());
		self::assertEquals($item4, $map['key4']);
		self::assertEquals(15, $map->priorityAt('key4')); // original item has original priority.
		
		// unsubscribe restores prior priority priority
		$this->subscription->setPriority(null)->setKey('key4')->setItem(null);
		self::assertEquals($item4, $map['key4']);
		self::assertEquals(15, $map->priorityAt('key4'));
		self::assertEquals(null, $this->subscription->getItem());
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals($item4, $map['key4']);
		self::assertEquals(10, $map->priorityAt('key4'));
		self::assertEquals(10, $this->subscription->getPriority());
		self::assertEquals('key4', $this->subscription->getKey());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals('key4', $this->subscription->getKey());
		self::assertEquals($item4, $map['key4']);
		self::assertEquals(15, $map->priorityAt('key4')); // original item has original priority.
		
		//unsubscribe doesn't restore key that is not value of item.
		$this->subscription->setPriority(20)->setKey('key4')->setItem($item1);
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals($item1, $map['key4']);
		$map['key4'] = $item2;
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals($item2, $map['key4']);
		self::assertEquals(10, $map->priorityAt('key4'));
		
	}


}
