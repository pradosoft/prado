<?php

/**
 * TMemoryMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\IO\Async\TAsync;
use Prado\Util\Mutex\TMemoryMutex;

/**
 * Unit tests for {@see TMemoryMutex}: exclusion between the mutexes and fibers of
 * one process.
 */
class TMemoryMutexTest extends \PHPUnit\Framework\TestCase
{
	private array $mutexes = [];

	protected function tearDown(): void
	{
		foreach ($this->mutexes as $mutex) {
			$mutex->releaseAll();
		}
		$this->mutexes = [];
	}

	private function newMutex(): TMemoryMutex
	{
		$mutex = new TMemoryMutex();
		$mutex->setRetryDelay(1);
		return $this->mutexes[] = $mutex;
	}

	public function testMutexesExcludeEachOther(): void
	{
		$a = $this->newMutex();
		$b = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
	}

	public function testKeyPrefixSeparatesNames(): void
	{
		$a = $this->newMutex()->setKeyPrefix('app1:');
		$b = $this->newMutex()->setKeyPrefix('app2:');
		$this->assertTrue($a->acquire('job'));
		$this->assertTrue($b->acquire('job'));
	}

	public function testDestructReleases(): void
	{
		$a = new TMemoryMutex();
		$this->assertTrue($a->acquire('job'));
		unset($a);
		$this->assertTrue($this->newMutex()->acquire('job'));
	}

	public function testForkedChildFreesParentNames(): void
	{
		$a = $this->newMutex();
		$a->acquire('job');
		$a->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($a->isAcquired('job'));
		$this->assertTrue($this->newMutex()->acquire('job'));
	}

	public function testWaitingFiberAcquiresAfterRelease(): void
	{
		$a = $this->newMutex();
		$b = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$order = [];
		$waiter = TAsync::run(function () use ($b, &$order) {
			$order[] = 'wait';
			$acquired = $b->acquire('job', 2);
			$order[] = 'acquired';
			return $acquired;
		});
		$holder = TAsync::run(function () use ($a, &$order) {
			TAsync::sleep(0.02);
			$order[] = 'release';
			$a->release('job');
		});
		TAsync::await($holder);
		$this->assertTrue(TAsync::await($waiter));
		$this->assertSame(['wait', 'release', 'acquired'], $order);
		$this->assertTrue($b->isAcquired('job'));
	}

	public function testFibersSharingOneMutexTakeTurns(): void
	{
		$mutex = $this->newMutex();
		$log = [];
		$futures = [];
		foreach (['x', 'y', 'z'] as $id) {
			$futures[] = TAsync::run(function () use ($mutex, $id, &$log) {
				$mutex->synchronize('shared', function () use ($id, &$log) {
					$log[] = "$id+";
					TAsync::sleep(0.005);
					$log[] = "$id-";
				}, 2);
			});
		}
		foreach ($futures as $future) {
			TAsync::await($future);
		}
		$this->assertCount(6, $log);
		for ($i = 0; $i < 6; $i += 2) {
			$this->assertSame(substr($log[$i], 0, 1), substr($log[$i + 1], 0, 1));
			$this->assertStringEndsWith('+', $log[$i]);
			$this->assertStringEndsWith('-', $log[$i + 1]);
		}
	}
}
