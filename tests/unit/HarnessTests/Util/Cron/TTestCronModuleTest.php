<?php

/**
 * TTestCronModuleTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\HarnessTests\Util\Cron;

use Prado\Test\Unit\Harness\Util\Cron\TTestCronModule;
use Prado\Util\Cron\TCronModule;

/**
 * Tests for the {@see TTestCronModule} harness — the fakeable clock/environment seams that cron unit
 * tests rely on. Pins the {@see TTestCronClockTrait} instrumentation contract.
 *
 */
class TTestCronModuleTest extends \PHPUnit\Framework\TestCase
{
	public function testIsConcreteTCronModule()
	{
		$m = new TTestCronModule();
		$this->assertInstanceOf(TCronModule::class, $m);
	}

	public function testFakeNowPinsTime()
	{
		$m = new TTestCronModule();
		$m->fakeNow = 1000;
		$this->assertSame(1000, $m->pubTime());

		$m->fakeNow = null;
		$this->assertEqualsWithDelta(\time(), $m->pubTime(), 2);
	}

	public function testFakeMicrotimePinsMicrotime()
	{
		$m = new TTestCronModule();
		$m->fakeMicrotime = 1234.5;
		$this->assertSame(1234.5, $m->pubMicrotime());

		$m->fakeMicrotime = null;
		$this->assertEqualsWithDelta(\microtime(true), $m->pubMicrotime(), 2.0);
	}

	public function testFakeRandPinsRandom()
	{
		$m = new TTestCronModule();
		$m->fakeRand = 7;
		$this->assertSame(7, $m->pubMtRand());

		$m->fakeRand = null;
		$r = $m->pubMtRand();
		$this->assertGreaterThanOrEqual(0, $r);
		$this->assertLessThanOrEqual(\mt_getrandmax(), $r);
	}

	public function testFakeSapiPinsSapiName()
	{
		$m = new TTestCronModule();
		$m->fakeSapi = 'cli';
		$this->assertSame('cli', $m->pubPhpSapiName());

		$m->fakeSapi = 'apache';
		$this->assertSame('apache', $m->pubPhpSapiName());

		$m->fakeSapi = null;
		$this->assertSame(\php_sapi_name(), $m->pubPhpSapiName());
	}

	public function testTimeSleepUntilRecordsWithoutSleeping()
	{
		$m = new TTestCronModule();
		$this->assertSame([], $m->sleptUntil);

		$start = \microtime(true);
		$result = $m->pubTimeSleepUntil($start + 5.0);

		$this->assertTrue($result);
		$this->assertSame([$start + 5.0], $m->sleptUntil);
		// did not actually sleep 5 seconds
		$this->assertLessThan(1.0, \microtime(true) - $start);
	}

	public function testWaitForWakeRecordsAndReturnsSignal()
	{
		$m = new TTestCronModule();
		$this->assertSame([], $m->waitedFor);

		$start = \microtime(true);
		$this->assertFalse($m->pubWaitForWake($start + 10.0)); // default: no kick

		$m->wakeSignaled = true;
		$this->assertTrue($m->pubWaitForWake($start + 20.0)); // simulated kick

		$this->assertSame([$start + 10.0, $start + 20.0], $m->waitedFor);
		$this->assertLessThan(1.0, \microtime(true) - $start); // did not actually wait
	}
}
