<?php

/**
 * TSemaphoreMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\Mutex\TSemaphoreMutex;

/**
 * Unit tests for {@see TSemaphoreMutex}.  Exclusion is exercised only when the
 * `sysvsem` extension is loaded (otherwise skipped).
 */
class TSemaphoreMutexTest extends \PHPUnit\Framework\TestCase
{
	private array $mutexes = [];

	protected function tearDown(): void
	{
		foreach ($this->mutexes as $mutex) {
			$mutex->releaseAll();
		}
		$this->mutexes = [];
	}

	private function newMutex(): TSemaphoreMutex
	{
		if (!TSemaphoreMutex::getIsAvailable()) {
			$this->markTestSkipped('The sysvsem extension is not available.');
		}
		$mutex = new TSemaphoreMutex();
		$mutex->setKeyPrefix('prado-unit-' . getmypid() . ':');
		$mutex->setRetryDelay(1);
		$mutex->init(null);
		return $this->mutexes[] = $mutex;
	}

	public function testIsAvailableReturnsBool(): void
	{
		$this->assertIsBool(TSemaphoreMutex::getIsAvailable());
	}

	public function testInitThrowsWhenExtensionUnavailable(): void
	{
		if (TSemaphoreMutex::getIsAvailable()) {
			$this->markTestSkipped('sysvsem extension present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		(new TSemaphoreMutex())->init(null);
	}

	public function testPermissions(): void
	{
		$mutex = new TSemaphoreMutex();
		$this->assertSame(TSemaphoreMutex::DEFAULT_PERMISSIONS, $mutex->getPermissions());
		$mutex->setPermissions('0660');
		$this->assertSame(0o660, $mutex->getPermissions());
		$mutex->setPermissions(0o600);
		$this->assertSame(0o600, $mutex->getPermissions());
	}

	public function testSemaphoreKeyIsPositive31Bit(): void
	{
		$mutex = new TSemaphoreMutex();
		foreach (['a', 'job', str_repeat('x', 500), ''] as $name) {
			$key = $mutex->getSemaphoreKey($name);
			$this->assertGreaterThanOrEqual(1, $key);
			$this->assertLessThanOrEqual(0x7FFFFFFF, $key);
		}
		$this->assertSame($mutex->getSemaphoreKey('job'), $mutex->getSemaphoreKey('job'));
		$this->assertNotSame($mutex->getSemaphoreKey('job'), $mutex->getSemaphoreKey('other'));
		$key = $mutex->getSemaphoreKey('job');
		$mutex->setKeyPrefix('app:');
		$this->assertNotSame($key, $mutex->getSemaphoreKey('job'));
	}

	public function testMutexesExcludeEachOther(): void
	{
		$a = $this->newMutex();
		$b = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.02));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$this->assertTrue($b->release('job'));
	}

	public function testForkedChildDropsSemaphore(): void
	{
		$a = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$a->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($a->isAcquired('job'));
		$this->assertFalse($this->newMutex()->acquire('job'));
	}
}
