<?php

use Prado\Exceptions\TSocketException;
use Prado\IO\Socket\TSocketAddress;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;
use Psr\Http\Message\StreamInterface;

class TSocketStreamTest extends PHPUnit\Framework\TestCase
{
	public function testTransports()
	{
		self::assertContains('tcp', TSocketStream::getTransports());
		self::assertTrue(TSocketStream::supportsTransport('tcp'));
		self::assertFalse(TSocketStream::supportsTransport('not-a-transport'));
	}

	public function testConnectFailureThrows()
	{
		self::expectException(TSocketException::class);
		TSocketStream::connect('tcp://127.0.0.1:1', 0.5);   // port 1 refuses quickly
	}

	public function testBindFailureThrows()
	{
		self::expectException(TSocketException::class);
		TSocketStream::bind('udp://256.256.256.256:5000');  // invalid bind address
	}

	public function testTcpRoundTripAndAddresses()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);

		self::assertInstanceOf(StreamInterface::class, $client);
		$client->write('ping');
		self::assertSame('ping', $conn->read(4));
		$conn->write('pong');
		self::assertSame('pong', $client->read(4));

		self::assertInstanceOf(TSocketAddress::class, $client->getRemoteAddress());
		$local = $client->getLocalAddress();
		self::assertInstanceOf(TSocketAddress::class, $local);
		self::assertSame('127.0.0.1', $local->getHost());

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testShutdown()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);

		self::assertTrue($client->shutdown(STREAM_SHUT_WR));

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testUdpDatagramRoundTrip()
	{
		// Bind a UDP socket (bind only, no listen/accept).
		$server = TSocketStream::bind('udp://127.0.0.1:0');
		$server->setTimeout(2);
		$port = $server->getLocalAddress()->getPort();
		self::assertNotNull($port);

		$client = TSocketStream::connect('udp://127.0.0.1:' . $port);
		$client->setTimeout(2);
		$client->write('ping');

		$peer = null;
		self::assertSame('ping', $server->recvFrom(65535, 0, $peer));
		self::assertNotEmpty($peer, 'recvFrom captures the sender address.');

		self::assertNotFalse($server->sendTo('pong', 0, $peer));
		self::assertSame('pong', $client->read(65535));

		$client->close();
		$server->close();
	}

	public function testPair()
	{
		[$a, $b] = TSocketStream::pair();
		self::assertInstanceOf(TSocketStream::class, $a);
		self::assertInstanceOf(TSocketStream::class, $b);
		$a->write('ping');
		self::assertSame('ping', $b->read(4));
		$b->write('pong');
		self::assertSame('pong', $a->read(4));
		$a->close();
		$b->close();
	}

	public function testClosedSocketOperationsReturnFalseOrNull()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$server->accept(1.0);
		$client->close();

		self::assertFalse($client->recvFrom(10));
		self::assertFalse($client->sendTo('x'));
		self::assertFalse($client->shutdown());
		self::assertFalse($client->enableCrypto());
		self::assertNull($client->getRemoteAddress());
		self::assertNull($client->getLocalAddress());

		$server->close();
	}
}
