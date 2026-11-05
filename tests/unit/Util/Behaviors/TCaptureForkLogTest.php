<?php

namespace Prado\Test\Unit\Util\Behaviors;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponent;
use Prado\Prado;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\Behaviors\TCaptureForkLog;
use Prado\Util\Log\TLogger;
use Prado\Test\Unit\PradoUnit;


class TCaptureForkLogTest extends \PHPUnit\Framework\TestCase
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
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}

		$logger = Prado::getLogger();

		$logger->deleteLogs();

		Prado::notice('parent notice');
		Prado::profileBegin('token1');
		
		$withCaptureLog = true;
		$pid = TProcessHelper::fork($withCaptureLog);
		
		if ($pid === -1) {
			self::fail("failed to fork.");
		} elseif ($pid === 0) {
			// The child always exits; a failed assertion must never return into PHPUnit.
			try {
				Prado::profileBegin('token2');
				Prado::profileBegin('token3');
				self::assertLessThan(1, Prado::profileEnd('token1'));
				Prado::profileEnd('token2');
				Prado::profileEnd('token4');
				Prado::warning('child warning');
				exit();
			} catch (\Throwable $e) {
				fwrite(STDERR, 'Forked log child failed: ' . $e->getMessage() . PHP_EOL);
				exit(1);
			}
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

	public function testForkCapturesGrandchildLogs()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}

		$logger = Prado::getLogger();
		$logger->deleteLogs();

		$pid = TProcessHelper::fork(true);
		if ($pid === -1) {
			self::fail("failed to fork.");
		} elseif ($pid === 0) {
			try {
				$grandchildPid = TProcessHelper::fork(true);
				if ($grandchildPid === 0) {
					Prado::warning('grandchild warning');
				} elseif ($grandchildPid > 0) {
					Prado::warning('child warning');
				}
			} finally {
				exit(0);
			}
		}
		try {
			Prado::getApplication()->receiveLogsFromChildren($pid);
			pcntl_waitpid($pid, $status);
			$messages = array_column($logger->getLogs(), 0);
			self::assertContains('child warning', $messages);
			self::assertContains('grandchild warning', $messages);
		} finally {
			$logger->deleteLogs();
		}
	}

	/**
	 * Simulates the parent side of a fork for a child pid and returns the child's
	 * end of the socket pair.
	 */
	protected function connectParent(TCaptureForkLog $behavior, int $pid): mixed
	{
		$behavior->generateConnection(null, null);
		$connection = PradoUnit::getProp($behavior, '_forkConnection');
		if (!$connection) {
			self::markTestSkipped('socket_create_pair is not available.');
		}
		$behavior->configureForChildLogs(null, ['pid' => $pid]);
		return $connection[1];
	}

	/**
	 * Writes a framed log message to the parent, as the child does.
	 */
	protected function writeChildLogs(mixed $childStream, array $logs, bool $final): void
	{
		$data = $logs ? serialize($logs) : '';
		fwrite($childStream, pack('N', ($final ? 0x80000000 : 0) | strlen($data)) . $data);
	}

	public function testCollectLogsFromChildrenOnCollectLogs()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$behavior = new TCaptureForkLog();
		$childPid = 999999;
		$childStream = $this->connectParent($behavior, $childPid);
		try {
			self::assertTrue($logger->getEventHandlers('onCollectLogs')->contains([$behavior, 'collectLogsFromChildren']));

			// Not final: receives the pending logs and keeps the connection.
			$this->writeChildLogs($childStream, [['pending', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $childPid]], false);
			$logger->onCollectLogs(false);
			$logs = $logger->getLogs(pid: $childPid);
			self::assertCount(1, $logs);
			self::assertEquals('pending', $logs[0][0]);
			self::assertArrayHasKey($childPid, PradoUnit::getProp($behavior, '_parentConnections'));

			// Final: receives the last logs and closes the connection.
			$this->writeChildLogs($childStream, [['final', TLogger::WARNING, 'cat', microtime(true), 0, null, null, $childPid]], true);
			$logger->onCollectLogs(true);
			$logs = $logger->getLogs(pid: $childPid);
			self::assertCount(2, $logs);
			self::assertEquals('final', $logs[1][0]);
			self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
		} finally {
			$logger->detachEventHandler('onCollectLogs', [$behavior, 'collectLogsFromChildren']);
			$logger->deleteLogs();
		}
	}

	public function testReceiveLogsFromChildrenOfOnePid()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$behavior = new TCaptureForkLog();
		$pidA = 999998;
		$pidB = 999999;
		$streamA = $this->connectParent($behavior, $pidA);
		$streamB = $this->connectParent($behavior, $pidB);
		try {
			$this->writeChildLogs($streamB, [['child B', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $pidB]], false);
			$this->writeChildLogs($streamA, [['child A', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $pidA]], true);

			$behavior->receiveLogsFromChildren($pidA);

			self::assertEquals(['child A'], array_column($logger->getLogs(pid: $pidA), 0));
			self::assertEmpty($logger->getLogs(pid: $pidB));
			$connections = PradoUnit::getProp($behavior, '_parentConnections');
			self::assertArrayNotHasKey($pidA, $connections);
			self::assertArrayHasKey($pidB, $connections);

			$behavior->receiveLogsFromChildren($pidB, false);
			self::assertEquals(['child B'], array_column($logger->getLogs(pid: $pidB), 0));
		} finally {
			$logger->detachEventHandler('onCollectLogs', [$behavior, 'collectLogsFromChildren']);
			$logger->deleteLogs();
		}
	}

	public function testReceiveLogsFromChildrenKeepsPartialMessages()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$behavior = new TCaptureForkLog();
		$childPid = 999999;
		$childStream = $this->connectParent($behavior, $childPid);
		try {
			$first = serialize([['first', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $childPid]]);
			$second = serialize([['second', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $childPid]]);
			$secondMessage = pack('N', 0x80000000 | strlen($second)) . $second;

			// A complete message and a split header of the next message.
			fwrite($childStream, pack('N', strlen($first)) . $first . substr($secondMessage, 0, 2));
			$behavior->receiveLogsFromChildren(null, false);
			self::assertEquals(['first'], array_column($logger->getLogs(pid: $childPid), 0));
			self::assertArrayHasKey($childPid, PradoUnit::getProp($behavior, '_parentConnections'));

			fwrite($childStream, substr($secondMessage, 2));
			$behavior->receiveLogsFromChildren(null, false);
			self::assertEquals(['first', 'second'], array_column($logger->getLogs(pid: $childPid), 0));
			self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
			self::assertEmpty(PradoUnit::getProp($behavior, '_receiveBuffers'));
		} finally {
			$logger->detachEventHandler('onCollectLogs', [$behavior, 'collectLogsFromChildren']);
			$logger->deleteLogs();
		}
	}

	public function testReceiveLogsFromChildrenClosesOnEndOfStream()
	{
		$logger = Prado::getLogger();
		$behavior = new TCaptureForkLog();
		$childPid = 999999;
		$childStream = $this->connectParent($behavior, $childPid);
		try {
			fclose($childStream);
			$behavior->receiveLogsFromChildren();
			self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
		} finally {
			$logger->detachEventHandler('onCollectLogs', [$behavior, 'collectLogsFromChildren']);
		}
	}

	public function testReceiveLogsFromChildrenRetriesAfterSignal()
	{
		if (!function_exists('pcntl_alarm')) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$behavior = new TCaptureForkLog();
		$childPid = 999999;
		$childStream = $this->connectParent($behavior, $childPid);
		$async = pcntl_async_signals(true);
		$priorHandler = pcntl_signal_get_handler(SIGALRM);
		$interrupted = false;
		// No restart, so the signal interrupts the waiting stream_select.
		pcntl_signal(SIGALRM, function () use (&$interrupted, $childStream, $childPid) {
			$interrupted = true;
			$this->writeChildLogs($childStream, [['after signal', TLogger::NOTICE, 'cat', microtime(true), 0, null, null, $childPid]], true);
		}, false);
		try {
			pcntl_alarm(1);
			$behavior->receiveLogsFromChildren();
			self::assertTrue($interrupted);
			self::assertEquals(['after signal'], array_column($logger->getLogs(pid: $childPid), 0));
			self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
		} finally {
			pcntl_alarm(0);
			pcntl_signal(SIGALRM, $priorHandler);
			pcntl_async_signals($async);
			$logger->detachEventHandler('onCollectLogs', [$behavior, 'collectLogsFromChildren']);
			$logger->deleteLogs();
		}
	}

	public function testSendLogsToParentAfterParentCloses()
	{
		if (!function_exists('socket_create_pair')) {
			$this->markTestSkipped('socket_create_pair is not available.');
		}
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$behavior = new TCaptureForkLog();
		socket_create_pair(TProcessHelper::isSystemWindows() ? AF_INET : AF_UNIX, SOCK_STREAM, 0, $pair);
		$parentStream = socket_export_stream($pair[0]);
		PradoUnit::setProp($behavior, '_childConnection', socket_export_stream($pair[1]));
		unset($pair);
		fclose($parentStream);
		try {
			Prado::notice('child notice');
			$behavior->sendLogsToParent($logger, false);
			self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
		} finally {
			$logger->deleteLogs();
		}
	}

	public function testSendLogsToParentSpoolsWhenConnectionIsFull()
	{
		if (!function_exists('socket_create_pair')) {
			$this->markTestSkipped('socket_create_pair is not available.');
		}
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$parent = new TCaptureForkLog();
		$child = new TCaptureForkLog();
		$childPid = 999999;
		$childStream = $this->connectParent($parent, $childPid);
		$spoolFile = PradoUnit::getProp($parent, '_spoolFiles')[$childPid];
		PradoUnit::setProp($child, '_childConnection', $childStream);
		PradoUnit::setProp($child, '_spoolFile', $spoolFile);
		$message = str_repeat('x', 1 << 20); // Larger than any socket buffer.
		try {
			// TLogger deletes the logs after each onFlushLogs.
			Prado::warning($message, 'spool');
			$child->sendLogsToParent($logger, false);
			$logger->deleteLogs();
			Prado::warning('after spool', 'spool');
			$child->sendLogsToParent($logger, true);
			$logger->deleteLogs();
			self::assertFileExists($spoolFile);
			self::assertNull(PradoUnit::getProp($child, '_childConnection'));

			$parent->receiveLogsFromChildren($childPid);

			$messages = array_column($logger->getLogs(categories: 'spool'), 0);
			self::assertCount(2, $messages);
			self::assertTrue($messages[0] === $message, 'The spooled message is incomplete.');
			self::assertEquals('after spool', $messages[1]);
			self::assertFileDoesNotExist($spoolFile);
			self::assertEmpty(PradoUnit::getProp($parent, '_parentConnections'));
		} finally {
			$logger->detachEventHandler('onCollectLogs', [$parent, 'collectLogsFromChildren']);
			$logger->deleteLogs();
			if (is_file($spoolFile)) {
				unlink($spoolFile);
			}
		}
	}

	public function testForkChildExitsWhileParentWaits()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}

		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$message = str_repeat('x', 1 << 20); // Larger than any socket buffer.

		$pid = TProcessHelper::fork(true);
		if ($pid === -1) {
			self::fail("failed to fork.");
		} elseif ($pid === 0) {
			try {
				Prado::warning($message, 'spool');
			} finally {
				exit(0);
			}
		}
		try {
			// The parent waits on the child before it collects the logs.
			$deadline = microtime(true) + 10;
			while (($reaped = pcntl_waitpid($pid, $status, WNOHANG)) === 0 && microtime(true) < $deadline) {
				usleep(10000);
			}
			self::assertEquals($pid, $reaped, 'The child blocked sending its logs.');

			Prado::getApplication()->receiveLogsFromChildren($pid);
			$messages = array_column($logger->getLogs(categories: 'spool'), 0);
			self::assertCount(1, $messages);
			self::assertTrue($messages[0] === $message, 'The spooled message is incomplete.');
		} finally {
			if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
				posix_kill($pid, SIGKILL);
				pcntl_waitpid($pid, $status);
			}
			$logger->deleteLogs();
		}
	}

	public function testCollectLogsFromChildrenWithoutConnections()
	{
		$behavior = new TCaptureForkLog();
		$behavior->collectLogsFromChildren(Prado::getLogger(), true);
		self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
	}
}
