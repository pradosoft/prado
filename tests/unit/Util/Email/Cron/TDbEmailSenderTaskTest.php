<?php

namespace Prado\Test\Unit\Util\Email\Cron;

class TDbEmailSenderTaskTest extends \PHPUnit\Framework\TestCase
{
	public function testGetTask()
	{
		$task = new \Prado\Util\Email\Cron\TDbEmailSenderTask();
		// Test that the task returns a valid string representation
		$this->assertIsString($task->getTask());
	}
}