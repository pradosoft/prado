<?php

/**
 * TMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Mutex\IMutex;
use Prado\Util\Mutex\TMutex;
use Prado\Util\Mutex\TMutexException;

/**
 * A mutex whose backend is a list of scripted attempt results.
 */
class TMutexTestScriptedMutex extends TMutex
{
	/** @var bool[] the results of the next attempts; an empty list grants. */
	public array $attempts = [];

	/** @var string[] the names of each backend attempt. */
	public array $acquireCalls = [];

	/** @var string[] the names of each backend release. */
	public array $releaseCalls = [];

	/** @var string[] the names forgotten after a fork. */
	public array $forgotten = [];

	/** @var bool the result of a backend release. */
	public bool $releaseResult = true;

	protected function acquireLock(string $name): bool
	{
		$this->acquireCalls[] = $name;
		return $this->attempts === [] ? true : array_shift($this->attempts);
	}

	protected function releaseLock(string $name): bool
	{
		$this->releaseCalls[] = $name;
		return $this->releaseResult;
	}

	protected function forgetLock(string $name): void
	{
		$this->forgotten[] = $name;
	}

	public function pubGetLockKey(string $name): string
	{
		return $this->getLockKey($name);
	}
}

/**
 * A scripted mutex whose release of 'a' throws.
 */
class TMutexTestThrowingMutex extends TMutexTestScriptedMutex
{
	protected function releaseLock(string $name): bool
	{
		parent::releaseLock($name);
		if ($name === 'a') {
			throw new \RuntimeException('connection lost');
		}
		return true;
	}
}

/**
 * Unit tests for {@see TMutex}: the held names, retries, timeouts, synchronize,
 * auto release, and the forked child.
 */
