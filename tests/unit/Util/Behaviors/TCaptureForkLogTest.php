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

	/**
	 * Runs $test with a fresh TLogger as the Prado logger.
	 */
	protected function withFreshLogger(callable $test): void
	{
		$saved = PradoUnit::getStaticProp(Prado::class, '_logger');
		$logger = new TLogger(false);
		PradoUnit::setStaticProp(Prado::class, '_logger', $logger);
		try {
			$test($logger);
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_logger', $saved);
		}
	}

	/**
	 * Returns a parent and a child stream from a socket pair.
	 */
	protected function socketPair(): array
	{
		if (!function_exists('socket_create_pair')) {
			self::markTestSkipped('socket_create_pair is not available.');
		}
		socket_create_pair(TProcessHelper::isSystemWindows() ? AF_INET : AF_UNIX, SOCK_STREAM, 0, $pair);
		return [socket_export_stream($pair[0]), socket_export_stream($pair[1])];
	}

	public function testDyAttachBehaviorKeepsOneInstance()
	{
		$owner = new TComponent();
		$first = new TCaptureForkLog();
		$owner->attachBehavior('first', $first);
		$owner->attachBehavior('second', new TCaptureForkLog());

		self::assertCount(1, $owner->getBehaviors(TCaptureForkLog::class));
		self::assertSame($first, $owner->asa('first'));
		self::assertNull($owner->asa('second'));
		$owner->detachBehavior('first');
	}

	public function testConfigureForChildLogsWithoutConnection()
	{
		$behavior = new TCaptureForkLog();

		$behavior->configureForChildLogs(null, ['pid' => 999999]);

		self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
		self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
	}

	public function testConfigureForChildLogsAfterFailedFork()
	{
		$behavior = new TCaptureForkLog();
		$behavior->generateConnection(null, null);
		$connection = PradoUnit::getProp($behavior, '_forkConnection');
		if (!$connection) {
			self::markTestSkipped('socket_create_pair is not available.');
		}

		$behavior->configureForChildLogs(null, ['pid' => -1]);

		self::assertNull(PradoUnit::getProp($behavior, '_forkConnection'));
		self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
		self::assertSame('', fread($connection[0], 1));
		self::assertSame('', fread($connection[1], 1));
	}

	public function testConfigureForChildLogsInTheChild()
	{
		$this->withFreshLogger(function (TLogger $logger) {
			$child = new TCaptureForkLog();
			$child->generateConnection(null, null);
			$connection = PradoUnit::getProp($child, '_forkConnection');
			if (!$connection) {
				self::markTestSkipped('socket_create_pair is not available.');
			}
			$spoolFile = PradoUnit::getProp($child, '_forkSpoolFile');
			$logger->attachEventHandler('onFlushLogs', fn () => null);
			Prado::notice('parent notice', 'fork');
			Prado::profileBegin('token', 'fork');

			$child->configureForChildLogs(null, ['pid' => 0]);

			self::assertSame($connection[1], PradoUnit::getProp($child, '_childConnection'));
			self::assertSame($spoolFile, PradoUnit::getProp($child, '_spoolFile'));
			$handlers = $logger->getEventHandlers('onFlushLogs');
			self::assertCount(1, $handlers);
			self::assertTrue($handlers->contains([$child, 'sendLogsToParent']));
			$logs = $logger->getLogs();
			self::assertEquals(['token'], array_column($logs, 0));
			self::assertSame(getmypid(), $logs[0][TLogger::LOG_PID]);

			// A log already logged is not sent; a non-TLogger sender sends the Prado logger.
			$time = microtime(true);
			PradoUnit::setProp($logger, '_logs', [
				['logged', TLogger::NOTICE | TLogger::LOGGED, 'fork', $time, 0, null, null, getmypid()],
				['child notice', TLogger::NOTICE, 'fork', $time, 0, null, null, getmypid()],
			]);
			$child->sendLogsToParent(null, false);
			$logger->deleteLogs();
			$logger->deleteProfileLogs();
			$child->sendLogsToParent($logger, true);
			self::assertNull(PradoUnit::getProp($child, '_childConnection'));

			$data = stream_get_contents($connection[0]);
			$header = unpack('N', $data)[1];
			self::assertSame(0, $header & 0x80000000);
			$messages = array_column(unserialize(substr($data, 4, $header)), 0);
			sort($messages);
			self::assertEquals(['child notice', 'token'], $messages);
			// The final message has no logs.
			self::assertSame(pack('N', 0x80000000), substr($data, 4 + $header));
		});
	}

	public function testReceiveLogsFromChildrenOfAnUnknownPid()
	{
		$behavior = new TCaptureForkLog();
		[$parentStream, $childStream] = $this->socketPair();
		PradoUnit::setProp($behavior, '_parentConnections', [999999 => $parentStream]);

		$behavior->receiveLogsFromChildren(999998);

		self::assertArrayHasKey(999999, PradoUnit::getProp($behavior, '_parentConnections'));
	}

	public function testSendLogsToParentWithoutConnection()
	{
		$behavior = new TCaptureForkLog();
		$behavior->sendLogsToParent(Prado::getLogger(), true);
		self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
	}

	public function testSendLogsToParentWaitsOnTheParent()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}
		[$parentStream, $childStream] = $this->socketPair();
		$message = str_repeat('x', 1 << 20); // Larger than any socket buffer.
		$this->withFreshLogger(function (TLogger $logger) use ($parentStream, $childStream, $message) {
			$pid = TProcessHelper::fork();
			if ($pid === 0) {
				// The reader waits past one stream_select timeout of the writer.
				try {
					fclose($childStream);
					usleep(1100000);
					$data = stream_get_contents($parentStream);
					$header = unpack('N', $data)[1];
					$messages = array_column(unserialize(substr($data, 4)), 0);
					exit(($header & 0x80000000) && strlen($data) === 4 + ($header & 0x7FFFFFFF) && in_array($message, $messages, true) ? 0 : 2);
				} finally {
					exit(1);
				}
			}
			fclose($parentStream);
			$behavior = new TCaptureForkLog();
			PradoUnit::setProp($behavior, '_childConnection', $childStream);
			Prado::warning($message, 'spool');

			// Without a spool file, the child writes to the parent as it reads.
			$behavior->sendLogsToParent($logger, true);

			self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
			pcntl_waitpid($pid, $status);
			self::assertSame(0, pcntl_wexitstatus($status), 'The parent did not receive the whole message.');
		});
	}

	public function testSendLogsToParentStopsWhenTheParentCloses()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}
		[$parentStream, $childStream] = $this->socketPair();
		$this->withFreshLogger(function (TLogger $logger) use ($parentStream, $childStream) {
			$pid = TProcessHelper::fork();
			if ($pid === 0) {
				// The parent closes its connection without reading.
				try {
					usleep(200000);
				} finally {
					exit(0);
				}
			}
			fclose($parentStream);
			$behavior = new TCaptureForkLog();
			PradoUnit::setProp($behavior, '_childConnection', $childStream);
			Prado::warning(str_repeat('x', 1 << 20), 'spool');

			$behavior->sendLogsToParent($logger, false);

			self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
			pcntl_waitpid($pid, $status);
		});
	}

	public function testDyAttachBehaviorKeepsOtherBehaviors()
	{
		$owner = new TComponent();
		$owner->attachBehavior('capture', new TCaptureForkLog());
		$owner->attachBehavior('other', $other = new TMapLazyLoadBehavior(fn () => null));

		self::assertSame($other, $owner->asa('other'));
		$owner->detachBehavior('other');
		$owner->detachBehavior('capture');
	}

	public function testConfigureForChildLogsInTheChildWithoutProfileLogs()
	{
		$this->withFreshLogger(function (TLogger $logger) {
			$child = new TCaptureForkLog();
			$child->generateConnection(null, null);
			$connection = PradoUnit::getProp($child, '_forkConnection');
			if (!$connection) {
				self::markTestSkipped('socket_create_pair is not available.');
			}

			$child->configureForChildLogs(null, ['pid' => 0]);

			self::assertSame([], $logger->getLogs());
			self::assertSame($connection[1], PradoUnit::getProp($child, '_childConnection'));
		});
	}

	public function testWriteToParent()
	{
		[$parentStream, $childStream] = $this->socketPair();
		$behavior = new TCaptureForkLog();

		// Nothing to write, and no connection to write to.
		PradoUnit::invoke($behavior, 'writeToParent', '');
		PradoUnit::invoke($behavior, 'writeToParent', 'lost');

		PradoUnit::setProp($behavior, '_childConnection', $childStream);
		PradoUnit::invoke($behavior, 'writeToParent', 'data');
		self::assertSame('data', fread($parentStream, 4));
		self::assertSame($childStream, PradoUnit::getProp($behavior, '_childConnection'));

		// The parent closed its connection.
		fclose($parentStream);
		PradoUnit::invoke($behavior, 'writeToParent', 'data');
		self::assertNull(PradoUnit::getProp($behavior, '_childConnection'));
	}

	public function testSpoolLogsWithAnExistingSpoolFile()
	{
		$behavior = new TCaptureForkLog();
		$spoolFile = tempnam(sys_get_temp_dir(), 'prado-forklog-test');
		try {
			PradoUnit::setProp($behavior, '_spoolFile', $spoolFile);

			self::assertFalse(PradoUnit::invoke($behavior, 'spoolLogs', 'data'));
			self::assertNull(PradoUnit::getProp($behavior, '_spoolHandle'));
			self::assertSame('', file_get_contents($spoolFile));
		} finally {
			unlink($spoolFile);
		}
	}

	public function testExtractChildLogsKeepsAShortHeader()
	{
		$behavior = new TCaptureForkLog();
		PradoUnit::setProp($behavior, '_receiveBuffers', [999999 => "\0\0"]);
		$completeLogs = [];

		self::assertFalse(PradoUnit::reflectionMethod($behavior, 'extractChildLogs')->invokeArgs($behavior, [999999, &$completeLogs]));

		self::assertSame([], $completeLogs);
		self::assertSame([999999 => "\0\0"], PradoUnit::getProp($behavior, '_receiveBuffers'));
	}

	public function testExtractChildLogsOfAFinalMessageWithoutLogs()
	{
		$behavior = new TCaptureForkLog();
		PradoUnit::setProp($behavior, '_receiveBuffers', [999999 => pack('N', 0x80000000)]);
		$completeLogs = [];

		self::assertTrue(PradoUnit::reflectionMethod($behavior, 'extractChildLogs')->invokeArgs($behavior, [999999, &$completeLogs]));

		self::assertSame([], $completeLogs);
		self::assertSame([999999 => ''], PradoUnit::getProp($behavior, '_receiveBuffers'));
	}

	public function testCloseChildConnectionWithAnEmptyOrNoSpoolFile()
	{
		$behavior = new TCaptureForkLog();
		[$parentA] = $this->socketPair();
		[$parentB] = $this->socketPair();
		$spoolFile = tempnam(sys_get_temp_dir(), 'prado-forklog-test');
		PradoUnit::setProp($behavior, '_parentConnections', [999998 => $parentA, 999999 => $parentB]);
		PradoUnit::setProp($behavior, '_spoolFiles', [999998 => $spoolFile]);
		$completeLogs = [];

		$close = PradoUnit::reflectionMethod($behavior, 'closeChildConnection');
		$close->invokeArgs($behavior, [999998, &$completeLogs]);
		$close->invokeArgs($behavior, [999999, &$completeLogs]);

		self::assertSame([], $completeLogs);
		self::assertFileDoesNotExist($spoolFile);
		self::assertSame([], PradoUnit::getProp($behavior, '_parentConnections'));
	}

	public function testCollectLogsFromChildrenWithoutConnections()
	{
		$behavior = new TCaptureForkLog();
		$behavior->collectLogsFromChildren(Prado::getLogger(), true);
		self::assertEmpty(PradoUnit::getProp($behavior, '_parentConnections'));
	}
}
