<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronDaemonEventParameter;

class TCronDaemonEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testDefaults()
	{
		$param = new TCronDaemonEventParameter();
		self::assertSame('', $param->getLockName());
		self::assertFalse($param->getWatching());
		self::assertSame(0, $param->getTasks());
		self::assertSame(0.0, $param->getElapsed());
		self::assertSame(0.0, $param->getWait());
		self::assertNull($param->getReason());
	}

	public function testSettersConvertAndChain()
	{
		$param = new TCronDaemonEventParameter();
		self::assertSame($param, $param->setLockName('cron:daemon:cron'));
		self::assertSame('cron:daemon:cron', $param->getLockName());
		self::assertSame($param, $param->setWatching('true'));
		self::assertTrue($param->getWatching());
		self::assertSame($param, $param->setTasks('7'));
		self::assertSame(7, $param->getTasks());
		self::assertSame($param, $param->setElapsed('2.5'));
		self::assertSame(2.5, $param->getElapsed());
		self::assertSame($param, $param->setWait('3.25'));
		self::assertSame(3.25, $param->getWait());
	}

	public function testWaitIsNotNegative()
	{
		$param = new TCronDaemonEventParameter();
		$param->setWait(-4);
		self::assertSame(0.0, $param->getWait());
	}

	public function testReasonNull()
	{
		$param = new TCronDaemonEventParameter();
		self::assertSame($param, $param->setReason(TCronDaemonEventParameter::REASON_STOP));
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $param->getReason());
		$param->setReason(null);
		self::assertNull($param->getReason());
	}
}
