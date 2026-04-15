<?php

use Prado\TEventParameter;
use Prado\Collections\TAttributeCollection;
use PHPUnit\Framework\TestCase;

class TEventParameterTest extends TestCase
{
	// ================================================================================
	// Constructor Tests
	// ================================================================================

	public function testDefaultConstructor()
	{
		$param = new TEventParameter();
		$this->assertNull($param->getParameter());
		$this->assertEquals('', $param->getEventName());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithParameter()
	{
		$param = new TEventParameter('test value');
		$this->assertEquals('test value', $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithNullParameter()
	{
		$param = new TEventParameter(null);
		$this->assertNull($param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithArrayParameter()
	{
		$data = ['key1' => 'value1', 'key2' => 'value2'];
		$param = new TEventParameter($data);
		$this->assertEquals($data, $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$this->assertEquals(42, $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithBooleanParameter()
	{
		$param = new TEventParameter(true);
		$this->assertTrue($param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithObjectParameter()
	{
		$obj = new stdClass();
		$obj->key = 'value';
		$param = new TEventParameter($obj);
		$this->assertSame($obj, $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testConstructorWithEmptyArray()
	{
		$param = new TEventParameter([]);
		$this->assertEquals([], $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	// ================================================================================
	// EventName Property Tests
	// ================================================================================

	public function testGetSetEventName()
	{
		$param = new TEventParameter();
		$param->setEventName('testEvent');
		$this->assertEquals('testEvent', $param->getEventName());
	}

	public function testEventNameDefaultIsEmpty()
	{
		$param = new TEventParameter();
		$this->assertEquals('', $param->getEventName());
	}

	public function testEventNameCanBeEmpty()
	{
		$param = new TEventParameter();
		$param->setEventName('');
		$this->assertEquals('', $param->getEventName());
	}

	// ================================================================================
	// Parameter Property Tests
	// ================================================================================

	public function testGetSetParameter()
	{
		$param = new TEventParameter();
		$param->setParameter('test');
		$this->assertEquals('test', $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToNull()
	{
		$param = new TEventParameter('initial');
		$param->setParameter(null);
		$this->assertNull($param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToArray()
	{
		$param = new TEventParameter();
		$param->setParameter(['a' => 1, 'b' => 2]);
		$this->assertEquals(['a' => 1, 'b' => 2], $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToZero()
	{
		$param = new TEventParameter();
		$param->setParameter(0);
		$this->assertEquals(0, $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToFalse()
	{
		$param = new TEventParameter();
		$param->setParameter(false);
		$this->assertFalse($param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToEmptyString()
	{
		$param = new TEventParameter();
		$param->setParameter('');
		$this->assertEquals('', $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterToNullNoChange()
	{
		$param = new TEventParameter();
		$param->setParameter(null);
		$this->assertEquals(null, $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testSetParameterInitialArrayToNull()
	{
		$param = new TEventParameter([]);
		$param->setParameter(null);
		$this->assertEquals(null, $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	// ================================================================================
	// ArrayAccess with Array Parameter Tests
	// ================================================================================

	public function testOffsetExistsWithArray()
	{
		$param = new TEventParameter(['key1' => 'value1', 'key2' => 'value2']);
		$this->assertTrue($param->offsetExists('key1'));
		$this->assertTrue($param->offsetExists('key2'));
		$this->assertFalse($param->offsetExists('nonexistent'));
	}

	public function testOffsetGetWithArray()
	{
		$param = new TEventParameter(['key1' => 'value1', 'key2' => 'value2']);
		$this->assertEquals('value1', $param->offsetGet('key1'));
		$this->assertEquals('value2', $param->offsetGet('key2'));
	}

	public function testOffsetGetWithNonexistentKey()
	{
		$param = new TEventParameter(['key1' => 'value1']);
		$this->assertNull($param->offsetGet('nonexistent'));
	}

	public function testOffsetSetWithArray()
	{
		$param = new TEventParameter(['initial' => 'value']);
		$param->offsetSet('newKey', 'newValue');
		$this->assertEquals('newValue', $param->getParameter()['newKey']);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetUnsetWithArray()
	{
		$param = new TEventParameter(['key1' => 'value1', 'key2' => 'value2']);
		$param->offsetUnset('key1');
		$this->assertFalse($param->offsetExists('key1'));
		$this->assertTrue($param->offsetExists('key2'));
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetUnsetWithArrayNoKey()
	{
		$param = new TEventParameter(['key2' => 'value2']);
		$param->resetParameterChanged();
		$param->offsetUnset('key1');
		$this->assertFalse($param->offsetExists('key1'));
		$this->assertTrue($param->offsetExists('key2'));
		$this->assertFalse($param->getParameterChanged());
	}

	// ================================================================================
	// ArrayAccess with Non-Array Parameter Tests
	// ================================================================================

	public function testOffsetExistsWithStringParameter()
	{
		$param = new TEventParameter('string value');
		$this->assertFalse($param->offsetExists('anyKey'));
	}

	public function testOffsetExistsWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$this->assertFalse($param->offsetExists(0));
	}

	public function testOffsetExistsWithNullParameter()
	{
		$param = new TEventParameter(null);
		$this->assertFalse($param->offsetExists('anyKey'));
	}

	public function testOffsetExistsWithObjectParameter()
	{
		$param = new TEventParameter(new stdClass());
		$this->assertFalse($param->offsetExists('anyKey'));
	}

	public function testOffsetGetWithStringParameter()
	{
		$param = new TEventParameter('string value');
		$this->assertNull($param->offsetGet('anyKey'));
	}

	public function testOffsetGetWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$this->assertNull($param->offsetGet(0));
	}

	public function testOffsetGetWithNullParameter()
	{
		$param = new TEventParameter(null);
		$this->assertNull($param->offsetGet('anyKey'));
	}

	public function testOffsetSetWithStringParameter()
	{
		$param = new TEventParameter('original');
		$param->offsetSet('key', 'value');
		$this->assertEquals('original', $param->getParameter());
	}

	public function testOffsetSetWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$param->offsetSet('key', 'value');
		$this->assertEquals(42, $param->getParameter());
	}

	public function testOffsetSetWithNullParameter()
	{
		$param = new TEventParameter(null);
		$param->offsetSet('key', 'value');
		$this->assertEquals(['key' => 'value'], $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetUnsetWithStringParameter()
	{
		$param = new TEventParameter('string value');
		$param->offsetUnset('key');
		$this->assertEquals('string value', $param->getParameter());
	}

	public function testOffsetUnsetWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$param->offsetUnset('key');
		$this->assertEquals(42, $param->getParameter());
	}

	// ================================================================================
	// ArrayAccess with ArrayAccess Object Tests
	// ================================================================================

	public function testOffsetExistsWithArrayAccessObject()
	{
		$collection = new TAttributeCollection();
		$collection['key1'] = 'value1';
		$collection['key2'] = 'value2';

		$param = new TEventParameter($collection);
		$this->assertTrue($param->offsetExists('key1'));
		$this->assertTrue($param->offsetExists('key2'));
		$this->assertFalse($param->offsetExists('nonexistent'));
	}

	public function testOffsetGetWithArrayAccessObject()
	{
		$collection = new TAttributeCollection();
		$collection['key1'] = 'value1';

		$param = new TEventParameter($collection);
		$this->assertEquals('value1', $param->offsetGet('key1'));
		$this->assertNull($param->offsetGet('nonexistent'));
	}

	public function testOffsetSetWithArrayAccessObject()
	{
		$collection = new TAttributeCollection();
		$param = new TEventParameter($collection);
		$param->offsetSet('newKey', 'newValue');
		$this->assertEquals('newValue', $param->offsetGet('newKey'));
		$this->assertTrue($param->getParameterChanged());
	}

	// Note: offsetUnset does NOT check for ArrayAccess, only is_array
	// So unsetting on an ArrayAccess object has no effect

	// ================================================================================
	// Edge Cases for Array Values
	// ================================================================================

	public function testOffsetSetWithNumericStringKey()
	{
		$param = new TEventParameter([]);
		$param->offsetSet('0', 'first');
		$this->assertEquals('first', $param->getParameter()['0']);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetGetWithNullKey()
	{
		$param = new TEventParameter([null => 'nullKey']);
		$this->assertEquals('nullKey', $param->offsetGet(null));
	}

	public function testOffsetExistsWithNullKey()
	{
		$param = new TEventParameter([null => 'nullKey']);
		$this->assertTrue($param->offsetExists(null));
	}

	public function testOffsetSetOverwritesExisting()
	{
		$param = new TEventParameter(['key' => 'original']);
		$param->offsetSet('key', 'overwritten');
		$this->assertEquals('overwritten', $param->getParameter()['key']);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetSetAddsNewKey()
	{
		$param = new TEventParameter(['existing' => 'value']);
		$param->offsetSet('new', 'newValue');
		$this->assertEquals('value', $param->getParameter()['existing']);
		$this->assertEquals('newValue', $param->getParameter()['new']);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testOffsetUnsetWithNonexistentKey()
	{
		$param = new TEventParameter(['key1' => 'value1']);
		$param->offsetUnset('nonexistent');
		$this->assertTrue($param->offsetExists('key1'));
		$this->assertEquals('value1', $param->getParameter()['key1']);
	}

	public function testOffsetUnsetWithNullKey()
	{
		$param = new TEventParameter([null => 'nullValue', 'key1' => 'value1']);
		$param->offsetUnset(null);
		$this->assertFalse($param->offsetExists(null));
		$this->assertTrue($param->offsetExists('key1'));
		$this->assertTrue($param->getParameterChanged());
	}

	// ================================================================================
	// IEventParameter Interface Tests
	// ================================================================================

	public function testImplementsIEventParameter()
	{
		$param = new TEventParameter();
		$this->assertInstanceOf(\Prado\IEventParameter::class, $param);
	}

	public function testImplementsArrayAccess()
	{
		$param = new TEventParameter();
		$this->assertInstanceOf(ArrayAccess::class, $param);
	}

	// ================================================================================
	// Complex Scenarios
	// ================================================================================

	public function testChainedSetOperations()
	{
		$param = new TEventParameter();
		$param->setEventName('TestEvent');
		$param->setParameter(['key' => 'value']);

		$this->assertEquals('TestEvent', $param->getEventName());
		$this->assertEquals(['key' => 'value'], $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
	}

	public function testModifyArrayViaOffsetAndRetrieve()
	{
		$param = new TEventParameter(['initial' => 'value']);
		$param->offsetSet('added', 'new');
		$this->assertEquals('new', $param->offsetGet('added'));
		$this->assertEquals('value', $param->offsetGet('initial'));
		$this->assertTrue($param->getParameterChanged());
	}

	public function testEmptyArrayHandling()
	{
		$param = new TEventParameter([]);
		$this->assertFalse($param->offsetExists('any'));
		$param->offsetSet('key', 'value');
		$this->assertTrue($param->offsetExists('key'));
	}

	public function testFalseAndNullDistinction()
	{
		$param = new TEventParameter(false);
		$this->assertFalse($param->offsetExists('key'));

		$param->setParameter(null);
		$this->assertFalse($param->offsetExists('key'));
	}

	public function testZeroAndEmptyStringDistinction()
	{
		$param = new TEventParameter(0);
		$this->assertFalse($param->offsetExists('key'));

		$param->setParameter('');
		$this->assertFalse($param->offsetExists('key'));
	}

	public function testConstructorSetsParameterNotEventName()
	{
		$param = new TEventParameter('parameterValue');
		$this->assertEquals('parameterValue', $param->getParameter());
		$this->assertEquals('', $param->getEventName());
	}

	public function testIntegerAsArrayIndex()
	{
		$param = new TEventParameter([5 => 'five', 10 => 'ten']);
		$this->assertEquals('five', $param->offsetGet(5));
		$this->assertEquals('ten', $param->offsetGet(10));
		$this->assertTrue($param->offsetExists(5));
		$this->assertTrue($param->offsetExists(10));
	}

	public function testMixedArrayKeys()
	{
		$param = new TEventParameter([
			'string' => 'stringValue',
			0 => 'zeroValue',
			null => 'nullValue',
			'bool' => true,
		]);

		$this->assertEquals('stringValue', $param->offsetGet('string'));
		$this->assertEquals('zeroValue', $param->offsetGet(0));
		$this->assertEquals('nullValue', $param->offsetGet(null));
		$this->assertEquals(true, $param->offsetGet('bool'));
	}

	// ================================================================================
	// ParameterChanged Manual Set Tests
	// ================================================================================

	public function testSetParameterChangedToTrue()
	{
		$param = new TEventParameter();
		$this->assertFalse($param->getParameterChanged());
		$param->setParameterChanged(true);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterChangedIsOneWay()
	{
		$param = new TEventParameter();
		$param->setParameterChanged(true);
		$this->assertTrue($param->getParameterChanged());
		$param->setParameterChanged(false);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testSetParameterChangedFalseToTrue()
	{
		$param = new TEventParameter();
		$this->assertFalse($param->getParameterChanged());
		$param->setParameterChanged(true);
		$this->assertTrue($param->getParameterChanged());
		$param->setParameterChanged(true);
		$this->assertTrue($param->getParameterChanged());
	}

	public function testResetParameterChanged()
	{
		$param = new TEventParameter();
		$param->setParameterChanged(true);
		$this->assertTrue($param->getParameterChanged());
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testResetParameterChangedWhenNotChanged()
	{
		$param = new TEventParameter();
		$this->assertFalse($param->getParameterChanged());
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testResetParameterChangedAfterOffsetSet()
	{
		$param = new TEventParameter(['key' => 'value']);
		$param->offsetSet('newKey', 'newValue');
		$this->assertTrue($param->getParameterChanged());
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testResetParameterChangedAfterOffsetUnset()
	{
		$param = new TEventParameter(['key' => 'value']);
		$param->offsetUnset('key');
		$this->assertTrue($param->getParameterChanged());
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testResetParameterChangedAfterSetParameter()
	{
		$param = new TEventParameter('initial');
		$param->setParameter('changed');
		$this->assertTrue($param->getParameterChanged());
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	// ================================================================================
	// EventName Resets ParameterChanged Tests
	// ================================================================================

	public function testSetEventNameDoesNotAffectParameter()
	{
		$param = new TEventParameter('original');
		$param->setEventName('TestEvent');
		$this->assertEquals('original', $param->getParameter());
		$this->assertEquals('TestEvent', $param->getEventName());
	}

	public function testSetEventNameWithNoPriorChange()
	{
		$param = new TEventParameter();
		$param->setEventName('Event');
		$this->assertFalse($param->getParameterChanged());
		$this->assertEquals('Event', $param->getEventName());
	}

	public function testSetEventNameWithPriorParameterChanged()
	{
		$param = new TEventParameter();
		$param->setParameter('value');
		$this->assertTrue($param->getParameterChanged());
		$param->setEventName('TestEvent');
		$this->assertFalse($param->getParameterChanged());
		$this->assertEquals('TestEvent', $param->getEventName());
	}

	// ================================================================================
	// ParameterHasChanged Tests
	// ================================================================================

	public function testgetParameterChangedDefault()
	{
		$param = new TEventParameter();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedConstructor()
	{
		$param = new TEventParameter('value');
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedConstructorNull()
	{
		$param = new TEventParameter(null);
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedWithArrayParameter()
	{
		$param = new TEventParameter(['a' => 1]);
		$param->resetParameterChanged();
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedAfterSetParameter()
	{
		$param = new TEventParameter();
		$param->setParameter('value');
		$this->assertTrue($param->getParameterChanged());
	}

	public function testgetParameterChangedWithSameValue()
	{
		$param = new TEventParameter('value');
		$param->resetParameterChanged();
		$param->setParameter('value');
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedWithDifferentValue()
	{
		$param = new TEventParameter('initial');
		$param->setParameter('changed');
		$this->assertTrue($param->getParameterChanged());
	}

	public function testgetParameterChangedAfterOffsetSet()
	{
		$param = new TEventParameter(['key' => 'value']);
		$param->offsetSet('newKey', 'newValue');
		$this->assertTrue($param->getParameterChanged());
	}

	public function testgetParameterChangedAfterOffsetSetSameValue()
	{
		$param = new TEventParameter(['key' => 'value']);
		$param->resetParameterChanged();
		$param->offsetSet('key', 'value');
		$this->assertFalse($param->getParameterChanged());
	}

	public function testgetParameterChangedAfterOffsetUnset()
	{
		$param = new TEventParameter(['key' => 'value']);
		$param->offsetUnset('key');
		$this->assertTrue($param->getParameterChanged());
	}

	// ================================================================================
	// ParameterIsArray Tests
	// ================================================================================

	public function testGetParameterIsArrayWithArray()
	{
		$param = new TEventParameter(['key' => 'value']);
		$this->assertTrue($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithArrayAccess()
	{
		$collection = new TAttributeCollection();
		$collection['key'] = 'value';
		$param = new TEventParameter($collection);
		$this->assertTrue($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithNull()
	{
		$param = new TEventParameter(null);
		$this->assertFalse($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithString()
	{
		$param = new TEventParameter('string');
		$this->assertFalse($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithInteger()
	{
		$param = new TEventParameter(42);
		$this->assertFalse($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithBoolean()
	{
		$param = new TEventParameter(true);
		$this->assertFalse($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithObject()
	{
		$param = new TEventParameter(new stdClass());
		$this->assertFalse($param->getParameterIsArray());
	}

	public function testGetParameterIsArrayWithEmptyArray()
	{
		$param = new TEventParameter([]);
		$this->assertTrue($param->getParameterIsArray());
	}
}
