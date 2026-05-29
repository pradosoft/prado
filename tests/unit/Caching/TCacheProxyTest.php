<?php

/**
 * TCacheProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICache;
use Prado\Caching\TCache;
use Prado\Caching\TCacheProxy;
use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\TApplication;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A minimal in-memory TCache implementation used as the backing store in tests.
 * Stores raw cache entries (as TCache serializes them) in a PHP array.
 */
class TCacheProxyBackend extends TCache
{
	/** @var array<string, mixed> raw storage keyed by the unique (prefixed) key */
	private array $_store = [];

	/** @var int count of flush() calls */
	public int $flushCalls = 0;

	/** @var string|null used to exercise __get/__set/__isset/__unset forwarding */
	private ?string $_customProp = null;

	public function getCustomProp(): ?string
	{
		return $this->_customProp;
	}

	public function setCustomProp(?string $value): void
	{
		$this->_customProp = $value;
	}

	public function customMethod(string $arg): string
	{
		return 'custom:' . $arg;
	}

	public function customMultiArgMethod(string $a, int $b): string
	{
		return $a . ':' . $b;
	}

	public function onTestEvent(\Prado\TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}

	protected function getValue($key)
	{
		return $this->_store[$key] ?? false;
	}

	protected function setValue($key, $value, $expire): bool
	{
		$this->_store[$key] = $value;
		return true;
	}

	protected function addValue($key, $value, $expire): bool
	{
		if (array_key_exists($key, $this->_store)) {
			return false;
		}
		$this->_store[$key] = $value;
		return true;
	}

	protected function deleteValue($key): bool
	{
		unset($this->_store[$key]);
		return true;
	}

	public function flush(): bool
	{
		$this->flushCalls++;
		$this->_store = [];
		return true;
	}
}

/**
 * A TModule that is NOT a TCache — used to test the invalid-type guard.
 */
class TCacheProxyNotACacheModule extends TModule
{
	public function init($config)
	{
		parent::init($config);
	}
}

/**
 * A TBehavior with a public on[A-Z]* event, used to verify that attachProxy()
 * discovers events exposed by behaviors attached to the backing cache.
 */
class TCacheProxyBehaviorWithEvent extends TBehavior
{
	public function onBehaviorEvent(\Prado\TEventParameter $param): void
	{
		$this->raiseEvent('OnBehaviorEvent', $this->getOwner(), $param);
	}
}

/**
 * Exposes protected internals for direct testing.
 */
class TCacheProxyAccessor extends TCacheProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetCacheDirect(): ?TCache
	{
		return $this->getCacheDirect();
	}

}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TCacheProxyTest class.
 *
 * Tests TCacheProxy: BackingCacheId property, lazy cache resolution, init() validation,
 * IModuleDependency, transparent ICache delegation, change logging, ArrayAccess,
 * serialization, and edge cases.
 *
 * @package Prado\Tests\Unit\Caching
 */
class TCacheProxyTest extends PHPUnit\Framework\TestCase
{
	private static string $mockAppPath;

	/** @var TApplication|null */
	private ?TApplication $app = null;

	/** @var TCacheProxyBackend */
	private TCacheProxyBackend $backend;

	/** @var TCacheProxyAccessor */
	private TCacheProxyAccessor $proxy;

	public static function setUpBeforeClass(): void
	{
		self::$mockAppPath = __DIR__ . '/mockapp';
	}

	protected function setUp(): void
	{
		$this->app = new TApplication(self::$mockAppPath);

		// Create and register a fully initialized backing cache.
		$this->backend = new TCacheProxyBackend();
		$this->backend->setPrimaryCache(false);
		$this->backend->init(null);
		$this->app->setModule('backingCache', $this->backend);

		// Build the proxy but do NOT call init() — tests that need it call it.
		$this->proxy = new TCacheProxyAccessor();
		$this->proxy->setPrimaryCache(false);
		$this->proxy->setBackingCacheId('backingCache');
	}

