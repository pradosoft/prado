<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakeSocket;

class TCronWakeSocketTest extends \PHPUnit\Framework\TestCase
{
	private string $runtime;

	protected function setUp(): void
	{
		$this->runtime = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-test-' . bin2hex(random_bytes(4));
		mkdir($this->runtime);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->runtime . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
			@unlink($file);
		}
		@rmdir($this->runtime);
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
		$s = new TCronWakeSocket();
		self::assertNotEmpty($s->getRuntimePath(), 'defaults to the application runtime path');
	}

	public function testKeyIsSharedThroughTheKeyFile()
	{
		$file = $this->runtime . DIRECTORY_SEPARATOR . TCronWakeSocket::KEY_FILE;
		$s = new TCronWakeSocket($this->runtime);
		self::assertFileDoesNotExist($file);
		$key = $s->getKey();
		self::assertSame(64, strlen($key), 'the first transport creates a random key');
		self::assertSame($key, file_get_contents($file));
		if (DIRECTORY_SEPARATOR === '/') {
			self::assertSame(0o600, fileperms($file) & 0o777, 'the key file is owner-only');
		}
		self::assertSame($key, (new TCronWakeSocket($this->runtime))->getKey(), 'another transport, as in another process, reads the same key');
		$s->setKey('secret');
		self::assertSame('secret', $s->getKey());
		$s->setKey('');
		self::assertSame($key, $s->getKey(), "'' returns to the key file");

		file_put_contents($file, '');
		$rewritten = (new TCronWakeSocket($this->runtime))->getKey();
		self::assertSame(64, strlen($rewritten), 'a file left empty is rewritten');
		self::assertSame($rewritten, file_get_contents($file));

		$missing = $this->runtime . DIRECTORY_SEPARATOR . 'missing';
		$fallback = (new TCronWakeSocket($missing))->getKey();
		self::assertSame($fallback, (new TCronWakeSocket($missing))->getKey(), 'an unwritable key file falls back to one key for every process');
		self::assertNotSame($rewritten, $fallback);
	}

	public function testKeyFileSignsAcrossTransports()
	{
		$sender = new TCronWakeSocket($this->runtime);
		$receiver = new TCronWakeSocket($this->runtime);
		self::assertTrue($receiver->verifyToken($sender->createToken()), 'a message from another transport of the application verifies');
		$foreign = new TCronWakeSocket($this->runtime);
		$foreign->setKey('other');
		self::assertFalse($receiver->verifyToken($foreign->createToken()), 'a message signed with another key does not');
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

	public function testInitUdpAddressesAndNonceExpiry()
	{
		$s = new TCronWakeSocket($this->runtime);
		$s->init(null);
		self::assertSame((int) TCronWakeSocket::DEFAULT_WAKE_PORT, $s->getBasePort());
		\Prado\Test\Unit\PradoUnit::setProp($s, '_transport', 'udp');
		self::assertSame('udp://127.0.0.1:' . ($s->getBasePort() + 1), $s->getAddress(1));
		self::assertSame('udp://127.0.0.1:' . ($s->getBasePort() + 2), $s->getDaemonAddress());

		$token = $s->createToken();
		\Prado\Test\Unit\PradoUnit::setProp($s, '_seenNonces', ['expired' => time() - 1]);
		self::assertTrue($s->verifyToken($token));
		self::assertArrayNotHasKey('expired', \Prado\Test\Unit\PradoUnit::getProp($s, '_seenNonces'), 'an expired nonce is forgotten');

		\Prado\Test\Unit\PradoUnit::setProp($s, '_transport', null);
		$now = microtime(true);
		self::assertNull($s->wait($now + 1, $now), 'without a transport the caller sleeps');
	}

	public function testALongRuntimePathUsesUdp()
	{
		if (!in_array('udp', stream_get_transports(), true)) {
			self::markTestSkipped('no udp transport');
		}
		$s = new TCronWakeSocket($this->runtime . DIRECTORY_SEPARATOR . str_repeat('r', TCronWakeSocket::UDG_PATH_MAX));
		self::assertSame('udp', $s->getTransport(), 'a socket path PHP would truncate is not used');
		self::assertStringStartsWith('udp://127.0.0.1:', (string) $s->getDaemonAddress());
		$s->setRuntimePath('/tmp');
		self::assertSame(in_array('udg', stream_get_transports(), true) ? 'udg' : 'udp', $s->getTransport(), 'a new runtime path resolves the transport again');
	}

	public function testWithoutAnAddressOrABindableSocket()
	{
		$s = new TCronWakeSocket($this->runtime);
		\Prado\Test\Unit\PradoUnit::setProp($s, '_transport', null);
		self::assertFalse($s->watch(TAsync::getLoop(), static fn () => null), 'without a transport there is no daemon address');

		$s = new TCronWakeSocket($this->runtime . DIRECTORY_SEPARATOR . 'missing');
		$this->skipWithoutTransport($s);
		if ($s->getTransport() !== 'udg') {
			self::markTestSkipped('a udp socket binds without a directory');
		}
		$now = microtime(true);
		self::assertFalse($s->watch(TAsync::getLoop(), static fn () => null), 'a socket in a missing directory cannot be bound');
		self::assertNull($s->wait($now + 1, $now));
	}
}
