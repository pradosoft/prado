<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Cron\TCronMethodTask;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;

class TCMeTTModule extends TModule
{
	public $property;
	
	public function simpleMethod() 
	{
		$this->property = 'simpleMethod';
	}
	public function paramMethod($data) 
	{
		$this->property = $data;
	}
}

class TDCronMethodTaskTest extends TCronTaskTest
{	
	protected function getTestClass()
	{
		return 'Prado\\Util\\Cron\\TCronMethodTask';
	}
	
	public function hasNullModuleId()
	{
		return false;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Cron\\TCronMethodTask', $this->obj);
		
		self::assertNull($this->obj->getModuleId());
		self::assertNull($this->obj->getModuleId());
		try {
			self::assertNull($this->obj->getModule());
			self::fail('TConfigurationException not thrown when getting module without ModuleId');
		} catch(TConfigurationException $e) {}
		self::assertNull($this->obj->getMethod());
		
		$this->obj = new TCronMethodTask('module', 'method');
		self::assertEquals('module', $this->obj->getModuleId());
		self::assertEquals('method', $this->obj->getMethod());
	}
	
	public function testGetTask()
	{
		$this->obj->setModuleId('mymodule'); 
		$this->obj->setMethod('mymethod(\'abc\')'); 
		$this->assertEquals($this->obj->getModuleId() . TCronModule::METHOD_SEPARATOR . $this->obj->getMethod(), $this->obj->getTask());
	}
	
	public function testTask()
	{
		$app = Prado::getApplication();
		$module = new TCMeTTModule();
		$app->setModule('TCMeTTModuleTask', $module);
		$this->obj->setModuleId('TCMeTTModuleTask');
		
		$this->obj->setMethod('simpleMethod');
		$this->obj->execute(null, null);
		
		self::assertEquals('simpleMethod', $module->property);
		
		$this->obj->setMethod('paramMethod(\'paramData\')');
		$this->obj->execute(null, null);
		
		self::assertEquals('paramData', $module->property);
	}
	
	public function testValidateTask()
	{
		$app = Prado::getApplication();
		$module = new TCMeTTModule();
		$app->setModule('TCMeTTModule1', $module);
		
		$this->obj->setModuleId('TCMeTTModule1');
		
		$this->obj->setMethod('simpleMethod');
		self::assertTrue($this->obj->validateTask());
		$this->obj->setMethod('paramMethod(\'paramData\')');
		self::assertTrue($this->obj->validateTask());
		
		$this->obj->setMethod('simpleMethod2');
		self::assertFalse($this->obj->validateTask());
		$this->obj->setMethod('paramMethod2(\'paramData\')');
		self::assertFalse($this->obj->validateTask());
		
	}
	
	public function testMethod()
	{
		$value = 'moduleMethod(true)';
		$this->obj->setMethod($value);
		self::assertEquals($value, $this->obj->getMethod());
	}

}
