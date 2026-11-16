<?php

namespace Prado\Test\Unit\Util\Email\Cron;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Email\Cron\TDbEmailSenderTask;

class TDbEmailSenderTaskTest extends TestCase
{
	public function testLimit()
	{
		$task = new TDbEmailSenderTask();
		$this->assertSame(0, $task->getLimit());
		$task->setLimit(25);
		$this->assertSame(25, $task->getLimit());
	}

	public function testModuleIdMustNameDbEmailModule()
	{
		$task = new TDbEmailSenderTask();
		$task->setModuleId('no-such-module');
		$this->expectException(TInvalidDataValueException::class);
		$task->execute(null);
	}
}
