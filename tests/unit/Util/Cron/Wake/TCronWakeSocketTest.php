<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakeSocket;

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

	private function unlinkUdg(?string $address): void
	{
		if ($address !== null && str_starts_with($address, 'udg://')) {
			@unlink(substr($address, 6));
		}
	}

	public function testTransportResolves()
	{
		$t = (new TCronWakeSocket($this->runtime))->getTransport();
		self::assertTrue($t === null || $t === 'udg' || $t === 'udp');
	}

	public function testProperties()
	{
		$s = new TCronWakeSocket($this->runtime, 60000);
		self::assertSame(rtrim($this->runtime, '/\\'), $s->getRuntimePath());
		self::assertSame(60000, $s->getBasePort());
		$s->setRuntimePath($this->runtime . '/sub/');
		self::assertSame($this->runtime . '/sub', $s->getRuntimePath());
		$s->setBasePort('61000');
		self::assertSame(61000, $s->getBasePort());
		self::assertSame(64, strlen($s->getKey()), 'the default key is a SHA-256 HMAC of the validation key');
		self::assertSame($s->getKey(), (new TCronWakeSocket($this->runtime))->getKey(), 'every transport of the application derives the same key');
		$s->setKey('secret');
		self::assertSame('secret', $s->getKey());
		$s->setKey('');
		self::assertSame(64, strlen($s->getKey()));
		$s = new TCronWakeSocket();
		self::assertNotEmpty($s->getRuntimePath(), 'defaults to the application runtime path');
	}

	public function testAddressKeyedByMinuteParityAndTheDaemon()
	{
		$s = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($s);
		self::assertSame($s->getAddress(2), $s->getAddress(4));    // even == even
		self::assertSame($s->getAddress(3), $s->getAddress(5));    // odd == odd
		self::assertNotSame($s->getAddress(2), $s->getAddress(3)); // even != odd
		self::assertNotSame($s->getAddress(2), $s->getDaemonAddress());
		self::assertNotSame($s->getAddress(3), $s->getDaemonAddress());
	}

	public function testSignalReachesTheMinuteAndTheDaemonSockets()
	{
		$s = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($s);
		$minute = 8;
		$addresses = [$s->getAddress($minute), $s->getDaemonAddress()];
		$servers = [];
		foreach ($addresses as $address) {
			$this->unlinkUdg($address);
			$servers[] = $server = stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
			self::assertIsResource($server);
		}
		try {
			self::assertTrue($s->signal($minute * 60 + 30.5));
			foreach ($servers as $server) {
				$read = [$server];
				$write = $except = [];
				self::assertGreaterThan(0, stream_select($read, $write, $except, 1, 0));
				self::assertNotFalse(stream_socket_recvfrom($server, 65535));
			}
		} finally {
			foreach ($servers as $i => $server) {
				fclose($server);
				$this->unlinkUdg($addresses[$i]);
			}
		}
	}

	public function testWaitReturnsTrueWhenWoken()
	{
		$base = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($base);
		$now = 10 * 60 + 1.0;
		$address = $base->getAddress(10);
		$this->unlinkUdg($address);
		$server = stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
		$client = stream_socket_client($address, $e1, $e2, 0, STREAM_CLIENT_CONNECT);
		fwrite($client, $base->createToken()); // queue a wake datagram
		fclose($client);

		$socket = new class ($this->runtime) extends TCronWakeSocket {
			public $injected;
			protected function bind($address)
			{
				return $this->injected;
			}
		};
		$socket->injected = $server;
		self::assertTrue($socket->wait($now + 5.0, $now));
		$socket->close();
	}

	public function testWaitReturnsFalseWhenTimedOut()
	{
		$base = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($base);
		$now = 12 * 60 + 1.0;
		$address = $base->getAddress(12);
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
		self::assertFalse($socket->wait($now - 1.0, $now)); // already past: no wait, not woken
		self::assertFalse($socket->wait($now + 0.01, $now));
		$socket->close();
	}

	public function testPersistentSocketBuffersSignalBetweenWaits()
	{
		$socket = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($socket);
		$now = 14 * 60 + 1.0;
		$this->unlinkUdg($socket->getAddress(14));
		$this->unlinkUdg($socket->getDaemonAddress());

		// First wait times out but binds the socket and keeps it bound.
		self::assertFalse($socket->wait($now + 0.01, $now));

		// A signal arrives while the process is "busy" (not in select); it must be buffered.
		self::assertTrue($socket->signal($now));

		// The next wait consumes the buffered datagram immediately instead of dropping it.
		self::assertTrue($socket->wait($now + 5.0, $now));

		$socket->close();
	}

	public function testAForeignDatagramDoesNotWake()
	{
		$base = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($base);
		$now = 16 * 60 + 1.0;
		$address = $base->getAddress(16);
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
		$send = static function (string $data) use ($address) {
			$client = stream_socket_client($address, $e1, $e2, 0, STREAM_CLIENT_CONNECT);
			fwrite($client, $data);
			fclose($client);
		};
		$send('1');
		$send(str_repeat('x', 64));
		$start = microtime(true);
		self::assertFalse($socket->wait($now + 0.05, $now), 'datagrams without the token are read and ignored');
		self::assertGreaterThanOrEqual(0.04, microtime(true) - $start, 'the wait goes on after a foreign datagram');
		$send($socket->createToken());
		self::assertTrue($socket->wait($now + 5.0, $now));
		$socket->close();
	}

	public function testTokensAreSignedFreshAndNotReplayable()
	{
		$s = (new TCronWakeSocket($this->runtime))->setKey('k1');
		$a = $s->createToken();
		$b = $s->createToken();
		self::assertNotSame($a, $b, 'every wake message differs');
		self::assertTrue($s->verifyToken($a));
		self::assertFalse($s->verifyToken($a), 'a message is accepted once');
		self::assertTrue($s->verifyToken($b));
		self::assertFalse((new TCronWakeSocket($this->runtime))->setKey('k2')->verifyToken($s->createToken()), 'another key rejects the signature');
		[$time, $nonce] = explode(':', $s->createToken());
		$stale = (time() - TCronWakeSocket::TOKEN_WINDOW - 5) . ':' . $nonce;
		self::assertFalse($s->verifyToken($stale . ':' . hash_hmac('sha256', $stale, 'k1')), 'a message older than the window is rejected');
		self::assertFalse($s->verifyToken('1'));
		self::assertFalse($s->verifyToken(''));
	}

	public function testWatchDeliversWakesOnTheLoop()
	{
		$socket = new TCronWakeSocket($this->runtime);
		$this->skipWithoutTransport($socket);
		$this->unlinkUdg($socket->getDaemonAddress());
		$loop = TAsync::getLoop();
		$wakes = 0;
		$suspension = $loop->suspension();
		$resumed = false;
		$resume = static function () use ($suspension, &$resumed) {
			if (!$resumed) {
				$resumed = true;
				$suspension->resume();
			}
		};
		self::assertTrue($socket->watch($loop, static function () use (&$wakes, $resume) {
			$wakes++;
			$resume();
		}));
		$loop->delay(0.02, static fn () => $socket->signal(microtime(true)));
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$socket->close();
		self::assertSame(1, $wakes);
	}
}
