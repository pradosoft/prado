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
