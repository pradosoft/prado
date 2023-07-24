<?php

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TExitException;
use Prado\TEventSubscription;
use Prado\Util\TSignalsDispatcher;
use Prado\Util\TSignalParameter;

class TTestSignalsDispatcher extends TSignalsDispatcher {
	
	public function setupAlarms($handler)
	{
		$now = time();
		self::$_nextAlarmTime = $now - 1;
		static::$_alarms[$now - 1] = [$handler];
		static::$_alarms[$now] = [$handler];
		static::$_alarms[$now + 2] = [$handler];
		return $now;
	}
}

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

class TSignalsDispatcherTest extends PHPUnit\Framework\TestCase
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
		$this->dispatcher = TSignalsDispatcher::singleton();
		$this->subscription = new TEventSubscription($this->dispatcher, SIGTERM, $handler = function($sender, $param) {return true;}, 5);
		self::assertTrue($this->subscription->getIsSubscribed());
		$handlers = $this->dispatcher->getEventHandlers('fxSignalTerminate');
		self::assertEquals($handlers, $this->subscription->getCollection());
		self::assertTrue($handlers->contains($handler));
		$this->subscription->unsubscribe();
		self::assertFalse($this->subscription->getIsSubscribed());
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
			
			$this->dispatcher->detachPidHandler($pid, $handler, 5);
			self::assertFalse($this->dispatcher->hasPidHandler($pid));
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
				self::assertEquals($ref, $ogHandler->sigInfo);
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
		
		// Preset custom handlers, stored on attach, to be restored on detach
		$ogHandler = new TTestSignalInvokable(3);
		pcntl_signal(SIGALRM, $ogHandler);
		pcntl_alarm(3);
		$now = time();
		
		$this->dispatcher = new TTestSignalsDispatcher();
		
		$alarmTime = TTestSignalsDispatcher::alarm();
		self::assertTrue($alarmTime == ($now + 3) || $alarmTime == ($now + 2));
		
		self::assertEquals($alarmTime, TTestSignalsDispatcher::disarm($ogHandler, $alarmTime));
		
		self::assertNull(TTestSignalsDispatcher::alarm());
			
		$alarm1 = null;
		$now = time();
		$time1 = TTestSignalsDispatcher::alarm(4, $f1 = function ($sender, $param) use (&$alarm1) {$alarm1 = $param;});
		self::assertTrue($time1 === $now + 4 || $time1 === $now + 5);
		self::assertEquals($time1, TTestSignalsDispatcher::alarm());
		
		$now = time();
		$time2 = TTestSignalsDispatcher::alarm(2, $f2 = function ($sender, $param) use (&$alarm1) {$alarm1 = $param;});
		self::assertTrue($time2 === $now + 2 || $time2 === $now + 3);
		self::assertEquals($time2, TTestSignalsDispatcher::alarm());
		
		self::assertEquals($time2, TTestSignalsDispatcher::disarm($f2));
		self::assertEquals($time1, TTestSignalsDispatcher::alarm());
		self::assertEquals($time1, TTestSignalsDispatcher::disarm($f1));
		
		$this->dispatcher->detach();
		
		// restore
		pcntl_signal(SIGALRM, $originalAlarm);
	}
	
	public function testAlarmDisarm()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::alarm and ::disarm.");
			return;
		}

		self::assertEquals(SIG_DFL, pcntl_signal_get_handler(SIGALRM));
		
		// Preset custom handlers, stored on attach, to be restored on detach
		$handler = new TTestSignalInvokable(5);
		pcntl_signal(SIGALRM, $handler);

		$this->dispatcher = new TTestSignalsDispatcher();

		// no initial alarm.
		self::assertNull(TTestSignalsDispatcher::alarm());
		
		$alarm1 = null;
		$alarmTime1 = TTestSignalsDispatcher::alarm(5, $f1 = function($sender, $param) use (&$alarm1) {$alarm1 = $param;} );
		$now = time();
		self::assertEquals($alarmTime1, TTestSignalsDispatcher::alarm());
		self::assertTrue($alarmTime1 === $now + 5 || $alarmTime1 === $now + 4);
		
		$alarm2 = null;
		$alarmTime2 = TTestSignalsDispatcher::alarm(2, $f2 = function($sender, $param) use (&$alarm2) {$alarm2 = $param;} );
		$now = time();
		self::assertEquals($alarmTime2, TTestSignalsDispatcher::alarm());
		self::assertTrue($alarmTime2 === $now + 2 || $alarmTime2 === $now + 1);
		self::assertGreaterThan($alarmTime2, $alarmTime1);
		
		self::assertNull(TTestSignalsDispatcher::disarm(-1, $f1));
		
		self::assertEquals($alarmTime2, TTestSignalsDispatcher::disarm($f2));
		
		self::assertEquals($alarmTime1, TTestSignalsDispatcher::alarm());
		
		self::assertEquals($alarmTime1, TTestSignalsDispatcher::disarm($f1));
		
		self::assertNull(TTestSignalsDispatcher::alarm());
		
		$now = time();
		$alarmTime3 = TTestSignalsDispatcher::alarm(2);
		
		self::assertTrue($alarmTime3 === $now + 1 || $alarmTime3 === $now + 2);
		self::assertEquals($alarmTime3, TTestSignalsDispatcher::alarm());
		
		self::assertEquals($alarmTime3, TTestSignalsDispatcher::disarm($alarmTime3));
		
		self::assertNull(TTestSignalsDispatcher::alarm());
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
	
	public function testDelegateChild()
	{
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
	
}

