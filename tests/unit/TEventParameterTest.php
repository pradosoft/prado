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
	}

	public function testConstructorWithParameter()
	{
		$param = new TEventParameter('test value');
		$this->assertEquals('test value', $param->getParameter());
	}

	public function testConstructorWithNullParameter()
	{
		$param = new TEventParameter(null);
		$this->assertNull($param->getParameter());
	}

	public function testConstructorWithArrayParameter()
	{
		$data = ['key1' => 'value1', 'key2' => 'value2'];
		$param = new TEventParameter($data);
		$this->assertEquals($data, $param->getParameter());
	}

	public function testConstructorWithIntegerParameter()
	{
		$param = new TEventParameter(42);
		$this->assertEquals(42, $param->getParameter());
	}

	public function testConstructorWithBooleanParameter()
	{
		$param = new TEventParameter(true);
		$this->assertTrue($param->getParameter());
	}

	public function testConstructorWithObjectParameter()
	{
		$obj = new stdClass();
		$obj->key = 'value';
		$param = new TEventParameter($obj);
		$this->assertSame($obj, $param->getParameter());
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

	public function testEventNameCanBeSetViaConstructor()
	{
		$param = new TEventParameter();
		$param->setEventName('OnClick');
		$this->assertEquals('OnClick', $param->getEventName());
	}

	// ================================================================================
	// Parameter Property Tests
	// ================================================================================

	public function testGetSetParameter()
	{
		$param = new TEventParameter();
		$param->setParameter('test');
		$this->assertEquals('test', $param->getParameter());
	}

	public function testSetParameterToNull()
	{
		$param = new TEventParameter('initial');
		$param->setParameter(null);
		$this->assertNull($param->getParameter());
	}

	public function testSetParameterToArray()
	{
		$param = new TEventParameter();
		$param->setParameter(['a' => 1, 'b' => 2]);
		$this->assertEquals(['a' => 1, 'b' => 2], $param->getParameter());
	}

	public function testSetParameterToZero()
	{
		$param = new TEventParameter();
		$param->setParameter(0);
		$this->assertEquals(0, $param->getParameter());
	}

	public function testSetParameterToFalse()
	{
		$param = new TEventParameter();
		$param->setParameter(false);
		$this->assertFalse($param->getParameter());
	}

	public function testSetParameterToEmptyString()
	{
		$param = new TEventParameter();
		$param->setParameter('');
		$this->assertEquals('', $param->getParameter());
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
	}

	public function testOffsetUnsetWithArray()
	{
		$param = new TEventParameter(['key1' => 'value1', 'key2' => 'value2']);
		$param->offsetUnset('key1');
		$this->assertFalse($param->offsetExists('key1'));
		$this->assertTrue($param->offsetExists('key2'));
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
	}

	public function testOffsetSetAddsNewKey()
	{
		$param = new TEventParameter(['existing' => 'value']);
		$param->offsetSet('new', 'newValue');
		$this->assertEquals('value', $param->getParameter()['existing']);
		$this->assertEquals('newValue', $param->getParameter()['new']);
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

	public function testEventNameGetterSetter()
	{
		$param = new TEventParameter();
		$param->setEventName('CustomEvent');
		$this->assertEquals('CustomEvent', $param->getEventName());
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
	}

	public function testModifyArrayViaOffsetAndRetrieve()
	{
		$param = new TEventParameter(['initial' => 'value']);
		$param->offsetSet('added', 'new');
		$this->assertEquals('new', $param->offsetGet('added'));
		$this->assertEquals('value', $param->offsetGet('initial'));
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
}
