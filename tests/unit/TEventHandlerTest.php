<?php

use Prado\Collections\IWeakRetainable;
use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TEventHandler;

class EventHandlerObject 
{
	public $called = false;
	public $data = null;
	public function __invoke($sender, $param, $data)
	{
		$this->called = true;
		$this->data = $data;
		return 1;
	}
	
	public function myHandler($sender, $param, $data)
	{
		$this->called = true;
		$this->data = $data;
	}
	
	public function myHandlerNoData($sender, $param)
	{
		$this->called = true;
		$this->data = 'noData';
	}
	
	public function resetTest()
	{
		$this->called = false;
		$this->data = null;
	}
}

class RetainableEventHandlerObject extends EventHandlerObject implements IWeakRetainable
{
}


/**
 */
class TEventHandlerTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}


	protected function tearDown(): void
	{
	}
	
	public static function myTestFunction()
	{
		return 3;
	}

	public function testConstruct()
	{
		$handler = new TEventHandler($callable = 'trim', $data = 5);
		self::assertEquals($callable, $handler->getHandler());
		self::assertFalse($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
		
		$handler = new TEventHandler($callable = [$this::class, 'myTestFunction'], $data = ['key0' => 'element0']);
		self::assertEquals($callable, $handler->getHandler());
		self::assertFalse($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
			
		$handler = new TEventHandler($callable = function() {return -1;}, $data = 8);
		self::assertEquals($callable, $handler->getHandler());
		self::assertFalse($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
		
		$handler2 = new TEventHandler($callable = [$this, 'myTestFunction'], $data = 13);
		self::assertEquals($callable, $handler2->getHandler());
		self::assertTrue($handler2->hasWeakObject());
		self::assertEquals($data, $handler2->getData());
		
		$handler = new TEventHandler($handler2, $data = 21);
		self::assertEquals($handler2, $handler->getHandler());
		self::assertTrue($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
		
		$invokable = new EventHandlerObject();
		$handler = new TEventHandler($invokable, $data = 22);
		self::assertEquals($invokable, $handler->getHandler());
		self::assertTrue($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
			
		$handler = new TEventHandler($callable = [$invokable, 'myHandler'], $data = 23);
		self::assertEquals($callable, $handler->getHandler());
		self::assertTrue($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
		
		$invokableRetainable = new RetainableEventHandlerObject();
		$handler = new TEventHandler($invokableRetainable, $data = 24);
		self::assertEquals($invokableRetainable, $handler->getHandler());
		self::assertFalse($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
			
		$handler = new TEventHandler($callable = [$invokableRetainable, 'myHandler'], $data = 25);
		self::assertEquals($callable, $handler->getHandler());
		self::assertFalse($handler->hasWeakObject());
		self::assertEquals($data, $handler->getData());
		
		self::expectException(TInvalidDataTypeException::class);
		$handler = new TEventHandler([$this, 'nonexistingMethod'], 26);
	}

	public function testPriority()
	{
		$object = new EventHandlerObject();
		$handler = new TEventHandler($object,  $referenceData = ['key1' => 'value1']);
		
		$list = new TWeakCallableCollection();
		
		self::assertEquals(null, $handler->getPriority());
		$list->add($handler, 11);
		self::assertEquals(11, $handler->getPriority());
		$list->removeAt(0);
		$handler->setPriority(5);
		$list->add($handler);
		self::assertEquals(5, $list->priorityOf($handler));
	}

	public function testInvoke()
	{
		$object = new EventHandlerObject();
		$handler = new TEventHandler($object,  $referenceData = ['key1' => 'value1']);
		self::assertNull($object->data);
		$handler();
		self::assertEquals($referenceData, $object->data);
		
		$object->resetTest();
		$object = null;
		self::expectException(TApplicationException::class);
		$handler();
	}
	
	public function testInvoke_WithoutHandlerDataParameter()
	{
		$object = new EventHandlerObject();
		$handler = new TEventHandler($refHandler = [$object, 'myHandlerNoData'],  $referenceData = ['key1' => 'value1']);
		self::assertNull($object->data);
		$handler();
		self::assertEquals('noData', $object->data);
		
		$refHandler = $object = null;
		self::expectException(TApplicationException::class);
		$handler();
	}
	
	
	public function testInvoke_WithInvokeData()
	{
		$object = new EventHandlerObject();
		$handler = new TEventHandler($object,  $referenceData = ['key1' => 'value1', 'key2' => 'value2']);
		self::assertNull($object->data);
			
		$handler(null, null);
		self::assertEquals($referenceData, $object->data);
		$handler(null, null, $overrideData = 3);
		self::assertEquals($overrideData, $object->data);
		$handler(null, null, ['key1' => 'replace1']);
		self::assertEquals(['key1'=> 'replace1', 'key2' => 'value2'], $object->data);
	}
	
	public function testGetHandler_NoObject()
	{
		$handler = new TEventHandler($refHandler = [$this::class, 'myTestFunction']);
		self::assertEquals($refHandler, $handler->getHandler());
		self::assertEquals($refHandler, $handler->getHandler(true));
	}
	
	public function testGetHandler_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertEquals($refHandler, $handler->getHandler());
		self::assertInstanceOf(WeakReference::class, $handler->getHandler(true));
			
		$refHandler = null;
		self::assertNull($handler->getHandler());
		self::assertInstanceOf(WeakReference::class, $handler->getHandler(true));
	}
	
	public function testGetHandler_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertEquals($refHandler, $handler->getHandler());
		self::assertInstanceOf(WeakReference::class, $handler->getHandler(true)[0]);
		
		$refHandler = $object = null;
		self::assertNull($handler->getHandler());
		self::assertInstanceOf(WeakReference::class, $handler->getHandler(true)[0]);
	}
	
	public function testIsSameHandler()
	{
		$handler1 = new TEventHandler($refHandler = new EventHandlerObject());
		$handler2 = new TEventHandler($handler1);
		
		self::assertFalse($handler1->isSameHandler($handler2));
		self::assertTrue($handler1->isSameHandler($refHandler));
		self::assertTrue($handler2->isSameHandler($handler1));
		self::assertTrue($handler2->isSameHandler($refHandler));
	}
	
	
	public function testGetHandlerObject_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertEquals($refHandler, $handler->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler->getHandlerObject(true));
		$refHandler = null;
		self::assertNull($handler->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertEquals($object, $handler->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler->getHandlerObject(true));
		$refHandler = $object = null;
		self::assertNull($handler->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_Closure()
	{
		$handler = new TEventHandler($refClosure = function($sender, $param, $data) {return $data;});
		self::assertEquals($refClosure, $handler->getHandlerObject());
		self::assertEquals($refClosure, $handler->getHandlerObject(true));
		$refClosure = null;
		self::assertInstanceOf(Closure::class, $handler->getHandlerObject());
		self::assertInstanceOf(Closure::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_Retainable()
	{
		$handler = new TEventHandler($refHandler = new RetainableEventHandlerObject());
		self::assertEquals($refHandler, $handler->getHandlerObject());
		self::assertEquals($refHandler, $handler->getHandlerObject(true));
		self::assertInstanceOf(RetainableEventHandlerObject::class, $handler->getHandlerObject());
		$refHandler = null;
		self::assertInstanceOf(RetainableEventHandlerObject::class, $handler->getHandlerObject());
		self::assertInstanceOf(RetainableEventHandlerObject::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_TEventHandler()
	{
		$handler1 = new TEventHandler($refHandler = [$object1 = new EventHandlerObject(), 'myHandler'], 9);
		$handler2 = new TEventHandler($handler1);
		
		self::assertEquals($object1, $handler1->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler1->getHandlerObject(true));
		self::assertEquals($object1, $handler2->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler2->getHandlerObject(true));
		
		$refHandler = $object1 = null;
		
		self::assertNull($handler1->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler1->getHandlerObject(true));
		self::assertNull($handler2->getHandlerObject());
		self::assertInstanceOf(WeakReference::class, $handler2->getHandlerObject(true));
	}
	
	
	public function testHasHandler_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertTrue($handler->hasHandler());
		$refHandler = null;
		self::assertFalse($handler->hasHandler());
	}
	
	public function testHasHandler_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertTrue($handler->hasHandler());
		
		$refHandler = $object = null;
		self::assertFalse($handler->hasHandler());
	}
	
	public function testHasHandler_TEventHandler()
	{
		$handler1 = new TEventHandler($refHandler = [$object1 = new EventHandlerObject(), 'myHandler'], 9);
		$handler2 = new TEventHandler($handler1);
		
		self::assertTrue($handler1->hasHandler());
		self::assertTrue($handler2->hasHandler());
		
		$refHandler = $object1 = null;
		
		self::assertFalse($handler1->hasHandler());
		self::assertFalse($handler2->hasHandler());
	}
	
	public function testData()
	{
		$handler = new TEventHandler(new EventHandlerObject(), 3);
		self::assertEquals(3, $handler->getData());
		$handler->setData([5]);
		self::assertEquals([5], $handler->getData());
	}
	
	public function testData_TEventHandler_arrayData()
	{
		$handler1 = new TEventHandler($refHandler = [$object1 = new EventHandlerObject(), 'myHandler'], $refdata1 = ['key1' => 'value1', 'key2' => 'value2']);
		$handler2 = new TEventHandler($handler1, $refData2 = ['key1' => 'replace1', 'key3' => 'replace3']);
		$handler3 = new TEventHandler($handler2, $refData3 = ['key1' => 'new1', 'key4' => 'new4']);
		
		self::assertEquals($refdata1, $handler1->getData());
		self::assertEquals($refdata1, $handler1->getData(true));
		self::assertEquals($refData2, $handler2->getData());
		self::assertEquals(['key1' => 'replace1', 'key2' => 'value2', 'key3' => 'replace3'], $handler2->getData(true));
		self::assertEquals($refData3, $handler3->getData());
		self::assertEquals(['key1' => 'new1', 'key2' => 'value2', 'key3' => 'replace3', 'key4' => 'new4'], $handler3->getData(true));
	}
	
	public function testHasWeakObject_NoObject()
	{
		$handler = new TEventHandler($refHandler = [$this::class, 'myTestFunction']);
		self::assertFalse($handler->hasWeakObject());
	}
	
	public function testHasWeakObject_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertTrue($handler->hasWeakObject());
	}
	
	public function testHasWeakObject_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertTrue($handler->hasWeakObject());
	}
	
	public function testHasWeakObject_NoClosure()
	{
		$handler = new TEventHandler($refHandler = function($sender, $param, $data) {} );
		self::assertFalse($handler->hasWeakObject());
	}
	
	public function testHasWeakObject_NoIWeakRetainable()
	{
		$handler = new TEventHandler($refHandler = new RetainableEventHandlerObject());
		self::assertFalse($handler->hasWeakObject());
	}
	
	public function testHasWeakObject_TEventHandler()
	{
		$handler1 = new TEventHandler($refHandler = new EventHandlerObject());
		$handler2 = new TEventHandler($handler1);
		self::assertTrue($handler1->hasWeakObject());
		self::assertTrue($handler2->hasWeakObject());
			
		$handler1 = new TEventHandler($refHandler = new RetainableEventHandlerObject());
		$handler2 = new TEventHandler($handler1);
		self::assertFalse($handler1->hasWeakObject());
		self::assertFalse($handler2->hasWeakObject());
	}
	
	public function testGetCount_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertEquals(2, $handler->getCount());
		self::assertEquals(2, $handler->count());
	}
	
	public function testGetCount_ArrayObject()
	{
		$handler = new TEventHandler([$object = new EventHandlerObject(), 'myHandler']);
		self::assertEquals(3, $handler->getCount());
		self::assertEquals(3, $handler->count());
	}
	
	public function testOffsetExists_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject());
		self::assertTrue($handler->offsetExists(null));
		self::assertTrue($handler->offsetExists(0));
		self::assertFalse($handler->offsetExists(1));
		self::assertTrue($handler->offsetExists(2));
		self::assertFalse($handler->offsetExists(3));
	}
	
	public function testOffsetExists_ArrayObject()
	{
		$handler = new TEventHandler([$object = new EventHandlerObject(), 'myHandler']);
		self::assertTrue($handler->offsetExists(null));
		self::assertTrue($handler->offsetExists(0));
		self::assertTrue($handler->offsetExists(1));
		self::assertTrue($handler->offsetExists(2));
		self::assertFalse($handler->offsetExists(3));
	}
	
	public function testOffsetGet_Invokable()
	{
		$handler = new TEventHandler($refHandler = new EventHandlerObject(), $refData = [8]);
		self::assertEquals($refHandler, $handler->offsetGet(null));
		self::assertEquals($refHandler, $handler->offsetGet(0));
		self::assertNull($handler->offsetGet(1));
		self::assertEquals($refData, $handler->offsetGet(2));
		
		$refHandler = null;
		
		self::assertNull($handler->offsetGet(null));
		self::assertNull($handler->offsetGet(0));
		self::assertNull($handler->offsetGet(1));
		self::assertEquals($refData, $handler->offsetGet(2));
			
		self::expectException(TInvalidDataValueException::class);
		self::assertFalse($handler->offsetGet(3));
	}
	
	public function testOffsetGet_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), $refMethod = 'myHandler'], $refData = [1, 2, 3, 5, 8, 13]);
		self::assertEquals($refHandler, $handler->offsetGet(null));
		self::assertEquals($object, $handler->offsetGet(0));
		self::assertEquals($refMethod, $handler->offsetGet(1));
		self::assertEquals($refData, $handler->offsetGet(2));
		
		$refHandler = $object = null;
		
		self::assertNull($handler->offsetGet(null));
		self::assertNull($handler->offsetGet(0));
		self::assertNull($handler->offsetGet(1));
		self::assertEquals($refData, $handler->offsetGet(2));
			
		self::expectException(TInvalidDataValueException::class);
		self::assertFalse($handler->offsetGet(3));
	}
	
	public function testOffsetSet()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), $refMethod = 'myHandler'], $refData = [2, 3, 5, 8, 13, 21]);
		try {
			$handler[null] = null;
			self::fail("Failed to throw TInvalidOperationException when improperly setting the handler.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$handler[0] = null;
			self::fail("Failed to throw TInvalidOperationException when improperly setting the handler [0].");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$handler[1] = null;
			self::fail("Failed to throw TInvalidOperationException when improperly setting the handler [1].");
		} catch(TInvalidOperationException $e) {}
		
		self::assertEquals($refData, $handler[2]);
		$handler[2] = $refData = '#888888';
		self::assertEquals($refData, $handler[2]);
		
		try {
			$handler[3] = null;
			self::fail("Failed to throw TInvalidDataValueException when improperly setting the handler [3].");
		} catch(TInvalidDataValueException $e) {}
	}
	
	public function testOffsetUnset()
	{
		
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), $refMethod = 'myHandler'], $refData = [2, 3, 5, 8, 13, 21]);
		try {
			unset($handler[null]);
			self::fail("Failed to throw TInvalidOperationException when improperly unset the handler.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			unset($handler[0]);
			self::fail("Failed to throw TInvalidOperationException when improperly unset the handler [0].");
		} catch(TInvalidOperationException $e) {}
		
		try {
			unset($handler[1]);
			self::fail("Failed to throw TInvalidOperationException when improperly unset the handler [1].");
		} catch(TInvalidOperationException $e) {}
		
		self::assertEquals($refData, $handler[2]);
		unset($handler[2]);
		self::assertNull($handler[2]);
		
		try {
			$handler[3] = null;
			self::fail("Failed to throw TInvalidDataValueException when improperly unset the handler [3].");
		} catch(TInvalidDataValueException $e) {}
	}
	
}
