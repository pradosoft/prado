<?php

namespace Prado\Test\Unit\IO\Socket;

use Prado\IO\Socket\TSocketReactor;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;
use Prado\Test\Unit\Harness\Util\Clock\TTestMonotonicClock;
use Prado\Util\Clock\TMonotonicClock;

/**
 * Tests the reactor's source API, monotonic clock, and tick mechanics.  The IEventLoop contract is
 * covered by the conformance suite in tests/unit/IO/Async.
 */
class TSocketReactorTest extends \PHPUnit\Framework\TestCase
{
	public function testListenerReadableDispatchesAccept()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$server->setBlocking(false);
		$reactor = new TSocketReactor();
		$accepted = [];
		$reactor->register($server, onReadable: function (string $id, $src) use (&$accepted) {
			$conn = $src->accept(0.0);
			if ($conn !== null) {
				$accepted[] = $conn;
			}
		});

		$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 1.0);
		$reactor->tick(1.0);

		self::assertCount(1, $accepted, 'A readable listener dispatches an accept.');
		$client->close();
		$accepted[0]->close();
		$server->close();
	}

	public function testRegisterServerAcceptsAndRegistersConnections()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$reactor = new TSocketReactor();
		$messages = [];
		$reactor->registerServer($server, function (string $id, $conn) use (&$messages) {
			$data = $conn->read(1024);
			if ($data !== '') {
				$messages[] = $data;
			}
		});
		self::assertSame(1, $reactor->getSourceCount(), 'Only the listener is registered up front.');

		$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 1.0);
		$reactor->tick(1.0);                 // listener readable -> accept -> register the connection
		self::assertSame(2, $reactor->getSourceCount(), 'The accepted connection joins the loop.');

		$client->write('hello');
		$reactor->tick(1.0);                 // connection readable -> $onData
		self::assertSame(['hello'], $messages, 'registerServer() wires accept and per-connection reads in one call.');

		$client->close();
		$server->close();
	}

	public function testUnregisterServerStopsAccepting()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$reactor = new TSocketReactor();
		$reactor->registerServer($server, fn () => null);
		self::assertSame(1, $reactor->getSourceCount());

		$reactor->unregister($server);
		self::assertFalse($reactor->isRegistered($server), 'The listener leaves the loop.');
		self::assertSame(0, $reactor->getSourceCount());
		self::assertSame([], $reactor->getIdentifiers(), 'Unregistering cancels the listener callback.');

		// The listener is no longer watched, so a connecting client is not accepted into the loop.
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 1.0);
		$reactor->tick(0.1);
		self::assertSame(0, $reactor->getSourceCount(), 'After unregister, nothing is accepted into the loop.');

		$client->close();
		$server->close();
	}

	public function testReadableConnectionDispatchesData()
	{
		[$a, $b] = TSocketStream::pair();
		$a->setBlocking(false);
		$reactor = new TSocketReactor();
		$got = null;
		$reactor->register($a, onReadable: function (string $id, $src) use (&$got) {
			$got = $src->read(1024);
		});

		$b->write('ping');
		$reactor->tick(1.0);

		self::assertSame('ping', $got, 'A readable connection dispatches its bytes.');
		$a->close();
		$b->close();
	}

	public function testWantWriteGatesWritableDispatch()
	{
		[$a, $b] = TSocketStream::pair();
		$reactor = new TSocketReactor();
		$writes = 0;
		$reactor->register($a, onWritable: function () use (&$writes) {
			$writes++;
		});

		$reactor->tick(0.05);
		self::assertSame(0, $writes, 'A disarmed source is not watched for writability.');

		$reactor->wantWrite($a, true);
		$reactor->tick(0.5);
		self::assertSame(1, $writes, 'An armed, writable source dispatches.');

		$reactor->wantWrite($a, false);
		$reactor->tick(0.05);
		self::assertSame(1, $writes, 'Disarming stops write dispatch.');

		$a->close();
		$b->close();
	}

	public function testReRegisteringASourceReplacesItsCallbacks()
	{
		[$a, $b] = TSocketStream::pair();
		$a->setBlocking(false);
		$reactor = new TSocketReactor();
		$first = $second = 0;
		$reactor->register($a, onReadable: function ($id, $src) use (&$first) {
			$first++;
			$src->read(1024);
		});
		$reactor->register($a, onReadable: function ($id, $src) use (&$second) {
			$second++;
			$src->read(1024);
		});
		self::assertCount(1, $reactor->getIdentifiers(), 'Re-registering leaves one callback.');

		$b->write('x');
		$reactor->tick(1.0);
		self::assertSame([0, 1], [$first, $second], 'The replacement callback runs.');
		$a->close();
		$b->close();
	}

	public function testTimersFireAndRepeatOnASetClock()
	{
		// A set monotonic clock makes timer firing deterministic on any platform; advance() moves it.
		$reactor = new TSocketReactor();
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		$reactor->setMonotonicClock($clock);

		$fired = 0;
		$reactor->delay(2.0, function () use (&$fired) {
			$fired++;
		});
		$clock->advance(2.0);                // reach the one-shot's deadline
		$reactor->tick(0);
		self::assertSame(1, $fired, 'A one-shot timer fires once.');

		$ticks = 0;
		$id = $reactor->repeat(1.0, function () use (&$ticks) {
			$ticks++;
		});
		$clock->advance(1.0);                // each interval boundary fires the repeating timer once
		$reactor->tick(0);
		$clock->advance(1.0);
		$reactor->tick(0);
		self::assertSame(2, $ticks, 'A repeating timer fires each interval.');

		$reactor->cancel($id);
		$clock->advance(1.0);
		$reactor->tick(0);
		self::assertSame(2, $ticks, 'A cancelled timer stops firing.');
	}

	public function testRegisterUnregisterAndPruneOnClose()
	{
		[$a, $b] = TSocketStream::pair();
		$reactor = new TSocketReactor();
		$reactor->register($a, onReadable: fn () => null);
		self::assertTrue($reactor->isRegistered($a));
		self::assertSame(1, $reactor->getSourceCount());

		$reactor->unregister($a);
		self::assertFalse($reactor->isRegistered($a));
		self::assertSame(0, $reactor->getSourceCount());

		$reactor->register($a, onReadable: fn () => null);
		$a->close();
		$reactor->tick(0.01);                // pruneClosed drops the dead source
		self::assertSame(0, $reactor->getSourceCount(), 'A closed source is pruned on the next tick.');
		self::assertSame([], $reactor->getIdentifiers(), 'Its callback is cancelled.');
		$b->close();
	}

	public function testSetClockDrivesTimers()
	{
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		$reactor = new TSocketReactor();
		self::assertSame($reactor, $reactor->setMonotonicClock($clock));
		self::assertSame($clock, $reactor->getMonotonicClock());

		$fired = 0;
		$reactor->delay(1.0, function () use (&$fired) {
			$fired++;
		});
		$reactor->tick(0);
		self::assertSame(0, $fired, 'The timer has not reached its deadline.');

		$clock->advance(1.0);           // advance the set clock to the deadline
		$reactor->tick(0);
		self::assertSame(1, $fired, 'Advancing the set clock fires the timer.');
	}

	public function testTimersFollowOnlyTheMonotonicClock()
	{
		// The reactor reads no wall clock, so a wall-clock step cannot move a timer.
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		$reactor = new TSocketReactor();
		$reactor->setMonotonicClock($clock);
		$fired = 0;
		$reactor->delay(0.05, function () use (&$fired) {
			$fired++;
		});

		$start = hrtime(true);
		$reactor->tick(0.2);            // select() waits the timer's 0.05 s of real time
		self::assertGreaterThanOrEqual(0.04, (hrtime(true) - $start) / 1e9);
		self::assertSame(0, $fired, 'Real time passing does not fire a timer whose clock stands still.');

		$clock->advance(0.05);
		$reactor->tick(0);
		self::assertSame(1, $fired, 'The timer fires when its clock reaches the deadline.');
	}

	public function testSetClockRebasesPendingTimers()
	{
		$reactor = new TSocketReactor();
		$fired = 0;
		$reactor->delay(5.0, function () use (&$fired) {
			$fired++;
		});
		$clock = new TTestMonotonicClock(1_000_000_000_000);
		$reactor->setMonotonicClock($clock);  // the timer keeps its remaining 5 s on the new clock

		$clock->advance(4.0);
		$reactor->tick(0);
		self::assertSame(0, $fired, 'The re-based timer is not yet due.');
		$clock->advance(1.0);
		$reactor->tick(0);
		self::assertSame(1, $fired, 'The re-based timer fires at its remaining delay.');
	}

	public function testDefaultTimeBaseIsTheSystemTimer()
	{
		$reactor = new TSocketReactor();
		$before = hrtime(true) / 1e9;
		$now = $reactor->now();
		self::assertEqualsWithDelta($before, $now, 1.0, 'Without a set clock, now() reads hrtime().');
		self::assertInstanceOf(TMonotonicClock::class, $reactor->getMonotonicClock());

		$reactor->setMonotonicClock(new TTestMonotonicClock(5_000_000_000));
		self::assertSame(5.0, $reactor->now(), 'With a set clock, now() reads the clock.');

		$reactor->setMonotonicClock(null);
		self::assertEqualsWithDelta(hrtime(true) / 1e9, $reactor->now(), 1.0, 'Null returns now() to hrtime().');
	}

	public function testEmptyTickReturnsAtOnceAndFiniteTimeoutWaits()
	{
		$reactor = new TSocketReactor();
		$start = microtime(true);
		self::assertSame(0, $reactor->tick(null), 'An empty loop does not wait on a null timeout.');
		self::assertLessThan(0.5, microtime(true) - $start);

		$start = microtime(true);
		$reactor->tick(0.05);
		self::assertGreaterThanOrEqual(0.04, microtime(true) - $start, 'A finite timeout is honored on an empty loop.');
	}

	public function testRunLoopsUntilStopped()
	{
		$reactor = new TSocketReactor();
		$ran = 0;
		$reactor->repeat(0.01, function () use (&$ran, $reactor) {
			$ran++;
			if ($ran >= 3) {
				$reactor->stop();
			}
		});

		$reactor->run();

		self::assertSame(3, $ran, 'run() ticks until stop() is called.');
		self::assertFalse($reactor->isRunning());
	}

	public function testNestedRunStopsOnlyTheInnerLoop()
	{
		$reactor = new TSocketReactor();
		$outer = 0;
		$inner = 0;
		$reactor->delay(0, function () use ($reactor, &$inner, &$outer) {
			$reactor->repeat(0.001, function () use ($reactor, &$inner) {
				if (++$inner >= 2) {
					$reactor->stop();   // stops the nested run only
				}
			});
			$reactor->run();
			$outer = $inner;
			$reactor->delay(0.01, fn () => $reactor->stop());   // stops the outer run
		});
		$reactor->run();

		self::assertSame(2, $outer, 'The inner run returned after its stop().');
		self::assertGreaterThan(2, $inner, 'The outer run kept the repeating timer going.');
	}
}
