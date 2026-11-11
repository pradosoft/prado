<?php

namespace Prado\Test\Unit\Util\Email\Cron;

class TDbEmailCleanerTaskTest extends \PHPUnit\Framework\TestCase
{
	public function testGetTimePeriod()
	{
		$task = new \Prado\Util\Email\Cron\TDbEmailCleanerTask();
		$this->assertEquals(2419200, $task->getTimePeriod()); // 28 days by default
	}

	public function testSetTimePeriod()
	{
		$task = new \Prado\Util\Email\Cron\TDbEmailCleanerTask();
		$task->setTimePeriod(1000);
		$this->assertEquals(1000, $task->getTimePeriod());
	}

	public function testGetTask()
	{
		$task = new \Prado\Util\Email\Cron\TDbEmailCleanerTask();
		// Test that the task returns a valid string representation
		$this->assertIsString($task->getTask());
	}
}