	protected function tearDown(): void
	{
		$this->backend->flush();
		$this->proxy->unlisten();
		$this->backend->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTCacheProxy(): void
	{
		$this->assertInstanceOf(TCacheProxy::class, $this->proxy);
	}

	public function testImplementsICache(): void
	{
		$this->assertInstanceOf(ICache::class, $this->proxy);
	}

	public function testImplementsIModuleDependency(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
	}

	public function testExtendsAbstractTCache(): void
	{
		$this->assertInstanceOf(TCache::class, $this->proxy);
	}

	// ── Default property values ──────────────────────────────────────────────────

	public function testDefaultBackingCacheIdIsEmptyString(): void
	{
		$fresh = new TCacheProxy();
		$this->assertSame('', $fresh->getBackingCacheId());
	}

	// ── getBackingCacheId / setBackingCacheId ──────────────────────────────────────────────────

	public function testSetGetBackingCacheId(): void
	{
		$proxy = new TCacheProxy();
		$proxy->setBackingCacheId('myCache');
		$this->assertSame('myCache', $proxy->getBackingCacheId());
	}

	public function testSetBackingCacheIdSameValueIsNoOp(): void
	{
		$this->proxy->setBackingCacheId('backingCache'); // same value — must not log
		$this->assertSame('backingCache', $this->proxy->getBackingCacheId());
	}

	public function testSetBackingCacheIdFromEmptyDoesNotLog(): void
	{
		$cat = 'prado.caching';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TCacheProxy();
		$proxy->setBackingCacheId('backingCache'); // first set from '' — no log

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingCacheIdChangeLogsWarning(): void
	{
		$cat = 'prado.caching';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingCacheId('otherCache'); // changes from 'backingCache'

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingCacheIdChangeLogMessageContainsBothIds(): void
	{
		$cat = 'prado.caching';
		$this->proxy->setBackingCacheId('replacementCache');

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingCache', $msg);
		$this->assertStringContainsString('replacementCache', $msg);
	}

	public function testSetBackingCacheIdInvalidatesResolvedReference(): void
	{
		// Force resolution of the proxy.
		$this->proxy->init(null);
		$first = $this->proxy->getCache();

		// Change the ID — the cached reference must be cleared.
		$secondBackend = new TCacheProxyBackend();
		$secondBackend->setPrimaryCache(false);
		$secondBackend->init(null);
		$this->app->setModule('secondCache', $secondBackend);

		$this->proxy->setBackingCacheId('secondCache');
		$second = $this->proxy->getCache();

		$this->assertNotSame($first, $second);
		$this->assertSame($secondBackend, $second);
		$secondBackend->unlisten();
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenBackingCacheIdEmpty(): void
	{
		$proxy = new TCacheProxy();
		$this->assertNull($proxy->getModuleDependencies());
	}

	public function testGetModuleDependenciesReturnsDependencyArrayWhenBackingCacheIdSet(): void
	{
		$deps = $this->proxy->getModuleDependencies();
		$this->assertIsArray($deps);
		$this->assertCount(1, $deps);
		$this->assertSame('backingCache', $deps[0]['id']);
		$this->assertTrue($deps[0]['required']);
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenBackingCacheIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertSame('backingCache', $this->proxy->getBackingCacheId());
	}

	public function testInitThrowsWhenBackingCacheIdIsEmpty(): void
	{
		$proxy = new TCacheProxy();
		$proxy->setPrimaryCache(false);

		$this->expectException(TConfigurationException::class);
		$proxy->init(null);
	}

	// ── getCache() ──────────────────────────────────────────────────────────────

	public function testGetCacheReturnsBackingCacheModule(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backend, $this->proxy->getCache());
	}

	public function testGetCacheCachesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getCache();
		$second = $this->proxy->getCache();
		$this->assertSame($first, $second);
	}

	public function testGetCacheThrowsWhenBackingCacheIdIsEmpty(): void
	{
		$proxy = new TCacheProxy();

		$this->expectException(TConfigurationException::class);
		$proxy->getCache();
	}

	public function testGetCacheThrowsWhenModuleNotFound(): void
	{
		$proxy = new TCacheProxyAccessor();
		$proxy->setPrimaryCache(false);
		$proxy->setBackingCacheId('nonExistentModule');

		$this->expectException(TConfigurationException::class);
		$proxy->getCache();
	}

	public function testGetCacheThrowsWhenModuleIsNotTCache(): void
	{
		$notACache = new TCacheProxyNotACacheModule();
		$this->app->setModule('notCache', $notACache);

		$proxy = new TCacheProxyAccessor();
		$proxy->setPrimaryCache(false);
		$proxy->setBackingCacheId('notCache');

		try {
			$proxy->getCache();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertInstanceOf(TConfigurationException::class, $e);
		} finally {
			$proxy->unlisten();
			$notACache->unlisten();
		}
	}

	// ── ICache delegation — get / set ────────────────────────────────────────────

	public function testSetAndGetDelegatesToBackingCache(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'hello');
		$this->assertSame('hello', $this->proxy->get('key'));
	}

	public function testGetReturnsFalseOnCacheMiss(): void
	{
		$this->proxy->init(null);
		$this->assertFalse($this->proxy->get('missing'));
	}

	public function testSetOverwritesExistingEntry(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'first');
		$this->proxy->set('key', 'second');
		$this->assertSame('second', $this->proxy->get('key'));
	}

