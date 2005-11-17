<?php

require_once(dirname(__FILE__).'/common.php');

class NewComponent extends TComponent
{
	private $_object=null;
	private $_text='default';
	private $_eventHandled=false;

	public function getText()
	{
		return $this->_text;
	}

	public function setText($value)
	{
		$this->_text=$value;
	}

	public function getObject()
	{
		if(!$this->_object)
		{
			$this->_object=new NewComponent;
			$this->_object->_text='object text';
		}
		return $this->_object;
	}

	public function onMyEvent($param)
	{
		$this->raiseEvent('MyEvent',$this,$param);
	}

	public function myEventHandler($sender,$param)
	{
		$this->_eventHandled=true;
	}

	public function isEventHandled()
	{
		return $this->_eventHandled;
	}
}

class utComponent extends UnitTestCase
{
	protected $component;

	public function setUp()
	{
		$this->component=new NewComponent;
	}

	public function tearDown()
	{
		$this->component=null;
	}

	public function testHasProperty()
	{
		$this->assertTrue($this->component->hasProperty('Text'));
		$this->assertTrue($this->component->hasProperty('text'));
		$this->assertFalse($this->component->hasProperty('Caption'));
	}

	public function testCanGetProperty()
	{
		$this->assertTrue($this->component->canGetProperty('Text'));
		$this->assertTrue($this->component->canGetProperty('text'));
		$this->assertFalse($this->component->canGetProperty('Caption'));
	}

	public function testCanSetProperty()
	{
		$this->assertTrue($this->component->canSetProperty('Text'));
		$this->assertTrue($this->component->canSetProperty('text'));
		$this->assertFalse($this->component->canSetProperty('Caption'));
	}

