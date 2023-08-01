<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponent;
use Prado\Prado;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;


class TCaptureForkLogTest extends PHPUnit\Framework\TestCase
{

	protected function setUp(): void
	{
	}
	

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		
		if ($app->asa(TCaptureForkLog::BEHAVIOR_NAME))
			$app->detachBehavior(TCaptureForkLog::BEHAVIOR_NAME);
	}

	public function testFork()
	{
		$logger = Prado::getLogger();

		$logger->deleteLogs();

		Prado::notice('parent notice');
		Prado::profileBegin('token1');
		
		$withCaptureLog = true;
		$pid = TProcessHelper::fork($withCaptureLog);
		
		if ($pid === -1) {
			self::fail("failed to fork.");
		} elseif ($pid === 0) {
			Prado::profileBegin('token2');
			Prado::profileBegin('token3');
			self::assertLessThan(1, Prado::profileEnd('token1'));
			Prado::profileEnd('token2');
			Prado::profileEnd('token4');
			Prado::warning('child warning');
			exit();
		} else {
			$app = Prado::getApplication();
			Prado::profileEnd('token2');
			$app->receiveLogsFromChildren();
			self::assertEquals(4, count($logger->getLogs(pid: getMyPid())));
			self::assertEquals(8, count($logs = $logger->getLogs(pid: $pid)));
			self::assertEquals('token1', $logs[0][0]);
			//$logs[1][0] = 'Executing child fork...'
			self::assertEquals('token2', $logs[2][0]);
			self::assertEquals('token3', $logs[3][0]);
			self::assertEquals('token1', $logs[4][0]);
			self::assertEquals('token2', $logs[5][0]);
			self::assertEquals('token4', $logs[6][0]);
			self::assertEquals('child warning', $logs[7][0]);
			
			self::assertEquals(12, count($logger->getLogs()));
		}
	}
	
}
