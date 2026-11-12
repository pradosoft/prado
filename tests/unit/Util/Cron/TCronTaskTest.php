<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TCronTaskMode;

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
		self::assertFalse($this->obj->getOverlap());
		self::assertStringNotContainsString('_overlap', serialize($this->obj));
		$this->obj->setOverlap('true');
		self::assertTrue($this->obj->getOverlap());
		$copy = unserialize(serialize($this->obj));
		self::assertTrue($copy->getOverlap());
		$this->obj->setOverlap(false);
		self::assertFalse($this->obj->getOverlap());
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
}
