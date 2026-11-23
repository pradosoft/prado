<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronSweepEventParameter;
use Prado\Util\Cron\TCronTask;

class TCronSweepEventParameterTest extends \PHPUnit\Framework\TestCase
{
	private function makeTask(string $name): TCronTask
	{
		$task = new class () extends TCronTask {
			public function execute($cron)
			{
			}
		};
		$task->setName($name);
		return $task;
	}

	public function testConstructDefaults()
	{
		$param = new TCronSweepEventParameter();
		self::assertSame([], $param->getTasks());
		self::assertSame(0, $param->getCount());
		self::assertFalse($param->getDaemon());
		self::assertSame([], $param->getResults());
		self::assertSame(0, $param->getFailed());

		$a = $this->makeTask('a');
		$param = new TCronSweepEventParameter(['a' => $a], true);
		self::assertSame(['a' => $a], $param->getTasks());
		self::assertSame(1, $param->getCount());
		self::assertTrue($param->getDaemon());
	}

	public function testSetTasksKeysByName()
	{
		$a = $this->makeTask('a');
		$b = $this->makeTask('b');
		$param = new TCronSweepEventParameter(['x' => $a]);
		self::assertSame($param, $param->setTasks(new \ArrayIterator([$b, $a])));
		self::assertSame(['b' => $b, 'a' => $a], $param->getTasks());
		self::assertSame(2, $param->getCount());

		$param->setTasks([$a]);
		self::assertSame(['a' => $a], $param->getTasks());

		$param->setTasks([]);
		self::assertSame([], $param->getTasks());
	}

	public function testDaemonResultsAndFailed()
	{
		$param = new TCronSweepEventParameter();
		self::assertSame($param, $param->setDaemon('true'));
		self::assertTrue($param->getDaemon());
		$param->setDaemon(false);
		self::assertFalse($param->getDaemon());

		self::assertSame($param, $param->setResults(['a' => true, 'b' => false, 'c' => false]));
		self::assertSame(['a' => true, 'b' => false, 'c' => false], $param->getResults());
		self::assertSame(2, $param->getFailed());

		$param->setResults(['a' => true]);
		self::assertSame(0, $param->getFailed());
	}
}
