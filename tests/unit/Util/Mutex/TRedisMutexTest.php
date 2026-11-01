<?php

/**
 * TRedisMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\Mutex\TRedisMutex;

/**
 * An in-memory stand-in for the `\Redis` calls of {@see TRedisMutex}.  `eval()`
 * runs {@see TRedisMutex::RELEASE_SCRIPT} as Redis would.
 */
class TRedisMutexTestFakeRedis
{
	/** @var array<string, string> the stored values. */
	public array $values = [];

	/** @var array<string, int> the PX option of each set key. */
	public array $px = [];

	/** @var array the arguments of connect() and select(). */
	public array $calls = [];

	/** @var ?string the last evaluated script. */
	public ?string $script = null;

	public function connect(...$args): bool
	{
		$this->calls[] = ['connect', $args];
		return true;
	}

	public function select(int $index): bool
	{
		$this->calls[] = ['select', [$index]];
		return true;
	}

	public function close(): bool
	{
		return true;
	}

	public function set(string $key, string $value, array $options): bool
	{
		if (in_array('nx', $options, true) && isset($this->values[$key])) {
			return false;
		}
		$this->values[$key] = $value;
		$this->px[$key] = $options['px'];
		return true;
	}

	public function eval(string $script, array $args, int $numKeys): int
	{
		$this->script = $script;
		[$key, $token] = $args;
		if (($this->values[$key] ?? null) !== $token) {
			return 0;
		}
		unset($this->values[$key]);
		return 1;
	}
}

/**
 * A TRedisMutex that connects to a {@see TRedisMutexTestFakeRedis}.
 */
class TRedisMutexTestMutex extends TRedisMutex
{
	public ?TRedisMutexTestFakeRedis $fake = null;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	protected function newRedis(): object
	{
		return $this->fake ??= new TRedisMutexTestFakeRedis();
	}
}

/**
 * Unit tests for {@see TRedisMutex} over a fake Redis connection, and against a live
 * server when the `redis` extension and a server on localhost are present
 * (otherwise skipped).
 */
class TRedisMutexTest extends \PHPUnit\Framework\TestCase
{
	private function newMutex(?TRedisMutexTestFakeRedis $fake = null): TRedisMutexTestMutex
	{
		$mutex = new TRedisMutexTestMutex();
		$mutex->fake = $fake;
		$mutex->setRetryDelay(1);
		$mutex->init(null);
		return $mutex;
	}

	public function testIsAvailableReturnsBool(): void
	{
		$this->assertIsBool(TRedisMutex::getIsAvailable());
	}

	public function testInitThrowsWhenExtensionUnavailable(): void
	{
		if (TRedisMutex::getIsAvailable()) {
			$this->markTestSkipped('redis extension present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		(new TRedisMutex())->init(null);
	}

	public function testConnectionProperties(): void
	{
		$mutex = new TRedisMutexTestMutex();
		$this->assertSame('localhost', $mutex->getHost());
		$this->assertSame(6379, $mutex->getPort());
		$this->assertNull($mutex->getSocket());
		$this->assertSame(0, $mutex->getIndex());
		$mutex->setHost('redis.example.test')->setPort('6380')->setIndex('3');
		$mutex->init(null);
		$this->assertSame([['connect', ['redis.example.test', 6380]], ['select', [3]]], $mutex->fake->calls);
		$this->expectException(TInvalidOperationException::class);
		$mutex->setHost('other');
	}

	public function testSocketTakesPrecedence(): void
	{
		$mutex = new TRedisMutexTestMutex();
		$mutex->setSocket('/var/run/redis.sock');
		$this->assertSame('/var/run/redis.sock', $mutex->getSocket());
		$mutex->init(null);
		$this->assertSame(['connect', ['/var/run/redis.sock']], $mutex->fake->calls[0]);
	}

	public function testExpire(): void
	{
		$mutex = new TRedisMutexTestMutex();
		$this->assertSame((float) TRedisMutex::DEFAULT_EXPIRE, $mutex->getExpire());
		$mutex->setExpire('1.5');
		$this->assertSame(1.5, $mutex->getExpire());
		$this->expectException(TInvalidDataValueException::class);
		$mutex->setExpire(0);
	}

	public function testMutexesExcludeEachOther(): void
	{
		$fake = new TRedisMutexTestFakeRedis();
		$a = $this->newMutex($fake);
		$b = $this->newMutex($fake);
		$a->setExpire(2.5);
		$this->assertTrue($a->acquire('job'));
		$this->assertSame(2500, $fake->px['mutex:job']);
		$this->assertFalse($b->acquire('job'));
		$this->assertTrue($a->release('job'));
		$this->assertSame(TRedisMutex::RELEASE_SCRIPT, $fake->script);
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
		$this->assertSame([], $fake->values);
	}

	public function testKeyPrefix(): void
	{
		$mutex = new TRedisMutexTestMutex();
		$mutex->setKeyPrefix('app:');
		$this->assertSame('mutex:app:job', $mutex->getRedisKey('job'));
	}

	public function testReleaseLeavesAnotherHoldersKey(): void
	{
		$fake = new TRedisMutexTestFakeRedis();
		$mutex = $this->newMutex($fake);
		$mutex->acquire('job');
		$fake->values['mutex:job'] = 'next-holder';
		$this->assertFalse($mutex->release('job'));
		$this->assertSame('next-holder', $fake->values['mutex:job']);
	}

	public function testForkedChildKeepsParentKey(): void
	{
		$fake = new TRedisMutexTestFakeRedis();
		$mutex = $this->newMutex($fake);
		$mutex->acquire('job');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($mutex->isAcquired('job'));
		$this->assertArrayHasKey('mutex:job', $fake->values);
	}

	public function testLiveServer(): void
	{
		if (!TRedisMutex::getIsAvailable()) {
			$this->markTestSkipped('redis extension not available.');
		}
		$probe = new \Redis();
		try {
			if (!@$probe->connect('localhost', 6379, 0.5)) {
				$this->markTestSkipped('No redis server on localhost:6379.');
			}
		} catch (\RedisException $e) {
			$this->markTestSkipped('No redis server on localhost:6379: ' . $e->getMessage());
		}
		$probe->close();
		$prefix = 'prado-unit-' . bin2hex(random_bytes(4)) . ':';
		$a = (new TRedisMutex())->setKeyPrefix($prefix)->setExpire(5);
		$b = (new TRedisMutex())->setKeyPrefix($prefix)->setExpire(5);
		$a->init(null);
		$b->init(null);
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$this->assertTrue($b->release('job'));
		$this->assertFalse($a->release('job'));
	}
}
