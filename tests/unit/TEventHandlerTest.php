<?php

namespace Prado\Test\Unit;

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
class TEventHandlerTest extends \PHPUnit\Framework\TestCase
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
		self::assertInstanceOf(\WeakReference::class, $handler->getHandler(true));
			
		$refHandler = null;
		self::assertNull($handler->getHandler());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandler(true));
	}
	
	public function testGetHandler_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertEquals($refHandler, $handler->getHandler());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandler(true)[0]);
		
		$refHandler = $object = null;
		self::assertNull($handler->getHandler());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandler(true)[0]);
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
		self::assertInstanceOf(\WeakReference::class, $handler->getHandlerObject(true));
		$refHandler = null;
		self::assertNull($handler->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_ArrayObject()
	{
		$handler = new TEventHandler($refHandler = [$object = new EventHandlerObject(), 'myHandler']);
		self::assertEquals($object, $handler->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandlerObject(true));
		$refHandler = $object = null;
		self::assertNull($handler->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler->getHandlerObject(true));
	}
	
	public function testGetHandlerObject_Closure()
	{
		$handler = new TEventHandler($refClosure = function($sender, $param, $data) {return $data;});
		self::assertEquals($refClosure, $handler->getHandlerObject());
		self::assertEquals($refClosure, $handler->getHandlerObject(true));
		$refClosure = null;
		self::assertInstanceOf(\Closure::class, $handler->getHandlerObject());
		self::assertInstanceOf(\Closure::class, $handler->getHandlerObject(true));
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
		self::assertInstanceOf(\WeakReference::class, $handler1->getHandlerObject(true));
		self::assertEquals($object1, $handler2->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler2->getHandlerObject(true));
		
		$refHandler = $object1 = null;
		
		self::assertNull($handler1->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler1->getHandlerObject(true));
		self::assertNull($handler2->getHandlerObject());
		self::assertInstanceOf(\WeakReference::class, $handler2->getHandlerObject(true));
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
			unset($handler[3]);
			self::fail("Failed to throw TInvalidDataValueException when improperly unset the handler [3].");
		} catch(TInvalidDataValueException $e) {}
	}

	public function testOffsetGet_StrongHandlers()
	{
		$static = new TEventHandler([\DateTime::class, 'createFromFormat']);
		self::assertSame(\DateTime::class, $static[0]);
		self::assertSame('createFromFormat', $static[1]);

		$closure = function ($sender, $param) {};
		$handler = new TEventHandler($closure);
		self::assertSame($closure, $handler[0]);
		self::assertNull($handler[1]);
	}

	/**
	 * Every offset paired with the item it addresses: null for the handler, 0 or 1 for
	 * a handler element, 2 for the data, and false for no item; for each handler kind.
	 */
	public static function offsetProvider(): array
	{
		$offsets = [
			'null' => [null, null],
			'0' => [0, 0],
			'1' => [1, 1],
			'2' => [2, 2],
			"'0'" => ['0', 0],
			"'1'" => ['1', 1],
			"'2'" => ['2', 2],
			'0.0' => [0.0, 0],
			'1.0' => [1.0, 1],
			'2.0' => [2.0, 2],
			'2.9' => [2.9, 2],
			'1.5' => [1.5, 1],
			'-0.5' => [-0.5, 0],
			"'2.9'" => ['2.9', 2],
			"' 2'" => [' 2', 2],
			'3' => [3, false],
			'-1' => [-1, false],
			'3.0' => [3.0, false],
			'-1.0' => [-1.0, false],
			"'3'" => ['3', false],
			"'-1'" => ['-1', false],
			"'x'" => ['x', false],
			"'2x'" => ['2x', false],
			"''" => ['', false],
			'true' => [true, false],
			'false' => [false, false],
			'NAN' => [NAN, false],
			'INF' => [INF, false],
			'-INF' => [-INF, false],
			'1.0E+30' => [1.0E+30, false],
			'2 ** 64' => [2.0 ** 64, false],
		];
		$cases = [];
		foreach (['array', 'invokable', 'function'] as $kind) {
			foreach ($offsets as $name => [$offset, $item]) {
				$cases["$kind handler, offset $name"] = [$kind, $offset, $item];
			}
		}
		return $cases;
	}

	/**
	 * Builds a TEventHandler of the kind and the value each existing item reads as.
	 * @return array The TEventHandler, the values keyed by item, and the handler object
	 *   to keep referenced.
	 */
	private function newOffsetHandler(string $kind, mixed $data): array
	{
		$object = new EventHandlerObject();
		if ($kind === 'array') {
			$values = ['handler' => [$object, 'myHandler'], 0 => $object, 1 => 'myHandler', 2 => $data];
		} elseif ($kind === 'invokable') {
			$values = ['handler' => $object, 0 => $object, 2 => $data];
		} else {
			$values = ['handler' => 'trim', 0 => 'trim', 2 => $data];
		}
		return [new TEventHandler($values['handler'], $data), $values, $object];
	}

	/** @dataProvider offsetProvider */
	public function testOffsetExists_AgreesWithOffsetGet(string $kind, mixed $offset, null|false|int $item)
	{
		[$handler, $values, $object] = $this->newOffsetHandler($kind, $refData = ['key' => 'value']);
		$key = $item ?? 'handler';
		$exists = $item !== false && array_key_exists($key, $values);

		self::assertSame($exists, $handler->offsetExists($offset));
		self::assertSame($exists, isset($handler[$offset]));
		self::assertSame(!$exists, empty($handler[$offset]));

		if ($exists) {
			self::assertSame($values[$key], $handler->offsetGet($offset));
			self::assertSame($values[$key], $handler[$offset]);
			self::assertSame($values[$key], $handler[$offset] ?? 'default');
		} else {
			self::assertSame('default', $handler[$offset] ?? 'default');
			if ($item === 1) {
				self::assertNull($handler[$offset]);
			} else {
				try {
					$handler[$offset];
					self::fail("Failed to throw TInvalidDataValueException when getting an offset that does not exist.");
				} catch (TInvalidDataValueException $e) {
				}
			}
		}
		self::assertSame($refData, $handler->getData());
	}

	/**
	 * An offset exists while its value is null: the data is null and the handler object
	 * is collected.
	 * @dataProvider offsetProvider
	 */
	public function testOffsetExists_NullValues(string $kind, mixed $offset, null|false|int $item)
	{
		[$handler, $values, $object] = $this->newOffsetHandler($kind, null);
		$exists = $item !== false && array_key_exists($item ?? 'handler', $values);
		$value = ($kind === 'function' && $exists && $item !== 2) ? 'trim' : null;
		$object = $values = null;

		self::assertSame($exists, $handler->offsetExists($offset));
		self::assertSame($exists, isset($handler[$offset]));
		self::assertSame($value === null, empty($handler[$offset]));
		self::assertSame($value ?? 'default', $handler[$offset] ?? 'default');
		if ($exists) {
			self::assertSame($value, $handler[$offset]);
		}
	}

	/** @dataProvider offsetProvider */
	public function testOffsetSet_AgreesWithOffsetGet(string $kind, mixed $offset, null|false|int $item)
	{
		[$handler, $values, $object] = $this->newOffsetHandler($kind, $refData = ['key' => 'value']);

		if ($item === 2) {
			$handler[$offset] = $refData = 'new data';
			self::assertSame($refData, $handler[$offset]);
		} else {
			try {
				$handler[$offset] = 'new data';
				self::fail("Failed to throw when setting an offset that is not the data.");
			} catch (TInvalidOperationException $e) {
				self::assertNotFalse($item, "A handler offset throws TInvalidOperationException.");
			} catch (TInvalidDataValueException $e) {
				self::assertFalse($item, "An offset of no item throws TInvalidDataValueException.");
			}
		}
		self::assertSame($refData, $handler->getData());
		self::assertSame($values['handler'], $handler->getHandler());
	}

	/** @dataProvider offsetProvider */
	public function testOffsetUnset_AgreesWithOffsetGet(string $kind, mixed $offset, null|false|int $item)
	{
		[$handler, $values, $object] = $this->newOffsetHandler($kind, $refData = ['key' => 'value']);

		if ($item === 2) {
			unset($handler[$offset]);
			$refData = null;
			self::assertTrue(isset($handler[$offset]));
			self::assertNull($handler[$offset]);
		} else {
			try {
				unset($handler[$offset]);
				self::fail("Failed to throw when unsetting an offset that is not the data.");
			} catch (TInvalidOperationException $e) {
				self::assertNotFalse($item, "A handler offset throws TInvalidOperationException.");
			} catch (TInvalidDataValueException $e) {
				self::assertFalse($item, "An offset of no item throws TInvalidDataValueException.");
			}
		}
		self::assertSame($refData, $handler->getData());
		self::assertSame($values['handler'], $handler->getHandler());
  }

	public function testOffsetGet_NonNumericOffset_Throws()
	{
		$handler = new TEventHandler([$object = new EventHandlerObject(), 'myHandler'], $refData = ['key1' => 'value1']);

		try {
			$handler->offsetGet('handler');
			self::fail('offsetGet() did not throw for a non-numeric offset.');
		} catch (TInvalidDataValueException $e) {
			self::assertEquals('eventhandler_bad_offset', $e->getErrorCode());
			self::assertStringContainsString('"handler"', $e->getMessage());
		}
		self::assertSame($refData, $handler[2]);
	}

	public function testInvoke_ArrayInvokeData_ScalarHandlerData_PassesInvokeData()
	{
		$object = new EventHandlerObject();
		$handler = new TEventHandler($object, 'scalar');

		self::assertEquals(1, $handler(null, null, $invokeData = ['key1' => 'invoke1']));
		self::assertSame($invokeData, $object->data);
		self::assertSame('scalar', $handler->getData());
	}

	public function testData_TEventHandler_ScalarChildData_IsNotCombined()
	{
		$handler1 = new TEventHandler([$object = new EventHandlerObject(), 'myHandler'], ['key1' => 'value1']);
		$handler2 = new TEventHandler($handler1, 'scalar');

		self::assertSame('scalar', $handler2->getData(true));
		self::assertSame('scalar', $handler2->getData());
	}

	public function testData_TEventHandler_ScalarParentData_IsNotCombined()
	{
		$handler1 = new TEventHandler([$object = new EventHandlerObject(), 'myHandler'], 'scalar');
		$handler2 = new TEventHandler($handler1, $refData2 = ['key1' => 'replace1']);
		$handler3 = new TEventHandler($handler2, $refData3 = ['key3' => 'new3']);

		self::assertSame('scalar', $handler1->getData(true));
		self::assertSame($refData2, $handler2->getData(true));
		self::assertSame(['key1' => 'replace1', 'key3' => 'new3'], $handler3->getData(true));
	}
}
