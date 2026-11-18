<?php

/**
 * TDbCacheDriverTestTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\Harness\Caching\TTestDbCache;
use Prado\Test\Unit\Harness\TTestApplication;

/**
 * TDbCacheDriverTestTrait runs {@see \Prado\Caching\TDbCache} against a live database
 * server: it creates its table, stores, replaces, adds, deletes, and expires values, and
 * keeps binary and large values intact. A test class in a `DbSpecific` suite uses it and
 * names its driver through {@see PradoUnitDataConnectionTrait::getDbDriver()}. The table
 * is named for the process and dropped after each test.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TDbCacheDriverTestTrait
{
	use PradoUnitDataConnectionTrait;

	private ?TTestApplication $_cacheApp = null;
	private ?TTestDbCache $_driverCache = null;
	private string $_cacheTable = '';

	protected function getTestTables(): array
	{
		return [];
	}

	protected function setUp(): void
	{
		if ($this->setUpConnection() === null) {
			$this->markTestSkipped('No ' . $this->getDbDriver() . ' connection.');
		}
		$this->_cacheApp = new TTestApplication();
		$this->_cacheTable = 'pradocache_' . getmypid();
		$this->dropCacheTable();
	}

	protected function tearDown(): void
	{
		if ($this->_driverCache !== null) {
			$this->_driverCache->deactivateDbConnection(true);
			$this->_driverCache = null;
			$this->dropCacheTable();
		}
		$this->_cacheApp?->restoreApplication();
		$this->_cacheApp = null;
	}

	/**
	 * Drops the cache table when it exists, on a connection of its own.
	 */
	private function dropCacheTable(): void
	{
		$conn = $this->setUpConnection();
		try {
			$conn->createCommand('DROP TABLE ' . $this->_cacheTable)->execute();
		} catch (TDbException $e) {
		}
		$conn->setActive(false);
	}

	/**
	 * @return TTestDbCache an initialized cache on this driver's server, in this test's table.
	 */
	private function driverCache(): TTestDbCache
	{
		$conn = $this->setUpConnection();
		$cache = new class ($conn) extends TTestDbCache {
			public function __construct(private TDbConnection $_testConnection)
			{
				parent::__construct();
			}

			protected function getCustomDbConnection(): ?TDbConnection
			{
				return $this->_testConnection;
			}
		};
		$cache->setPrimaryCache(false);
		$cache->setCacheTableName($this->_cacheTable);
		$cache->init(null);
		return $this->_driverCache = $cache;
	}

	private function countRows(TTestDbCache $cache): int
	{
		return (int) $cache->getDbConnection()->createCommand('SELECT COUNT(*) FROM ' . $this->_cacheTable)->queryScalar();
	}

	public function testSetCreatesTheTableAndReplacesAValue(): void
	{
		$cache = $this->driverCache();
		$this->assertTrue($cache->set('key', 'first'));
		$this->assertTrue($cache->set('key', 'second', 60));
		$this->assertSame('second', $cache->get('key'));
		$this->assertSame(1, $this->countRows($cache));
	}

	public function testAddStoresOnlyAnAbsentKey(): void
	{
		$cache = $this->driverCache();
		$this->assertTrue($cache->add('key', 'first'));
		$this->assertFalse($cache->add('key', 'second'));
		$this->assertSame('first', $cache->get('key'));
	}

	public function testDeleteAndFlush(): void
	{
		$cache = $this->driverCache();
		$cache->set('a', 'one');
		$cache->set('b', 'two');
		$this->assertTrue($cache->delete('a'));
		$this->assertFalse($cache->get('a'));
		$this->assertSame('two', $cache->get('b'));
		$this->assertTrue($cache->flush());
		$this->assertSame(0, $this->countRows($cache));
	}

	public function testBinaryValuesRoundTrip(): void
	{
		$cache = $this->driverCache();
		$binary = implode('', array_map('chr', range(0, 255)));
		$this->assertTrue($cache->set('binary', $binary));
		$this->assertSame($binary, $cache->get('binary'));
		$this->assertTrue($cache->add('added', $binary));
		$this->assertSame($binary, $cache->get('added'));
	}

	public function testLargeValuesRoundTrip(): void
	{
		$cache = $this->driverCache();
		$large = str_repeat('0123456789abcdef', 8_192);
		$this->assertTrue($cache->set('large', $large));
		$read = $cache->get('large');
		if ($read === false && $this->getDbDriver() === TDbDriver::DRIVER_IBM) {
			$this->markTestSkipped('pdo_ibm fetches a BLOB of about 64 KiB or more as NULL, which TDbCache reads as a miss.');
		}
		$this->assertSame($large, $read);
	}

	public function testAnExpiredValueIsAMissAndIsFlushed(): void
	{
		$cache = $this->driverCache();
		$cache->fakeNow = 1_000_000;
		$cache->set('expiring', 'v', 10);
		$cache->set('lasting', 'v');
		$cache->fakeNow = 1_000_011;
		$this->assertFalse($cache->get('expiring'));
		$cache->flushCacheExpired(true);
		$this->assertSame(1, $this->countRows($cache));
	}

	public function testAMissingTableIsCreatedAgain(): void
	{
		$cache = $this->driverCache();
		$this->assertTrue($cache->set('key', 'first'));
		$cache->getDbConnection()->createCommand('DROP TABLE ' . $this->_cacheTable)->execute();
		$this->assertTrue($cache->set('key', 'second'));
		$this->assertSame('second', $cache->get('key'));
	}

	public function testSetJoinsTheCurrentTransaction(): void
	{
		$cache = $this->driverCache();
		$cache->set('key', 'first');
		$transaction = $cache->getDbConnection()->beginTransaction();
		$this->assertTrue($cache->set('key', 'second'));
		$this->assertSame($transaction, $cache->getDbConnection()->getCurrentTransaction());
		$transaction->rollback();
		$this->assertSame('first', $cache->get('key'));
	}
}
