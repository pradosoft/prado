<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TCronTaskEventParameter;

class TCronTaskEventParameterTest extends \PHPUnit\Framework\TestCase
{
	private function makeTask(): TCronTask
	{
		return new class () extends TCronTask {
			public function execute($cron)
			{
			}
		};
	}

	public function testConstructDefaults()
	{
		$task = $this->makeTask();
		$param = new TCronTaskEventParameter($task);
		self::assertSame($task, $param->getTask());
		self::assertFalse($param->getManual());
		self::assertFalse($param->getCancel());
		self::assertNull($param->getReason());
		self::assertNull($param->getResult());
		self::assertNull($param->getDuration());
		self::assertNull($param->getException());
		self::assertSame([], $param->getChain());
		self::assertNull($param->getTrigger());
		self::assertSame(1, $param->getAttempt());
		self::assertNull($param->getPing());
		self::assertNull($param->getUrl());
		self::assertFalse($param->getHandled());

		self::assertTrue((new TCronTaskEventParameter($task, true))->getManual());
	}

	public function testSettersAreChainable()
	{
		$param = new TCronTaskEventParameter($this->makeTask());
		$exception = new \RuntimeException('failed');
		self::assertSame($param, $param->setCancel('true'));
		self::assertTrue($param->getCancel());
		self::assertSame($param, $param->setReason(TCronTaskEventParameter::REASON_PAUSED));
		self::assertSame(TCronTaskEventParameter::REASON_PAUSED, $param->getReason());
		self::assertSame($param, $param->setResult('ok'));
		self::assertSame('ok', $param->getResult());
		self::assertSame($param, $param->setDuration(1.5));
		self::assertSame(1.5, $param->getDuration());
		self::assertSame($param, $param->setException($exception));
		self::assertSame($exception, $param->getException());
		self::assertSame($param, $param->setChain(['a', 'b']));
		self::assertSame(['a', 'b'], $param->getChain());
		self::assertSame($param, $param->setTrigger(1000));
		self::assertSame(1000, $param->getTrigger());
		self::assertSame($param, $param->setAttempt(3));
		self::assertSame(3, $param->getAttempt());
		self::assertSame($param, $param->setPing(TCronTask::PING_BEFORE));
		self::assertSame(TCronTask::PING_BEFORE, $param->getPing());
		self::assertSame($param, $param->setUrl('https://example.com/ping'));
		self::assertSame('https://example.com/ping', $param->getUrl());
		self::assertSame($param, $param->setHandled('1'));
		self::assertTrue($param->getHandled());

		$param->setReason(null)->setResult(null)->setDuration(null)->setException(null)->setTrigger(null)->setPing(null)->setUrl(null);
		self::assertNull($param->getReason());
		self::assertNull($param->getResult());
		self::assertNull($param->getDuration());
		self::assertNull($param->getException());
		self::assertNull($param->getTrigger());
		self::assertNull($param->getPing());
		self::assertNull($param->getUrl());
		$param->setCancel(false)->setHandled(false);
		self::assertFalse($param->getCancel());
		self::assertFalse($param->getHandled());
	}
}
