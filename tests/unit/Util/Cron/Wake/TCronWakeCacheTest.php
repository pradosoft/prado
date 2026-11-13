<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\Util\Cron\Wake\TCronWakeCache;

class TCronWakeCacheTest extends \PHPUnit\Framework\TestCase
{
	private TMemoryCache $cache;

	protected function setUp(): void
	{
		$this->cache = new TMemoryCache();
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	public function testCacheResolution()
	{
		$t = new TCronWakeCache($this->cache);
		self::assertSame($this->cache, $t->getCache());
		self::assertSame(TCronWakeCache::DEFAULT_CACHE_KEY, $t->getCacheKey());
		self::assertSame($t, $t->setCacheKey('mykey'));
		self::assertSame('mykey', $t->getCacheKey());

		$app = Prado::getApplication();
		$app->setModule('cronWakeTestCache', $this->cache);
		$t = (new TCronWakeCache())->setCacheID('cronWakeTestCache');
		self::assertSame('cronWakeTestCache', $t->getCacheID());
		self::assertSame($this->cache, $t->getCache());

		$t = (new TCronWakeCache())->setCacheID('cronWakeNoSuchModule');
		self::expectException(TConfigurationException::class);
		$t->getCache();
	}

	public function testSignalWritesTheStampAndWaitDetectsIt()
	{
		$t = (new TCronWakeCache($this->cache))->setPollInterval(0.02);
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.05, $now), 'no signal: the wait times out');

		TAsync::getLoop()->delay(0.03, static fn () => $t->signal(microtime(true)));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 2.0, $start), 'a signal during the wait wakes it');
		self::assertLessThan(1.0, microtime(true) - $start);
		self::assertNotFalse($this->cache->get(TCronWakeCache::DEFAULT_CACHE_KEY));
	}

	public function testWatchCallsBackOnEachSignal()
	{
		$t = (new TCronWakeCache($this->cache))->setPollInterval(0.02);
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
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes);
	}

	public function testInitAndTheApplicationCache()
	{
		$t = new TCronWakeCache();
		$t->init(null);
		self::assertSame(TCronWakeCache::DEFAULT_CACHE_KEY, $t->getCacheKey());
		self::assertSame(Prado::getApplication()->getCache(), $t->getCache(), 'without a CacheID the application cache');
		self::assertSame($t, $t->setCache($this->cache));
		self::assertSame($this->cache, $t->getCache());
	}
}
