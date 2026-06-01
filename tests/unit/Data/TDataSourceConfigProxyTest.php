<?php

/**
 * TDataSourceConfigProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Collections\TWeakCallableCollection;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDataSourceConfigProxy;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A minimal concrete TModule used to exercise the wrong-type check in
 * {@see TDataSourceConfigProxy::getDataSource()}.
 */
class TDataSourceConfigProxyTestModule extends TModule
{
	public function init($config): void
	{
		parent::init($config);
	}
}

/**
 * A TDataSourceConfig subclass that adds a custom property, a custom method,
 * and a custom on[A-Z]* event to exercise forwarding through the proxy.
 */
class TDataSourceConfigProxyBackingDs extends TDataSourceConfig
{
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
		return 'dsproxy:' . $arg;
	}

	public function customMultiArgMethod(string $a, int $b): string
	{
		return $a . ':' . $b;
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TBehavior with a public on[A-Z]* event, used to verify that attachProxy()
 * discovers events exposed by behaviors attached to the backing data source.
 */
class TDataSourceConfigProxyBehaviorWithEvent extends TBehavior
{
	public function onBehaviorEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnBehaviorEvent', $this->getOwner(), $param);
	}
}

/**
 * Exposes protected internals of TDataSourceConfigProxy for direct testing.
 */
