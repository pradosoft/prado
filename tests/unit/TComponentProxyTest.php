<?php

/**
 * TComponentProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\Collections\TWeakCallableCollection;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TComponentProxy;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A concrete TComponent used as the backing in TComponentProxy tests. Exposes a
 * custom property and method to exercise __get/__set/__call forwarding.
 */
class TComponentProxyBackingComponent extends TComponent
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
		return 'custom:' . $arg;
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
}

/**
 * A TBehavior with a public on[A-Z]* event, used to verify that attachProxy()
 * discovers events exposed by behaviors attached to the backing component.
 */
class TComponentProxyBehaviorWithEvent extends TBehavior
{
	public function onBehaviorEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnBehaviorEvent', $this->getOwner(), $param);
	}
}

/**
 * A second distinct backing class to test change logging.
 */
class TComponentProxyAltBackingComponent extends TComponent
{
}

/**
 * A TComponentProxy subclass that also owns OnTestEvent.
 * Used to verify that when both the proxy and the backing expose the same
 * on[A-Z]* event, the backing's raise still forwards through the proxy's list
 * (with $sender = the backing), while a direct proxy raise uses $sender = proxy.
 */
class TComponentProxyWithOwnEvent extends TComponentProxy
{
	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * Exposes protected internals of TComponentProxy for direct testing.
 */
class TComponentProxyAccessor extends TComponentProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetBackingComponentDirect(): ?TComponent
	{
		return $this->getBackingComponentDirect();
	}

