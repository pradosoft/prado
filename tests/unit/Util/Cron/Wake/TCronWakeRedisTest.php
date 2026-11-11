<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakeRedis;

/**
 * Tests for {@see TCronWakeRedis}: the properties, the RESP codec, an unreachable server, and the
 * publish/subscribe round trip on a live Redis server at localhost:6379 (skipped without one).
 */
class TCronWakeRedisTest extends \PHPUnit\Framework\TestCase
{
	private function liveTransport(): TCronWakeRedis
	{
		$t = (new TCronWakeRedis())->setChannel('prado:unit:cronwake:' . bin2hex(random_bytes(3)))->setTimeout(0.5);
		$socket = @stream_socket_client('tcp://' . $t->getHost() . ':' . $t->getPort(), $errno, $errstr, 0.5);
		if ($socket === false) {
			self::markTestSkipped('No Redis server at localhost:6379.');
		}
		fclose($socket);
		return $t;
	}

	public function testProperties()
	{
		$t = new TCronWakeRedis();
		self::assertSame('localhost', $t->getHost());
		self::assertSame(6379, $t->getPort());
		self::assertNull($t->getSocket());
		self::assertNull($t->getUsername());
		self::assertNull($t->getPassword());
		self::assertSame(TCronWakeRedis::DEFAULT_CHANNEL, $t->getChannel());
		self::assertSame(TCronWakeRedis::DEFAULT_TIMEOUT, $t->getTimeout());
		self::assertSame($t, $t->setHost('redis.internal')->setPort('6380')->setSocket('/run/redis.sock')->setUsername('cron')->setPassword('secret')->setChannel('wake:1')->setTimeout('0.25'));
		self::assertSame('redis.internal', $t->getHost());
		self::assertSame(6380, $t->getPort());
		self::assertSame('/run/redis.sock', $t->getSocket());
		self::assertSame('cron', $t->getUsername());
		self::assertSame('secret', $t->getPassword());
		self::assertSame('wake:1', $t->getChannel());
		self::assertSame(0.25, $t->getTimeout());
		$t->setSocket('')->setUsername('')->setPassword('');
		self::assertNull($t->getSocket());
		self::assertNull($t->getUsername());
		self::assertNull($t->getPassword());
		try {
			$t->setChannel('bad channel');
			self::fail('a channel holds no whitespace');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			$t->setChannel('');
			self::fail('a channel is not empty');
		} catch (TInvalidDataValueException $e) {
		}
		self::expectException(TInvalidDataValueException::class);
		$t->setTimeout(0);
	}

