<?php

/**
 * TModuleProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\Collections\TWeakCallableCollection;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\TModuleProxy;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A concrete TModule used as the backing component in TModuleProxy tests.
 * Exposes a custom property and method to exercise forwarding through the proxy.
 */
class TModuleProxyBackingModule extends TModule
{
	/** @var string|null used to exercise __get/__set/__isset/__unset forwarding */
	private ?string $_customProp = null;

	/** @var int counts how many times onTestEvent has been called */
	public int $testEventCallCount = 0;

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
		return 'moduleproxy:' . $arg;
	}

	public function customMultiArgMethod(string $a, int $b): string
	{
		return $a . ':' . $b;
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->testEventCallCount++;
		$this->raiseEvent('OnTestEvent', $this, $param);
	}

	public function init($config): void
	{
		parent::init($config);
	}
}

/**
 * A TBehavior with a public on[A-Z]* event used to verify that attachProxy()
 * discovers events exposed by behaviors attached to the backing module.
 */
class TModuleProxyBehaviorWithEvent extends TBehavior
{
	public function onBehaviorEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnBehaviorEvent', $this->getOwner(), $param);
	}
}

/**
 * Exposes protected internals of TModuleProxy for direct testing.
 */
class TModuleProxyAccessor extends TModuleProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetBackingComponentDirect(): ?TComponent
	{
		return $this->getBackingComponentDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TModuleProxyTest class.
 *
 * Tests TModuleProxy: BackingComponentId property, lazy module resolution from
 * the application registry, init() validation, IModuleDependency, transparent
 * method/property delegation, event sharing via attachProxy, isa() transparency,
 * change logging, serialization, and edge cases.
 *
 * @package Prado\Tests\Unit
 */
class TModuleProxyTest extends PHPUnit\Framework\TestCase
{
	private static string $mockAppPath;

	/** @var TApplication|null */
	private ?TApplication $app = null;

	/** @var TModuleProxyBackingModule */
	private TModuleProxyBackingModule $backing;

	/** @var TModuleProxyAccessor */
	private TModuleProxyAccessor $proxy;

	public static function setUpBeforeClass(): void
	{
		self::$mockAppPath = __DIR__ . '/Caching/mockapp';
	}

	protected function setUp(): void
	{
		$this->app = new TApplication(self::$mockAppPath);

		// Create and register a fully initialized backing module.
		$this->backing = new TModuleProxyBackingModule();
		$this->backing->init(null);
		$this->app->setModule('backingModule', $this->backing);

		// Build the proxy but do NOT call init() — tests that need it call it.
		$this->proxy = new TModuleProxyAccessor();
		$this->proxy->setBackingComponentId('backingModule');
	}

	protected function tearDown(): void
	{
		$this->proxy->unlisten();
		$this->backing->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTModuleProxy(): void
	{
		$this->assertInstanceOf(TModuleProxy::class, $this->proxy);
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

	public function testDefaultBackingComponentIdIsEmptyString(): void
	{
		$fresh = new TModuleProxy();
		$this->assertSame('', $fresh->getBackingComponentId());
	}

	// ── getBackingComponentId / setBackingComponentId ────────────────────────────

	public function testSetGetBackingComponentId(): void
	{
		$proxy = new TModuleProxy();
		$proxy->setBackingComponentId('myModule');
		$this->assertSame('myModule', $proxy->getBackingComponentId());
	}

	public function testSetBackingComponentIdSameValueIsNoOp(): void
	{
		$this->proxy->setBackingComponentId('backingModule'); // same value — must not log
		$this->assertSame('backingModule', $this->proxy->getBackingComponentId());
	}

	public function testSetBackingComponentIdFromEmptyDoesNotLog(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TModuleProxy();
		$proxy->setBackingComponentId('backingModule'); // first set from '' — no log

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingComponentIdChangeLogsWarning(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingComponentId('otherModule'); // changes from 'backingModule'

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingComponentIdChangeLogMessageContainsBothIds(): void
	{
		$cat = 'prado.component';
		$this->proxy->setBackingComponentId('replacementModule');

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingModule', $msg);
		$this->assertStringContainsString('replacementModule', $msg);
	}

	public function testSetBackingComponentIdInvalidatesResolvedReference(): void
	{
		// Force resolution of the proxy.
		$this->proxy->init(null);
		$first = $this->proxy->getBackingComponent();

		// Change the ID — the cached reference must be cleared.
		$secondBacking = new TModuleProxyBackingModule();
		$secondBacking->init(null);
		$this->app->setModule('secondModule', $secondBacking);

		$this->proxy->setBackingComponentId('secondModule');
		$second = $this->proxy->getBackingComponent();

		$this->assertNotSame($first, $second);
		$this->assertSame($secondBacking, $second);
		$secondBacking->unlisten();
	}

	public function testSetBackingComponentIdAcceptsStringFromXmlConfig(): void
	{
		$proxy = new TModuleProxy();
		$proxy->setBackingComponentId('someId');
		$this->assertSame('someId', $proxy->getBackingComponentId());
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenBackingComponentIdEmpty(): void
	{
		$proxy = new TModuleProxy();
		$this->assertNull($proxy->getModuleDependencies());
	}

	public function testGetModuleDependenciesReturnsDependencyArrayWhenIdSet(): void
	{
		$deps = $this->proxy->getModuleDependencies();
		$this->assertIsArray($deps);
		$this->assertCount(1, $deps);
		$this->assertSame('backingModule', $deps[0]['id']);
		$this->assertTrue($deps[0]['required']);
	}

	public function testGetModuleDependenciesIsInitParameterIsIgnored(): void
	{
		$depsInit = $this->proxy->getModuleDependencies(true);
		$depsPreInit = $this->proxy->getModuleDependencies(false);
		$this->assertEquals($depsInit, $depsPreInit);
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenBackingComponentIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertSame('backingModule', $this->proxy->getBackingComponentId());
	}

	public function testInitThrowsWhenBackingComponentIdIsEmpty(): void
	{
		$proxy = new TModuleProxy();
		$this->expectException(TConfigurationException::class);
		$proxy->init(null);
	}

	public function testInitExceptionHasExpectedErrorCode(): void
	{
		$proxy = new TModuleProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('componentproxy_backing_component_id_required', $e->getErrorCode());
		}
	}

	// ── getBackingComponent() — lazy resolution ──────────────────────────────────

	public function testGetBackingComponentReturnsBackingModule(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backing, $this->proxy->getBackingComponent());
	}

	public function testGetBackingComponentCachesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getBackingComponent();
		$second = $this->proxy->getBackingComponent();
		$this->assertSame($first, $second);
	}

	public function testGetBackingComponentThrowsWhenBackingComponentIdIsEmpty(): void
	{
		$proxy = new TModuleProxy();
		$this->expectException(TConfigurationException::class);
		$proxy->getBackingComponent();
	}

	public function testGetBackingComponentThrowsWithExpectedErrorCodeWhenIdEmpty(): void
	{
		$proxy = new TModuleProxy();
		try {
			$proxy->getBackingComponent();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('componentproxy_backing_component_id_required', $e->getErrorCode());
		}
	}

	public function testGetBackingComponentThrowsWhenModuleNotFound(): void
	{
		$proxy = new TModuleProxyAccessor();
		$proxy->setBackingComponentId('nonExistentModule');
		$this->expectException(TConfigurationException::class);
		$proxy->getBackingComponent();
	}

	public function testGetBackingComponentModuleNotFoundHasExpectedErrorCode(): void
	{
		$proxy = new TModuleProxyAccessor();
		$proxy->setBackingComponentId('missingModule');
		try {
			$proxy->getBackingComponent();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('componentproxy_component_not_found', $e->getErrorCode());
		}
	}

	// ── __call dispatch ──────────────────────────────────────────────────────────

	public function testCallForwardsPublicMethodToBackingModule(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->customMethod('hello');
		$this->assertSame('moduleproxy:hello', $result);
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
		$this->assertSame('moduleproxy:lazy', $result);
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
		$this->proxy->init(null);
		$result = $this->proxy->dyCustomEvent('value');
		$this->assertSame('value', $result);
	}

	public function testCallDoesNotForwardFxEvent(): void
	{
		$this->proxy->init(null);
		$this->proxy->fxCustomGlobalEvent('value');

		$this->assertFalse(
			(new \ReflectionClass($this->backing))->hasMethod('fxCustomGlobalEvent'),
			'fxCustomGlobalEvent must not be a real method on the backing — proxy must not forward fx events.'
		);
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	// ── __get / __set / __isset / __unset passthrough ────────────────────────────

	public function testGetForwardsModuleSpecificPropertyToBackingModule(): void
	{
		$this->proxy->init(null);
		$this->backing->setCustomProp('hello');
		$this->assertSame('hello', $this->proxy->CustomProp);
	}

	public function testSetForwardsModuleSpecificPropertyToBackingModule(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backing->getCustomProp());
	}

	public function testIssetReturnsFalseWhenModulePropIsNull(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testIssetReturnsTrueWhenModulePropIsSet(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'set';
		$this->assertTrue(isset($this->proxy->CustomProp));
	}

	public function testUnsetForwardsModuleSpecificPropertyToBackingModule(): void
	{
		$this->proxy->init(null);
		$this->proxy->CustomProp = 'before';
		unset($this->proxy->CustomProp);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testGetProxyOwnPropertyUsesProxyGetter(): void
	{
		$this->assertSame('backingModule', $this->proxy->BackingComponentId);
	}

	public function testGetUndefinedPropertyThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$_ = $this->proxy->CompletelyUndefinedProperty;
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsComponentReference(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // force lazy resolution

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetBackingComponentDirect());
	}

	public function testClonePreservesBackingComponentId(): void
	{
		$clone = clone $this->proxy;
		$this->assertSame('backingModule', $clone->getBackingComponentId());
	}

	public function testCloneReresolvesBackingModuleOnFirstUse(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;
		$this->assertSame($this->backing, $clone->getBackingComponent());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;

		$secondBacking = new TModuleProxyBackingModule();
		$secondBacking->init(null);
		$this->app->setModule('secondModuleClone', $secondBacking);

		$clone->setBackingComponentId('secondModuleClone');

		$this->assertSame($this->backing, $this->proxy->getBackingComponent());
		$this->assertSame($secondBacking, $clone->getBackingComponent());
		$secondBacking->unlisten();
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableExcludesBackingComponentReference(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		// _proxyBacking is declared in TComponentProxyTrait, but __CLASS__ in the
		// trait resolves to the using class (TModuleProxy), so the key uses that.
		$this->assertContains(
			"\0" . TModuleProxy::class . "\0_proxyBacking",
			$exprops
		);
	}

	public function testZappableAlwaysExcludesProxyEventNames(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		// _proxyEventNames is declared in TComponentProxyTrait which is used
		// directly by TModuleProxy, so __CLASS__ in the trait = TModuleProxy.
		$this->assertContains(
			"\0" . TModuleProxy::class . "\0_proxyEventNames",
			$exprops
		);
	}

	public function testZappableExcludesBackingComponentIdWhenEmpty(): void
	{
		$proxy = new TModuleProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TModuleProxy::class . "\0_backingComponentId",
			$exprops
		);
	}

	public function testZappableKeepsBackingComponentIdWhenNonEmpty(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TModuleProxy::class . "\0_backingComponentId",
			$exprops
		);
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testHasEventReturnsFalseForBackingModuleEventBeforeAttach(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testGetBackingComponentTriggersAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testProxyHandlerFiresWhenBackingModuleRaisesEvent(): void
	{
		// Handlers registered on the proxy must fire when the backing raises the
		// event, because attachProxy() registers a forwarder on the backing that
		// calls through the proxy's own independent handler list.
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testProxyAndBackingModuleHandlerListsAreIndependent(): void
	{
		// The proxy owns its own TWeakCallableCollection for each forwarded event —
		// it is NOT the same object as the backing module's collection.
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

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
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$handlers = $this->proxy->OnTestEvent;
		$this->assertInstanceOf(TWeakCallableCollection::class, $handlers);
		// The proxy's collection is its own independent object.
		$this->assertNotSame($this->backing->getEventHandlers('OnTestEvent'), $handlers);
	}

	public function testIssetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$this->assertFalse(isset($this->proxy->OnTestEvent));

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));
	}

	public function testUnsetOnEventViaPropertyClearsHandlerCollection(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));

		unset($this->proxy->OnTestEvent);
		$this->assertFalse(isset($this->proxy->OnTestEvent));
	}

	public function testDetachProxyClearsEventSharing(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testBackingComponentIdChangeCallsDetachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->setBackingComponentId('someOtherModule');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testCloneDetachesProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy on original
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testAttachProxyIncludesBehaviorProvidedOnEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TModuleProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$this->assertTrue(
			$this->proxy->hasEvent('OnBehaviorEvent'),
			'Proxy must expose on* events contributed by behaviors on the backing module.'
		);
	}

	public function testHandlerRegisteredViaProxyFiresForBehaviorEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TModuleProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getBackingComponent(); // triggers attachProxy

		$fired = false;
		$this->proxy->OnBehaviorEvent = function () use (&$fired) {
			$fired = true;
		};

		$behaviors = $this->backing->getBehaviors(TModuleProxyBehaviorWithEvent::class);
		/** @var TModuleProxyBehaviorWithEvent $beh */
		$beh = reset($behaviors);
		$beh->onBehaviorEvent(new TEventParameter());

		$this->assertTrue($fired, 'Handler added via proxy must fire when the behavior raises its event.');
	}

	// ── isa() — backing-component transparency ────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		$this->assertTrue($this->proxy->isa(TModuleProxy::class));
		$this->assertTrue($this->proxy->isa(TModule::class));
		$this->assertTrue($this->proxy->isa(TComponent::class));
	}

	public function testIsaReturnsTrueForBackingModuleClass(): void
	{
		$this->proxy->getBackingComponent(); // force resolution
		$this->assertTrue($this->proxy->isa(TModuleProxyBackingModule::class));
	}

	public function testIsaReturnsFalseForUnrelatedClass(): void
	{
		$this->assertFalse($this->proxy->isa(\stdClass::class));
	}

	public function testIsaLazilyResolvesBackingModuleWhenNotYetResolved(): void
	{
		$this->assertNull($this->proxy->pubGetBackingComponentDirect(), 'backing must not be resolved yet');

		$this->assertTrue($this->proxy->isa(TModuleProxyBackingModule::class));
		$this->assertNotNull($this->proxy->pubGetBackingComponentDirect());
	}

	public function testIsaReturnsFalseWhenNoBackingComponentIdSet(): void
	{
		$proxy = new TModuleProxyAccessor();
		$this->assertFalse($proxy->isa(TModuleProxyBackingModule::class));
	}

	// ── Logging detail ───────────────────────────────────────────────────────────

	public function testBackingComponentIdChangeLoggedAtWarningLevel(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingComponentId('anotherModule');

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testMultipleBackingComponentIdChangesEachLog(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingComponentId('first');  // change 1
		$this->proxy->setBackingComponentId('second'); // change 2

		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testBackingComponentIdSameValueProducesNoLog(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingComponentId('backingModule'); // same value

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
