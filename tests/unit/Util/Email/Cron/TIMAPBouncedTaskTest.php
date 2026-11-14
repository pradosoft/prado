<?php

namespace Prado\Test\Unit\Util\Email\Cron;

class TIMAPBouncedTaskTest extends \PHPUnit\Framework\TestCase
{
	public function testGetImapDns()
	{
		$task = new \Prado\Util\Email\Cron\TIMAPBouncedTask();
		$this->assertNull($task->getImapDns());
	}

	public function testSetImapDns()
	{
		$task = new \Prado\Util\Email\Cron\TIMAPBouncedTask();
		$task->setImapDns('imap://user:pass@server');
		$this->assertEquals('imap://user:pass@server', $task->getImapDns());
	}

	public function testGetTask()
	{
		$task = new \Prado\Util\Email\Cron\TIMAPBouncedTask();
		// Test that the task returns a valid string representation
		$this->assertIsString($task->getTask());
	}
}