class TDataSourceConfigProxyAccessor extends TDataSourceConfigProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetDataSourceDirect(): ?TDataSourceConfig
	{
		return $this->getDataSourceDirect();
	}

	public function pubGetBackingComponentDirect(): ?TComponent
	{
		return $this->getProxyBackingDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TDataSourceConfigProxyTest class.
 *
 * Tests TDataSourceConfigProxy: BackingDataSourceId property, lazy module
 * resolution, init() validation, IModuleDependency, transparent delegation of
 * getDbConnection(), event sharing, isa() transparency, change logging,
 * serialization, and edge cases.
 *
 * @package Prado\Tests\Unit\Data
 */
class TDataSourceConfigProxyTest extends PHPUnit\Framework\TestCase
{
	private static string $mockAppPath;

	/** @var TApplication|null */
	private ?TApplication $app = null;

	/** @var TDataSourceConfigProxyBackingDs */
	private TDataSourceConfigProxyBackingDs $backing;

	/** @var TDataSourceConfigProxyAccessor */
	private TDataSourceConfigProxyAccessor $proxy;

	public static function setUpBeforeClass(): void
	{
		self::$mockAppPath = __DIR__ . '/../Caching/mockapp';
	}

	protected function setUp(): void
	{
		$this->app = new TApplication(self::$mockAppPath);

		// Create and register a fully initialized backing data source module.
		$this->backing = new TDataSourceConfigProxyBackingDs();
		$this->backing->init(null);
		$this->app->setModule('backingDs', $this->backing);

		// Build the proxy but do NOT call init() — tests that need it call it.
		$this->proxy = new TDataSourceConfigProxyAccessor();
		$this->proxy->setBackingDataSourceId('backingDs');
	}

	protected function tearDown(): void
	{
		$this->proxy->unlisten();
		$this->backing->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTDataSourceConfigProxy(): void
	{
		$this->assertInstanceOf(TDataSourceConfigProxy::class, $this->proxy);
	}

	public function testExtendsTDataSourceConfig(): void
	{
		$this->assertInstanceOf(TDataSourceConfig::class, $this->proxy);
	}

	public function testImplementsIModuleDependency(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
	}

	public function testImplementsIProxy(): void
	{
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	public function testExtendsTModule(): void
	{
		$this->assertInstanceOf(TModule::class, $this->proxy);
	}

	// ── Default property values ──────────────────────────────────────────────────

	public function testDefaultBackingDataSourceIdIsEmptyString(): void
	{
		$fresh = new TDataSourceConfigProxy();
		$this->assertSame('', $fresh->getBackingDataSourceId());
	}

	// ── getBackingDataSourceId / setBackingDataSourceId ──────────────────────────

	public function testSetGetBackingDataSourceId(): void
	{
		$proxy = new TDataSourceConfigProxy();
		$proxy->setBackingDataSourceId('myDs');
		$this->assertSame('myDs', $proxy->getBackingDataSourceId());
	}

	public function testSetBackingDataSourceIdSameValueIsNoOp(): void
	{
		$this->proxy->setBackingDataSourceId('backingDs'); // same value — must not log
		$this->assertSame('backingDs', $this->proxy->getBackingDataSourceId());
	}

	public function testSetBackingDataSourceIdFromEmptyDoesNotLog(): void
	{
		$cat = 'prado.data';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TDataSourceConfigProxy();
		$proxy->setBackingDataSourceId('backingDs');

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingDataSourceIdChangeLogsWarning(): void
	{
		$cat = 'prado.data';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingDataSourceId('otherDs');

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingDataSourceIdChangeLogContainsBothIds(): void
	{
		$cat = 'prado.data';
		$this->proxy->setBackingDataSourceId('replacementDs');

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingDs', $msg);
		$this->assertStringContainsString('replacementDs', $msg);
	}

	public function testSetBackingDataSourceIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getDataSource();

		$secondBacking = new TDataSourceConfig();
		$secondBacking->init(null);
		$this->app->setModule('secondDs', $secondBacking);

		$this->proxy->setBackingDataSourceId('secondDs');
		$second = $this->proxy->getDataSource();

		$this->assertNotSame($first, $second);
		$this->assertSame($secondBacking, $second);
		$secondBacking->unlisten();
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = new TDataSourceConfigProxy();
		$this->assertNull($proxy->getModuleDependencies());
	}

	public function testGetModuleDependenciesReturnsDependencyArrayWhenIdSet(): void
	{
		$deps = $this->proxy->getModuleDependencies();
		$this->assertIsArray($deps);
		$this->assertCount(1, $deps);
		$this->assertSame('backingDs', $deps[0]['id']);
		$this->assertTrue($deps[0]['required']);
	}

	public function testGetModuleDependenciesIsInitParameterIsIgnored(): void
	{
		$depsInit = $this->proxy->getModuleDependencies(true);
		$depsPreInit = $this->proxy->getModuleDependencies(false);
		$this->assertEquals($depsInit, $depsPreInit);
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenBackingDataSourceIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertSame('backingDs', $this->proxy->getBackingDataSourceId());
	}

	public function testInitThrowsWhenBackingDataSourceIdIsEmpty(): void
	{
		$proxy = new TDataSourceConfigProxy();
		$this->expectException(TConfigurationException::class);
		$proxy->init(null);
	}

	public function testInitExceptionHasExpectedErrorCode(): void
	{
		$proxy = new TDataSourceConfigProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('datasourceproxy_backing_data_source_id_required', $e->getErrorCode());
		}
	}

	// ── getDataSource() — lazy resolution ────────────────────────────────────────

	public function testGetDataSourceReturnsBackingModule(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backing, $this->proxy->getDataSource());
	}

	public function testGetDataSourceCachesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getDataSource();
		$second = $this->proxy->getDataSource();
		$this->assertSame($first, $second);
	}

	public function testGetDataSourceThrowsWhenIdEmpty(): void
	{
		$proxy = new TDataSourceConfigProxy();
		$this->expectException(TConfigurationException::class);
		$proxy->getDataSource();
	}

	public function testGetDataSourceThrowsWithExpectedErrorCodeWhenIdEmpty(): void
	{
		$proxy = new TDataSourceConfigProxy();
		try {
			$proxy->getDataSource();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('datasourceproxy_backing_data_source_id_required', $e->getErrorCode());
		}
	}

	public function testGetDataSourceThrowsWhenModuleNotFound(): void
	{
		$proxy = new TDataSourceConfigProxyAccessor();
		$proxy->setBackingDataSourceId('nonExistentDs');
		$this->expectException(TConfigurationException::class);
		$proxy->getDataSource();
	}

	public function testGetDataSourceModuleNotFoundHasExpectedErrorCode(): void
	{
		$proxy = new TDataSourceConfigProxyAccessor();
		$proxy->setBackingDataSourceId('missingDs');
		try {
			$proxy->getDataSource();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('datasourceproxy_data_source_not_found', $e->getErrorCode());
		}
	}

	public function testGetDataSourceThrowsWhenModuleIsNotTDataSourceConfig(): void
	{
		// Register a non-TDataSourceConfig module under the ID.
		$wrongModule = new TDataSourceConfigProxyTestModule();
		$wrongModule->init(null);
		$this->app->setModule('wrongDs', $wrongModule);

		$proxy = new TDataSourceConfigProxyAccessor();
		$proxy->setBackingDataSourceId('wrongDs');

		try {
			$proxy->getDataSource();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('datasourceproxy_invalid_data_source_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$wrongModule->unlisten();
		}
	}

	// ── getDbConnection() delegation ─────────────────────────────────────────────

	public function testGetDbConnectionDelegatesToBacking(): void
	{
		$this->proxy->init(null);

		// getDbConnection() on the proxy must return the same connection as the backing.
		$proxyConn = $this->proxy->getDbConnection();
		$backingConn = $this->backing->getDbConnection();

		$this->assertSame($backingConn, $proxyConn);
	}

	public function testGetDatabaseDelegatesToBacking(): void
	{
		$this->proxy->init(null);

		// getDatabase() on TDataSourceConfig calls getDbConnection(); on the proxy
		// it must still delegate to the backing's connection.
		$proxyConn = $this->proxy->getDatabase();
		$backingConn = $this->backing->getDbConnection();

		$this->assertSame($backingConn, $proxyConn);
	}

	// ── isa() — backing transparency ─────────────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		$this->assertTrue($this->proxy->isa(TDataSourceConfigProxy::class));
		$this->assertTrue($this->proxy->isa(TDataSourceConfig::class));
		$this->assertTrue($this->proxy->isa(TModule::class));
	}

	public function testIsaReturnsTrueForBackingClass(): void
	{
		$this->proxy->getDataSource(); // force resolution
		$this->assertTrue($this->proxy->isa(TDataSourceConfig::class));
	}

	public function testIsaReturnsFalseForUnrelatedClass(): void
	{
		$this->assertFalse($this->proxy->isa(\stdClass::class));
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsDataSourceReference(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // force lazy resolution

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetDataSourceDirect());
		$this->assertNull($clone->pubGetBackingComponentDirect());
	}

	public function testClonePreservesBackingDataSourceId(): void
	{
		$clone = clone $this->proxy;
		$this->assertSame('backingDs', $clone->getBackingDataSourceId());
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableExcludesDataSourceReference(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		// _proxyBacking is declared in TComponentProxyTrait, but __CLASS__ in the
		// trait resolves to the using class (TDataSourceConfigProxy), so the key uses that.
		$this->assertContains(
			"\0" . TDataSourceConfigProxy::class . "\0_proxyBacking",
			$exprops
		);
	}

	public function testZappableExcludesProxyEventNames(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TDataSourceConfigProxy::class . "\0_proxyEventNames",
			$exprops
		);
	}

	public function testZappableExcludesBackingDataSourceIdWhenEmpty(): void
	{
		$proxy = new TDataSourceConfigProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TDataSourceConfigProxy::class . "\0_backingDataSourceId",
			$exprops
		);
	}

	public function testZappableKeepsBackingDataSourceIdWhenNonEmpty(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TDataSourceConfigProxy::class . "\0_backingDataSourceId",
			$exprops
		);
	}

	// ── __call dispatch ──────────────────────────────────────────────────────────

	public function testCallForwardsPublicMethodToBackingDataSource(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMethod('hello');
		$this->assertSame('dsproxy:hello', $result);
	}

	public function testCallForwardsMultipleArguments(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMultiArgMethod('key', 42);
		$this->assertSame('key:42', $result);
	}

	public function testCallLazilyResolvesBeforeForwarding(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMethod('lazy');
		$this->assertSame('dsproxy:lazy', $result);
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
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

	public function testGetForwardsBackingSpecificPropertyToBackingDataSource(): void
	{
		$this->proxy->init(null);
		$this->backing->setCustomProp('hello');
		$this->assertSame('hello', $this->proxy->CustomProp);
	}

	public function testSetForwardsBackingSpecificPropertyToBackingDataSource(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backing->getCustomProp());
	}

	public function testIssetReturnsFalseWhenBackingPropIsNull(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testIssetReturnsTrueWhenBackingPropIsSet(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'set';
		$this->assertTrue(isset($this->proxy->CustomProp));
	}

	public function testUnsetForwardsBackingSpecificPropertyToBackingDataSource(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'before';
		unset($this->proxy->CustomProp);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testGetProxyOwnPropertyUsesProxyGetter(): void
	{
		$this->assertSame('backingDs', $this->proxy->BackingDataSourceId);
	}

	public function testGetUndefinedPropertyThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$_ = $this->proxy->CompletelyUndefinedProperty;
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneReresolvesDataSourceOnFirstUse(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;
		$this->assertSame($this->backing, $clone->getDataSource());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;

		$secondBacking = new TDataSourceConfigProxyBackingDs();
		$secondBacking->init(null);
		$this->app->setModule('secondDs2', $secondBacking);

		$clone->setBackingDataSourceId('secondDs2');

		$this->assertSame($this->backing, $this->proxy->getDataSource());
		$this->assertSame($secondBacking, $clone->getDataSource());
		$secondBacking->unlisten();
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testHasEventReturnsFalseForBackingEventBeforeAttach(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testGetDataSourceTriggersAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testProxyHandlerFiresWhenBackingRaisesEvent(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy

		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testProxyAndBackingHandlerListsAreIndependent(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy

		$backingFired = false;
		$this->backing->OnTestEvent = function () use (&$backingFired) {
			$backingFired = true;
		};
		$proxyFired = false;
		$this->proxy->OnTestEvent = function () use (&$proxyFired) {
			$proxyFired = true;
		};

		$this->backing->onTestEvent(new TEventParameter());

		$this->assertTrue($backingFired);
		$this->assertTrue($proxyFired);
		$this->assertNotSame(
			$this->backing->getEventHandlers('OnTestEvent'),
			$this->proxy->getEventHandlers('OnTestEvent')
		);
	}

	public function testGetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource();

		$handlers = $this->proxy->OnTestEvent;
		$this->assertInstanceOf(TWeakCallableCollection::class, $handlers);
		$this->assertNotSame($this->backing->getEventHandlers('OnTestEvent'), $handlers);
	}

	public function testIssetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource();

		$this->assertFalse(isset($this->proxy->OnTestEvent));

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));
	}

	public function testUnsetOnEventViaPropertyClearsHandlerCollection(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource();

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));

		unset($this->proxy->OnTestEvent);
		$this->assertFalse(isset($this->proxy->OnTestEvent));
	}

	public function testDetachProxyClearsEventSharing(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testBackingDataSourceIdChangeCallsDetachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->setBackingDataSourceId('someOtherDs');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testCloneDetachesProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy on original
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testAttachProxyIncludesBehaviorProvidedOnEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TDataSourceConfigProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy

		$this->assertTrue(
			$this->proxy->hasEvent('OnBehaviorEvent'),
			'Proxy must expose on* events contributed by behaviors on the backing data source.'
		);
	}

	public function testHandlerRegisteredViaProxyFiresForBehaviorEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TDataSourceConfigProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getDataSource(); // triggers attachProxy

		$fired = false;
		$this->proxy->OnBehaviorEvent = function () use (&$fired) {
			$fired = true;
		};

		$behaviors = $this->backing->getBehaviors(TDataSourceConfigProxyBehaviorWithEvent::class);
		/** @var TDataSourceConfigProxyBehaviorWithEvent $beh */
		$beh = reset($behaviors);
		$beh->onBehaviorEvent(new TEventParameter());

		$this->assertTrue($fired, 'Handler added via proxy must fire when the behavior raises its event.');
	}

	// ── isa() — backing transparency ─────────────────────────────────────────────

	public function testIsaLazilyResolvesBackingWhenNotYetResolved(): void
	{
		$this->assertNull($this->proxy->pubGetBackingComponentDirect(), 'backing must not be resolved yet');

		$this->assertTrue($this->proxy->isa(TDataSourceConfigProxyBackingDs::class));
		$this->assertNotNull($this->proxy->pubGetBackingComponentDirect());
	}

	public function testIsaReturnsFalseWhenNoBackingDataSourceIdSet(): void
	{
		$proxy = new TDataSourceConfigProxyAccessor();
		$this->assertFalse($proxy->isa(TDataSourceConfigProxyBackingDs::class));
	}

	// ── Logging detail ───────────────────────────────────────────────────────────

	public function testMultipleBackingDataSourceIdChangesEachLog(): void
	{
		$cat = 'prado.data';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingDataSourceId('first');  // change 1
		$this->proxy->setBackingDataSourceId('second'); // change 2

		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testBackingDataSourceIdSameValueProducesNoLog(): void
	{
		$cat = 'prado.data';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingDataSourceId('backingDs'); // same value

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
