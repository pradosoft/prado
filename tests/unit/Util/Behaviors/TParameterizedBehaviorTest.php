<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Util\Behaviors\TParameterizeBehavior;

class TParameterizeObjectNoSet extends \Prado\TComponent
{
	protected $_field;
	public function getField()
	{
		return $this->_field;
	}
}
class TParameterizeObject extends TParameterizeObjectNoSet
{
	
	public function setField($value)
	{
		$this->_field = $value;
	}
}

/**
 * TParameterizeBehaviorTest class.
 *
 * This tests the TParameterizeBehavior class 
 */
 
class TParameterizeBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TParameterizeBehavior();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TParameterizeBehavior::class, $this->obj);
	}

	public function testAttachBehavior_badParameters()
	{
		$behaviorName = 'testingBehavior';
		$key = 'param_key';
		$property = 'Field';
		$value = 'test_value';
		$owner = new TParameterizeObject;
		
		self::assertNull($owner->getField());
		
		try {
			$this->obj->setParameter($key);
			$owner->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException without a Property");
		} catch(TConfigurationException $e) {}
		$owner->detachBehavior($behaviorName);
		
		try {
			$this->obj->setProperty('');
			$owner->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException with a blank Property");
		} catch(TConfigurationException $e) {}
		$owner->detachBehavior($behaviorName);
		
		$ownerNoSet = new TParameterizeObjectNoSet;
		try {
			$this->obj->setProperty($property);
			$ownerNoSet->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException with a get-only Property");
		} catch(TConfigurationException $e) {}
		$ownerNoSet->detachBehavior($behaviorName);
		
		try {
			$this->obj->setProperty('NonField');
			$ownerNoSet->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException as object doesn't have the Property");
		} catch(TConfigurationException $e) {}
		$ownerNoSet->detachBehavior($behaviorName);
		
		$this->obj->setParameter('');
		try {
			$this->obj->setProperty($property);
			$owner->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException without a Parameter");
		} catch(TConfigurationException $e) {}
		$owner->detachBehavior($behaviorName);
		
		// Test the non-set, null parameter case
		$this->obj = new TParameterizeBehavior();
		try {
			$this->obj->setProperty($property);
			$owner->attachBehavior($behaviorName, $this->obj);
			$this->fail("Attaching should have thrown TConfigurationException without a Parameter");
		} catch(TConfigurationException $e) {}
		$owner->detachBehavior($behaviorName);
		
		$behaviorName = 'testingBehavior';
		$routeName = 'routeBehavior';
		$key = 'param_key';
		$key2 = 'param_key2';
		$property = 'Field';
		$this->obj->setParameter($key);
		$this->obj->setProperty($property);
		$this->obj->setRouteBehaviorName($routeName);
		$owner->attachBehavior($behaviorName, $this->obj);
		
		try {
			$this->obj->setValidNullValue(true);
			$this->fail("failed to throw TInvalidOperationException on setValidNullValue after being attached");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setProperty('Field2');
			$this->fail("failed to throw TInvalidOperationException on setProperty after being attached");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setDefaultValue('default_value');
			$this->fail("failed to throw TInvalidOperationException on setDefaultValue after being attached");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setLocalize('true');
			$this->fail("failed to throw TInvalidOperationException on setLocalize after being attached");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setRouteBehaviorName('newRouteBehavior');
			$this->fail("failed to throw TInvalidOperationException on setRouteBehaviorName after being attached");
		} catch(TInvalidOperationException $e) {}
		
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$this->obj->setParameter($key2);
		
		self::assertEquals($key2, $params->asa($routeName)->getParameter());
		
		$owner->detachBehavior($behaviorName);
	}

	public function testAttachBehavior_setProperty()
	{
		$behaviorName = 'testingBehavior';
		$routeName = 'routeBehavior';
		$key = 'param_key';
		$property = 'Field';
		$value = 'test_value';
		$defaultvalue = 'default_value';
		$owner = new TParameterizeObject;
		$owner->setField('data');
		
		$this->obj->setParameter($key);
		$this->obj->setProperty($property);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals('data', $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$this->obj->setDefaultValue($defaultvalue);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals($defaultvalue, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$this->obj->setValidNullValue(true);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals(null, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$app = Prado::getApplication();
		$params = $app->getParameters();
		
		$params[$key] = $value;
		$this->obj->setRouteBehaviorName($routeName);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals($value, $owner->getField());
		$params[$key] = $defaultvalue;
		self::assertEquals($defaultvalue, $owner->getField());
		$this->obj->setEnabled(false);
		$params[$key] = '--nothing--';
		self::assertEquals($defaultvalue, $owner->getField());
		$this->obj->setEnabled(true);
		$defaultvalue = $params[$key] = 'nextValue';
		self::assertEquals($defaultvalue, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$params[$key] = $value;
		self::assertEquals($defaultvalue, $owner->getField());
		
		$params[$key] = null;
		unset($params[$key]);
		
		$this->obj = null;
		$this->obj = new TParameterizeBehavior();
		$this->obj->setLocalize(true);
		$owner = new TParameterizeObject;
		$owner->setField('data');
		
		$this->obj->setParameter($key);
		$this->obj->setProperty($property);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals('data', $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$this->obj->setDefaultValue($defaultvalue);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals($defaultvalue, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$this->obj->setValidNullValue(true);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals(null, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$app = Prado::getApplication();
		$params = $app->getParameters();
		
		$params[$key] = $value;
		$this->obj->setRouteBehaviorName($routeName);
		$owner->attachBehavior($behaviorName, $this->obj);
		self::assertEquals($value, $owner->getField());
		$params[$key] = $defaultvalue;
		self::assertEquals($defaultvalue, $owner->getField());
		$owner->detachBehavior($behaviorName);
		
		$params[$key] = $value;
		self::assertEquals($defaultvalue, $owner->getField());
		unset($params[$key]);
	}

	public function testParameter()
	{
		self::assertNull($this->obj->getParameter());
		$this->obj->setParameter('param_key');
		self::assertEquals('param_key', $this->obj->getParameter());
	}

	public function testValidNullValue()
	{
		self::assertNull($this->obj->getValidNullValue());
		$this->obj->setValidNullValue(true);
		self::assertTrue($this->obj->getValidNullValue());
		$this->obj->setValidNullValue(false);
		self::assertFalse($this->obj->getValidNullValue());
		$this->obj->setValidNullValue('true');
		self::assertTrue($this->obj->getValidNullValue());
		$this->obj->setValidNullValue('false');
		self::assertFalse($this->obj->getValidNullValue());
	}

	public function testProperty()
	{
		self::assertNull($this->obj->getProperty());
		$this->obj->setProperty('OwnerProperty');
		self::assertEquals('OwnerProperty', $this->obj->getProperty());
	}

	public function testDefaultValue()
	{
		self::assertNull($this->obj->getDefaultValue());
		$this->obj->setDefaultValue('new_value');
		self::assertEquals('new_value', $this->obj->getDefaultValue());
	}

	public function testLocalize()
	{
		self::assertNull($this->obj->getLocalize());
		$this->obj->setLocalize(true);
		self::assertTrue($this->obj->getLocalize());
		$this->obj->setLocalize(false);
		self::assertFalse($this->obj->getLocalize());
		$this->obj->setLocalize('true');
		self::assertTrue($this->obj->getLocalize());
		$this->obj->setLocalize('false');
		self::assertFalse($this->obj->getLocalize());
	}

	public function testRouteBehaviorName()
	{
		self::assertNull($this->obj->getRouteBehaviorName());
		$this->obj->setRouteBehaviorName('testRouteBehaviorName');
		self::assertEquals('testRouteBehaviorName', $this->obj->getRouteBehaviorName());
	}

}