	public function testSetVariousValueTypes(): void
	{
		$this->proxy->init(null);

		$this->proxy->set('int', 42);
		$this->assertSame(42, $this->proxy->get('int'));

		$this->proxy->set('arr', [1, 2, 3]);
		$this->assertSame([1, 2, 3], $this->proxy->get('arr'));

		$this->proxy->set('obj', new stdClass());
		$this->assertInstanceOf(stdClass::class, $this->proxy->get('obj'));
	}

	public function testSetEmptyValueWithZeroExpireDeletesEntry(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'present');
		$this->proxy->set('key', '', 0); // empty + no expire → delete
		$this->assertFalse($this->proxy->get('key'));
	}

	// ── ICache delegation — add ──────────────────────────────────────────────────

	public function testAddStoresWhenAbsent(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->add('newKey', 'value');
		$this->assertTrue($result);
		$this->assertSame('value', $this->proxy->get('newKey'));
	}

	public function testAddReturnsFalseWhenEntryExists(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('existing', 'original');
		$result = $this->proxy->add('existing', 'new');
		$this->assertFalse($result);
		$this->assertSame('original', $this->proxy->get('existing'));
	}

	public function testAddEmptyValueWithZeroExpireReturnsFalse(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->add('key', '', 0);
		$this->assertFalse($result);
	}

	// ── ICache delegation — delete ───────────────────────────────────────────────

	public function testDeleteRemovesExistingEntry(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'value');
		$this->proxy->delete('key');
		$this->assertFalse($this->proxy->get('key'));
	}

	public function testDeleteReturnsTrueWhenEntryAbsent(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->delete('neverStored');
		$this->assertTrue($result);
	}

	// ── ICache delegation — flush ────────────────────────────────────────────────

	public function testFlushDelegatesToBackingCache(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('a', 1);
		$this->proxy->set('b', 2);

		$flushBefore = $this->backend->flushCalls;
		$result = $this->proxy->flush();

		$this->assertTrue($result);
		$this->assertSame($flushBefore + 1, $this->backend->flushCalls);
		$this->assertFalse($this->proxy->get('a'));
		$this->assertFalse($this->proxy->get('b'));
	}

	// ── Transparency: proxy uses backing cache's key space ───────────────────────

	public function testProxyAndBackingCacheShareKeySpace(): void
	{
		$this->proxy->init(null);

		// Store via the proxy; retrieve via the backing cache directly.
		$this->proxy->set('sharedKey', 'proxyValue');
		$this->assertSame('proxyValue', $this->backend->get('sharedKey'));
	}

	public function testBackingCacheSetVisibleThroughProxy(): void
	{
		$this->proxy->init(null);

		// Store directly on the backing cache; read through the proxy.
		$this->backend->set('directKey', 'directValue');
		$this->assertSame('directValue', $this->proxy->get('directKey'));
	}

	// ── ArrayAccess delegation ───────────────────────────────────────────────────

	public function testOffsetExistsDelegatesToGet(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('present', 'yes');

		$this->assertTrue(isset($this->proxy['present']));
		$this->assertFalse(isset($this->proxy['absent']));
	}

	public function testOffsetGetDelegatesToGet(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'value');

		$this->assertSame('value', $this->proxy['key']);
	}

	public function testOffsetSetDelegatesToSet(): void
	{
		$this->proxy->init(null);
		$this->proxy['key'] = 'arrayValue';

		$this->assertSame('arrayValue', $this->proxy->get('key'));
	}

	public function testOffsetUnsetDelegatesToDelete(): void
	{
		$this->proxy->init(null);
		$this->proxy->set('key', 'value');
		unset($this->proxy['key']);

		$this->assertFalse($this->proxy->get('key'));
	}

	// ── Primary cache registration ───────────────────────────────────────────────

	public function testInitRegistersPrimaryCache(): void
	{
		$proxy = new TCacheProxyAccessor();
		$proxy->setBackingCacheId('backingCache');
		$proxy->setPrimaryCache(true);

		// The app has no primary cache yet — register the proxy as primary.
		$proxy->init(null);

		$this->assertSame($proxy, $this->app->getCache());
	}

	public function testInitWithPrimaryFalseDoesNotRegisterAsAppCache(): void
	{
		// Proxy has PrimaryCache=false; app cache should remain null unless set elsewhere.
		$this->proxy->init(null);
		$this->assertNull($this->app->getCache());
	}

	// ── KeyPrefix property (inherited from TCache) ───────────────────────────────

	public function testKeyPrefixGetSet(): void
	{
		$this->proxy->setKeyPrefix('myprefix');
		$this->assertSame('myprefix', $this->proxy->getKeyPrefix());
	}

	// ── Multiple set/get cycles ──────────────────────────────────────────────────

	public function testMultipleKeysStoredAndRetrievedIndependently(): void
	{
		$this->proxy->init(null);

		for ($i = 0; $i < 5; $i++) {
			$this->proxy->set("key{$i}", "val{$i}");
		}
		for ($i = 0; $i < 5; $i++) {
			$this->assertSame("val{$i}", $this->proxy->get("key{$i}"));
		}
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableAlwaysExcludesCacheReference(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		// _proxyBacking is declared in TComponentProxyTrait, but __CLASS__ in the
		// trait resolves to the using class (TCacheProxy), so the key uses that.
		$this->assertContains(
			"\0" . TCacheProxy::class . "\0_proxyBacking",
			$exprops
		);
	}

	public function testZappableExcludesBackingCacheIdWhenEmpty(): void
	{
		$proxy = new TCacheProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TCacheProxy::class . "\0_backingCacheId",
			$exprops
		);
	}

	public function testZappableKeepsBackingCacheIdWhenNonEmpty(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TCacheProxy::class . "\0_backingCacheId",
			$exprops
		);
	}

	// ── __call dispatch ──────────────────────────────────────────────────────────

	public function testCallForwardsPublicMethodToBackingCache(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMethod('hello');
		$this->assertSame('custom:hello', $result);
	}

	public function testCallForwardsMultipleArguments(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMultiArgMethod('key', 42);
		$this->assertSame('key:42', $result);
	}

	public function testCallLazilyResolvesBeforeForwarding(): void
	{
		// init() called but getCache() never called yet — __call must still resolve.
		$this->proxy->init(null);
		$result = $this->proxy->customMethod('lazy');
		$this->assertSame('custom:lazy', $result);
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
		// dy-prefixed names belong to TComponent's behavior system, not the cache.
		// An unimplemented dy event returns its first argument (or null).
		$this->proxy->init(null);
		$result = $this->proxy->dyCustomEvent('value');
		$this->assertSame('value', $result);
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	// ── __get / __set / __isset / __unset passthrough ────────────────────────────

	public function testGetForwardsCacheSpecificPropertyToBackingCache(): void
	{
		$this->proxy->init(null);
		$this->backend->setCustomProp('hello');
		$this->assertSame('hello', $this->proxy->CustomProp);
	}

	public function testSetForwardsCacheSpecificPropertyToBackingCache(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backend->getCustomProp());
	}

	public function testIssetReturnsFalseWhenCachePropIsNull(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testIssetReturnsTrueWhenCachePropIsSet(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'set';
		$this->assertTrue(isset($this->proxy->CustomProp));
	}

	public function testUnsetForwardsCacheSpecificPropertyToBackingCache(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'before';
		unset($this->proxy->CustomProp);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testGetProxyOwnPropertyUsesProxyGetter(): void
	{
		// BackingCacheId is defined on TCacheProxy itself; __get must return the
		// proxy's value, not try the backing cache.
		$this->assertSame('backingCache', $this->proxy->BackingCacheId);
	}

	public function testSetProxyReadOnlyPropertyThrows(): void
	{
		// getCache() exists on the proxy but setCache() does not → read-only.
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->Cache = 'anything';
	}

	public function testGetUndefinedPropertyThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$_ = $this->proxy->CompletelyUndefinedProperty;
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsCacheReference(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // force lazy resolution

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetCacheDirect());
	}

	public function testClonePreservesBackingCacheId(): void
	{
		$clone = clone $this->proxy;

		$this->assertSame('backingCache', $clone->getBackingCacheId());
	}

	public function testCloneReresolvesBackingCacheOnFirstUse(): void
	{
		$this->proxy->init(null);

		$clone = clone $this->proxy;

		$this->assertSame($this->backend, $clone->getCache());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);

		$clone = clone $this->proxy;

		// Redirecting the clone's BackingCacheId must not affect the original.
		$secondBackend = new TCacheProxyBackend();
		$secondBackend->setPrimaryCache(false);
		$secondBackend->init(null);
		$this->app->setModule('secondCache', $secondBackend);

		$clone->setBackingCacheId('secondCache');

		$this->assertSame($this->backend, $this->proxy->getCache());
		$this->assertSame($secondBackend, $clone->getCache());
		$secondBackend->unlisten();
	}

	// ── Logging detail ───────────────────────────────────────────────────────────

	public function testBackingCacheIdChangeLoggedAtWarningLevel(): void
	{
		$cat = 'prado.caching';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingCacheId('anotherModule');

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testMultipleBackingCacheIdChangesEachLog(): void
	{
		$cat = 'prado.caching';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingCacheId('first');   // change 1
		$this->proxy->setBackingCacheId('second');  // change 2

		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testBackingCacheIdSameValueProducesNoLog(): void
	{
		$cat = 'prado.caching';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingCacheId('backingCache'); // same value

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	// ── Error-message keys ───────────────────────────────────────────────────────

	public function testInitExceptionMessageContainsExpectedText(): void
	{
		$proxy = new TCacheProxy();
		$proxy->setPrimaryCache(false);

		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('cacheproxy_backing_cache_id_required', $e->getErrorCode());
		}
	}

	public function testGetCacheModuleNotFoundExceptionContainsId(): void
	{
		$proxy = new TCacheProxyAccessor();
		$proxy->setPrimaryCache(false);
		$proxy->setBackingCacheId('missingModule');

		try {
			$proxy->getCache();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('cacheproxy_cache_not_found', $e->getErrorCode());
		}
	}

	public function testGetCacheInvalidTypeExceptionContainsId(): void
	{
		$notACache = new TCacheProxyNotACacheModule();
		$this->app->setModule('notCache2', $notACache);

		$proxy = new TCacheProxyAccessor();
		$proxy->setPrimaryCache(false);
		$proxy->setBackingCacheId('notCache2');

		try {
			$proxy->getCache();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('cacheproxy_invalid_cache_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$notACache->unlisten();
		}
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testHasEventReturnsFalseForBackingCacheEventBeforeAttach(): void
	{
		// Before getCache() is called, attachProxy has not run; the backing
		// cache's OnTestEvent is not yet exposed on the proxy.
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testGetCacheTriggersAttachProxy(): void
	{
		// getCache() calls attachProxy() internally; after resolution the proxy
		// must report OnTestEvent as a known event.
		$this->proxy->init(null);
		$this->proxy->getCache();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testAttachProxySharesHandlerListWithBackingCache(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy

		// Adding a handler via the proxy must place it in the backing cache's list.
		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testHandlerAddedDirectlyOnBackingCacheVisibleViaProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy

		// A handler added directly on the backend fires when the backend raises the event.
		// The proxy and backend each hold their own independent TWeakCallableCollection
		// (forwarder pattern); they are NOT the same object.
		$fired = false;
		$this->backend->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
		$this->assertNotSame(
			$this->backend->getEventHandlers('OnTestEvent'),
			$this->proxy->getEventHandlers('OnTestEvent')
		);
	}

	public function testGetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache();

		// __get for 'OnTestEvent' must return the proxy's own handler list.
		// With the forwarder approach the proxy owns an independent collection —
		// it is NOT the same object as the backend's collection.
		$handlers = $this->proxy->OnTestEvent;
		$this->assertInstanceOf(TWeakCallableCollection::class, $handlers);
		$this->assertNotSame($this->backend->getEventHandlers('OnTestEvent'), $handlers);
	}

	public function testIssetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache();

		// No handlers yet → isset returns false.
		$this->assertFalse(isset($this->proxy->OnTestEvent));

		// Add one handler → isset returns true.
		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));
	}

	public function testDetachProxyClearsEventSharing(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testBackingCacheIdChangeCallsDetachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		// Changing the BackingCacheId must detach the old proxy.
		$this->proxy->setBackingCacheId('someOtherCache');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testCloneDetachesProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy on original
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		// The clone must NOT retain the attached event references.
		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		// The original is unaffected.
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testZappableAlwaysExcludesProxyEventNames(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TCacheProxy::class . "\0_proxyEventNames",
			$exprops
		);
	}

	public function testAttachProxyIncludesBehaviorProvidedOnEvent(): void
	{
		// attachProxy() must discover on[A-Z]* events exposed by behaviors
		// attached to the backing cache via TComponent::hasMethod(), not just
		// events declared directly on the cache class.
		$this->backend->attachBehavior('testBehavior', new TCacheProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy

		$this->assertTrue(
			$this->proxy->hasEvent('OnBehaviorEvent'),
			'Proxy must expose on* events contributed by behaviors on the backing cache.'
		);
	}

	public function testHandlerRegisteredViaProxyFiresForBehaviorEvent(): void
	{
		// Handlers added to the proxy's shared list must fire when the behavior
		// raises the event on the backing cache.
		$this->backend->attachBehavior('testBehavior', new TCacheProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getCache(); // triggers attachProxy

		$fired = false;
		$this->proxy->OnBehaviorEvent = function () use (&$fired) {
			$fired = true;
		};

		$behaviors = $this->backend->getBehaviors(TCacheProxyBehaviorWithEvent::class);
		/** @var TCacheProxyBehaviorWithEvent $beh */
		$beh = reset($behaviors);
		$beh->onBehaviorEvent(new TEventParameter());

		$this->assertTrue($fired, 'Handler added via proxy must fire when the behavior raises its event.');
	}

	// ── isa() — backing-cache transparency ───────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		// The proxy's own class and inherited hierarchy are still reported.
		$this->assertTrue($this->proxy->isa(TCacheProxy::class));
		$this->assertTrue($this->proxy->isa(TCache::class));
		$this->assertTrue($this->proxy->isa(\Prado\Caching\ICache::class));
	}

	public function testIsaReturnsTrueForBackingCacheClass(): void
	{
		// After the backing cache is resolved, isa() must see through the proxy.
		$this->proxy->getCache(); // force resolution
		$this->assertTrue($this->proxy->isa(TCacheProxyBackend::class),
			'isa() must return true for the backing cache class once resolved');
	}

	public function testIsaReturnsFalseForUnrelatedClass(): void
	{
		$this->assertFalse($this->proxy->isa(TCacheProxyNotACacheModule::class));
	}

	public function testIsaLazilyResolvesBackingCacheWhenNotYetResolved(): void
	{
		// $this->proxy has BackingCacheId set but getCache() has NOT been called yet.
		$this->assertNull($this->proxy->pubGetCacheDirect(), 'cache must not be resolved yet');

		// isa() should trigger lazy resolution and return true for the backing class.
		$this->assertTrue($this->proxy->isa(TCacheProxyBackend::class),
			'isa() must trigger lazy resolution and match the backing cache class');
		$this->assertNotNull($this->proxy->pubGetCacheDirect(),
			'lazy resolution must have stored the backing cache reference');
	}

	public function testIsaReturnsFalseWhenNoBackingCacheIdSet(): void
	{
		// A proxy with no BackingCacheId set cannot resolve a cache; isa() for a
		// backing-cache-only class must return false without throwing.
		$proxy = new TCacheProxyAccessor();
		$this->assertFalse($proxy->isa(TCacheProxyBackend::class));
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
