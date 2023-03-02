<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Cron\TCronTask;

class TTestCronTask extends TCronTask 
{
	public function execute($cron)
	{
	}
}
class TCTTModule extends TModule
{
	
}

class TCronTaskTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	
	protected function getTestClass()
	{
		return 'TTestCronTask';
	}

	protected function setUp(): void
	{
		$this->obj = Prado::createComponent($this->getTestClass());
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Cron\\TCronTask', $this->obj);
	}
	
	public function testName()
	{
		$value = 'myTaskName';
		$this->obj->setName($value);
		self::assertEquals($value, $this->obj->getName());
	}
	
	public function testGetTask()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Cron\\TCronTask', $this->obj);
	}
	
	public function testSchedule()
	{
		$value = '* * * * *';
		$this->obj->setSchedule($value);
		self::assertEquals($value, $this->obj->getSchedule());
		$scheduler = $this->obj->getScheduler();
		self::assertEquals($value, $scheduler->getSchedule());
		
		$value = '*/5 * * * *';
		$this->obj->setSchedule($value);
		self::assertEquals($value, $this->obj->getSchedule());
		self::assertEquals($value, $scheduler->getSchedule());
	}
	
	public function testUserName()
	{
		$value = 'admin';
		$this->obj->setUserName($value);
		self::assertEquals($value, $this->obj->getUserName());
		
		$value = 'cron';
		$this->obj->setUserName($value);
		self::assertEquals($value, $this->obj->getUserName());
	}
	
	public function testModuleId()
	{
		$value = 'myMid';
		$this->obj->setModuleId($value);
		self::assertEquals($value, $this->obj->getModuleId());
	}
	
	public function testGetModule()
	{
		$value = 'cronTaskTestModule';
		$this->obj->setModuleId($value);
		
		$app = Prado::getApplication();
		if(!($module = $app->getModule($value))) {
			$module = new TCTTModule;
			$app->setModule($value, $module);
		}
		
		self::assertEquals($module, $this->obj->getModule());
		
		try {	// module id nonexistant
			$this->obj->setModuleId($value.'2');
			$this->obj->getModule($value.'2');
			self::fail('failed to throw TConfigurationException on ModuleId that does not exist');
		} catch(TConfigurationException $e) {}
		
		//null module id means null module
		if ($this->hasNullModuleId()) {
			$this->obj->setModuleId(null);
			self::assertNull($this->obj->getModule());
		}
	}
	
	public function hasNullModuleId()
	{
		return true;
	}
	
	public function testProcessCount()
	{
		$value = rand();
		$this->obj->setProcessCount($value);
		self::assertEquals($value, $this->obj->getProcessCount());
	}
	
	public function testLastExecTime()
	{
		// integer value
		$value = time() - 120;
		$this->obj->setLastExecTime($value);
		self::assertEquals($value, $this->obj->getLastExecTime());
		
		// double value
		$value = microtime(true) - 120;
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// double value
		$value = $this->obj->getLastExecTime() - 0.5;
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// string of a numeric
		$value = "9.999";
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// null value
		$value = null;
		$this->obj->setLastExecTime($value);
		self::assertEquals($value, $this->obj->getLastExecTime());
	}
	
	public function testIsPending()
	{
		$this->obj->setSchedule("* * * * *");
		$this->obj->setLastExecTime(time() - 61);
		self::assertTrue($this->obj->getIsPending());
		
		$this->obj->setLastExecTime(null);
		self::assertTrue($this->obj->getIsPending());
		
		$this->obj->setLastExecTime(time());
		self::assertFalse($this->obj->getIsPending());
		
		$this->obj->setSchedule("0 0 1 1 * 2000");
		$this->obj->setLastExecTime(time());
		self::assertEquals(null, $this->obj->getNextTriggerTime());
		self::assertFalse($this->obj->getIsPending());
	}
}
