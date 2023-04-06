<?php

use Prado\IO\TTextWriter;
use Prado\TComponent;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TShellCronLogBehavior;


class TShellCronLogBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	

	protected function setUp(): void
	{
		$this->obj = new TShellCronLogBehavior();
		$this->writer = new TShellWriter(new TTextWriter());
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TShellCronLogBehavior::class, $this->obj);
		self::assertNull($this->obj->getOutputWriter());
		
		$this->obj = new TShellCronLogBehavior($this->writer);
		self::assertEquals($this->writer, $this->obj->getOutputWriter());
	}
	
	public function testOutputWriter()
	{
		self::assertNull($this->obj->getOutputWriter());
		$this->obj->setOutputWriter($this->writer);
		self::assertEquals($this->writer, $this->obj->getOutputWriter());
	}
	
	public function testDyWrite_Line_Flush()
	{
		$this->obj->setOutputWriter($this->writer);
		$this->writer->setColorSupported(true);
		$component = new TComponent();
		$component->attachBehavior('behavior', $this->obj);
		
		$component->dyWrite('some text ');
		$component->dyWrite('some text', TShellWriter::GREEN);
		self::assertEquals("some text \033[32msome text\033[0m",$this->writer->flush());
		
		$component->dyWriteLine('some text');
		$component->dyWriteLine('some text', TShellWriter::GREEN);
		self::assertEquals("some text\n\033[32msome text\033[0m\n",$this->writer->flush());
		
		$component->dyWrite('some text');
		$component->dyWrite('some text', TShellWriter::GREEN);
		self::assertEquals("some text\033[32msome text\033[0m",$component->dyFlush());
	}
	
	public function testShellCronLogs()
	{
		$this->obj->setOutputWriter($this->writer);
		$this->writer->setColorSupported(true);
		$component = new TComponent();
		$component->attachBehavior('behavior', $this->obj);
		
		$task = new TTestCronModuleTask();
		$task->setName('testName');
		
		$component->dyLogCron(15748);
		self::assertEquals(1, preg_match("/Running 15748 Cron Tasks/i", $this->writer->flush()));
		
		$component->dyLogCronTask($task, 'admin-cron');
		//This is auto-flushed
		
		
		$component->dyLogCronTaskEnd($task);
		self::assertEquals(1, preg_match("/Ending Task/i", $this->writer->flush()));
	}
	
}
