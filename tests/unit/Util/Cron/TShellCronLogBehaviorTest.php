<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\IO\TTextWriter;
use Prado\TComponent;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TShellCronLogBehavior;


class TShellCronLogBehaviorTest extends \PHPUnit\Framework\TestCase
{
	protected $obj;
	protected $writer;

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

		$component->dyLogCronTaskError($task, new \RuntimeException('boom'));
		self::assertEquals(1, preg_match("/Task testName failed: RuntimeException: boom/", $this->writer->flush()));
	}

	public function testSleepAndDaemonLogs()
	{
		$inner = new class () extends TTextWriter {
			public string $flushed = '';

			public function flush()
			{
				$text = parent::flush();
				$this->flushed .= $text;
				return $text;
			}
		};
		$this->obj->setOutputWriter(new TShellWriter($inner));
		$component = new TComponent();
		$component->attachBehavior('behavior', $this->obj);
		$component->dyLogCronSleep(1.23456);
		self::assertStringContainsString('Sleeping for 1.235 seconds', $inner->flushed);
		$component->dyLogCronDaemonStart('cron:lease', true);
		self::assertStringContainsString('Cron daemon started (cron:lease), watching for wakes', $inner->flushed);
		$inner->flushed = '';
		$component->dyLogCronDaemonStart('cron:lease', false);
		self::assertStringNotContainsString('watching', $inner->flushed);
		$component->dyLogCronDaemonEnd(7);
		self::assertStringContainsString('Cron daemon ended after 7 tasks', $inner->flushed);
	}
}
