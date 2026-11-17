<?php

namespace Prado\Test\Unit\Util;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TExitException;
use Prado\TEventSubscription;
use Prado\Test\Unit\Harness\Util\Clock\TTestMonotonicClock;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TSignalsDispatcher;
use Prado\Util\TSignalParameter;
use Prado\Collections\TWeakCallableCollection;
use Prado\Util\Helpers\TProcessHelper;

class TTestSignalInvokable {
	public $data = null;
	
	public $signal = null;
	public $sigInfo = null;
	public function __construct($data)
	{
		$this->data = $data;
	}
	public function __invoke($signal, $sigInfo)
	{
		$this->signal = $signal;
		$this->sigInfo = $sigInfo;
	}
}

class TTestNoSignalsDispatcher extends TSignalsDispatcher
{
	public static function hasSignals(): bool
	{
		return false;
	}
}

class TSignalsDispatcherTest extends \PHPUnit\Framework\TestCase
{
	public $dispatcher = null;
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
		if ($this->dispatcher) {
			$this->dispatcher->detach();
			$this->dispatcher = null;
		}
		TTestSignalsDispatcher::setPriorHandlerPriority(null);
	}
	
	/**
	 * Asserts an alarm time set for $seconds between the times $before and $after;
	 * a slow run, as under coverage, can cross second boundaries in between.
	 */
	private static function assertAlarmTime(int $before, int $seconds, ?int $alarmTime, int $after): void
	{
		self::assertNotNull($alarmTime);
		self::assertGreaterThanOrEqual($before + $seconds - 1, $alarmTime);
		self::assertLessThanOrEqual($after + $seconds, $alarmTime);
	}

	public function testConstruct()
	{
		if (TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::__construct without signals.");
			return;
		}
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		$this->dispatcher = new TTestSignalsDispatcher();
		self::assertEquals($this->dispatcher, TTestSignalsDispatcher::singleton(false));
	}
	
	public function testSingleton()
	{
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		if (!TSignalsDispatcher::hasSignals()) {
			self::assertNull(TTestSignalsDispatcher::singleton());
		} else {
			self::assertInstanceOf(TTestSignalsDispatcher::class, $this->dispatcher = TTestSignalsDispatcher::singleton());
		}
	}
	
	public function testGetSignalFromEvent()
	{
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			self::assertEquals($signal, TTestSignalsDispatcher::getSignalFromEvent($event));
		}
		self::assertNull(TTestSignalsDispatcher::singleton(false));
	}
	
	public function testAttachDetach()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::attach.");
			return;
		}

		// Preset custom handlers, stored on attach, to be restored on detach
		$ogHandler = new TTestSignalInvokable(1);
		
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, $ogHandler);
		}
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		$priorAsync = TTestSignalsDispatcher::getAsyncSignals();
		self::assertNull(TTestSignalsDispatcher::getPriorHandlerPriority());
		
		// attach new signals dispatcher.
		self::assertTrue(TTestSignalsDispatcher::setPriorHandlerPriority(5));
		$this->dispatcher = new TTestSignalsDispatcher();
		
		// attach state
		{ // cannot set PriorHandlerPriority after attach
			try {
				self::assertFalse(TTestSignalsDispatcher::setPriorHandlerPriority(8));
				self::fail("failed to throw TInvalidOperationException when setting prior handler priority when already used.");
			} catch(TInvalidOperationException $e) {
			}
			self::assertEquals(5, TTestSignalsDispatcher::getPriorHandlerPriority());
		}
		self::assertTrue($this->dispatcher->getAsyncSignals());
		
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			self::assertEquals($this->dispatcher, pcntl_signal_get_handler($signal));
				
			$handlers = $this->dispatcher->getEventHandlers($event);
			$c = $handlers->getCount();
			$handler = $handlers[0];
			if ($signal !== SIGALRM)
				self::assertEquals(5, $handlers->priorityOf($handler));
			if ($signal === SIGALRM) {
				self::assertTrue($handlers->contains([$this->dispatcher, 'ring']));
			} elseif ($signal === SIGCHLD) {
				self::assertTrue($handlers->contains([$this->dispatcher, 'delegateChild']));
			}
		}

		$this->dispatcher->detach();

		// original handlers restored
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			self::assertEquals($ogHandler, pcntl_signal_get_handler($signal), "$event signal does not match the original");
			
			$handlers = $this->dispatcher->getEventHandlers($event);
			self::assertFalse($handlers->contains($handler));
			if ($signal === SIGALRM) {
				self::assertFalse($handlers->contains([$this->dispatcher, 'ring']));
			} elseif ($signal === SIGCHLD) {
				self::assertFalse($handlers->contains([$this->dispatcher, 'delegateChild']));
			}
		}
		self::assertEquals($priorAsync, $this->dispatcher->getAsyncSignals());
		TTestSignalsDispatcher::setPriorHandlerPriority(null);
		
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, SIG_DFL);
		}
	}
	
	public function testHasEvent_Handler_Get()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::getEventHandler.");
			return;
		}
		
		// Preset custom handlers, stored on attach, to be restored on detach
		$handler = new TTestSignalInvokable(2);
		
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, $handler);
		}
		
		// New PID
		$command = TProcessHelper::filterCommand(['@php', '-r', 'sleep(5);']);
		
		$descriptorspec = [];
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$process = proc_open($command, $descriptorspec, $pipes);
		$info = proc_get_status($process);
		$pid = $info['pid'];
		
		{ // With valid pid
			// Signal rather than event name: hasEvent, hasEventHandler, getEventHandlers
			foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
				self::assertTrue($this->dispatcher->hasEvent($signal));
				self::assertTrue($this->dispatcher->hasEventHandler($signal));
				self::assertEquals(in_array($signal, [SIGCHLD]) ? 2 : 1, $this->dispatcher->getEventHandlers($signal)->count(), 'failed on '. $event);
			}

			// handles pids
			self::assertTrue($this->dispatcher->hasEvent('Pid: ' . $pid));
			self::assertTrue($this->dispatcher->hasEvent('pid:' . $pid));
			
			self::assertFalse($this->dispatcher->hasEventHandler('pid:' . $pid));
			
			self::assertInstanceOf(TWeakCallableCollection::class, $pidHandlers = $this->dispatcher->getEventHandlers('pid:' . $pid));
			
			$sub = new TEventSubscription($this->dispatcher, 'pid:' . $pid, function($sender, $param) {});
			
			self::assertTrue($this->dispatcher->hasEventHandler('pid:' . $pid));
			
			$sub->unsubscribe();
			
			self::assertFalse($this->dispatcher->hasEventHandler('pid:' . $pid));
			
			self::assertInstanceOf(TWeakCallableCollection::class, $pidHandlers = $this->dispatcher->getEventHandlers('pid:' . $pid));
		}
		
		// End the PID
		self::assertTrue(TProcessHelper::kill($pid)); // kill pid despite sleeping.
		proc_close($process);
		
		self::assertNull($this->dispatcher->getEventHandlers('pid:' . $pid));
		
		// detach dispatcher
		$this->dispatcher->detach();
		
		// reset signal handlers
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, SIG_DFL);
		}
		
		self::assertFalse($this->dispatcher->hasEvent('pid:' . $pid));
	}
	
	public function testTEventSubscription()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = TSignalsDispatcher::singleton();
		$subscription = new TEventSubscription($this->dispatcher, SIGTERM, $handler = function($sender, $param) {return true;}, 5);
		self::assertTrue($subscription->getIsSubscribed());
		$handlers = $this->dispatcher->getEventHandlers('fxSignalTerminate');
		self::assertEquals($handlers, $subscription->getCollection());
		self::assertTrue($handlers->contains($handler));
		$subscription->unsubscribe();
		self::assertFalse($subscription->getIsSubscribed());
		self::assertFalse($handlers->contains($handler));
	}
	
	public function testPidHandlers()
	{
		// New PID
		$command = TProcessHelper::filterCommand(['@php', '-r', 'sleep(5);']);
		
		$descriptorspec = [];
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$process = proc_open($command, $descriptorspec, $pipes);
		$info = proc_get_status($process);
		$pid = $info['pid'];
		
		{ // With valid pid
			self::assertFalse($this->dispatcher->hasPidHandler($pid));
			$handlers = $this->dispatcher->getPidHandlers($pid);
			self::assertTrue($this->dispatcher->hasPidHandler($pid));
			$handlers = $this->dispatcher->getPidHandlers($pid, true);
			self::assertTrue($this->dispatcher->hasPidHandler($pid));
			
			$this->dispatcher->clearPidHandlers($pid);
			
			$handler = function ($sender, $param) {
				
			};
			self::assertTrue($this->dispatcher->attachPidHandler($pid, $handler, 5, true));
			$handlers = $this->dispatcher->getPidHandlers($pid);
			self::assertTrue($handlers->contains($handler));
			self::assertEquals(5, $handlers->priorityOf($handler));
			
			self::assertFalse($this->dispatcher->detachPidHandler($pid, function ($sender, $param) {}));
			self::assertFalse($this->dispatcher->detachPidHandler($pid, $handler, 10));
			self::assertTrue($this->dispatcher->hasPidHandler($pid));
			self::assertTrue($this->dispatcher->detachPidHandler($pid, $handler, 5));
			self::assertFalse($this->dispatcher->hasPidHandler($pid));
			self::assertFalse($this->dispatcher->detachPidHandler($pid, $handler, 5));
		}
		
		// End the PID
		self::assertTrue(TProcessHelper::kill($pid)); // kill pid despite sleeping.
		proc_close($process);
		
		
		// detach dispatcher
		$this->dispatcher->detach();
		
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
		self::assertNull($this->dispatcher->getPidHandlers($pid, true));
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
			
		self::assertInstanceOf(TWeakCallableCollection::class, $handlers = $this->dispatcher->getPidHandlers($pid));
		self::assertTrue($this->dispatcher->hasPidHandler($pid));
		self::assertTrue($this->dispatcher->clearPidHandlers($pid));
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
		
		self::assertFalse($this->dispatcher->attachPidHandler($pid, $handler, 5, true));
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
		self::assertTrue($this->dispatcher->attachPidHandler($pid, $handler));
		self::assertTrue($this->dispatcher->clearPidHandlers($pid));
		self::assertFalse($this->dispatcher->clearPidHandlers($pid));
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
	}

	public function testRaiseEvent()
	{
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$sentParam = null;
		$handler = function ($sender, $param) use (&$sentParam) {
			$sentParam = $param;
		};
		$param = new TSignalParameter();
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			$subscription = new TEventSubscription($this->dispatcher, $event, $handler);
			$this->dispatcher->raiseEvent($signal, $this->dispatcher, $param);
			self::assertEquals(strtolower($event), $sentParam->getEventName());
		}
	}
	
	public function testInvoke()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		// Preset custom handlers, stored on attach, to be restored on detach
		$ogHandler = new TTestSignalInvokable(8);
		
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, $ogHandler);
		}
		
		$this->dispatcher = new TTestSignalsDispatcher();
		
		self::assertNull(($this->dispatcher)(-555));
		
		$sentSender = null;
		$sentParam = null;
		$handler = function ($sender, $param) use (&$sentSender, &$sentParam) {
			$sentSender = $sender;
			$sentParam = $param;
		};
		$param = new TSignalParameter();
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			$exception = false;
			$subscription = new TEventSubscription($this->dispatcher, $signal, $handler);
			try {
				($this->dispatcher)($signal, $ref = ['uid' => 13]);
			} catch(TExitException $e) {
				$exception = true;
			}
			if ($exception || isset(TSignalsDispatcher::EXIT_SIGNALS[$signal])) {
				if (isset(TSignalsDispatcher::EXIT_SIGNALS[$signal]) && !$exception) {
					self::fail("TExitException not thrown on exiting signal");
				} elseif (!isset(TSignalsDispatcher::EXIT_SIGNALS[$signal]) && $exception) {
					self::fail("TExitException thrown when not exiting during $event");
				}
			}
			self::assertEquals($this->dispatcher, $sentSender);
			self::assertEquals(strtolower($event), $sentParam->getEventName());
			
			if ($signal !== SIGALRM) {
				self::assertEquals($signal, $ogHandler->signal, "$event did not signal");
				self::assertEquals(is_array($ref), is_array($ogHandler->sigInfo));
			}
		}
		
		$this->dispatcher->detach();
		
		// reset signal handlers
		foreach(TSignalsDispatcher::SIGNAL_MAP as $signal => $event) {
			pcntl_signal($signal, SIG_DFL);
		}
	}
	
	public function testInitialAttachAlarm()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::alarm and ::disarm.");
			return;
		}
		
		$originalAlarm = pcntl_signal_get_handler(SIGALRM);
		try {
			// Preset custom handlers, stored on attach, to be restored on detach
			$ogHandler = new TTestSignalInvokable(3);
			pcntl_signal(SIGALRM, $ogHandler);
			$before = time();
			pcntl_alarm(3);

			$this->dispatcher = new TTestSignalsDispatcher();

			$alarmTime = TTestSignalsDispatcher::alarm();
			self::assertAlarmTime($before, 3, $alarmTime, time());

			self::assertEquals($alarmTime, TTestSignalsDispatcher::disarm($ogHandler, $alarmTime));

			self::assertNull(TTestSignalsDispatcher::alarm());

			$alarm1 = null;
			$before = time();
			$time1 = TTestSignalsDispatcher::alarm(4, $f1 = function ($sender, $param) use (&$alarm1) {$alarm1 = $param;});
			self::assertAlarmTime($before, 4, $time1, time());
			self::assertEquals($time1, TTestSignalsDispatcher::alarm());

			$before = time();
			$time2 = TTestSignalsDispatcher::alarm(2, $f2 = function ($sender, $param) use (&$alarm1) {$alarm1 = $param;});
			self::assertAlarmTime($before, 2, $time2, time());
			self::assertEquals($time2, TTestSignalsDispatcher::alarm());

			self::assertEquals($time2, TTestSignalsDispatcher::disarm($f2));
			self::assertEquals($time1, TTestSignalsDispatcher::alarm());
			self::assertEquals($time1, TTestSignalsDispatcher::disarm($f1));

			$this->dispatcher->detach();
			$this->dispatcher = null;
		} finally {
			pcntl_alarm(0);
			pcntl_signal(SIGALRM, $originalAlarm);
		}
	}
	
	public function testAlarmDisarm()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::alarm and ::disarm.");
			return;
		}

		self::assertEquals(SIG_DFL, pcntl_signal_get_handler(SIGALRM));
		try {
			// Preset custom handlers, stored on attach, to be restored on detach
			$handler = new TTestSignalInvokable(5);
			pcntl_signal(SIGALRM, $handler);

			$this->dispatcher = new TTestSignalsDispatcher();

			// no initial alarm.
			self::assertNull(TTestSignalsDispatcher::alarm());

			$alarm1 = null;
			$before = time();
			$alarmTime1 = TTestSignalsDispatcher::alarm(5, $f1 = function($sender, $param) use (&$alarm1) {$alarm1 = $param;} );
			self::assertAlarmTime($before, 5, $alarmTime1, time());
			self::assertEquals($alarmTime1, TTestSignalsDispatcher::alarm());

			$alarm2 = null;
			$before = time();
			$alarmTime2 = TTestSignalsDispatcher::alarm(2, $f2 = function($sender, $param) use (&$alarm2) {$alarm2 = $param;} );
			self::assertAlarmTime($before, 2, $alarmTime2, time());
			self::assertEquals($alarmTime2, TTestSignalsDispatcher::alarm());
			self::assertGreaterThan($alarmTime2, $alarmTime1);

			self::assertNull(TTestSignalsDispatcher::disarm(-1, $f1));

			self::assertEquals($alarmTime2, TTestSignalsDispatcher::disarm($f2));

			self::assertEquals($alarmTime1, TTestSignalsDispatcher::alarm());

			self::assertEquals($alarmTime1, TTestSignalsDispatcher::disarm($f1));

			self::assertNull(TTestSignalsDispatcher::alarm());

			$before = time();
			$alarmTime3 = TTestSignalsDispatcher::alarm(2);
			self::assertAlarmTime($before, 2, $alarmTime3, time());
			self::assertEquals($alarmTime3, TTestSignalsDispatcher::alarm());

			self::assertEquals($alarmTime3, TTestSignalsDispatcher::disarm($alarmTime3));

			self::assertNull(TTestSignalsDispatcher::alarm());
		} finally {
			pcntl_alarm(0);
			if ($this->dispatcher) {
				$this->dispatcher->detach();
				$this->dispatcher = null;
			}
			pcntl_signal(SIGALRM, SIG_DFL);
		}
	}
	
	public function testRing()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::ring.");
			return;
		}
		
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$called = [];
		$handler = function ($sender, $parameter) use (&$called) {
			$called[] = $parameter->getAlarmTime();
		};
		
		$time = $this->dispatcher->setupAlarms($handler);
		
		$param = new TSignalParameter(SIGALRM);
		$this->dispatcher->ring($this->dispatcher, $param);
		
		self::assertLessThan(2, $called[0] - $time);
		self::assertLessThan(2, $called[1] - $time);
		self::assertEquals(2, count($called));
	}

	public function testAlarmTimesFollowTheMonotonicClock()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . " alarm times.");
			return;
		}
		// Alarm times read the wall clock once, as the anchor, so a later wall-clock step cannot move an alarm.
		$this->dispatcher = new TTestSignalsDispatcher();
		self::assertSame($this->dispatcher, TTestSignalsDispatcher::singleton(false));
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		self::assertSame($this->dispatcher, $this->dispatcher->setMonotonicClock($clock));
		$base = $this->dispatcher->getAlarmNow();
		self::assertEqualsWithDelta(time(), $base, 1, 'The alarm time base is anchored to the Unix time.');

		$called = [];
		$time = TTestSignalsDispatcher::alarm(5, function ($sender, $param) use (&$called) {
			$called[] = $param->getAlarmTime();
		});
		self::assertSame($base + 5, $time);
		self::assertSame($this->dispatcher->getAlarmNow(), TTestSignalsDispatcher::alarmNow(), 'The static alarmNow() is the singleton\'s.');
		self::assertSame(5, $time - TTestSignalsDispatcher::alarmNow(), 'The seconds until an alarm.');

		$clock->advance(4);
		self::assertSame(1, $time - TTestSignalsDispatcher::alarmNow());
		$this->dispatcher->ring($this->dispatcher, new TSignalParameter(SIGALRM));
		self::assertSame([], $called, 'The alarm is not due after 4 s on the monotonic clock.');

		$clock->advance(1);
		$this->dispatcher->ring($this->dispatcher, new TSignalParameter(SIGALRM));
		self::assertSame([$base + 5], $called, 'The alarm rings after 5 s on the monotonic clock.');
		self::assertNull(TTestSignalsDispatcher::alarm(), 'No alarm is pending.');

		$this->dispatcher->setMonotonicClock(new TTestMonotonicClock(7_000_000_000));
		self::assertSame($base + 5, $this->dispatcher->getAlarmNow(), 'A new clock continues the alarm time base.');
		pcntl_alarm(0);
	}
	
	public function testDisarmKeepsTheOtherAlarmsOfTheTimeAndRearmsForTheNext()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::disarm.");
			return;
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		$this->dispatcher->setMonotonicClock($clock);

		// Disarming one callback keeps the others of the same alarm time.
		$called = [];
		$time = TTestSignalsDispatcher::alarm(5, $f1 = function () use (&$called) { $called[] = 'f1'; });
		self::assertSame($time, TTestSignalsDispatcher::alarm(5, $f2 = function () use (&$called) { $called[] = 'f2'; }));
		self::assertSame($time, TTestSignalsDispatcher::disarm($f1));
		self::assertSame($time, TTestSignalsDispatcher::alarm(), 'f2 is still pending at the same time.');
		$clock->advance(5);
		$this->dispatcher->ring($this->dispatcher, new TSignalParameter(SIGALRM));
		self::assertSame(['f2'], $called);
		pcntl_alarm(0);

		// Disarming the next alarm arms the OS alarm for the remaining seconds of the following one.
		$near = TTestSignalsDispatcher::alarm(2, $g1 = function () {});
		$far = TTestSignalsDispatcher::alarm(10, $g2 = function () {});
		self::assertSame($near, TTestSignalsDispatcher::disarm($g1));
		self::assertSame($far, TTestSignalsDispatcher::alarm());
		self::assertContains(pcntl_alarm(0), [9, 10], 'The OS alarm waits for the far alarm, not 1 s.');

		// A following alarm that is already due arms the OS alarm for 1 s instead of cancelling it.
		$near = TTestSignalsDispatcher::alarm(2, $h1 = function () {});
		$clock->advance(20);
		self::assertSame($near, TTestSignalsDispatcher::disarm($h1));
		self::assertSame($far, TTestSignalsDispatcher::alarm());
		self::assertSame(1, pcntl_alarm(0), 'An overdue alarm still rings.');
		TTestSignalsDispatcher::disarm($g2);
		pcntl_alarm(0);
	}

	public function testDelegateChild()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$this->dispatcher->delegateChild($this->dispatcher, null);
		
		$param = new TSignalParameter(SIGCHLD);
		$this->dispatcher->delegateChild($this->dispatcher, $param);
		
		$param->setParameter(['pid' => $pid = 55, 'code' => 0]);
		$this->dispatcher->delegateChild($this->dispatcher, $param);
		
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
		
		$called = false;
		$this->dispatcher->attachPidHandler($pid, function($sender, $param) use (&$called) {$called = true;});
		
		self::assertTrue($this->dispatcher->hasPidHandler($pid));
		
		$this->dispatcher->delegateChild($this->dispatcher, $param);
		
		self::assertTrue($called);
		self::assertTrue($this->dispatcher->hasPidHandler($pid));
		
		$called = false;
		
		$param->setParameter(['pid' => $pid = 55, 'code' => 1]);
		$this->dispatcher->delegateChild($this->dispatcher, $param);
		
		self::assertTrue($called);
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
	}
	
	public function testSyncDispatch()
	{
		if (!TTestSignalsDispatcher::hasSignals()) {
			self::assertNull(TTestSignalsDispatcher::syncDispatch());
			return;
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$called = false;
		$sub = new TEventSubscription($this->dispatcher, SIGUSR1, function($sender, $param) use (&$called) {$called = true;});
		
		$originalASync = TTestSignalsDispatcher::setAsyncSignals(false);
		
		self::assertTrue(TProcessHelper::sendSignal(SIGUSR1));
		self::assertFalse($called);
		
		self::assertTrue(TTestSignalsDispatcher::syncDispatch());
		self::assertTrue($called);
		$called = false;
		
		self::assertTrue(TProcessHelper::sendSignal(SIGUSR1));
		self::assertFalse($called);
		
		self::assertFalse(TTestSignalsDispatcher::setAsyncSignals(true));
		self::assertTrue($called);
		
		TTestSignalsDispatcher::setAsyncSignals($originalASync);
	}
	
	public function testAsyncSignals()
	{
		$oAsyncSignals = TTestSignalsDispatcher::getAsyncSignals();
		$originalASyncSignals = TTestSignalsDispatcher::setAsyncSignals(true);
		
		self::assertEquals($oAsyncSignals, $originalASyncSignals);
		
		if (!TSignalsDispatcher::hasSignals()) {
			self::assertNull(TTestSignalsDispatcher::getAsyncSignals());
			self::assertNull(TTestSignalsDispatcher::setAsyncSignals(false));
			self::assertNull(TTestSignalsDispatcher::setAsyncSignals(true));
		} else {
			self::assertTrue(TTestSignalsDispatcher::getAsyncSignals());
			self::assertTrue(TTestSignalsDispatcher::setAsyncSignals(false));
			self::assertFalse(TTestSignalsDispatcher::getAsyncSignals());
			self::assertFalse(TTestSignalsDispatcher::setAsyncSignals(true));
			self::assertTrue(TTestSignalsDispatcher::setAsyncSignals($originalASyncSignals));
		}
	}
	
	public function testPriorHandlerPriority()
	{
		self::assertNull(TTestSignalsDispatcher::getPriorHandlerPriority());
			
		TTestSignalsDispatcher::setPriorHandlerPriority(5);
		self::assertEquals(5, TTestSignalsDispatcher::getPriorHandlerPriority());
		
		TTestSignalsDispatcher::setPriorHandlerPriority(15);
		self::assertEquals(15, TTestSignalsDispatcher::getPriorHandlerPriority());
		
		TTestSignalsDispatcher::setPriorHandlerPriority(0);
		self::assertEquals(0, TTestSignalsDispatcher::getPriorHandlerPriority());
		
		TTestSignalsDispatcher::setPriorHandlerPriority(null);
		self::assertNull(TTestSignalsDispatcher::getPriorHandlerPriority());
	}
	

	public function testWithoutSignals()
	{
		self::assertNull(TTestNoSignalsDispatcher::singleton());
		self::assertNull(TTestNoSignalsDispatcher::alarm(5));
		self::assertNull(TTestNoSignalsDispatcher::disarm());
		self::assertNull(TTestNoSignalsDispatcher::syncDispatch());
		self::assertNull(TTestNoSignalsDispatcher::getAsyncSignals());
		self::assertNull(TTestNoSignalsDispatcher::setAsyncSignals(true));

		$dispatcher = new TTestNoSignalsDispatcher();
		try {
			self::assertSame($dispatcher, TTestNoSignalsDispatcher::singleton(false), 'The singleton is set without signals.');
			self::assertSame([], PradoUnit::getStaticProp(TSignalsDispatcher::class, '_priorHandlers'));
			self::assertNull($dispatcher->ring($dispatcher, new TSignalParameter(SIGALRM)));
			$dispatcher->delegateChild($dispatcher, null);
		} finally {
			self::assertTrue($dispatcher->detach());
		}
		self::assertNull(TTestNoSignalsDispatcher::singleton(false));
	}

	public function testDisarmWithoutADispatcher()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		self::assertNull(TTestSignalsDispatcher::singleton(false));
		self::assertNull(TTestSignalsDispatcher::disarm());
		self::assertNull(TTestSignalsDispatcher::disarm(fn () => null));
	}

	public function testAttachWhileAnotherIsAttached()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$other = new TTestSignalsDispatcher();
		self::assertFalse($other->attach());
		self::assertFalse($other->detach());
		self::assertSame($this->dispatcher, TTestSignalsDispatcher::singleton(false));
	}

	public function testAttachSkipsTheSignalsItAlreadyHandles()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$state = PradoUnit::snapshotStatic(TSignalsDispatcher::class);
		try {
			// A dispatcher attaching while the signals still route to another records no prior handler.
			PradoUnit::setStaticProp(TSignalsDispatcher::class, '_singleton', null);
			$second = new TTestSignalsDispatcher();
			self::assertSame($second, TTestSignalsDispatcher::singleton(false));
			self::assertSame($state['_priorHandlers'], PradoUnit::getStaticProp(TSignalsDispatcher::class, '_priorHandlers'));
			self::assertInstanceOf(TSignalsDispatcher::class, pcntl_signal_get_handler(SIGUSR1));
		} finally {
			PradoUnit::restoreStatic(TSignalsDispatcher::class, $state);
		}
	}

	public function testPriorHandler()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$original = pcntl_signal_get_handler(SIGUSR1);
		$handler = new TTestSignalInvokable(1);
		pcntl_signal(SIGUSR1, $handler);
		try {
			$this->dispatcher = new TTestSignalsDispatcher();
			self::assertSame($handler, TTestSignalsDispatcher::getPriorHandler(SIGUSR1, true));
			self::assertInstanceOf(\Closure::class, TTestSignalsDispatcher::getPriorHandler(SIGUSR1));
			self::assertSame(SIG_DFL, TTestSignalsDispatcher::getPriorHandler(SIGUSR2, true));
			self::assertNull(TTestSignalsDispatcher::getPriorHandler(SIGUSR2));
			self::assertNull(TTestSignalsDispatcher::getPriorHandler(-1, true));
		} finally {
			if ($this->dispatcher) {
				$this->dispatcher->detach();
				$this->dispatcher = null;
			}
			pcntl_signal(SIGUSR1, $original);
		}
	}

	public function testPidEventNamesMustBeNumeric()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		self::assertFalse($this->dispatcher->hasEvent('pid:abc'));
		self::assertFalse($this->dispatcher->hasEventHandler('pid:abc'));
		try {
			$this->dispatcher->getEventHandlers('pid:abc');
			self::fail('getEventHandlers() did not throw for a pid that is not a number.');
		} catch (TInvalidOperationException $e) {
			self::assertSame('signalsdispatcher_bad_pid', $e->getErrorCode());
		}
	}

	public function testGetEventHandlersOfAnEndedPidClearsThem()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$pid = 999999;
		self::assertFalse(TProcessHelper::isRunning($pid));
		$this->dispatcher->attachPidHandler($pid, fn () => null);
		self::assertTrue($this->dispatcher->hasEventHandler('pid:' . $pid));

		self::assertNull($this->dispatcher->getEventHandlers('pid:' . $pid));
		self::assertFalse($this->dispatcher->hasEventHandler('pid:' . $pid));
	}

	public function testDelegateChildReapsAnEndedChild()
	{
		if (!TSignalsDispatcher::hasSignals() || !TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl signals or fork are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		// Synchronous signals hold SIGCHLD, so delegateChild() reaps the child itself.
		TTestSignalsDispatcher::setAsyncSignals(false);
		foreach ([[7, 'exit', true], [SIGKILL, 'kill', true], [3, 'exit', false]] as [$expectedStatus, $end, $withParameter]) {
			$pid = pcntl_fork();
			if ($pid === 0) {
				if ($end === 'kill') {
					posix_kill(posix_getpid(), SIGKILL);
				}
				exit($expectedStatus);
			}
			self::assertGreaterThan(0, $pid);
			$received = null;
			$this->dispatcher->attachPidHandler($pid, function ($sender, $param) use (&$received) {
				$received = $param->getParameter();
			});

			$deadline = microtime(true) + 10;
			while ($received === null && microtime(true) < $deadline) {
				$this->dispatcher->delegateChild($this->dispatcher, $withParameter ? new TSignalParameter(SIGCHLD) : null);
				usleep(10000);
			}
			self::assertSame($pid, $received['pid'] ?? null);
			self::assertSame($end === 'exit' ? 1 : 2, $received['code']);
			self::assertSame($expectedStatus, $received['status']);
			self::assertFalse($this->dispatcher->hasPidHandler($pid), 'The handlers of an ended child are cleared.');
		}
		TTestSignalsDispatcher::setAsyncSignals(true);
	}

	public function testDelegateChildClearsTheHandlersOfAChildThatDumpedCore()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$this->dispatcher->attachPidHandler($pid = 55, fn () => null);
		$this->dispatcher->delegateChild($this->dispatcher, new TSignalParameter(SIGCHLD, false, 0, ['pid' => $pid, 'code' => 3]));
		self::assertFalse($this->dispatcher->hasPidHandler($pid));
	}

	public function testAlarmWithoutSecondsAndACallbackIsNull()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		self::assertNull(TTestSignalsDispatcher::alarm(0, fn () => null));
		self::assertNull(TTestSignalsDispatcher::disarm(12345));
		TSignalsDispatcher::nullAlarm(null, null);
		self::assertIsBool(TTestSignalsDispatcher::syncDispatch());
		self::assertTrue(TTestSignalsDispatcher::getAsyncSignals());
	}

	public function testSetAsyncSignalsDispatchesPendingSignals()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		self::assertTrue(TTestSignalsDispatcher::setAsyncSignals(false));
		$received = null;
		$this->dispatcher->attachEventHandler(TSignalsDispatcher::SIGNAL_MAP[SIGUSR2], function ($sender, $param) use (&$received) {
			$received = $param->getSignal();
		});
		posix_kill(posix_getpid(), SIGUSR2);
		self::assertNull($received, 'A synchronous signal waits for a dispatch.');
		self::assertFalse(TTestSignalsDispatcher::setAsyncSignals(true));
		self::assertSame(SIGUSR2, $received, 'Turning asynchronous signals on dispatches the pending signal.');
	}

	public function testAlarmNowWithoutADispatcher()
	{
		self::assertNull(TTestNoSignalsDispatcher::singleton(false));
		self::assertEqualsWithDelta(time(), TTestNoSignalsDispatcher::alarmNow(), 1);
	}

	public function testRaiseEventByEventName()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$received = [];
		$event = TSignalsDispatcher::SIGNAL_MAP[SIGUSR2];
		$this->dispatcher->attachEventHandler($event, function ($sender, $param) use (&$received) {
			$received[] = $param->getSignal();
		});
		$this->dispatcher->raiseEvent($event, $this->dispatcher, new TSignalParameter(SIGUSR2));
		$this->dispatcher->raiseEvent(SIGUSR2, $this->dispatcher, new TSignalParameter(SIGUSR2));
		self::assertSame([SIGUSR2, SIGUSR2], $received);
	}

	public function testPidHandlerCollections()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$pid = getmypid();
		try {
			self::assertInstanceOf(TWeakCallableCollection::class, $this->dispatcher->getPidHandlers($pid, true));
			self::assertTrue($this->dispatcher->attachPidHandler($pid, $h1 = fn () => 1, null, true));
			self::assertTrue($this->dispatcher->attachPidHandler($pid, $h2 = fn () => 2, null, true));
			self::assertTrue($this->dispatcher->attachPidHandler($pid, $h3 = fn () => 3));
			self::assertCount(3, $this->dispatcher->getPidHandlers($pid));

			self::assertTrue($this->dispatcher->detachPidHandler($pid, $h1));
			self::assertTrue($this->dispatcher->hasPidHandler($pid), 'The other handlers remain.');
			self::assertCount(2, $this->dispatcher->getPidHandlers($pid));
		} finally {
			$this->dispatcher->clearPidHandlers($pid);
		}
	}

	public function testSetAsyncSignalsRecordsThePriorState()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$original = pcntl_async_signals();
		$state = PradoUnit::snapshotStatic(TSignalsDispatcher::class, ['_asyncSignals', '_priorAsync']);
		try {
			pcntl_async_signals(true);
			PradoUnit::setStaticProp(TSignalsDispatcher::class, '_priorAsync', null);
			self::assertTrue(TTestSignalsDispatcher::setAsyncSignals(true));
			self::assertTrue(PradoUnit::getStaticProp(TSignalsDispatcher::class, '_priorAsync'));

			PradoUnit::setStaticProp(TSignalsDispatcher::class, '_priorAsync', null);
			self::assertTrue(TTestSignalsDispatcher::setAsyncSignals(false));
			self::assertTrue(PradoUnit::getStaticProp(TSignalsDispatcher::class, '_priorAsync'));
			self::assertFalse(pcntl_async_signals());
		} finally {
			PradoUnit::restoreStatic(TSignalsDispatcher::class, $state);
			pcntl_async_signals($original);
		}
	}

	public function testDisarmSearchesTheLaterAlarmTimes()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$this->dispatcher = new TTestSignalsDispatcher();
		$this->dispatcher->setMonotonicClock(new TTestMonotonicClock(1_000_000_000_000));
		try {
			$near = TTestSignalsDispatcher::alarm(5, $f1 = fn () => null);
			$far = TTestSignalsDispatcher::alarm(10, $f2 = fn () => null);
			self::assertNull(TTestSignalsDispatcher::disarm(fn () => null), 'A callback that is not armed disarms nothing.');
			self::assertSame($far, TTestSignalsDispatcher::disarm($f2));
			self::assertSame($near, TTestSignalsDispatcher::alarm());
			self::assertSame($near, TTestSignalsDispatcher::disarm($f1));
		} finally {
			pcntl_alarm(0);
		}
	}
}
