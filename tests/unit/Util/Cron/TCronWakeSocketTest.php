<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronWakeSocket;

class TCronWakeSocketTest extends \PHPUnit\Framework\TestCase
{
	private string $runtime;

	protected function setUp(): void
	{
		$this->runtime = sys_get_temp_dir();
	}

	private function skipWithoutTransport(TCronWakeSocket $s): void
	{
		if ($s->getTransport() === null) {
			self::markTestSkipped('no datagram transport (udg/udp) available');
		}
	}

	private function unlinkUdg(string $address): void
	{
		if (str_starts_with($address, 'udg://')) {
			@unlink(substr($address, 6));
		}
	}

	public function testTransportResolves()
	{
		$t = (new TCronWakeSocket($this->runtime))->getTransport();
		self::assertTrue($t === null || $t === 'udg' || $t === 'udp');
	}

	public function testAddressKeyedByMinuteParity()
	{
		$s = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($s);
		self::assertSame($s->getAddress(2), $s->getAddress(4));    // even == even
		self::assertSame($s->getAddress(3), $s->getAddress(5));    // odd == odd
		self::assertNotSame($s->getAddress(2), $s->getAddress(3)); // even != odd
	}

	public function testSignalReachesABoundSocket()
	{
		$s = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($s);
		$minute = 8;
		$address = $s->getAddress($minute);
		$this->unlinkUdg($address);
		$server = stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
		self::assertIsResource($server);
		try {
			self::assertTrue($s->signal($minute));
			$read = [$server];
			$write = $except = [];
			self::assertGreaterThan(0, stream_select($read, $write, $except, 1, 0));
			self::assertNotFalse(stream_socket_recvfrom($server, 65535));
		} finally {
			fclose($server);
			$this->unlinkUdg($address);
		}
	}

	public function testWaitReturnsTrueWhenWoken()
	{
		$base = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($base);
		$minute = 10;
		$address = $base->getAddress($minute);
		$this->unlinkUdg($address);
		$server = stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
		$client = stream_socket_client($address, $e1, $e2, 0, STREAM_CLIENT_CONNECT);
		fwrite($client, '1'); // queue a wake datagram
		fclose($client);

		$socket = new class ($this->runtime) extends TCronWakeSocket {
			public $injected;
			protected function bind($address)
			{
				return $this->injected;
			}
		};
		$socket->injected = $server;
		$now = microtime(true);
		self::assertTrue($socket->wait($now + 5.0, $minute, $now));
		$socket->close();
	}

	public function testWaitReturnsFalseWhenTimedOut()
	{
		$base = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($base);
		$minute = 12;
		$address = $base->getAddress($minute);
		$this->unlinkUdg($address);
		$server = stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);

		$socket = new class ($this->runtime) extends TCronWakeSocket {
			public $injected;
			protected function bind($address)
			{
				return $this->injected;
			}
		};
		$socket->injected = $server;
		$now = microtime(true);
		self::assertFalse($socket->wait($now - 1.0, $minute, $now)); // already past → no wait, not woken
		$socket->close();
	}

	public function testPersistentSocketBuffersSignalBetweenWaits()
	{
		$socket = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($socket);
		$minute = 14;
		$this->unlinkUdg($socket->getAddress($minute));

		// First wait times out but binds the socket and keeps it bound.
		$now = microtime(true);
		self::assertFalse($socket->wait($now + 0.01, $minute, $now));

		// A signal arrives while the process is "busy" (not in select); it must be buffered.
		self::assertTrue($socket->signal($minute));

		// The next wait consumes the buffered datagram immediately instead of dropping it.
		$now = microtime(true);
		self::assertTrue($socket->wait($now + 5.0, $minute, $now));

		$socket->close();
	}
}