	public function testTlsProperties()
	{
		$t = new class () extends TCronWakeRedis {
			public function addressPublic(): string
			{
				return $this->getAddress();
			}
			public function contextPublic(): array
			{
				return $this->getStreamContextOptions();
			}
		};
		self::assertFalse($t->getTls());
		self::assertNull($t->getCaFile());
		self::assertTrue($t->getVerifyPeer());
		self::assertNull($t->getPeerName());
		self::assertNull($t->getLocalCert());
		self::assertNull($t->getLocalKey());
		self::assertSame('tcp://localhost:6379', $t->addressPublic());
		self::assertSame([], $t->contextPublic(), 'no ssl options without TLS');
		self::assertSame($t, $t->setTls('true')->setHost('redis.internal'));
		self::assertSame('tls://redis.internal:6379', $t->addressPublic());
		self::assertSame(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => 'redis.internal']], $t->contextPublic(), 'the peer name defaults to the host');
		$t->setCaFile('/etc/ssl/redis-ca.pem')->setPeerName('redis.example.com')->setLocalCert('/etc/ssl/client.pem')->setLocalKey('/etc/ssl/client.key')->setVerifyPeer('false');
		self::assertSame([
			'ssl' => [
				'verify_peer' => false, 'verify_peer_name' => false, 'peer_name' => 'redis.example.com',
				'cafile' => '/etc/ssl/redis-ca.pem', 'local_cert' => '/etc/ssl/client.pem', 'local_pk' => '/etc/ssl/client.key',
			],
		], $t->contextPublic());
		$t->setCaFile('')->setPeerName('')->setLocalCert(null)->setLocalKey(null);
		self::assertNull($t->getCaFile());
		self::assertNull($t->getPeerName());
		self::assertNull($t->getLocalCert());
		self::assertNull($t->getLocalKey());
		$t->setCaFile('Application.runtime.ca');
		self::assertStringEndsWith('runtime' . DIRECTORY_SEPARATOR . 'ca', $t->getCaFile(), 'a dot namespace is a path');
		$t->setSocket('/run/redis.sock');
		self::assertSame('unix://run/redis.sock', str_replace('unix:///', 'unix://', $t->addressPublic()), 'a Unix socket is never TLS');
		self::assertSame([], $t->contextPublic());
	}

	/**
	 * Starts a TLS-only redis-server with a self-signed certificate for one test, or skips when
	 * `redis-server` or the `openssl` extension is absent.
	 * @return array{0: resource, 1: int, 2: string} the process, the TLS port, and the certificate file
	 */
	private function startTlsRedis(): array
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('No openssl extension.');
		}
		$server = trim((string) @shell_exec('command -v redis-server 2>/dev/null'));
		if ($server === '') {
			self::markTestSkipped('No redis-server in the PATH.');
		}
		$dir = sys_get_temp_dir() . '/prado-redis-tls-' . bin2hex(random_bytes(4));
		mkdir($dir, 0o700);
		$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		$csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
		$cert = openssl_csr_sign($csr, null, $key, 2, ['digest_alg' => 'sha256']);
		openssl_x509_export_to_file($cert, $dir . '/redis.crt');
		openssl_pkey_export_to_file($key, $dir . '/redis.key');
		$port = random_int(20000, 40000);
		$command = escapeshellarg($server) . ' --port 0 --tls-port ' . $port
			. ' --tls-cert-file ' . escapeshellarg($dir . '/redis.crt') . ' --tls-key-file ' . escapeshellarg($dir . '/redis.key')
			. ' --tls-ca-cert-file ' . escapeshellarg($dir . '/redis.crt') . ' --tls-auth-clients no'
			. " --save '' --appendonly no --dir " . escapeshellarg($dir) . " --logfile '' --daemonize no";
		$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if (!is_resource($process)) {
			self::markTestSkipped('redis-server did not start.');
		}
		for ($i = 0; $i < 50; $i++) {
			usleep(50000);
			$probe = @stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $errstr, 0.2);
			if ($probe !== false) {
				fclose($probe);
				return [$process, $port, $dir . '/redis.crt'];
			}
		}
		proc_terminate($process);
		self::markTestSkipped('redis-server did not open its TLS port (built without TLS?).');
	}

	public function testTlsRoundTrip()
	{
		[$process, $port, $ca] = $this->startTlsRedis();
		try {
			$make = static fn () => (new TCronWakeRedis())->setHost('localhost')->setPort($port)->setTls(true)->setTimeout(1.0)->setChannel('prado:unit:tls:' . bin2hex(random_bytes(3)));
			$t = $make()->setCaFile($ca);
			$now = microtime(true);
			self::assertFalse($t->wait($now + 0.05, $now), 'no message over TLS: the wait times out');
			self::assertTrue($t->signal($now));
			$start = microtime(true);
			self::assertTrue($t->wait($start + 3.0, $start), 'a message published over TLS wakes the TLS subscriber');
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
			self::assertTrue($t->watch($loop, static function () use (&$wakes, $resume) {
				$wakes++;
				$resume();
			}));
			$loop->delay(0.03, static fn () => $t->signal(microtime(true)));
			$timeout = $loop->delay(3.0, $resume);
			$suspension->suspend();
			$loop->cancel($timeout);
			$t->close();
			self::assertSame(1, $wakes, 'the TLS socket wakes the loop');

			$now = microtime(true);
			$untrusted = $make();
			self::assertNull($untrusted->wait($now + 1.0, $now), 'a self-signed certificate fails against the system bundle');
			self::assertFalse($untrusted->signal($now));
			self::assertTrue($make()->setVerifyPeer(false)->signal($now), 'VerifyPeer false accepts it');
			self::assertFalse($make()->setCaFile($ca)->setPeerName('other.host')->signal($now), 'the peer name is checked');
			self::assertFalse((new TCronWakeRedis())->setPort($port)->setTimeout(1.0)->signal($now), 'plain TCP cannot talk to the TLS port');
		} finally {
			proc_terminate($process);
			proc_close($process);
			foreach (['redis.crt', 'redis.key'] as $file) {
				@unlink(dirname($ca) . '/' . $file);
			}
			@rmdir(dirname($ca));
		}
	}

	public function testRespCodec()
	{
		$t = new class () extends TCronWakeRedis {
			public function encodePublic(array $args): string
			{
				return $this->encode($args);
			}
			public function decodePublic(string $buffer, int $pos): ?array
			{
				return $this->decode($buffer, $pos);
			}
		};
		self::assertSame("*3\r\n\$7\r\nPUBLISH\r\n\$4\r\nchan\r\n\$2\r\nhi\r\n", $t->encodePublic(['PUBLISH', 'chan', 'hi']));
		self::assertSame(['OK', 5], $t->decodePublic("+OK\r\n", 0));
		self::assertSame(['42', 5], $t->decodePublic(":42\r\n", 0));
		self::assertSame([null, 10], $t->decodePublic("-ERR bad\r\n", 0));
		self::assertSame(['hello', 11], $t->decodePublic("\$5\r\nhello\r\n", 0));
		self::assertSame([null, 5], $t->decodePublic("\$-1\r\n", 0));
		$message = "*3\r\n\$7\r\nmessage\r\n\$4\r\nchan\r\n\$3\r\n1.5\r\n";
		self::assertSame([['message', 'chan', '1.5'], strlen($message)], $t->decodePublic($message, 0));
		self::assertSame([['message', 'chan', '1.5'], 5 + strlen($message)], $t->decodePublic("+OK\r\n" . $message, 5), 'decodes at a position');
		self::assertNull($t->decodePublic(substr($message, 0, 20), 0), 'an incomplete frame is null');
		self::assertNull($t->decodePublic("\$5\r\nhel", 0));
		self::assertNull($t->decodePublic('', 0));
		self::expectException(TInvalidDataValueException::class);
		$t->decodePublic("garbage\r\n", 0);
	}

	public function testUnreachableServer()
	{
		$t = (new TCronWakeRedis())->setPort(1)->setTimeout(0.2);
		$now = microtime(true);
		self::assertNull($t->wait($now + 1.0, $now), 'wait is null when the server is unreachable');
		self::assertLessThan(1.0, microtime(true) - $now, 'the wait does not block for the timeout');
		self::assertFalse($t->signal($now));
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null));
		$t->close();
	}

	public function testPublishWakesTheSubscriber()
	{
		$t = $this->liveTransport();
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.05, $now), 'no message: the wait times out');
		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start), 'a message published while busy is received by the next wait');
		self::assertLessThan(2.0, microtime(true) - $start);
		self::assertTrue($t->signal($start));
		self::assertTrue($t->signal($start));
		usleep(20000);
		self::assertTrue($t->wait($start + 1.0, microtime(true)));
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.05, $now), 'both messages were consumed by one wait');
		$t->close();
	}

	public function testWatchDeliversMessagesOnTheLoop()
	{
		$t = $this->liveTransport();
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
		self::assertTrue($t->watch($loop, static function () use (&$wakes, $resume) {
			$wakes++;
			$resume();
		}));
		$loop->delay(0.03, static fn () => $t->signal(microtime(true)));
		$timeout = $loop->delay(3.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes);
	}

	public function testOtherClientsPublishOnTheChannel()
	{
		$t = $this->liveTransport();
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.02, $now));
		$publisher = stream_socket_client('tcp://localhost:6379', $errno, $errstr, 0.5);
		fwrite($publisher, "PUBLISH " . $t->getChannel() . " 1\r\n");
		fread($publisher, 64);
		fclose($publisher);
		$start = microtime(true);
		self::assertTrue($t->wait($start + 2.0, $start), 'a message from any client wakes the subscriber');
		$t->close();
	}
}