class TMutexTest extends \PHPUnit\Framework\TestCase
{
	private function newMutex(): TMutexTestScriptedMutex
	{
		$mutex = new TMutexTestScriptedMutex();
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	public function testImplementsIMutex(): void
	{
		$this->assertInstanceOf(IMutex::class, $this->newMutex());
	}

	public function testAcquireAndRelease(): void
	{
		$mutex = $this->newMutex();
		$this->assertFalse($mutex->isAcquired('a'));
		$this->assertTrue($mutex->acquire('a'));
		$this->assertTrue($mutex->isAcquired('a'));
		$this->assertSame(['a'], $mutex->getAcquiredLocks());
		$this->assertTrue($mutex->release('a'));
		$this->assertFalse($mutex->isAcquired('a'));
		$this->assertSame([], $mutex->getAcquiredLocks());
		$this->assertSame(['a'], $mutex->acquireCalls);
		$this->assertSame(['a'], $mutex->releaseCalls);
	}

	public function testReleaseOfUnheldNameReturnsFalse(): void
	{
		$mutex = $this->newMutex();
		$this->assertFalse($mutex->release('missing'));
		$this->assertSame([], $mutex->releaseCalls);
	}

	public function testReleaseForgetsNameWhenBackendFails(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('a');
		$mutex->releaseResult = false;
		$this->assertFalse($mutex->release('a'));
		$this->assertFalse($mutex->isAcquired('a'));
	}

	public function testAcquireWithoutTimeoutTriesOnce(): void
	{
		$mutex = $this->newMutex();
		$mutex->attempts = [false, true];
		$this->assertFalse($mutex->acquire('a'));
		$this->assertCount(1, $mutex->acquireCalls);
		$this->assertFalse($mutex->isAcquired('a'));
	}

	public function testAcquireRetriesUntilGranted(): void
	{
		$mutex = $this->newMutex();
		$mutex->attempts = [false, false, true];
		$this->assertTrue($mutex->acquire('a', 5));
		$this->assertCount(3, $mutex->acquireCalls);
	}

	public function testAcquireFailsAfterTimeout(): void
	{
		$mutex = $this->newMutex();
		$mutex->setRetryDelay(10);
		$mutex->attempts = array_fill(0, 1000, false);
		$start = microtime(true);
		$this->assertFalse($mutex->acquire('a', 0.1));
		$elapsed = microtime(true) - $start;
		$this->assertGreaterThanOrEqual(0.09, $elapsed);
		$this->assertLessThan(2, $elapsed);
		$this->assertGreaterThan(1, count($mutex->acquireCalls));
	}

	public function testHeldNameDoesNotReenter(): void
	{
		$mutex = $this->newMutex();
		$this->assertTrue($mutex->acquire('a'));
		$this->assertFalse($mutex->acquire('a'));
		$this->assertFalse($mutex->acquire('a', 0.02));
		$this->assertSame(['a'], $mutex->acquireCalls);
	}

	public function testNamesAreIndependent(): void
	{
		$mutex = $this->newMutex();
		$this->assertTrue($mutex->acquire('a'));
		$this->assertTrue($mutex->acquire('b'));
		$this->assertTrue($mutex->acquire('7'));
		$this->assertSame(['a', 'b', '7'], $mutex->getAcquiredLocks());
	}

	public function testReleaseAll(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('a');
		$mutex->acquire('b');
		$mutex->releaseAll();
		$this->assertSame([], $mutex->getAcquiredLocks());
		$this->assertSame(['a', 'b'], $mutex->releaseCalls);
	}

	public function testSynchronizeRunsAndReleases(): void
	{
		$mutex = $this->newMutex();
		$result = $mutex->synchronize('a', function ($m) use ($mutex) {
			$this->assertSame($mutex, $m);
			$this->assertTrue($mutex->isAcquired('a'));
			return 42;
		});
		$this->assertSame(42, $result);
		$this->assertFalse($mutex->isAcquired('a'));
		$this->assertSame(['a'], $mutex->releaseCalls);
	}

	public function testSynchronizeReleasesOnException(): void
	{
		$mutex = $this->newMutex();
		try {
			$mutex->synchronize('a', function () {
				throw new \RuntimeException('boom');
			});
			$this->fail('The exception did not propagate.');
		} catch (\RuntimeException $e) {
			$this->assertSame('boom', $e->getMessage());
		}
		$this->assertFalse($mutex->isAcquired('a'));
		$this->assertSame(['a'], $mutex->releaseCalls);
	}

	public function testSynchronizeThrowsWhenNotAcquired(): void
	{
		$mutex = $this->newMutex();
		$mutex->attempts = [false];
		$ran = false;
		try {
			$mutex->synchronize('a', function () use (&$ran) {
				$ran = true;
			});
			$this->fail('TMutexException was not thrown.');
		} catch (TMutexException $e) {
			$this->assertStringContainsString("'a'", $e->getMessage());
		}
		$this->assertFalse($ran);
	}

	public function testRetryDelay(): void
	{
		$mutex = new TMutexTestScriptedMutex();
		$this->assertSame(TMutex::DEFAULT_RETRY_DELAY, $mutex->getRetryDelay());
		$this->assertSame($mutex, $mutex->setRetryDelay('25'));
		$this->assertSame(25, $mutex->getRetryDelay());
		$this->expectException(TInvalidDataValueException::class);
		$mutex->setRetryDelay(0);
	}

	public function testKeyPrefix(): void
	{
		$mutex = $this->newMutex();
		$this->assertSame('', $mutex->getKeyPrefix());
		$this->assertSame('a', $mutex->pubGetLockKey('a'));
		$mutex->setKeyPrefix('app1:');
		$this->assertSame('app1:', $mutex->getKeyPrefix());
		$this->assertSame('app1:a', $mutex->pubGetLockKey('a'));
	}

	public function testAutoReleaseOnDestruct(): void
	{
		$mutex = $this->newMutex();
		$this->assertTrue($mutex->getAutoRelease());
		$mutex->acquire('a');
		$releases = &$mutex->releaseCalls;
		unset($mutex);
		$this->assertSame(['a'], $releases);
	}

	public function testAutoReleaseContinuesPastThrowingRelease(): void
	{
		$mutex = new TMutexTestThrowingMutex();
		$mutex->acquire('a');
		$mutex->acquire('b');
		$releases = &$mutex->releaseCalls;
		unset($mutex);
		$this->assertSame(['a', 'b'], $releases);
	}

	public function testNoAutoReleaseOnDestruct(): void
	{
		$mutex = $this->newMutex();
		$mutex->setAutoRelease('false');
		$this->assertFalse($mutex->getAutoRelease());
		$mutex->acquire('a');
		$releases = &$mutex->releaseCalls;
		unset($mutex);
		$this->assertSame([], $releases);
	}

	public function testForkedChildForgetsWithoutRelease(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('a');
		$mutex->acquire('b');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertSame([], $mutex->getAcquiredLocks());
		$this->assertSame(['a', 'b'], $mutex->forgotten);
		$this->assertSame([], $mutex->releaseCalls);
	}

	public function testForkingParentKeepsLocks(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('a');
		$mutex->fxRestoreAfterFork(null, ['pid' => 1234]);
		$mutex->fxRestoreAfterFork(null, ['pid' => -1]);
		$this->assertSame(['a'], $mutex->getAcquiredLocks());
		$this->assertSame([], $mutex->forgotten);
	}
}
