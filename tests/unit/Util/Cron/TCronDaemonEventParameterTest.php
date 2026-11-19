<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronDaemonEventParameter;

class TCronDaemonEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testReasonNull()
	{
		$param = new TCronDaemonEventParameter();
		self::assertSame($param, $param->setReason(TCronDaemonEventParameter::REASON_STOP));
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $param->getReason());
		$param->setReason(null);
		self::assertNull($param->getReason());
	}
}
