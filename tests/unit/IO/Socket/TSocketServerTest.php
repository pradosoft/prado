<?php

use Prado\IO\Socket\TSocketAddress;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;

class TSocketServerTest extends PHPUnit\Framework\TestCase
{
	public function testBindAndLocalAddressShortcuts()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');   // ephemeral port
		self::assertTrue($server->isListening());
		self::assertInstanceOf(TSocketAddress::class, $server->getLocalAddress());
		self::assertGreaterThan(0, $server->getPort());
		self::assertSame('127.0.0.1', $server->getHost());

		$server->close();
		self::assertFalse($server->isListening());
		self::assertNull($server->getLocalAddress());
		self::assertNull($server->getPort());
		self::assertNull($server->closeStream(), 'closeStream() is null after close (idempotent).');
	}

	public function testAcceptYieldsConnectedStream()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);

		$conn = $server->accept(1.0);
		self::assertInstanceOf(TSocketStream::class, $conn);
		$client->write('ping');
		self::assertSame('ping', $conn->read(4));

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testAcceptTimesOutToNull()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		self::assertNull($server->accept(0.1), 'accept() returns null when nothing connects.');
		$server->close();
		self::assertNull($server->accept(0.1), 'accept() on a closed server returns null.');
	}

	public function testIterationYieldsConnections()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$client->write('hi');

		$received = null;
		foreach ($server as $connection) {            // \IteratorAggregate accept loop
			self::assertInstanceOf(TSocketStream::class, $connection);
			$received = $connection->read(2);
			$connection->close();
			break;                                    // handle one connection then stop
		}
		self::assertSame('hi', $received);

		$client->close();
		$server->close();
	}

	public function testSelectWithObjects()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);
		$client->write('data');

		$read = [$conn];                              // IResource objects
		$write = null;
		$except = null;
		self::assertSame(1, TSocketServer::select($read, $write, $except, 1));
		self::assertCount(1, $read);
		self::assertSame($conn, $read[0], 'select() rebuilds with the original objects.');
		self::assertSame('data', $conn->read(4));

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testTracksAndUntracksConnections()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		self::assertSame(0, $server->getConnectionCount());

		$c1 = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn1 = $server->accept(1.0);
		$c2 = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn2 = $server->accept(1.0);
		self::assertSame(2, $server->getConnectionCount());
		self::assertSame([$conn1, $conn2], $server->getConnections());

		$conn1->close();
		self::assertSame(1, $server->getConnectionCount(), 'A closed connection leaves the registry.');
		self::assertSame([$conn2], $server->getConnections());

		$conn2->close();
		self::assertSame(0, $server->getConnectionCount());

		$c1->close();
		$c2->close();
		$server->close();
	}

	public function testRaisesClientLifecycleEvents()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);

		$finalized = null;
		$closed = null;
		$server->attachEventHandler('onClientFinalize', function ($sender, $param) use (&$finalized) {
			$finalized = $param;
		});
		$server->attachEventHandler('onClientClose', function ($sender, $param) use (&$closed) {
			$closed = $param;
		});

		$conn->close();
		self::assertSame($conn, $finalized, 'onClientFinalize carries the closing connection.');
		self::assertSame($conn, $closed, 'onClientClose carries the closed connection.');

		$client->close();
		$server->close();
	}

	public function testSelectWithRawResources()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);
		$client->write('z');

		$raw = $conn->getResource();                  // a raw PHP resource, not an IResource
		$read = [$raw];
		$write = null;
		$except = null;
		self::assertSame(1, TSocketServer::select($read, $write, $except, 1));
		self::assertCount(1, $read);
		self::assertSame($raw, $read[0], 'select() preserves raw resources too.');

		$conn->close();
		$client->close();
		$server->close();
	}
}