	public function pubZappableExcludeBackingComponent(array &$exprops): void
	{
		$this->_zappableExcludeBackingComponent($exprops);
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TComponentProxyTest class.
 *
 * Tests TComponentProxy: direct BackingComponent injection, transparent method
 * and property delegation, event sharing via attachProxy, isa() transparency,
 * change logging, serialization, and edge cases.
 *
 * @package Prado\Tests\Unit
 */
class TComponentProxyTest extends PHPUnit\Framework\TestCase
{
	private static string $mockAppPath;

	/** @var TApplication|null */
	private ?TApplication $app = null;

	/** @var TComponentProxyBackingComponent */
	private TComponentProxyBackingComponent $backing;

	/** @var TComponentProxyAccessor */
	private TComponentProxyAccessor $proxy;

	public static function setUpBeforeClass(): void
	{
		self::$mockAppPath = __DIR__ . '/Caching/mockapp';
	}

	protected function setUp(): void
	{
		$this->app = new TApplication(self::$mockAppPath);

		$this->backing = new TComponentProxyBackingComponent();

		// Build the proxy and inject the backing directly.
		$this->proxy = new TComponentProxyAccessor();
		$this->proxy->setBackingComponent($this->backing);
	}

	protected function tearDown(): void
	{
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTComponentProxy(): void
	{
		$this->assertInstanceOf(TComponentProxy::class, $this->proxy);
	}

	public function testImplementsIProxy(): void
	{
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	public function testExtendsTComponent(): void
	{
		$this->assertInstanceOf(TComponent::class, $this->proxy);
	}

	public function testDoesNotExtendTModule(): void
	{
		$this->assertNotInstanceOf(TModule::class, $this->proxy);
	}

	// ── setBackingComponent / getBackingComponent ────────────────────────────────

	public function testSetGetBackingComponent(): void
	{
		$proxy = new TComponentProxy();
		$backing = new TComponentProxyBackingComponent();
		$proxy->setBackingComponent($backing);
		$this->assertSame($backing, $proxy->getBackingComponent());
	}

	public function testGetBackingComponentThrowsWhenNotSet(): void
	{
		$proxy = new TComponentProxy();
		$this->expectException(TConfigurationException::class);
		$proxy->getBackingComponent();
	}

	public function testGetBackingComponentThrowsExpectedErrorCode(): void
	{
		$proxy = new TComponentProxy();
		try {
			$proxy->getBackingComponent();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('componentproxy_backing_component_required', $e->getErrorCode());
		}
	}

	public function testSetBackingComponentSameValueIsNoOp(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingComponent($this->backing); // same object — no log

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
		$this->assertSame($this->backing, $this->proxy->getBackingComponent());
	}

	public function testSetBackingComponentFromNullDoesNotLog(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TComponentProxy();
		$proxy->setBackingComponent($this->backing); // first set from null — no log

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingComponentChangeLogsWarning(): void
	{
		$cat = 'prado.component';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$alt = new TComponentProxyAltBackingComponent();
		$this->proxy->setBackingComponent($alt); // changes from backing

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingComponentChangeLogContainsBothClassNames(): void
	{
		$cat = 'prado.component';
		$alt = new TComponentProxyAltBackingComponent();
		$this->proxy->setBackingComponent($alt);

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString(TComponentProxyBackingComponent::class, $msg);
		$this->assertStringContainsString(TComponentProxyAltBackingComponent::class, $msg);
	}

	// ── __call dispatch ──────────────────────────────────────────────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$result = $this->proxy->customMethod('hello');
		$this->assertSame('custom:hello', $result);
	}

	public function testCallForwardsMultipleArguments(): void
	{
		$result = $this->proxy->customMultiArgMethod('key', 42);
		$this->assertSame('key:42', $result);
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
		$result = $this->proxy->dyCustomEvent('value');
		$this->assertSame('value', $result);
	}

	public function testCallDoesNotForwardFxEvent(): void
	{
		$this->proxy->fxCustomGlobalEvent('value');

		$this->assertFalse(
			(new \ReflectionClass($this->backing))->hasMethod('fxCustomGlobalEvent'),
			'fxCustomGlobalEvent must not be a real method on the backing — proxy must not forward fx events.'
		);
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	// ── __get / __set / __isset / __unset passthrough ────────────────────────────

	public function testGetForwardsBackingSpecificPropertyToBackingComponent(): void
	{
		$this->backing->setCustomProp('hello');
		$this->assertSame('hello', $this->proxy->CustomProp);
	}

	public function testSetForwardsBackingSpecificPropertyToBackingComponent(): void
	{
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backing->getCustomProp());
	}

	public function testIssetReturnsFalseWhenBackingPropIsNull(): void
	{
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testIssetReturnsTrueWhenBackingPropIsSet(): void
	{
		$this->proxy->CustomProp = 'set';
		$this->assertTrue(isset($this->proxy->CustomProp));
	}

	public function testUnsetForwardsBackingSpecificPropertyToBackingComponent(): void
	{
		$this->proxy->CustomProp = 'before';
		unset($this->proxy->CustomProp);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testGetUndefinedPropertyThrows(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$_ = $this->proxy->CompletelyUndefinedProperty;
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingComponentReference(): void
	{
		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetBackingComponentDirect());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$clone = clone $this->proxy;

		$alt = new TComponentProxyAltBackingComponent();
		$clone->setBackingComponent($alt);

		$this->assertSame($this->backing, $this->proxy->getBackingComponent());
		$this->assertSame($alt, $clone->getBackingComponent());
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableExcludesProxyEventNames(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TComponentProxy::class . "\0_proxyEventNames",
			$exprops
		);
	}

	public function testZappablePreservesBackingComponent(): void
	{
		// TComponentProxy preserves _proxyBacking in serialization because
		// there is no module registry to re-resolve it from.
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TComponentProxy::class . "\0_proxyBacking",
			$exprops
		);
	}

	public function testZappableExcludeBackingComponentHelperAddsKey(): void
	{
		// _zappableExcludeBackingComponent delegates to _addProxyBackingZappable(),
		// so the key uses the using class name (TComponentProxy), not the trait name.
		$exprops = [];
		$this->proxy->pubZappableExcludeBackingComponent($exprops);

		$this->assertContains(
			"\0" . TComponentProxy::class . "\0_proxyBacking",
			$exprops
		);
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testHasEventReturnsFalseForBackingEventBeforeAttach(): void
	{
		// Before attachProxy() has run, the backing's OnTestEvent is not exposed.
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testProxyHandlerFiresWhenBackingRaisesEvent(): void
	{
		// Handlers registered on the proxy must fire when the backing raises the
		// event, because attachProxy() registers a forwarder on the backing that
		// calls through the proxy's own independent handler list.
		$this->proxy->attachProxy();

		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testProxyAndBackingHandlerListsAreIndependent(): void
	{
		// The proxy has its own TWeakCallableCollection for each forwarded event —
		// it is NOT the same object as the backing's collection.
		$this->proxy->attachProxy();

		$backingFired = false;
		$this->backing->OnTestEvent = function () use (&$backingFired) {
			$backingFired = true;
		};
		$proxyFired = false;
		$this->proxy->OnTestEvent = function () use (&$proxyFired) {
			$proxyFired = true;
		};

		$this->backing->onTestEvent(new TEventParameter());

		// Both fire — backing's own handler fires normally, and the forwarder calls
		// the proxy's list which fires the proxy handler.
		$this->assertTrue($backingFired);
		$this->assertTrue($proxyFired);

		// The collections themselves are separate objects.
		$this->assertNotSame(
			$this->backing->getEventHandlers('OnTestEvent'),
			$this->proxy->getEventHandlers('OnTestEvent')
		);
	}

	public function testGetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->attachProxy();

		$handlers = $this->proxy->OnTestEvent;
		$this->assertInstanceOf(TWeakCallableCollection::class, $handlers);
		// The proxy's collection is its own independent object.
		$this->assertNotSame($this->backing->getEventHandlers('OnTestEvent'), $handlers);
	}

	public function testSenderDifferentiatesBackingRaiseFromProxyRaise(): void
	{
		// When the proxy itself also owns the event, the backing's raise still
		// forwards through the proxy's list. $sender identifies the origin:
		// backing-raise → $sender = backing; proxy-raise → $sender = proxy.
		$proxy = new TComponentProxyWithOwnEvent();
		$proxy->setBackingComponent($this->backing);
		$proxy->attachProxy();

		$senders = [];
		$proxy->OnTestEvent = function ($sender) use (&$senders) {
			$senders[] = $sender;
		};

		// Backing raises the event — forwarder fires, sender = backing.
		$this->backing->onTestEvent(new TEventParameter());
		// Proxy raises the event directly — sender = proxy.
		$proxy->onTestEvent(new TEventParameter());

		$this->assertCount(2, $senders);
		$this->assertSame($this->backing, $senders[0]);
		$this->assertSame($proxy, $senders[1]);
	}

	public function testIssetOnEventViaPropertyAfterAttachProxy(): void
	{
		$this->proxy->attachProxy();

		$this->assertFalse(isset($this->proxy->OnTestEvent));

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));
	}

	public function testUnsetOnEventViaPropertyClearsHandlerCollection(): void
	{
		$this->proxy->attachProxy();

		$this->proxy->OnTestEvent = function () {};
		$this->assertTrue(isset($this->proxy->OnTestEvent));

		unset($this->proxy->OnTestEvent);
		$this->assertFalse(isset($this->proxy->OnTestEvent));
	}

	public function testDetachProxyClearsEventSharing(): void
	{
		$this->proxy->attachProxy();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testBackingComponentChangeCallsDetachProxy(): void
	{
		$this->proxy->attachProxy();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$alt = new TComponentProxyAltBackingComponent();
		$this->proxy->setBackingComponent($alt);
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testCloneDetachesProxy(): void
	{
		$this->proxy->attachProxy();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testAttachProxyIncludesBehaviorProvidedOnEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TComponentProxyBehaviorWithEvent());

		$this->proxy->attachProxy();

		$this->assertTrue(
			$this->proxy->hasEvent('OnBehaviorEvent'),
			'Proxy must expose on* events contributed by behaviors on the backing component.'
		);
	}

	public function testHandlerRegisteredViaProxyFiresForBehaviorEvent(): void
	{
		$this->backing->attachBehavior('testBehavior', new TComponentProxyBehaviorWithEvent());
		$this->proxy->attachProxy();

		$fired = false;
		$this->proxy->OnBehaviorEvent = function () use (&$fired) {
			$fired = true;
		};

		$behaviors = $this->backing->getBehaviors(TComponentProxyBehaviorWithEvent::class);
		/** @var TComponentProxyBehaviorWithEvent $beh */
		$beh = reset($behaviors);
		$beh->onBehaviorEvent(new TEventParameter());

		$this->assertTrue($fired, 'Handler added via proxy must fire when the behavior raises its event.');
	}

	// ── isa() — backing transparency ─────────────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		$this->assertTrue($this->proxy->isa(TComponentProxy::class));
		$this->assertTrue($this->proxy->isa(TComponent::class));
	}

	public function testIsaReturnsTrueForBackingClass(): void
	{
		$this->assertTrue($this->proxy->isa(TComponentProxyBackingComponent::class));
	}

	public function testIsaReturnsFalseForUnrelatedClass(): void
	{
		$this->assertFalse($this->proxy->isa(\stdClass::class));
	}

	public function testIsaReturnsFalseWhenNoBackingSet(): void
	{
		$proxy = new TComponentProxyAccessor();
		$this->assertFalse($proxy->isa(TComponentProxyBackingComponent::class));
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
