<?php

use Prado\Prado;
use Prado\TModule;
use Prado\Util\Cron\TCronTaskInfo;

class TCTITModule extends TModule
{
	
}

class TCronTaskInfoTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TCronTaskInfo('name', 'class', 'mid', 'title', 'description');
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf(TCronTaskInfo::class, $this->obj);
		
		self::assertEquals('name', $this->obj->getName());
		self::assertEquals('class', $this->obj->getTask());
		self::assertEquals('mid', $this->obj->getModuleId());
		self::assertEquals('title', $this->obj->getTitle());
		self::assertEquals('description', $this->obj->getDescription());
		
		$this->obj = new TCronTaskInfo('name2', 'class2');
		self::assertEquals('name2', $this->obj->getName());
		self::assertEquals('class2', $this->obj->getTask());
		self::assertNull($this->obj->getModuleId());
		self::assertNull($this->obj->getTitle());
		self::assertNull($this->obj->getDescription());

		$module = new TCTITModule();
		$module->setId('mymid');
		$this->obj = new TCronTaskInfo('name3', 'class3', $module);
		
		self::assertEquals('name3', $this->obj->getName());
		self::assertEquals('class3', $this->obj->getTask());
		self::assertEquals('mymid',$this->obj->getModuleId());
	}
	
	public function testName()
	{
		$value = 'myname';
		$this->obj->setName($value);
		self::assertEquals($value, $this->obj->getName());
	}
	
	public function testTask()
	{
		$value = 'myTask';
		$this->obj->setTask($value);
		self::assertEquals($value, $this->obj->getTask());
	}
	
	public function testModuleId()
	{
		$value = 'myMid';
		$this->obj->setModuleId($value);
		self::assertEquals($value, $this->obj->getModuleId());
	}
	
	public function testGetModule()
	{
		$value = 'cronTaskInfoModule';
		$this->obj->setModuleId($value);
		
		$app = Prado::getApplication();
		$module = new TCTITModule;
		$app->setModule($value, $module);
		
		self::assertEquals($module, $this->obj->getModule());
	}
	
	public function testTitle()
	{
		$value = 'my title';
		$this->obj->setTitle($value);
		self::assertEquals($value, $this->obj->getTitle());
	}
	
	public function testDescription()
	{
		$value = 'my description';
		$this->obj->setDescription($value);
		self::assertEquals($value, $this->obj->getDescription());
	}
	
}
