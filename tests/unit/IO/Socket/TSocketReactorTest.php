<?php

namespace Prado\Test\Unit\IO\Socket;

use Prado\IO\Socket\TSocketReactor;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;
use Prado\Util\Clock\TMockClock;

class TSocketReactorTest extends \PHPUnit\Framework\TestCase
{
	public function testListenerReadableDispatchesAccept()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$server->setBlocking(false);
		$reactor = new TSocketReactor();
		$accepted = [];
		$reactor->register($server, onReadable: function ($src) use (&$accepted) {
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
		$reactor->registerServer($server, function ($conn) use (&$messages) {
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
		$reactor->register($a, onReadable: function ($src) use (&$got) {
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

	public function testTimersFireAndRepeat()
	{
		// A pinned clock makes timer firing deterministic on any platform; sleep() advances the pin.
		$reactor = new TSocketReactor();
		$clock = new TMockClock();
		$clock->setMicrotime(1000.0);
		$reactor->setClock($clock);

		$fired = 0;
		$reactor->after(2.0, function () use (&$fired) {
			$fired++;
		});
		$clock->sleep(2.0);                  // reach the one-shot's deadline
		$reactor->tick(0);
		self::assertSame(1, $fired, 'A one-shot timer fires once.');

		$ticks = 0;
		$id = $reactor->every(1.0, function () use (&$ticks) {
			$ticks++;
		});
		$clock->sleep(1.0);                  // each interval boundary fires the repeating timer once
		$reactor->tick(0);
		$clock->sleep(1.0);
		$reactor->tick(0);
		self::assertSame(2, $ticks, 'A repeating timer fires each interval.');

		$reactor->cancelTimer($id);
		$clock->sleep(1.0);
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
		$b->close();
	}

	public function testInjectedClockDrivesTimers()
	{
		$clock = new TMockClock();
		$clock->setMicrotime(1000.0);
		$reactor = new TSocketReactor();
		$reactor->setClock($clock);

		$fired = 0;
		$reactor->after(1.0, function () use (&$fired) {
			$fired++;
		});
		$reactor->tick(0);
		self::assertSame(0, $fired, 'The timer has not reached its deadline.');

		$clock->setMicrotime(1001.0);   // advance the injected clock to the deadline
		$reactor->tick(0);
		self::assertSame(1, $fired, 'Advancing the injected clock fires the timer.');
	}

	public function testRunLoopsUntilStopped()
	{
		$reactor = new TSocketReactor();
		$ran = 0;
		$reactor->every(0.01, function () use (&$ran, $reactor) {
			$ran++;
			if ($ran >= 3) {
				$reactor->stop();
			}
		});

		$reactor->run(0.05);

		self::assertSame(3, $ran, 'run() ticks until stop() is called.');
		self::assertFalse($reactor->isRunning());
	}
}
