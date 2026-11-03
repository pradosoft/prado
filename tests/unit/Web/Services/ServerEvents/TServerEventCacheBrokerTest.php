<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Caching\ICache;
use Prado\Caching\TDbCache;
use Prado\Caching\TFileCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Mutex\TCacheMutex;
use Prado\Util\Mutex\TDbMutex;
use Prado\Util\Mutex\TFileMutex;
use Prado\Util\Mutex\TMemoryMutex;
use Prado\Util\Mutex\TMutexException;
use Prado\Web\Services\ServerEvents\TServerEventCacheBroker;

/**
 * A cache broker over any ICache.
 */
class TServerEventCacheBrokerTestBroker extends TServerEventCacheBroker
{
	public function __construct(public ICache $eventCache)
	{
		parent::__construct();
	}

	protected function getCache(): ICache
	{
		return $this->eventCache;
	}
}

/**
 * An in-process mutex that records its lock calls and whether the channel entry was written while held.
 */
class TServerEventCacheBrokerTestMutex extends TMemoryMutex
{
	public array $calls = [];

	protected function acquireLock(string $name): bool
	{
		$this->calls[] = 'acquire ' . $name;
		return parent::acquireLock($name);
	}

	protected function releaseLock(string $name): bool
	{
		$this->calls[] = 'release ' . $name;
		return parent::releaseLock($name);
	}
}

