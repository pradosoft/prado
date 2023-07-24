<?php

use Prado\Util\TSignalParameter;

class TTestSignalParameter extends TSignalParameter {
}

class TSignalParameterTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testConstruct()
	{
		$param = new TTestSignalParameter($signal = 20, $exit = false, $exitCode = 0, ['signo' => 20, 'errno' => $errno = 2, 'code' => $code = 1, 'status' => $status = 3, 'pid' => $pid = 1810, 'uid' => $uid = 501]);
		
		self::assertEquals($signal, $param->getSignal());
		self::assertEquals($exit, $param->getIsExiting());
		self::assertEquals($exitCode, $param->getExitCode());
		self::assertEquals($errno, $param->getParameterErrorNumber());
		self::assertEquals($code, $param->getParameterCode());
		self::assertEquals($status, $param->getParameterStatus());
		self::assertEquals($pid, $param->getParameterPID());
		self::assertEquals($uid, $param->getParameterUID());
			
		$param = new TTestSignalParameter();
		
		self::assertEquals(0, $param->getSignal());
		self::assertFalse($param->getIsExiting());
		self::assertEquals(0, $param->getExitCode());
		self::assertNull($param->getParameterErrorNumber());
		self::assertNull($param->getParameterCode());
		self::assertNull($param->getParameterStatus());
		self::assertNull($param->getParameterPID());
		self::assertNull($param->getParameterUID());
	}
	
	public function testSignal()
	{
		$param = new TTestSignalParameter();
		
		$param->setSignal($ref = 3);
		self::assertEquals($ref, $param->getSignal());
		$param->setSignal($ref = 5);
		self::assertEquals($ref, $param->getSignal());
	}
	
	public function testIsExiting()
	{
		$param = new TTestSignalParameter();
		
		$param->setIsExiting($ref = true);
		self::assertEquals($ref, $param->getIsExiting());
		$param->setIsExiting($ref = false);
		self::assertEquals($ref, $param->getIsExiting());
		$param->setIsExiting($ref = true);
		self::assertEquals($ref, $param->getIsExiting());
	}
	
	public function testExitCode()
	{
		$param = new TTestSignalParameter();
		
		$param->setExitCode($ref = 3);
		self::assertEquals($ref, $param->getExitCode());
		$param->setExitCode($ref = 5);
		self::assertEquals($ref, $param->getExitCode());
		$param->setExitCode($ref = 0);
		self::assertEquals($ref, $param->getExitCode());
	}
	
	public function testAlarmTime()
	{
		$param = new TTestSignalParameter();
		
		$param->setAlarmTime($ref = time());
		self::assertEquals($ref, $param->getAlarmTime());
		$param->setAlarmTime($ref = time() - 2);
		self::assertEquals($ref, $param->getAlarmTime());
		$param->setAlarmTime($ref = time() + 10);
		self::assertEquals($ref, $param->getAlarmTime());
	}
	
	public function testParameterValues()
	{
		$param = new TTestSignalParameter();
		
		$param->setParameter(['signo' => 20, 'errno' => $errno = 2, 'code' => $code = 1, 'status' => $status = 3, 'pid' => $pid = 1810, 'uid' => $uid = 501]);
		self::assertEquals($errno, $param->getParameterErrorNumber());
		self::assertEquals($code, $param->getParameterCode());
		self::assertEquals($status, $param->getParameterStatus());
		self::assertEquals($pid, $param->getParameterPID());
		self::assertEquals($uid, $param->getParameterUID());
	}
}

