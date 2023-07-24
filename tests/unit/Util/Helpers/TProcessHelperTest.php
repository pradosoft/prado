<?php

use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\TEventSubscription;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\TSignalsDispatcher;

class TProcessHelperTest extends PHPUnit\Framework\TestCase
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
	
	public function testIsSystemWindows()
	{
		self::assertEquals(strncasecmp(php_uname('s'), 'win', 3) === 0, TProcessHelper::isSystemWindows());
	}
	
	public function testIsForkable()
	{
		self::assertEquals(function_exists('pcntl_fork'), TProcessHelper::isForkable());
	}
	
	public function testFork()
	{
		if (!TProcessHelper::isForkable()) {
			//$this->markTestSkipped("skipping " . TProcessHelper::class . "::sendSignal on Windows.");
			self::expectException(TNotSupportedException::class);
			TProcessHelper::fork();
			return;
		}
		
		$dispatcher = TSignalsDispatcher::singleton();
		$restoreCalled = $prepareCalled = false;
		
		
		$prepareSub = new TEventSubscription($dispatcher, TProcessHelper::FX_PREPARE_FOR_FORK, 
			function($sender, $param) use (&$prepareCalled) {
				$prepareCalled = true;
				return ['datakey' => 'value'];
			}
		);
		$restoreParam = null;
		$restoreSub = new TEventSubscription($dispatcher, TProcessHelper::FX_RESTORE_AFTER_FORK, 
			function($sender, $param) use (&$restoreCalled, &$restoreParam) {
				$restoreCalled = true;
				$restoreParam = $param;
			}
		);
		
		$pid = TProcessHelper::fork();
			
		if ($pid === 0) {
			usleep(50_000); // time for isRunning by parent.  delay of start is usually enough.
			exit();
		} else if ($pid === -1) {
			self::fail('Failed to fork with pid = -1');
			return;
		}
		
		self::assertTrue(TProcessHelper::isRunning($pid));
		self::assertTrue($prepareCalled);
		self::assertTrue($restoreCalled);
		self::assertEquals($pid, $restoreParam['pid']);
		self::assertEquals('value', $restoreParam['datakey']);
		
		$dispatcher->detach();
	}
	
	public function testSendSignal()
	{
		if (TProcessHelper::isSystemWindows()) {
			self::expectException(TNotSupportedException::class);
			TProcessHelper::sendSignal(0);
			return;
		}
		$app = Prado::getApplication();
		$this->dispatcher = TSignalsDispatcher::singleton();
		
		self::assertTrue($this->dispatcher->getAsyncSignals());
		
		$done = false;
		$signal = SIGUSR1;
		$this->dispatcher->attachEventHandler($signal, $handler = function($sender, $param) use (&$done) {$done = true;});
		self::assertTrue(TProcessHelper::sendSignal($signal));
		self::assertTrue($done, 'did not send the signal');
		self::assertTrue($this->dispatcher->getEventHandlers('fxSignalUser1')->contains($handler));
		self::assertTrue($this->dispatcher->getEventHandlers($signal)->contains($handler));
		self::assertEquals($this->dispatcher, pcntl_signal_get_handler($signal));
		
		$this->dispatcher->detachEventHandler($signal, $handler);
		$this->dispatcher->detach();
	}
	
	public function testIsRunning_Kill_Priority()
	{
		self::assertTrue(TProcessHelper::isRunning(getmypid()));
		
		$command = TProcessHelper::filterCommand(['@php', '-r', 'sleep(5);']);
		
		$descriptorspec = [];
		
		$process = proc_open($command, $descriptorspec, $pipes);
		$info = proc_get_status($process);
		$pid = $info['pid'];
		self::assertTrue(TProcessHelper::isRunning($pid));
		
		$processPriority = TProcessHelper::getProcessPriority($pid);
		self::assertTrue(TProcessHelper::setProcessPriority(TProcessHelper::WINDOWS_BELOW_NORMAL_PRIORITY, $pid));
		self::assertEquals(TProcessHelper::WINDOWS_BELOW_NORMAL_PRIORITY, TProcessHelper::getProcessPriority($pid));
		
		self::assertTrue(TProcessHelper::setProcessPriority(TProcessHelper::WINDOWS_IDLE_PRIORITY, $pid));
		self::assertEquals(TProcessHelper::WINDOWS_IDLE_PRIORITY, TProcessHelper::getProcessPriority($pid));
		
		self::assertTrue(TProcessHelper::kill($pid)); // kill pid despite sleeping.
		proc_close($process);
		self::assertFalse(TProcessHelper::isRunning($pid));
	}
	
	public function testEscapeShellArg()
	{
		if (TProcessHelper::isSystemWindows()) {
			$argument = '';
			$expected = '""';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test';
			$expected = '"test"';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test argument';
			$expected = '"test argument"';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = '"quoted"';
			$expected = '\\"quoted\\"';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = '%ENVIRONMENT%';
			$expected = '^%"ENVIRONMENT"^%';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));

		} else {
			
			$argument = '';
			$expected = "''";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test';
			$expected = "'test'";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test argument';
			$expected = "'test argument'";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = "'quoted'";
			$expected = "''\\''quoted'\\'''";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));

		}
	}
	
	public function testisSurroundedBy()
	{
		self::assertFalse(TProcessHelper::isSurroundedBy('text', '-'));
		self::assertTrue(TProcessHelper::isSurroundedBy('-text-', '-'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-text', '-'));
		self::assertTrue(TProcessHelper::isSurroundedBy('-text-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-ext-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-tex-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-ex-', '-t'));
	}
}