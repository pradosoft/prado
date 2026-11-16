<?php

namespace Prado\Test\Unit\Util\Email\Cron;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Email\Cron\TDbEmailCleanerTask;

class TDbEmailCleanerTaskTest extends TestCase
{
	public function testProperties()
	{
		$task = new TDbEmailCleanerTask();
		$this->assertSame(7776000, $task->getTimePeriod());
		$task->setTimePeriod('3600');
		$this->assertSame(3600, $task->getTimePeriod());
		$this->assertNull($task->getStatus());
		$task->setStatus('failed');
		$this->assertSame('failed', $task->getStatus());
		$task->setStatus('');
		$this->assertNull($task->getStatus());
	}

	public function testModuleIdMustNameDbEmailModule()
	{
		$task = new TDbEmailCleanerTask();
		$task->setModuleId('no-such-module');
		$this->expectException(TInvalidDataValueException::class);
		$task->execute(null);
	}
}