	public function testGetProperty()
	{
		$this->assertTrue('default'===$this->component->Text);
		try
		{
			$value2=$this->component->Caption;
			$this->fail('exception not raised when getting undefined property');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testSetProperty()
	{
		$value='new value';
		$this->component->Text=$value;
		$text=$this->component->Text;
		$this->assertTrue($value===$this->component->Text);
		try
		{
			$this->component->NewMember=$value;
			$this->fail('exception not raised when setting undefined property');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testGetSubProperty()
	{
		$this->assertTrue('object text'===$this->component->getSubProperty('Object.Text'));
	}

	public function testSetSubProperty()
	{
		$this->component->setSubProperty('Object.Text','new object text');
		$this->assertEqual('new object text',$this->component->getSubProperty('Object.Text'));
	}

	public function testHasEvent()
	{
		$this->assertTrue($this->component->hasEvent('MyEvent'));
		$this->assertTrue($this->component->hasEvent('myevent'));
		$this->assertFalse($this->component->hasEvent('YourEvent'));
	}

	public function testHasEventHandler()
	{
		$this->assertFalse($this->component->hasEventHandler('MyEvent'));
		$this->component->attachEventHandler('MyEvent','foo');
		$this->assertTrue($this->component->hasEventHandler('MyEvent'));
	}

	public function testGetEventHandlers()
	{
		$list=$this->component->getEventHandlers('MyEvent');
		$this->assertTrue(($list instanceof TList) && ($list->getCount()===0));
		$this->component->attachEventHandler('MyEvent','foo');
		$this->assertTrue(($list instanceof TList) && ($list->getCount()===1));
		try
		{
			$list=$this->component->getEventHandlers('YourEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testAttachEventHandler()
	{
		$this->component->attachEventHandler('MyEvent','foo');
		$this->assertTrue($this->component->getEventHandlers('MyEvent')->getCount()===1);
		try
		{
			$this->component->attachEventHandler('YourEvent','foo');
			$this->fail('exception not raised when attaching event handlers for undefined event');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
		/*$this->component->MyEvent[]='foo2';
		$this->assertTrue($this->component->getEventHandlers('MyEvent')->getCount()===2);
		$this->component->getEventHandlers('MyEvent')->add('foo3');
		$this->assertTrue($this->component->getEventHandlers('MyEvent')->getCount()===3);
		$this->component->MyEvent[0]='foo4';
		$this->assertTrue($this->component->getEventHandlers('MyEvent')->getCount()===3);
		$this->component->getEventHandlers('MyEvent')->addAt(0,'foo5');
		$this->assertTrue($this->component->MyEvent->Count===4 && $this->component->MyEvent[0]==='foo5');
		$this->component->MyEvent='foo6';
		$this->assertTrue($this->component->MyEvent->Count===5 && $this->component->MyEvent[4]==='foo6');*/
	}

	public function testRaiseEvent()
	{
		$this->component->attachEventHandler('MyEvent',array($this->component,'myEventHandler'));
		$this->assertFalse($this->component->isEventHandled());
		$this->component->raiseEvent('MyEvent',$this,null);
		$this->assertTrue($this->component->isEventHandled());
		$this->component->attachEventHandler('MyEvent',array($this->component,'Object.myEventHandler'));
		$this->assertFalse($this->component->Object->isEventHandled());
		$this->component->raiseEvent('MyEvent',$this,null);
		$this->assertTrue($this->component->Object->isEventHandled());
	}

	public function testEvaluateExpression()
	{
		$expression="1+2";
		$this->assertTrue(3===$this->component->evaluateExpression($expression));
		try
		{
			$button=$this->component->evaluateExpression('$this->button');
			$this->fail('exception not raised when evaluating an invalid exception');
		}
		catch(Exception $e)
		{
			$this->pass();
		}
	}

	public function testEvaluateStatements()
	{
		$statements='$a="test string"; echo $a;';
		$this->assertEqual('test string',$this->component->evaluateStatements($statements));
		try
		{
			$statements='$a=new NewComponent; echo $a->button;';
			$button=$this->component->evaluateStatements($statements);
			$this->fail('exception not raised when evaluating an invalid statement');
		}
		catch(Exception $e)
		{
			$this->pass();
		}
	}
	
	
	/**
	 * Tests the TPropertyValue::ensureBoolean function
	 */
	public function testEnsureBoolean()
	{
		// Note: we must use assertEqual not assertTrue or assertFalse because then we can check that the return value is strictly of type boolean
		$this->assertEqual(TPropertyValue::ensureBoolean('false'), false);
		$this->assertEqual(TPropertyValue::ensureBoolean('False'), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(false), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(0), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(0.0), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(""), false);
		$this->assertEqual(TPropertyValue::ensureBoolean("0"), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(array()), false);
		$this->assertEqual(TPropertyValue::ensureBoolean(null), false);
		
		$this->assertEqual(TPropertyValue::ensureBoolean('true'), true);
		$this->assertEqual(TPropertyValue::ensureBoolean('True'), true);
		$this->assertEqual(TPropertyValue::ensureBoolean(1), true);
		$this->assertEqual(TPropertyValue::ensureBoolean("1"), true);
		$this->assertEqual(TPropertyValue::ensureBoolean("-1"), true);
		$this->assertEqual(TPropertyValue::ensureBoolean(array("foboar")), true);
	}
	
	/**
	 * Tests the TPropertyValue::ensureString function
	 */
	public function testEnsureString()
	{
		$this->assertEqual(TPropertyValue::ensureString("foobar"), "foobar");
		$this->assertEqual(TPropertyValue::ensureString(true), "true");
		$this->assertEqual(TPropertyValue::ensureString(false), "false");
		$this->assertEqual(TPropertyValue::ensureString(array("foo","bar")), (string)array("foo","bar"));
	}
	
	/**
	 * Tests the TPropertyValue::ensureInteger function
	 */
	public function testEnsureInteger()
	{
		$this->assertEqual(TPropertyValue::ensureInteger(123), 123);
		$this->assertEqual(TPropertyValue::ensureInteger("123"), 123);
		$this->assertEqual(TPropertyValue::ensureInteger(""), 0);
	}
	
	/**
	 * Tests the TPropertyValue::ensureFloat function
	 */
	public function testEnsureFloat()
	{
		$this->assertEqual(TPropertyValue::ensureFloat(123.123), 123.123);
		$this->assertEqual(TPropertyValue::ensureFloat("123.123"), 123.123);
		$this->assertEqual(TPropertyValue::ensureFloat(""), 0.0);
	}
	
	/**
	 * Tests the TPropertyValue::ensureArray function
	 */
	public function testEnsureArray()
	{
		$this->assertEqual(TPropertyValue::ensureArray(array()), array());
		$this->assertEqual(TPropertyValue::ensureArray(array(1,2,3)), array(1,2,3));
		$this->assertEqual(TPropertyValue::ensureArray("(1,2,3)"), array(1,2,3));
		$this->assertEqual(TPropertyValue::ensureArray(""), array());
	}
	
	/**
	 * Tests the TPropertyValue::ensureObject function
	 */
	public function testEnsureObject()
	{
		$this->assertEqual(TPropertyValue::ensureObject($this->component), $this->component);
	}
	
	/**
	 * Tests the TPropertyValue::ensureEnum function
	 */
	public function testEnsureEnum()
	{
		$this->assertEqual(TPropertyValue::ensureEnum("foo", array("foo", "bar", "BAR")), "foo");
		$this->assertEqual(TPropertyValue::ensureEnum("bar", array("foo", "bar", "BAR")), "bar");
		$this->assertEqual(TPropertyValue::ensureEnum("BAR", array("foo", "bar", "BAR")), "BAR");
		$pass = false;
		try {
			$this->assertEqual(TPropertyValue::ensureEnum("xxx", array("foo", "bar", "BAR")), "BAR");
		} catch (TInvalidDataValueException $e) {
			$this->pass();
			$pass = true;
		}
		if (!$pass) {
			$this->fail("ensureEnun didn't raise a TInvalidDataValueException when it should have");
		}
		$pass = false;
		try {
			$this->assertEqual(TPropertyValue::ensureEnum("FOO", array("foo", "bar", "BAR")), "BAR");
		} catch (TInvalidDataValueException $e) {
			$this->pass();
			$pass = true;
		}
		if (!$pass) {
			$this->fail("ensureEnun didn't raise a TInvalidDataValueException when it should have");
		}
	}
}

?>