class TServerEventCacheBrokerTest extends \PHPUnit\Framework\TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-serverevent-' . bin2hex(random_bytes(6));
		mkdir($this->dir);
	}

	protected function tearDown(): void
	{
		$this->removeDirectory($this->dir);
	}

	public function testDefaultMutexMatchesTheCache(): void
	{
		$fileCache = new TFileCache($this->dir . DIRECTORY_SEPARATOR . 'cache');
		$mutex = (new TServerEventCacheBrokerTestBroker($fileCache))->getMutex();
		self::assertInstanceOf(TFileMutex::class, $mutex);
		self::assertSame($fileCache->getDirectory() . DIRECTORY_SEPARATOR . TServerEventCacheBroker::FILE_MUTEX_FOLDER, $mutex->getMutexPath());
		self::assertSame(TServerEventCacheBroker::MUTEX_RETRY_DELAY, $mutex->getRetryDelay());

		$dbCache = $this->newDbCache();
		$mutex = (new TServerEventCacheBrokerTestBroker($dbCache))->getMutex();
		self::assertInstanceOf(TDbMutex::class, $mutex);
		self::assertSame($dbCache->getDbConnection(), $mutex->getDbConnection());

		$arrayCache = new TServerEventArrayCache();
		$broker = new TServerEventCacheBrokerTestBroker($arrayCache);
		$mutex = $broker->getMutex();
		self::assertInstanceOf(TCacheMutex::class, $mutex);
		self::assertSame($arrayCache, $mutex->getCache());
		self::assertSame($mutex, $broker->getMutex(), 'The mutex is resolved once.');
	}

	public function testMutexIdNamesAMutexModule(): void
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		try {
			$mutex = new TMemoryMutex();
			$app->setModule('serverevent-test-mutex', $mutex);
			$broker = new TServerEventCacheBrokerTestBroker(new TServerEventArrayCache());
			self::assertSame('', $broker->getMutexID());
			$broker->setMutexID('serverevent-test-mutex');
			self::assertSame('serverevent-test-mutex', $broker->getMutexID());
			self::assertSame($mutex, $broker->getMutex());

			$broker->setMutexID('no-such-mutex');
			try {
				$broker->getMutex();
				self::fail('Expected TConfigurationException for an unknown MutexID');
			} catch (TConfigurationException $e) {
				self::assertSame('servereventcachebroker_mutexid_invalid', $e->getErrorCode());
				self::assertStringContainsString("'no-such-mutex'", $e->getMessage());
			}
		} finally {
			PradoUnit::restore($app, $snapshot);
		}
	}

	public function testPublishHoldsTheChannelLock(): void
	{
		$cache = new TServerEventArrayCache();
		$broker = new TServerEventCacheBrokerTestBroker($cache);
		$mutex = new TServerEventCacheBrokerTestMutex();
		PradoUnit::setProp($broker, '_mutex', $mutex);

		$event = $broker->publish('orders', 'changed', 1);

		$key = TServerEventCacheBroker::CACHE_KEY_PREFIX . 'orders';
		self::assertSame(['acquire ' . $key, 'release ' . $key], $mutex->calls);
		self::assertFalse($mutex->isAcquired($key));
		self::assertSame($event->getID(), $broker->getLastEventId('orders'));
	}

	public function testPublishThrowsAndStoresNothingWhenTheLockIsHeld(): void
	{
		$broker = new TServerEventCacheBrokerTestBroker(new TServerEventArrayCache());
		PradoUnit::setProp($broker, '_mutex', new TMemoryMutex());
		$broker->setLockTimeout(0);
		$holder = new TMemoryMutex();
		$key = TServerEventCacheBroker::CACHE_KEY_PREFIX . 'orders';
		self::assertTrue($holder->acquire($key));
		try {
			$broker->publish('orders', 'changed', 1);
			self::fail('Expected TMutexException while another mutex holds the channel');
		} catch (TMutexException $e) {
			self::assertSame('mutex_acquire_failed', $e->getErrorCode());
		} finally {
			$holder->release($key);
		}
		self::assertSame('', $broker->getLastEventId('orders'));
	}

	public function testLockTimeout(): void
	{
		$broker = new TServerEventCacheBroker();
		self::assertSame(TServerEventCacheBroker::DEFAULT_LOCK_TIMEOUT, $broker->getLockTimeout());
		$broker->setLockTimeout('0.25');
		self::assertSame(0.25, $broker->getLockTimeout());
		$broker->setLockTimeout(0);
		self::assertSame(0.0, $broker->getLockTimeout());
		$this->expectException(TInvalidDataValueException::class);
		$broker->setLockTimeout(-1);
	}

	/**
	 * Processes publishing on one channel at once keep every event, through the default
	 * mutex of a TFileCache and of a TDbCache on sqlite.
	 * @dataProvider sharedCacheProvider
	 */
	public function testConcurrentPublishesKeepEveryEvent(string $type): void
	{
		if (!function_exists('proc_open')) {
			$this->markTestSkipped('proc_open is not available.');
		}
		$workers = 4;
		$count = 25;
		mkdir($this->dir . DIRECTORY_SEPARATOR . 'runtime');
		$location = $type === 'file' ? $this->dir . DIRECTORY_SEPARATOR . 'cache' : $this->dir . DIRECTORY_SEPARATOR . 'cache.db';
		$start = (string) (microtime(true) + 1.0);
		$processes = [];
		for ($worker = 1; $worker <= $workers; $worker++) {
			$command = [PHP_BINARY, __DIR__ . DIRECTORY_SEPARATOR . 'concurrent-publish.php', Prado::getFrameworkPath() . '/../vendor/autoload.php', $this->dir, $type, $location, (string) $count, 'w' . $worker, $start];
			$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
			$processes[] = [$process, $pipes];
		}
		foreach ($processes as [$process, $pipes]) {
			$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
			self::assertSame('ok', trim($output));
		}

		$cache = $type === 'file' ? new TFileCache($location) : $this->newDbCache($location);
		$reader = new TServerEventCacheBrokerTestBroker($cache);
		$reader->setBufferSize(10000);
		$events = $reader->receive('race', '', 0.0);

		self::assertCount($workers * $count, $events, 'No publish lost an event.');
		$data = array_map(fn ($event) => $event->getData(), $events);
		self::assertCount($workers * $count, array_unique($data));
		$ids = array_map(fn ($event) => $event->getID(), $events);
		self::assertCount($workers * $count, array_unique($ids));
		for ($worker = 1; $worker <= $workers; $worker++) {
			$own = array_values(array_filter($data, fn ($value) => str_starts_with($value, 'w' . $worker . ':')));
			self::assertSame(array_map(fn ($i) => 'w' . $worker . ':' . $i, range(0, $count - 1)), $own, 'A worker\'s events keep their order.');
		}
	}

	public static function sharedCacheProvider(): array
	{
		return ['TFileCache' => ['file'], 'TDbCache' => ['db']];
	}

	private function newDbCache(?string $file = null): TDbCache
	{
		$cache = new TDbCache();
		$cache->setConnectionString('sqlite:' . ($file ?? $this->dir . DIRECTORY_SEPARATOR . 'cache.db'));
		return $cache;
	}

	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
			$entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
		}
		rmdir($dir);
	}
}
