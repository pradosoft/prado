<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TCronTaskMode;
use Prado\Util\Cron\TCronTaskOverlap;

class TTestCronTask extends TCronTask 
{
	public function execute($cron)
	{
	}
}
class TCTTModule extends TModule
{
	
}

class TCronTaskTest extends \PHPUnit\Framework\TestCase
{
	protected $obj;
	
	protected function getTestClass()
	{
		return TTestCronTask::class;
	}

	protected function setUp(): void
	{
		$this->obj = Prado::createComponent($this->getTestClass());
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TCronTask::class, $this->obj);
	}
	
	public function testName()
	{
		$value = 'myTaskName';
		$this->obj->setName($value);
		self::assertEquals($value, $this->obj->getName());
	}
	
	public function testGetTask()
	{
		$this->assertInstanceOf(TCronTask::class, $this->obj);
	}
	
	public function testSchedulerHashContextIsTheTaskName()
	{
		$task = new TTestCronTask();
		$task->setName('alpha');
		$task->setSchedule('H * * * ?');
		$scheduler = $task->getScheduler();
		self::assertSame('alpha', $scheduler->getHashContext());
		self::assertSame((crc32("alpha\0minute") % 60) . ' * * * ?', $scheduler->getResolvedSchedule());
		$task->setName('beta');
		self::assertSame('beta', $scheduler->getHashContext(), 'a renamed task re-resolves its hashed schedule');
		self::assertSame((crc32("beta\0minute") % 60) . ' * * * ?', $scheduler->getResolvedSchedule());
		self::assertSame('H * * * ?', $task->getSchedule());
	}

	public function testSchedule()
	{
		$value = '* * * * *';
		$this->obj->setSchedule($value);
		self::assertEquals($value, $this->obj->getSchedule());
		$scheduler = $this->obj->getScheduler();
		self::assertEquals($value, $scheduler->getSchedule());
		
		$value = '*/5 * * * *';
		$this->obj->setSchedule($value);
		self::assertEquals($value, $this->obj->getSchedule());
		self::assertEquals($value, $scheduler->getSchedule());
	}
	
	public function testUserName()
	{
		$value = 'admin';
		$this->obj->setUserName($value);
		self::assertEquals($value, $this->obj->getUserName());
		
		$value = 'cron';
		$this->obj->setUserName($value);
		self::assertEquals($value, $this->obj->getUserName());
	}
	
	public function testModuleId()
	{
		$value = 'myMid';
		$this->obj->setModuleId($value);
		self::assertEquals($value, $this->obj->getModuleId());
	}
	
	public function testGetModule()
	{
		$value = 'cronTaskTestModule';
		$this->obj->setModuleId($value);
		
		$app = Prado::getApplication();
		if(!($module = $app->getModule($value))) {
			$module = new TCTTModule;
			$app->setModule($value, $module);
		}
		
		self::assertEquals($module, $this->obj->getModule());
		
		try {	// module id nonexistant
			$this->obj->setModuleId($value.'2');
			$this->obj->getModule($value.'2');
			self::fail('failed to throw TConfigurationException on ModuleId that does not exist');
		} catch(TConfigurationException $e) {}
		
		//null module id means null module
		if ($this->hasNullModuleId()) {
			$this->obj->setModuleId(null);
			self::assertNull($this->obj->getModule());
		}
	}
	
	public function hasNullModuleId()
	{
		return true;
	}
	
	public function testMode()
	{
		self::assertSame(TCronTaskMode::Serial, $this->obj->getMode());
		$this->obj->setMode('parallel');
		self::assertSame(TCronTaskMode::Parallel, $this->obj->getMode(), 'the mode is case-insensitive');
		$this->obj->setMode(TCronTaskMode::Process);
		self::assertSame(TCronTaskMode::Process, $this->obj->getMode());
		self::assertStringContainsString('_mode', serialize($this->obj), 'a mode other than Serial is serialized');
		$this->obj->setMode('Serial');
		self::assertStringNotContainsString('_mode', serialize($this->obj), 'the default mode is not serialized');
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setMode('Fork');
	}

	public function testOverlap()
	{
		self::assertSame(TCronTaskOverlap::Skip, $this->obj->getOverlap());
		self::assertStringNotContainsString('_overlap', serialize($this->obj));
		$this->obj->setOverlap('true');
		self::assertSame(TCronTaskOverlap::Allow, $this->obj->getOverlap(), 'true is Allow');
		$copy = unserialize(serialize($this->obj));
		self::assertSame(TCronTaskOverlap::Allow, $copy->getOverlap());
		$this->obj->setOverlap(false);
		self::assertSame(TCronTaskOverlap::Skip, $this->obj->getOverlap(), 'false is Skip');
		$this->obj->setOverlap('replace');
		self::assertSame(TCronTaskOverlap::Replace, $this->obj->getOverlap());
		$this->obj->setOverlap('Queue');
		self::assertSame(TCronTaskOverlap::Queue, $this->obj->getOverlap());
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setOverlap('Sometimes');
	}

	public function testProcessPropertiesAndWarnDuration()
	{
		self::assertNull($this->obj->getProcessOutputFile());
		self::assertNull($this->obj->getProcessPriority());
		self::assertNull($this->obj->getProcessMemoryLimit());
		self::assertNull($this->obj->getWarnDuration());
		$this->obj->setProcessOutputFile('/var/log/task.log');
		$this->obj->setProcessPriority('10');
		$this->obj->setProcessMemoryLimit('512M');
		$this->obj->setWarnDuration('90');
		$copy = unserialize(serialize($this->obj));
		self::assertSame('/var/log/task.log', $copy->getProcessOutputFile());
		self::assertSame(10, $copy->getProcessPriority());
		self::assertSame('512M', $copy->getProcessMemoryLimit());
		self::assertSame(90.0, $copy->getWarnDuration());
		$this->obj->setProcessMemoryLimit('-1');
		self::assertSame('-1', $this->obj->getProcessMemoryLimit());
		foreach ([['setProcessPriority', 20], ['setProcessPriority', -21], ['setProcessMemoryLimit', '1T'], ['setWarnDuration', 0]] as [$method, $value]) {
			try {
				$this->obj->$method($value);
				self::fail($method . ' accepted ' . $value);
			} catch (TInvalidDataValueException $e) {
			}
		}
		$this->obj->setProcessPriority('');
		$this->obj->setWarnDuration(null);
		self::assertNull($this->obj->getProcessPriority());
		self::assertNull($this->obj->getWarnDuration());
	}

	public function testTimeZone()
	{
		self::assertNull($this->obj->getTimeZone());
		$this->obj->setSchedule('0 9 * * *');
		$this->obj->setLastExecTime(gmmktime(0, 0, 0, 6, 1, 2026));
		$utc = $this->obj->getNextTriggerTime();
		$this->obj->setTimeZone('Asia/Tokyo');
		self::assertSame('Asia/Tokyo', $this->obj->getTimeZone());
		self::assertSame('Asia/Tokyo', $this->obj->getScheduler()->getTimeZone());
		self::assertNotEquals($utc, $this->obj->getNextTriggerTime(), 'the cached next trigger is cleared');
		self::assertStringContainsString('Asia/Tokyo', serialize($this->obj));
		$this->obj->setTimeZone(null);
		self::assertNull($this->obj->getScheduler()->getTimeZone());
		self::assertStringNotContainsString('_timeZone', serialize($this->obj));
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setTimeZone('Nowhere/Never');
	}

	public function testStartingDeadline()
	{
		self::assertNull($this->obj->getStartingDeadline());
		self::assertStringNotContainsString('_startingDeadline', serialize($this->obj));
		$this->obj->setStartingDeadline('300');
		self::assertSame(300.0, $this->obj->getStartingDeadline());
		$this->obj->setStartingDeadline(0);
		self::assertSame(0.0, $this->obj->getStartingDeadline());
		$this->obj->setStartingDeadline('');
		self::assertNull($this->obj->getStartingDeadline());
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setStartingDeadline(-1);
	}

	public function testDescriptionAndPingUrls()
	{
		self::assertSame('', $this->obj->getDescription());
		$this->obj->setDescription('Nightly backup');
		self::assertSame('Nightly backup', $this->obj->getDescription());
		self::assertStringNotContainsString('_pingUrls', serialize($this->obj));
		foreach (['Before' => TCronTask::PING_BEFORE, 'After' => TCronTask::PING_AFTER, 'Success' => TCronTask::PING_SUCCESS, 'Failure' => TCronTask::PING_FAILURE] as $name => $ping) {
			self::assertNull($this->obj->{'getPing' . $name . 'Url'}());
			$this->obj->{'setPing' . $name . 'Url'}('https://example.test/' . $ping);
			self::assertSame('https://example.test/' . $ping, $this->obj->{'getPing' . $name . 'Url'}());
			self::assertSame('https://example.test/' . $ping, $this->obj->getPingUrl($ping));
		}
		$copy = unserialize(serialize($this->obj));
		self::assertSame('https://example.test/failure', $copy->getPingFailureUrl());
		self::assertSame('Nightly backup', $copy->getDescription());
		$this->obj->setPingFailureUrl('');
		self::assertNull($this->obj->getPingFailureUrl());
		self::assertNull($this->obj->getPingUrl('unknown'));
	}

	public function testProcessCount()
	{
		$value = rand();
		$this->obj->setProcessCount($value);
		self::assertEquals($value, $this->obj->getProcessCount());
	}
	
	public function testLastExecTime()
	{
		// integer value
		$value = time() - 120;
		$this->obj->setLastExecTime($value);
		self::assertEquals($value, $this->obj->getLastExecTime());
		
		// double value
		$value = microtime(true) - 120;
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// double value
		$value = $this->obj->getLastExecTime() - 0.5;
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// string of a numeric
		$value = "9.999";
		$this->obj->setLastExecTime($value);
		self::assertEquals(floor($value), $this->obj->getLastExecTime());
		
		// null value
		$value = null;
		$this->obj->setLastExecTime($value);
		self::assertEquals($value, $this->obj->getLastExecTime());
	}
	
	public function testIsPending()
	{
		$this->obj->setSchedule("* * * * *");
		$this->obj->setLastExecTime(time() - 61);
		self::assertTrue($this->obj->getIsPending());
		
		$this->obj->setLastExecTime(null);
		self::assertTrue($this->obj->getIsPending());
		
		$this->obj->setLastExecTime(time());
		self::assertFalse($this->obj->getIsPending());
		
		$this->obj->setSchedule("0 0 1 1 * 2000");
		$this->obj->setLastExecTime(time());
		self::assertEquals(null, $this->obj->getNextTriggerTime());
		self::assertFalse($this->obj->getIsPending());
	}

	public function testDefaultTimeZone()
	{
		self::assertNull($this->obj->getScheduleTimeZone());
		$this->obj->setDefaultTimeZone('Asia/Tokyo');
		self::assertSame('Asia/Tokyo', $this->obj->getDefaultTimeZone());
		self::assertSame('Asia/Tokyo', $this->obj->getScheduleTimeZone());
		self::assertSame('Asia/Tokyo', $this->obj->getScheduler()->getTimeZone());
		$this->obj->setTimeZone('Europe/Paris');
		self::assertSame('Europe/Paris', $this->obj->getScheduleTimeZone(), 'the task zone wins');
		self::assertSame('Europe/Paris', $this->obj->getScheduler()->getTimeZone());
		self::assertStringNotContainsString('Asia/Tokyo', serialize($this->obj), 'the default zone is not serialized');
	}

	public function testRetries()
	{
		self::assertSame(0, $this->obj->getRetries());
		self::assertSame(0.0, $this->obj->getRetryDelay());
		self::assertSame(2.0, $this->obj->getRetryBackoff());
		$this->obj->setRetries('3');
		$this->obj->setRetryDelay('10');
		self::assertSame([10.0, 20.0, 40.0], [$this->obj->getRetryWait(1), $this->obj->getRetryWait(2), $this->obj->getRetryWait(3)]);
		$this->obj->setRetryBackoff(1);
		self::assertSame(10.0, $this->obj->getRetryWait(3));
		self::assertStringContainsString('_retries', serialize($this->obj));
		foreach ([['setRetries', -1], ['setRetryDelay', -0.5], ['setRetryBackoff', 0.5]] as [$method, $value]) {
			try {
				$this->obj->$method($value);
				self::fail($method . ' accepted ' . $value);
			} catch (TInvalidDataValueException $e) {
			}
		}
	}

	public function testTimeWindows()
	{
		$this->obj->setDefaultTimeZone('UTC');
		self::assertNull($this->obj->getBetween());
		self::assertTrue($this->obj->getIsInWindow(gmmktime(3, 0, 0, 6, 1, 2026)));
		$this->obj->setBetween('8:00-17:00');
		self::assertSame('08:00-17:00', $this->obj->getBetween());
		self::assertTrue($this->obj->getIsInWindow(gmmktime(8, 0, 0, 6, 1, 2026)));
		self::assertTrue($this->obj->getIsInWindow(gmmktime(17, 0, 0, 6, 1, 2026)), 'the end is inside');
		self::assertFalse($this->obj->getIsInWindow(gmmktime(17, 0, 1, 6, 1, 2026)));
		$this->obj->setBetween('');
		$this->obj->setUnlessBetween('23:00-04:00:30');
		self::assertSame('23:00:00-04:00:30', $this->obj->getUnlessBetween());
		self::assertFalse($this->obj->getIsInWindow(gmmktime(2, 0, 0, 6, 1, 2026)), 'a window past midnight wraps');
		self::assertFalse($this->obj->getIsInWindow(gmmktime(23, 30, 0, 6, 1, 2026)));
		self::assertTrue($this->obj->getIsInWindow(gmmktime(12, 0, 0, 6, 1, 2026)));
		$this->obj->setTimeZone('Asia/Tokyo');
		self::assertFalse($this->obj->getIsInWindow(gmmktime(15, 0, 0, 6, 1, 2026)), '15:00 UTC is 00:00 in Tokyo');
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setBetween('25:00-26:00');
	}

	public function testJitter()
	{
		$this->obj->setName('jittered');
		$this->obj->setSchedule('0 * * * *');
		$from = gmmktime(0, 30, 0, 6, 1, 2026);
		$raw = $this->obj->getNextTriggerTime($from);
		self::assertSame(0, $this->obj->getJitter());
		$this->obj->setJitter('300');
		$delayed = $this->obj->getNextTriggerTime($from);
		self::assertGreaterThanOrEqual($raw, $delayed);
		self::assertLessThanOrEqual($raw + 300, $delayed);
		self::assertSame($delayed, $this->obj->getNextTriggerTime($from), 'the delay is stable');
		$times = $this->obj->getNextTriggerTimes(3, $from);
		self::assertSame($delayed, $times[0]);
		self::assertCount(3, $times);
		self::assertEqualsWithDelta(3600, $times[1] - $times[0], 300, 'each trigger keeps its hour');
		$other = clone $this->obj;
		$other->setName('another');
		self::assertNotSame($delayed, $other->getNextTriggerTime($from), 'the delay depends on the task name');
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setJitter(-1);
	}

}
