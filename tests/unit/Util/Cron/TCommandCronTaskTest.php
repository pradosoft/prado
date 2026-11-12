<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TIOException;
use Prado\IO\Async\TCancellationToken;
use Prado\Util\Cron\TCommandCronTask;
use Prado\Util\Cron\TCronModule;

class TCommandCronTaskTest extends \PHPUnit\Framework\TestCase
{
	private TCommandCronTask $task;

	private string $log;

	protected function setUp(): void
	{
		$this->task = new TCommandCronTask();
		$this->task->setName('command');
		$this->log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-commandtask-' . bin2hex(random_bytes(4)) . '.log';
	}

	protected function tearDown(): void
	{
		if (is_file($this->log)) {
			unlink($this->log);
		}
	}

	public function testProperties()
	{
		self::assertNull($this->task->getCommand());
		self::assertNull($this->task->getDirectory());
		self::assertNull($this->task->getLogFile());
		self::assertTrue($this->task->getAppendLog());
		self::assertNull($this->task->getExitCode());
		$this->task->setCommand('@php -v')->setDirectory(sys_get_temp_dir())->setLogFile($this->log)->setAppendLog('false');
		self::assertSame('@php -v', $this->task->getCommand());
		self::assertSame(sys_get_temp_dir(), $this->task->getDirectory());
		self::assertSame($this->log, $this->task->getLogFile());
		self::assertFalse($this->task->getAppendLog());
		$copy = unserialize(serialize($this->task));
		self::assertSame('@php -v', $copy->getCommand());
		self::assertFalse($copy->getAppendLog());
		$this->task->setLogFile('')->setDirectory('');
		self::assertNull($this->task->getLogFile());
		self::assertNull($this->task->getDirectory());
	}

	public function testExecute_WritesTheOutputToTheLogFile()
	{
		$this->task->setCommand('@php -r "echo \'hello\';"')->setLogFile($this->log);
		$this->task->execute(new TCronModule());
		self::assertSame(0, $this->task->getExitCode());
		self::assertSame('hello', file_get_contents($this->log));
		$this->task->execute(new TCronModule());
		self::assertSame('hellohello', file_get_contents($this->log), 'AppendLog appends each run');
		$this->task->setAppendLog(false);
		$this->task->execute(new TCronModule());
		self::assertSame('hello', file_get_contents($this->log), 'without AppendLog each run starts the file over');
	}

	public function testExecute_WritesOutputAndErrorsInOrder()
	{
		$this->task->setCommand('@php -r "echo \'out\'; fwrite(STDERR, \'err\'); echo \'more\';"')->setLogFile($this->log);
		$this->task->execute(new TCronModule());
		self::assertSame('outerrmore', file_get_contents($this->log), 'the output and errors share the file without overwriting each other');
	}

	public function testExecute_ANonzeroExitThrows()
	{
		$this->task->setCommand('@php -r "exit(3);"');
		try {
			$this->task->execute(new TCronModule());
			self::fail('a nonzero exit code did not throw');
		} catch (TIOException $e) {
			self::assertStringContainsString('code 3', $e->getMessage());
		}
		self::assertSame(3, $this->task->getExitCode());
	}

	public function testExecute_WithoutACommandThrows()
	{
		self::expectException(TConfigurationException::class);
		$this->task->execute(new TCronModule());
	}

	public function testExecute_ACancelledRunTerminatesTheCommand()
	{
		$this->task->setCommand('@php -r "sleep(5);"');
		$this->task->setCancellationToken(new TCancellationToken(0.3));
		$start = microtime(true);
		try {
			$this->task->execute(new TCronModule());
			self::fail('a cancelled run did not throw');
		} catch (TCancelledException $e) {
		}
		self::assertLessThan(4.0, microtime(true) - $start, 'the command is terminated at the cancellation');
	}
}
