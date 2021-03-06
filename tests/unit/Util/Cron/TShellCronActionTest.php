<?php

use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Prado;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TDbCronModule;


class TShellCronActionTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	
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
	
	public function testPerformAction()
	{
		$app = Prado::getApplication();
		
		//  no TCronModule in application, failed 
		self::assertTrue($this->obj->performAction(['prado-cli', 'app', '.', 'cron']));
		self::assertEquals(1, preg_match("/TCronModule/", $text = $this->writer->flush()));
		
		$jobs = [['name' => 'testTaskA', 'schedule' => '1 2 3 4 ?', 'task' => 'TTestCronModuleTask', 'userid' => 'admin123', 'moduleid' => 'cronmodule99', 'propertya' => 'value1']];
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->init($jobs);
		$app->setModule('ShellCron', $cron);
		self::assertNull($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR));
		{	//cron pending tasks
			$task = $cron->getTask('testTaskA');
			self::assertEquals(0, $task->getProcessCount());
			self::assertTrue($this->obj->performAction(['app', '.', 'cron']));
			self::assertEquals(1, $task->getProcessCount());
			$this->writer->flush();
		}
		self::assertTrue(is_object($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)));
		{	//cron tasks
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'tasks']));
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
		{	//cron info	
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'info']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/No registered application tasks/", $text));
			
			//Register & call again
			$fxtest = new TTestCronFXTest();
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'info']));
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
		{	//cron help	
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'help']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/usage/", $text));
			self::assertEquals(1, preg_match("/prado-cli/", $text));
			self::assertEquals(1, preg_match("/example/", $text));
			self::assertEquals(1, preg_match("/tasks/", $text));
			self::assertEquals(1, preg_match("/info/", $text));
			self::assertEquals(1, preg_match("/help/", $text));
		}
		{	//cron help	tasks
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'help', 'tasks']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/help/", $text));
			self::assertEquals(1, preg_match("/tasks command/", $text));
		}
		{	//cron help	info
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'help', 'info']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/help/", $text));
			self::assertEquals(1, preg_match("/info command/", $text));
		}
		{	//cron help	info
			self::assertTrue($this->obj->performAction(['app', '.', 'cron', 'help', 'help']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/help command/", $text));
		}
		
		//$app->setModule('ShellCron', null);
		$cron->unlisten();
	}
	
}
