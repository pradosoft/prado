<?php

/**
 * TTestDbCronManagerTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\HarnessTests\Util\Cron;

use Prado\Test\Unit\Harness\Util\Cron\TTestDbCronManager;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TDbCronManager;

/**
 * Tests for the {@see TTestDbCronManager} harness — confirms it is an instrumentable TDbCronManager and
 * pins the {@see TTestCronClockTrait} seam contract inherited from {@see \Prado\Util\Cron\TCronModule}.
 *
 */
class TTestDbCronManagerTest extends \PHPUnit\Framework\TestCase
{
	public function testIsConcreteTDbCronManager()
	{
		$m = new TTestDbCronManager();
		$this->assertInstanceOf(TDbCronManager::class, $m);
		$this->assertInstanceOf(TCronModule::class, $m);
	}

	public function testInheritedClockSeams()
	{
		$m = new TTestDbCronManager();
		$m->fakeNow = 2000;
		$m->fakeMicrotime = 2000.25;
		$this->assertSame(2000, $m->pubTime());
		$this->assertSame(2000.25, $m->pubMicrotime());
	}

	public function testInheritedRandomAndSapiSeams()
	{
		$m = new TTestDbCronManager();
		$m->fakeRand = 3;
		$m->fakeSapi = 'cli';
		$this->assertSame(3, $m->pubMtRand());
		$this->assertSame('cli', $m->pubPhpSapiName());
	}

	public function testInheritedSleepSeamRecordsWithoutSleeping()
	{
		$m = new TTestDbCronManager();
		$start = \microtime(true);
		$result = $m->pubTimeSleepUntil($start + 5.0);

		$this->assertTrue($result);
		$this->assertSame([$start + 5.0], $m->sleptUntil);
		$this->assertLessThan(1.0, \microtime(true) - $start);
	}
}
