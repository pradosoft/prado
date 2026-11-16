<?php

/**
 * TMemCacheSeamTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TMemCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Caching\TTestMemCache;
use Prado\Xml\TXmlDocument;

/**
 * A Memcached stand-in that records calls and stores values in an array.
 */
class TMemCacheSeamTestMemcached
{
	public array $servers = [];
	public array $calls = [];
	public array $data = [];
	public array $options = [];
	public bool $addServerResult = true;

	public function getServerList(): array
	{
		return $this->servers;
	}

	public function addServer($host, $port, $weight = 0): bool
	{
		$this->calls[] = ['addServer', $host, $port, $weight];
		if ($this->addServerResult) {
			$this->servers[] = ['host' => $host, 'port' => $port];
		}
		return $this->addServerResult;
	}

	public function get($key)
	{
		return $this->data[$key] ?? false;
	}

	public function set($key, $value, $expire = 0): bool
	{
		$this->calls[] = ['set', $key, $expire];
		$this->data[$key] = $value;
		return true;
	}

	public function add($key, $value, $expire = 0): bool
	{
		$this->calls[] = ['add', $key, $expire];
		if (array_key_exists($key, $this->data)) {
			return false;
		}
		$this->data[$key] = $value;
		return true;
	}

	public function delete($key): bool
	{
		$found = array_key_exists($key, $this->data);
		unset($this->data[$key]);
		return $found;
	}

	public function flush(): bool
	{
		$this->data = [];
		return true;
	}

	public function setOptions(array $options): bool
	{
		$this->options = $options;
		return true;
	}
}

/**
 * TMemCache over {@see TMemCacheSeamTestMemcached}, with the extension check switchable.
 */
class TMemCacheSeamTestCache extends TTestMemCache
{
	public static bool $available = true;
	public ?TMemCacheSeamTestMemcached $fake = null;
	public string|null|false $createdWith = false;

	public static function getIsAvailable(): bool
	{
		return static::$available;
	}

	protected function newMemcached($persistentId): object
	{
		$this->createdWith = $persistentId;
		return $this->fake ??= new TMemCacheSeamTestMemcached();
	}
}

/**
 * Tests TMemCache through its newMemcached() seam, so no memcached extension or server is needed.
 */
class TMemCacheSeamTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TMemCacheSeamTestCache::$available = true;
	}

	private function newCache(): TMemCacheSeamTestCache
	{
		$cache = new TMemCacheSeamTestCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	private function xmlConfig(string $servers): TXmlDocument
	{
		$doc = new TXmlDocument();
		$doc->loadFromString('<module>' . $servers . '</module>');
		return $doc;
	}

	public function testIsAvailableFollowsTheExtension(): void
	{
		$this->assertSame(extension_loaded('memcached'), TMemCache::getIsAvailable());
	}

	public function testInitThrowsWhenUnavailable(): void
	{
		TMemCacheSeamTestCache::$available = false;
		$cache = $this->newCache();
		try {
			$cache->init(null);
			$this->fail('init() did not throw without the memcached extension.');
		} catch (TConfigurationException $e) {
			$this->assertSame('memcached_extension_required', $e->getErrorCode());
		}
		$this->assertNull($cache->pubGetCacheDirect());
	}

	public function testInitAddsTheHostAndPort(): void
	{
		$cache = $this->newCache();
		$cache->setHost('cachehost');
		$cache->setPort('11300');
		$cache->init(null);

		$this->assertNull($cache->createdWith);
		$this->assertSame([['addServer', 'cachehost', 11300, 0]], $cache->fake->calls);
		$this->assertTrue($cache->getIsInitialized());
		$this->assertSame('cachehost', $cache->getHost());
		$this->assertSame(11300, $cache->getPort());
	}

	public function testInitThrowsWhenTheHostIsRefused(): void
	{
		$cache = $this->newCache();
		$cache->fake = new TMemCacheSeamTestMemcached();
		$cache->fake->addServerResult = false;
		try {
			$cache->init(null);
			$this->fail('init() did not throw for a refused server.');
		} catch (TConfigurationException $e) {
			$this->assertSame('memcache_connection_failed', $e->getErrorCode());
		}
		$this->assertFalse($cache->getIsInitialized());
	}

	public function testInitAddsEachConfiguredServer(): void
	{
		$cache = $this->newCache();
		$cache->init(['servers' => [
			['Host' => 'one', 'Port' => '11211'],
			['Host' => 'two', 'Port' => 11212, 'Weight' => '3'],
		]]);
		$this->assertSame([
			['addServer', 'one', '11211', 1],
			['addServer', 'two', 11212, '3'],
		], $cache->fake->calls);
	}

	public function testInitThrowsWhenAConfiguredServerIsRefused(): void
	{
		$cache = $this->newCache();
		$cache->fake = new TMemCacheSeamTestMemcached();
		$cache->fake->addServerResult = false;
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('two:11212');
		$cache->init(['servers' => [['Host' => 'two', 'Port' => 11212]]]);
	}

	public function testInitIgnoresAnArrayWithoutServers(): void
	{
		$cache = $this->newCache();
		$cache->init(['servers' => 'not-a-list']);
		$this->assertSame([['addServer', 'localhost', 11211, 0]], $cache->fake->calls);
	}

	public function testPersistentInstanceWithServersIsNotGivenThemAgain(): void
	{
		$cache = $this->newCache();
		$cache->setPersistentID('shared');
		$cache->fake = new TMemCacheSeamTestMemcached();
		$cache->fake->servers = [['host' => 'localhost', 'port' => 11211]];
		$cache->init(null);

		$this->assertSame('shared', $cache->createdWith);
		$this->assertSame('shared', $cache->getPersistentID());
		$this->assertSame([], $cache->fake->calls);
	}

	public function testPersistentInstanceWithoutServersIsGivenThem(): void
	{
		$cache = $this->newCache();
		$cache->setPersistentID('fresh');
		$cache->init(null);
		$this->assertSame('fresh', $cache->createdWith);
		$this->assertSame([['addServer', 'localhost', 11211, 0]], $cache->fake->calls);
	}

	public function testPersistentInstanceWithoutServersIsGivenTheConfiguredServers(): void
	{
		$cache = $this->newCache();
		$cache->setPersistentID('fresh');
		$cache->init(['servers' => [['Host' => 'one', 'Port' => 11211]]]);
		$this->assertSame([['addServer', 'one', 11211, 1]], $cache->fake->calls);
	}

	/**
	 * @dataProvider persistentRefusalProvider
	 */
	public function testPersistentInstanceThrowsWhenAServerIsRefused(?array $config): void
	{
		$cache = $this->newCache();
		$cache->setPersistentID('fresh');
		$cache->fake = new TMemCacheSeamTestMemcached();
		$cache->fake->addServerResult = false;
		try {
			$cache->init($config);
			$this->fail('init() did not throw for a refused server.');
		} catch (TConfigurationException $e) {
			$this->assertSame('memcache_connection_failed', $e->getErrorCode());
		}
	}

	public static function persistentRefusalProvider(): array
	{
		return [
			'configured server' => [['servers' => [['Host' => 'one', 'Port' => 11211]]]],
			'Host and Port'     => [null],
		];
	}

	public function testXmlServersAreAdded(): void
	{
		$cache = $this->newCache();
		$cache->init($this->xmlConfig('<server Host="one" Port="11211" /><server Host="two" Port="11212" Weight="5" />'));
		$this->assertSame([
			['addServer', 'one', '11211', 1],
			['addServer', 'two', '11212', '5'],
		], $cache->fake->calls);
	}

	/**
	 * @dataProvider invalidServerProvider
	 */
	public function testInvalidXmlServerThrows(string $server, string $errorCode): void
	{
		$cache = $this->newCache();
		try {
			$cache->init($this->xmlConfig($server));
			$this->fail('init() accepted an invalid server.');
		} catch (TConfigurationException $e) {
			$this->assertSame($errorCode, $e->getErrorCode());
		}
	}

	public static function invalidServerProvider(): array
	{
		return [
			'no Host'        => ['<server Port="11211" />', 'memcache_serverhost_required'],
			'no Port'        => ['<server Host="one" />', 'memcache_serverport_required'],
			'Port not a number' => ['<server Host="one" Port="abc" />', 'memcache_serverport_invalid'],
			'Weight not a number' => ['<server Host="one" Port="11211" Weight="heavy" />', 'memcache_serverweight_invalid'],
		];
	}

	/**
	 * @dataProvider invalidArrayServerProvider
	 */
	public function testInvalidArrayServerThrows(array $server, string $errorCode): void
	{
		$cache = $this->newCache();
		try {
			$cache->init(['servers' => [$server]]);
			$this->fail('init() accepted an invalid server.');
		} catch (TConfigurationException $e) {
			$this->assertSame($errorCode, $e->getErrorCode());
		}
	}

	public static function invalidArrayServerProvider(): array
	{
		return [
			'no Host'        => [['Port' => 11211], 'memcache_serverhost_required'],
			'no Port'        => [['Host' => 'one'], 'memcache_serverport_required'],
			'Port not a number' => [['Host' => 'one', 'Port' => 'abc'], 'memcache_serverport_invalid'],
			'Weight not a number' => [['Host' => 'one', 'Port' => 11211, 'Weight' => 'heavy'], 'memcache_serverweight_invalid'],
		];
	}

	public function testValuesGoThroughTheMemcachedInstance(): void
	{
		$cache = $this->newCache();
		$cache->init(null);

		$this->assertTrue($cache->set('k', 'v', 30));
		$this->assertSame('v', $cache->get('k'));
		$this->assertFalse($cache->add('k', 'other'));
		$this->assertTrue($cache->add('fresh', 'value', 10));
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));
		$this->assertTrue($cache->flush());
		$this->assertSame([], $cache->fake->data);
		$this->assertSame(['set', 30], [$cache->fake->calls[1][0], $cache->fake->calls[1][2]]);
	}

	public function testSetOptionsReachesTheInstanceAfterInit(): void
	{
		$cache = $this->newCache();
		$cache->init(null);
		$cache->setOptions(['a' => 1]);
		$this->assertSame(['a' => 1], $cache->fake->options);
	}

	public function testSetOptionsBeforeInitThrows(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$this->newCache()->setOptions(['a' => 1]);
	}

	public function testSetUseMemcached(): void
	{
		$cache = $this->newCache();
		$cache->setUseMemcached(true);
		try {
			$cache->setUseMemcached(false);
			$this->fail('UseMemcached false was accepted.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('initialized_property_unchangeable', $e->getErrorCode());
		}
		$cache->init(null);
		$this->expectException(TInvalidOperationException::class);
		$cache->setUseMemcached(true);
	}

	/**
	 * @dataProvider frozenSetterProvider
	 */
	public function testConnectionPropertiesCannotChangeAfterInit(string $setter, mixed $value): void
	{
		$cache = $this->newCache();
		$cache->init(null);
		$this->expectException(TInvalidOperationException::class);
		$cache->$setter($value);
	}

	public static function frozenSetterProvider(): array
	{
		return [
			'Host'         => ['setHost', 'other'],
			'Port'         => ['setPort', 11212],
			'PersistentID' => ['setPersistentID', 'other'],
		];
	}
}
