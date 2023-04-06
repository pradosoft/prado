<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TDbParameterModule;

function dbParamTestFunction($data, $encode)
{
	if($encode)
		return serialize($data);
	else
		return unserialize($data);
}

class TDbParameterModuleTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TDbParameterModule();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		Prado::getApplication()->onBeginRequest->clear();
		$key = 'testparam';
		$key2 = 'testparam2';
		$key3 = 'testparam3';
		$key4 = 'testparam4';
		$this->obj->remove($key);
		$this->obj->remove($key2);
		$this->obj->remove($key3);
		$this->obj->remove($key4);
		
		$this->obj = null;
	}

	public function testInit()
	{
		$key = 'testparam';
		$value = 'test_value';
		$key2 = 'testparam2';
		$value2 = 'test_value2';
		$key3 = 'testparam3';
		$key4 = 'testparam4';
		
		$app = Prado::getApplication();
		$params = $app->getParameters();
		
		self::assertNull($params->asa(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR));
		
		$this->obj->init(null);
		
		self::assertNull($this->obj->get($key, false));
		self::assertInstanceOf(TMapLazyLoadBehavior::class, $params->asa(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR));
		self::assertEquals(1, Prado::getApplication()->onBeginRequest->count());
		
		try {
			$this->obj->setConnectionID('db');
			self::fail("setConnectionID failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setKeyField('param_key');
			self::fail("setKeyField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setValueField('param_value');
			self::fail("setValueField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setTableName('prado_params');
			self::fail("setTableName failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadField('initload');
			self::fail("setAutoLoadField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadValue('\'yes\'');
			self::fail("setAutoLoadValue failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadValueFalse('\'no\'');
			self::fail("setAutoLoadValueFalse failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setSerializer(TDbParameterModule::SERIALIZE_PHP);
			self::fail("setSerializer failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		$params[$key] = $value; 
		self::assertNull($this->obj->get($key, false));
		
		self::assertNull($params->asa(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR));
		$this->obj->attachParameterStorage(null, null);
		self::assertInstanceOf(TMapRouteBehavior::class, $params->asa(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR));
		
		$params[$key] = $value;
		//makes sure the behavior is working for setting the parameter when 
		self::assertEquals($value, $this->obj->get($key, false));
		
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		
		//******   setting parameters from database
		$this->obj = new TDbParameterModule();
		self::assertNull($params[$key]);
		$this->obj->init(null);
		self::assertEquals($value, $params[$key]);
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key2, $value2, false);
		
		$arrayValue = ['propA' => 'data1', 'propB' => 'data2'];
		$objValue = new stdClass;
		$objValue->propA = 'data1';
		$objValue->propB = 'data2';
		$this->obj->set($key3, $arrayValue);
		$this->obj->set($key4, $objValue);
		
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		unset($params[$key2]);
		unset($params[$key3]);
		unset($params[$key4]);
		
		//******  lazy loading from database
		$this->obj = new TDbParameterModule();
		$this->obj->init(null);
		
		//Check that the key was NOT loaded, it was removed before
		self::assertNull($params[$key]);
		self::assertNull($this->obj->get($key, false));
		
		//Check lazy load key2
		self::assertFalse(isset($params[$key2]));
		self::assertTrue($this->obj->exists($key2));
		self::assertEquals($value2, $params[$key2]);
		
		$this->obj->attachParameterStorage(null, null);
		
		$params[$key2] = null;
		self::assertFalse($this->obj->exists($key2));
		self::assertTrue(isset($params[$key2]));
		self::assertNull($params[$key2]);
		
		$params[$key2] = $value2;
		self::assertTrue($this->obj->exists($key2));
		self::assertEquals($value2, $params[$key2]);
		unset($params[$key2]);
		self::assertFalse($this->obj->exists($key2));
		self::assertFalse(isset($params[$key2]));
		self::assertNull($params[$key2]);
		
		self::assertEquals($arrayValue, $this->obj->remove($key3));
		self::assertEquals($objValue, $this->obj->remove($key4));
		
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		$this->obj->remove($key2);
		unset($params[$key2]);
	}

	public function testConstruct()
	{
		self::assertInstanceOf(TDbParameterModule::class, $this->obj);
	}
	
	public function testGet()
	{
		$app = Prado::getApplication();
		
		$key = 'testparam';
		$value = 'test_value';
		$app->getParameters()[$key] = $value;
		self::assertFalse($this->obj->exists($key));
		self::assertNull($this->obj->get($key, false));
		self::assertEquals($value, $this->obj->get($key, true));
		$this->obj->set($key, $value);
		self::assertEquals($value, $this->obj->get($key, false));
		self::assertEquals($value, $this->obj->remove($key));
		self::assertFalse($this->obj->exists($key));
	}

	public function testSet()
	{
		$key = 'testparam';
		$value = 'test_value';
		self::assertFalse($this->obj->exists($key));
		self::assertNull($this->obj->get($key, false));
		self::assertEquals($value, $this->obj->get($key, true));
		$this->obj->set($key, $value);
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testExists()
	{
		$key = 'testparam';
		$value = 'test_value';
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testRemove()
	{
		$key = 'testparam';
		$value = 'test_value';
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		
		self::assertEquals($value, $this->obj->remove($key));
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testConnectionId()
	{
		// Default is blank string
		self::assertEquals('', $this->obj->getConnectionID());
		
		$this->obj->setConnectionID('database');
		self::assertEquals('database', $this->obj->getConnectionID());
		
		$this->obj->setConnectionID('db');
		self::assertEquals('db', $this->obj->getConnectionID());
		
		//change back so not to invalidate testing reset
		$this->obj->setConnectionID('');
	}

	public function testGetDbConnection()
	{
		self::assertInstanceOf(TDbConnection::class, $this->obj->getDbConnection());
	}

	public function testKeyField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getKeyField()));
		self::assertTrue(strlen($this->obj->getKeyField()) > 0);
		
		$this->obj->setKeyField('options_key');
		self::assertEquals('options_key', $this->obj->getKeyField());
		
		$this->obj->setKeyField('param_key');
		self::assertEquals('param_key', $this->obj->getKeyField());
	}

	public function testValueField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getValueField()));
		self::assertTrue(strlen($this->obj->getValueField()) > 0);
		
		$this->obj->setValueField('options_value');
		self::assertEquals('options_value', $this->obj->getValueField());
		
		$this->obj->setValueField('param_value');
		self::assertEquals('param_value', $this->obj->getValueField());
	}

	public function testTableName()
	{
		$tableName = $this->obj->getTableName();
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getTableName()));
		self::assertTrue(strlen($this->obj->getTableName()) > 0);
		
		$this->obj->setTableName('wp_options');
		self::assertEquals('wp_options', $this->obj->getTableName());
		
		$this->obj->setTableName('prado_parameters');
		self::assertEquals('prado_parameters', $this->obj->getTableName());
		$this->obj->setTableName($tableName);
	}

	public function testAutoLoadField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getAutoLoadField()));
		self::assertTrue(strlen($this->obj->getAutoLoadField()) > 0);
		
		$this->obj->setAutoLoadField('initload');
		self::assertEquals('initload', $this->obj->getAutoLoadField());
		
		$this->obj->setAutoLoadField('autoload');
		self::assertEquals('autoload', $this->obj->getAutoLoadField());
	}

	public function testAutoLoadValue()
	{
		$this->obj->setAutoLoadValue('1');
		self::assertEquals('1', $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue('true');
		self::assertEquals('true', $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue("\'true\'");
		self::assertEquals("\'true\'", $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue("\'yes\'");
		self::assertEquals("\'yes\'", $this->obj->getAutoLoadValue());
	}

	public function testAutoLoadValueFalse()
	{
		$this->obj->setAutoLoadValueFalse('0');
		self::assertEquals('0', $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse('false');
		self::assertEquals('false', $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse("\'false\'");
		self::assertEquals("\'false\'", $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse("\'no\'");
		self::assertEquals("\'no\'", $this->obj->getAutoLoadValueFalse());
	}

	public function testAutoCreateParamTable()
	{
		self::assertTrue($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable(false);
		self::assertFalse($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable(true);
		self::assertTrue($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable('false');
		self::assertFalse($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable('true');
		self::assertTrue($this->obj->getAutoCreateParamTable());
	}
	
	public function testSerializer()
	{
		$key = 'testparam';
		$value = ['propA' => 'data1', 'propB' => 'data2'];
		$value2 = new stdClass;
		$value2->propA = 'data1';
		$value2->propB = 'data2';
		
		self::assertEquals(TDbParameterModule::SERIALIZE_PHP, $this->obj->getSerializer());
		$this->obj->setSerializer(TDbParameterModule::SERIALIZE_JSON);
		self::assertEquals(TDbParameterModule::SERIALIZE_JSON, $this->obj->getSerializer());
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value, $this->obj->get($key, false));
		
		$this->obj->setSerializer(TDbParameterModule::SERIALIZE_PHP);
		self::assertEquals(TDbParameterModule::SERIALIZE_PHP, $this->obj->getSerializer());
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value2, $this->obj->get($key, false));
		
		$this->obj->setSerializer('dbParamTestFunction');
		self::assertEquals('dbParamTestFunction', $this->obj->getSerializer());
		
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value2, $this->obj->get($key, false));
	
		$this->obj->remove($key);
		try {
			$this->obj->setSerializer('dbParamTestFunction_nonexistant');
			self::fail("TDbParameterModule::setSerializer should raise TInvalidDataTypeException when givin a non-existant function to Serializer\n");
		} catch(TInvalidDataTypeException $e) {
		}
	}
	

	public function testCaptureParameterChanges()
	{
		$this->obj->setCaptureParameterChanges(false);
		self::assertFalse($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges(true);
		self::assertTrue($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges('false');
		self::assertFalse($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges('true');
		self::assertTrue($this->obj->getCaptureParameterChanges());
	}
}
