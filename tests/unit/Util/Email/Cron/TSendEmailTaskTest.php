<?php

namespace Prado\Test\Unit\Util\Email\Cron;

class TSendEmailTaskTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$email = $this->createMock(\Prado\Util\Email\TEmail::class);
		$email->expects($this->any())
			->method('getMessageId')
			->willReturn('test-message-id');
		$email->expects($this->any())
			->method('getUsername')
			->willReturn('test-user');
		$email->expects($this->any())
			->method('getEmailerModule')
			->willReturn(null);
		
		$task = new \Prado\Util\Email\Cron\TSendEmailTask($email);
		
		// Verify basic properties were set during construction
		$this->assertNotNull($task->getName());
		$this->assertEquals('test-user', $task->getUsername());
		$this->assertTrue($task->getAutoRemove());
	}
	
	public function testGetTask()
	{
		$task = new \Prado\Util\Email\Cron\TSendEmailTask();
		// Test that the task returns a valid string representation
		$this->assertIsString($task->getTask());
	}
}