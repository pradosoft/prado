<?php

/**
 * TCacheMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Mutex\TCacheMutex;

/**
 * Unit tests for {@see TCacheMutex} over the {@see TTestArrayCache} harness.
 */
class TCacheMutexTest extends \PHPUnit\Framework\TestCase
{
	private TTestArrayCache $cache;

	protected function setUp(): void
	{
		$this->cache = new TTestArrayCache();
	}

	private function newMutex(): TCacheMutex
	{
		$mutex = new TCacheMutex();
		$mutex->setCache($this->cache);
		$mutex->setRetryDelay(1);
		return $mutex;
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
		$b->releaseAll();
		$this->assertSame([], $this->cache->values);
	}

	public function testEntryHoldsTokenWithExpire(): void
	{
		$mutex = $this->newMutex();
		$mutex->setExpire(90);
		$mutex->setKeyPrefix('app:');
		$this->assertSame('mutex:app:job', $mutex->getCacheKey('job'));
		$mutex->acquire('job');
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $this->cache->values['mutex:app:job']);
		$this->assertSame(90, $this->cache->expires['mutex:app:job']);
		$mutex->release('job');
	}

	public function testReleaseLeavesAnotherHoldersEntry(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('job');
		$this->cache->values['mutex:job'] = 'next-holder';
		$this->assertFalse($mutex->release('job'));
		$this->assertSame('next-holder', $this->cache->values['mutex:job']);
	}

	public function testReleaseOfExpiredLeaseReturnsFalse(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('job');
		unset($this->cache->values['mutex:job']);
		$this->assertFalse($mutex->release('job'));
		$this->assertFalse($mutex->isAcquired('job'));
	}

	public function testForkedChildKeepsParentEntry(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('job');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertArrayHasKey('mutex:job', $this->cache->values);
		$this->assertFalse($mutex->isAcquired('job'));
	}

	public function testExpire(): void
	{
		$mutex = new TCacheMutex();
		$this->assertSame(TCacheMutex::DEFAULT_EXPIRE, $mutex->getExpire());
		$mutex->setExpire('5');
		$this->assertSame(5, $mutex->getExpire());
		$this->expectException(TInvalidDataValueException::class);
		$mutex->setExpire(0);
	}

	public function testCacheModuleIDResolvesModule(): void
	{
		$app = Prado::getApplication();
		$cache = new TMemoryCache();
		$cache->setPrimaryCache(false);
		$app->setModule('mutexTestCache', $cache);
		try {
			$mutex = new TCacheMutex();
			$mutex->setCacheModuleID('mutexTestCache');
			$this->assertSame($cache, $mutex->getCache());
			$this->assertTrue($mutex->acquire('job'));
			$this->assertFalse((new TCacheMutex())->setCache($cache)->acquire('job'));
			$this->assertTrue($mutex->release('job'));
		} finally {
			$modules = PradoUnit::getProp($app, '_modules');
			unset($modules['mutexTestCache']);
			PradoUnit::setProp($app, '_modules', $modules);
		}
	}

	public function testCacheModuleIDOfNonCacheThrows(): void
	{
		$mutex = new TCacheMutex();
		$mutex->setCacheModuleID('missingMutexCache');
		$this->expectException(TConfigurationException::class);
		$mutex->getCache();
	}
}
