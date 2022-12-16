<?php

use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TDbCronModule;


class TShellCronActionTest extends PHPUnit\Framework\TestCase
{
	protected $obj, $writer;
	
	protected function getTestClass()
	{
		return 'Prado\\Util\\Cron\\TShellCronAction';
	}
	
	protected function getTestCronClass()
	{
		return 'Prado\\Util\\Cron\\TCronModule';
	}

	protected function setUp(): void
	{
		$this->obj = Prado::createComponent($this->getTestClass());
		$this->writer = new TShellWriter(new TTextWriter());
		$this->obj->setWriter($this->writer);
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Cron\\TShellCronAction', $this->obj);
	}

	public function testCronModule()
	{	
		$this->obj->setCronModule($v = new TComponent());
		$this->assertEquals($v, $this->obj->getCronModule());
	}
	
	public function testPerformAction()
	{
		
		//  no TCronModule in application, failed 
		self::assertTrue($this->obj->actionRun(['cron']));
		self::assertEquals(1, preg_match("/TCronModule/", $text = $this->writer->flush()));
		
		$jobs = [['name' => 'testTaskA', 'schedule' => '1 2 3 4 ? 2020', 'task' => 'TTestCronModuleTask', 'username' => 'admin123', 'moduleid' => 'cronmodule99', 'propertya' => 'value1']];
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->init($jobs);
		$this->obj->setCronModule($cron);
		self::assertNull($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR));
		{	//cron pending tasks
			$task = $cron->getTask('testTaskA');
			self::assertEquals(0, $task->getProcessCount());
			self::assertTrue($this->obj->actionRun(['cron/run']));
			$text = $this->writer->flush();
			self::assertEquals(1, $task->getProcessCount());
				
			$ntask = $cron->getTask('testTaskA');
			self::assertTrue($ntask === $task);
		}
		self::assertTrue(is_object($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)));
		{	//cron tasks
			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			$text = $this->writer->flush();
			
			self::assertEquals(1, preg_match("/Name/", $text));
			self::assertEquals(1, preg_match("/Schedule/", $text));
			self::assertEquals(1, preg_match("/Task/", $text));
			self::assertEquals(1, preg_match("/Last Run/", $text));
			self::assertEquals(1, preg_match("/Next Run/", $text));
			self::assertEquals(1, preg_match("/User/", $text));
			self::assertEquals(1, preg_match("/Run #/", $text));
			
			self::assertEquals(1, preg_match("/testTaskA/", $text));
			self::assertEquals(1, preg_match("/1 2 3 4 \?/", $text));
			self::assertEquals(1, preg_match("/TTestCronModuleTask/", $text));
			self::assertEquals(1, preg_match("/admin123/", $text));
			self::assertEquals(0, preg_match("/(1969|1970)/", $text));
		}
		{	//cron index	
			self::assertTrue($this->obj->actionIndex(['cron/index']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/No registered application tasks/", $text));
			
			//Register & call again
			$fxtest = new TTestCronFXTest();
			self::assertTrue($this->obj->actionIndex(['cron/index']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task ID/", $text));
			self::assertEquals(1, preg_match("/Task/", $text));
			self::assertEquals(1, preg_match("/Module ID/", $text));
			self::assertEquals(1, preg_match("/Title/", $text));
			
			self::assertEquals(1, preg_match("/taskName/", $text));
			self::assertEquals(1, preg_match("/taskDefinition/", $text));
			self::assertEquals(1, preg_match("/module1/", $text));
			self::assertEquals(1, preg_match("/text title/", $text));
			self::assertEquals(1, preg_match("/text description/", $text));
			$fxtest->unlisten();
		}
		
		$cron->unlisten();
	}
	
}
