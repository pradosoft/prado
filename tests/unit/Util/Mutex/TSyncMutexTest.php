<?php

/**
 * TSyncMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\Log\TLogger;
use Prado\Util\Mutex\TSyncMutex;

/**
 * A named one-permit semaphore of the process, standing in for `SyncSemaphore`.
 */
class TSyncMutexTestFakeSemaphore
{
	/** @var array<string, int> the free permits by semaphore name. */
	public static array $permits = [];

	/** @var array the constructor arguments of each semaphore. */
	public static array $created = [];

	public function __construct(public string $name, int $initial = 1, bool $autoUnlock = true)
	{
		self::$created[] = [$name, $initial, $autoUnlock];
		self::$permits[$name] ??= $initial;
	}

	public function lock(int $wait = -1): bool
	{
		if (self::$permits[$this->name] < 1) {
			return false;
		}
		self::$permits[$this->name]--;
		return true;
	}

	public function unlock(): bool
	{
		self::$permits[$this->name]++;
		return true;
	}
}

/**
 * A TSyncMutex over {@see TSyncMutexTestFakeSemaphore}.
 */
class TSyncMutexTestMutex extends TSyncMutex
{
	protected function newSemaphore(string $semaphoreName): object
	{
		return new TSyncMutexTestFakeSemaphore($semaphoreName, 1, false);
	}
}

/**
 * Unit tests for {@see TSyncMutex}: semaphore names and the lock logic over a fake
 * semaphore, and exclusion over real `SyncSemaphore`s when the `sync` extension is
 * loaded (otherwise skipped).
 */
class TSyncMutexTest extends \PHPUnit\Framework\TestCase
{
	private array $mutexes = [];

	private string $prefix;

	protected function setUp(): void
	{
		TSyncMutexTestFakeSemaphore::$permits = [];
		TSyncMutexTestFakeSemaphore::$created = [];
		$this->prefix = 'prado-unit-' . bin2hex(random_bytes(6)) . ':';
	}

	protected function tearDown(): void
	{
		foreach ($this->mutexes as $mutex) {
			$mutex->releaseAll();
		}
		$this->mutexes = [];
	}

	private function newFake(): TSyncMutexTestMutex
	{
		$mutex = new TSyncMutexTestMutex();
		$mutex->setKeyPrefix($this->prefix);
		$mutex->setRetryDelay(1);
		return $this->mutexes[] = $mutex;
	}

	private function newLive(): TSyncMutex
	{
		if (!TSyncMutex::getIsAvailable()) {
			$this->markTestSkipped('The sync extension is not available.');
		}
		$mutex = new TSyncMutex();
		$mutex->setKeyPrefix($this->prefix);
		$mutex->setRetryDelay(1);
		$mutex->init(null);
		return $this->mutexes[] = $mutex;
	}

	public function testIsAvailableReturnsBool(): void
	{
		$this->assertIsBool(TSyncMutex::getIsAvailable());
	}

	public function testInitThrowsWhenExtensionUnavailable(): void
	{
		if (TSyncMutex::getIsAvailable()) {
			$this->markTestSkipped('sync extension present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		(new TSyncMutex())->init(null);
	}

	public function testSemaphoreName(): void
	{
		$mutex = new TSyncMutex();
		$this->assertSame('Prado_Mutex_' . md5('job'), $mutex->getSemaphoreName('job'));
		$mutex->setKeyPrefix('app:');
		$this->assertSame('Prado_Mutex_' . md5('app:job'), $mutex->getSemaphoreName('job'));
		$this->assertFalse($mutex->getGlobalNamespace());
		$mutex->setGlobalNamespace('true');
		$this->assertTrue($mutex->getGlobalNamespace());
		$expected = (TProcessHelper::isSystemWindows() ? 'Global\\' : '') . 'Prado_Mutex_' . md5('app:job');
		$this->assertSame($expected, $mutex->getSemaphoreName('job'));
	}

	public function testCreatesOnePermitSemaphoreWithoutAutoUnlock(): void
	{
		$mutex = $this->newFake();
		$this->assertTrue($mutex->acquire('job'));
		$this->assertSame([[$mutex->getSemaphoreName('job'), 1, false]], TSyncMutexTestFakeSemaphore::$created);
	}

	public function testFakeMutexesExcludeEachOther(): void
	{
		$a = $this->newFake();
		$b = $this->newFake();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$this->assertTrue($b->release('job'));
		$this->assertSame(1, TSyncMutexTestFakeSemaphore::$permits[$a->getSemaphoreName('job')]);
	}

	public function testForkedChildKeepsParentPermit(): void
	{
		$mutex = $this->newFake();
		$mutex->acquire('job');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($mutex->isAcquired('job'));
		$this->assertSame(0, TSyncMutexTestFakeSemaphore::$permits[$mutex->getSemaphoreName('job')]);
	}

	public function testLiveMutexesExcludeEachOther(): void
	{
		$a = $this->newLive();
		$b = $this->newLive();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.02));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$this->assertTrue($b->release('job'));
		$this->assertTrue($b->release('other'));
	}

	public function testLiveDestructReleases(): void
	{
		$a = $this->newLive();
		$this->assertTrue($a->acquire('job'));
		$this->mutexes = [];
		unset($a);
		$b = $this->newLive();
		$this->assertTrue($b->acquire('job'));
	}

	public function testForkWarnsForHeldNamesOnPosix(): void
	{
		$mutex = $this->newFake();
		$mutex->acquire('job');
		$logger = Prado::getLogger();
		$logger->deleteLogs(TLogger::WARNING, TSyncMutexTestMutex::class);
		$this->assertSame([], $mutex->fxPrepareForFork(null, null));
		$logs = $logger->deleteLogs(TLogger::WARNING, TSyncMutexTestMutex::class);
		if (TProcessHelper::isSystemWindows()) {
			$this->assertSame([], $logs);
		} else {
			$this->assertCount(1, $logs);
			$this->assertStringContainsString("'job'", $logs[0][0]);
		}
	}

	public function testLiveSemaphoreResetsWithNoOpenObject(): void
	{
		$a = $this->newLive();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($this->newLive()->acquire('job'));
		$a->fxRestoreAfterFork(null, ['pid' => 0]);
		gc_collect_cycles();
		$this->assertTrue($this->newLive()->acquire('job'));
	}
}
