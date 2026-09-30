<?php

/**
 * TLogRouterProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TLogRouter;
use Prado\Util\Log\TLogRouterProxy;
use Prado\Xml\TXmlDocument;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TLogRouter subclass used as the backing module. Adds a property, methods,
 * an event, and a collectLogs counter to exercise forwarding.
 */
class TLogRouterProxyBackingRouter extends TLogRouter
{
	/** @var int count of collectLogs() calls */
	public int $collectCalls = 0;

	/** @var ?string used to exercise __get/__set/__isset/__unset forwarding */
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

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}

	public function collectLogs($logger, bool $final)
	{
		$this->collectCalls++;
		parent::collectLogs($logger, $final);
	}
}

/**
 * A TModule that is NOT a TLogRouter, used to test the invalid-type guard.
 */
class TLogRouterProxyNotARouterModule extends TModule
{
}

/**
 * Exposes protected internals for direct testing.
 */
class TLogRouterProxyAccessor extends TLogRouterProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetLogRouterDirect(): ?TLogRouter
	{
		return $this->getLogRouterDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TLogRouterProxyTest class.
 *
 * Tests TLogRouterProxy: BackingLogRouterId property, lazy router resolution,
 * init() validation, IModuleDependency, TLogRouter delegation, change logging,
 * isa transparency, cloning, serialization, and event forwarding.
 */
class TLogRouterProxyTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;

	private TLogRouterProxyBackingRouter $backing;

	private TLogRouterProxyAccessor $proxy;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/../../Caching/mockapp');

		$this->backing = new TLogRouterProxyBackingRouter();
		$this->backing->init([]);
		$this->app->setModule('backingLog', $this->backing);

		$this->proxy = new TLogRouterProxyAccessor();
		$this->proxy->setBackingLogRouterId('backingLog');
	}

	protected function tearDown(): void
	{
		Prado::getLogger()->detachEventHandler('onFlushLogs', [$this->backing, 'collectLogs']);
		$this->proxy->unlisten();
		$this->backing->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}

	private function countFlushHandlers(): int
	{
		return Prado::getLogger()->getEventHandlers('onFlushLogs')->getCount();
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testInstanceAndInterfaces(): void
	{
		$this->assertInstanceOf(TLogRouterProxy::class, $this->proxy);
		$this->assertInstanceOf(TLogRouter::class, $this->proxy);
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	public function testDefaultBackingLogRouterIdIsEmptyString(): void
	{
		$fresh = new TLogRouterProxy();
		$this->assertSame('', $fresh->getBackingLogRouterId());
	}

	// ── BackingLogRouterId ───────────────────────────────────────────────────────

	public function testSetGetBackingLogRouterId(): void
	{
		$proxy = new TLogRouterProxy();
		$proxy->setBackingLogRouterId('myLog');
		$this->assertSame('myLog', $proxy->getBackingLogRouterId());
		$this->assertSame('myLog', $proxy->BackingLogRouterId);
	}

	public function testSetBackingLogRouterIdFromEmptyDoesNotLog(): void
	{
		$cat = 'prado.util.log';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TLogRouterProxy();
		$proxy->setBackingLogRouterId('backingLog');

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingLogRouterIdSameValueProducesNoLog(): void
	{
		$cat = 'prado.util.log';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingLogRouterId('backingLog');

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingLogRouterIdChangeLogsWarningWithBothIds(): void
	{
		$cat = 'prado.util.log';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingLogRouterId('replacementLog');

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingLog', $msg);
		$this->assertStringContainsString('replacementLog', $msg);
	}

	public function testMultipleBackingLogRouterIdChangesEachLog(): void
	{
		$cat = 'prado.util.log';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingLogRouterId('first');
		$this->proxy->setBackingLogRouterId('second');

		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingLogRouterIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getLogRouter();

		$second = new TLogRouterProxyBackingRouter();
		$second->init([]);
		$this->app->setModule('secondLog', $second);

		$this->proxy->setBackingLogRouterId('secondLog');

		$this->assertNotSame($first, $this->proxy->getLogRouter());
		$this->assertSame($second, $this->proxy->getLogRouter());
		Prado::getLogger()->detachEventHandler('onFlushLogs', [$second, 'collectLogs']);
		$second->unlisten();
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = new TLogRouterProxy();
		$this->assertNull($proxy->getModuleDependencies());
		$this->assertNull($proxy->getModuleDependencies(true));
	}

	public function testGetModuleDependenciesReturnsBackingId(): void
	{
		$this->assertSame('backingLog', $this->proxy->getModuleDependencies());
		$this->assertSame('backingLog', $this->proxy->getModuleDependencies(true));
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenBackingLogRouterIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertSame('backingLog', $this->proxy->getBackingLogRouterId());
	}

	public function testInitThrowsWhenBackingLogRouterIdIsEmpty(): void
	{
		$proxy = new TLogRouterProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_backing_log_router_id_required', $e->getErrorCode());
		}
	}

	public function testInitDoesNotAttachSecondCollectLogsHandler(): void
	{
		$before = $this->countFlushHandlers();
		$this->proxy->init(null);
		$this->assertSame($before, $this->countFlushHandlers());
	}

	public function testInitDoesNotResolveBacking(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetLogRouterDirect());
	}

	public function testInitRejectsRoutesArrayKey(): void
	{
		try {
			$this->proxy->init(['routes' => [['class' => TBrowserLogRoute::class]]]);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_routes_not_allowed', $e->getErrorCode());
		}
		$this->assertSame(0, $this->backing->getRoutesCount());
	}

	public function testInitRejectsConfigFileArrayKey(): void
	{
		try {
			$this->proxy->init(['ConfigFile' => 'Application.Config.Log']);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_routes_not_allowed', $e->getErrorCode());
		}
		try {
			$this->proxy->init(['properties' => ['ConfigFile' => 'Application.Config.Log']]);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_routes_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitRejectsRouteXmlChild(): void
	{
		$dom = new TXmlDocument();
		$dom->loadFromString('<module id="log"><route class="Prado\Util\Log\TBrowserLogRoute" /></module>');
		try {
			$this->proxy->init($dom);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_routes_not_allowed', $e->getErrorCode());
		}
		$this->assertSame(0, $this->backing->getRoutesCount());
	}

	public function testInitRejectsConfigFileXmlAttribute(): void
	{
		$dom = new TXmlDocument();
		$dom->loadFromString('<module id="log" ConfigFile="Application.Config.Log" />');
		try {
			$this->proxy->init($dom);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_routes_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitAcceptsRoutelessConfig(): void
	{
		$dom = new TXmlDocument();
		$dom->loadFromString('<module id="log" BackingLogRouterId="backingLog" />');
		$this->proxy->init($dom);
		$this->proxy->init([]);
		$this->proxy->init(['properties' => ['BackingLogRouterId' => 'backingLog']]);
		$this->assertSame(0, $this->backing->getRoutesCount());
	}

	// ── getLogRouter() ──────────────────────────────────────────────────────────

	public function testGetLogRouterReturnsBackingModuleAndCachesIt(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backing, $this->proxy->getLogRouter());
		$this->assertSame($this->backing, $this->proxy->getLogRouter());
		$this->assertSame($this->backing, $this->proxy->pubGetLogRouterDirect());
		$this->assertSame($this->backing, $this->proxy->getProxyBacking());
		$this->assertSame($this->backing, $this->proxy->LogRouter);
	}

	public function testGetLogRouterThrowsWhenIdIsEmpty(): void
	{
		$proxy = new TLogRouterProxy();
		try {
			$proxy->getLogRouter();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_backing_log_router_id_required', $e->getErrorCode());
		}
	}

	public function testGetLogRouterThrowsWhenModuleNotFound(): void
	{
		$proxy = new TLogRouterProxy();
		$proxy->setBackingLogRouterId('missingModule');
		try {
			$proxy->getLogRouter();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_log_router_not_found', $e->getErrorCode());
		}
	}

	public function testGetLogRouterThrowsWhenModuleIsNotTLogRouter(): void
	{
		$notARouter = new TLogRouterProxyNotARouterModule();
		$this->app->setModule('notLog', $notARouter);

		$proxy = new TLogRouterProxy();
		$proxy->setBackingLogRouterId('notLog');
		try {
			$proxy->getLogRouter();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouterproxy_invalid_log_router_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$notARouter->unlisten();
		}
	}

	public function testSetLogRouterIsReadOnlyOnProxy(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->LogRouter = new TLogRouter();
	}

	// ── Route delegation ────────────────────────────────────────────────────────

	public function testAddRouteDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$route = new TBrowserLogRoute();
		$this->proxy->addRoute($route);

		$this->assertSame(1, $this->backing->getRoutesCount());
		$this->assertSame([$route], $this->backing->getRoutes());
		$this->assertSame(1, $this->proxy->getRoutesCount());
		$this->assertSame([$route], $this->proxy->getRoutes());
		$this->assertSame([$route], $this->proxy->Routes);
		$this->assertSame(1, $this->proxy->RoutesCount);
	}

	public function testAddRouteInvalidTypeThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidDataTypeException::class);
		$this->proxy->addRoute(new \stdClass());
	}

	public function testRoutesAddedOnBackingVisibleThroughProxy(): void
	{
		$this->proxy->init(null);
		$route = new TBrowserLogRoute();
		$this->backing->addRoute($route);

		$this->assertSame([$route], $this->proxy->getRoutes());
	}

	public function testRemoveRouteDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$route1 = new TBrowserLogRoute();
		$route2 = new TBrowserLogRoute();
		$this->proxy->addRoute($route1);
		$this->proxy->addRoute($route2);

		$this->assertSame($route1, $this->proxy->removeRoute(0));
		$this->assertSame([$route2], $this->backing->getRoutes());
		$this->assertSame($route2, $this->proxy->removeRoute($route2));
		$this->assertSame(0, $this->proxy->getRoutesCount());
		$this->assertNull($this->proxy->removeRoute($route1));
	}

	public function testCollectLogsDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$before = $this->backing->collectCalls;
		$this->proxy->collectLogs(Prado::getLogger(), false);
		$this->assertSame($before + 1, $this->backing->collectCalls);
	}

	// ── ConfigFile / FlushCount / TraceLevel delegation ─────────────────────────

	public function testConfigFileDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->getConfigFile());

		Prado::setPathOfAlias('TLogRouterProxyTestDir', __DIR__);
		$this->proxy->setConfigFile('TLogRouterProxyTestDir.log');
		$this->assertSame($this->backing->getConfigFile(), $this->proxy->getConfigFile());
		$this->assertSame(__DIR__ . DIRECTORY_SEPARATOR . 'log.xml', $this->proxy->getConfigFile());
	}

	public function testSetConfigFileInvalidThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TConfigurationException::class);
		$this->proxy->setConfigFile('Not.A.Real.Namespace.File');
	}

	public function testFlushCountForwardsToLogger(): void
	{
		$this->proxy->init(null);
		$logger = Prado::getLogger();
		$original = $logger->getFlushCount();
		try {
			$this->assertSame($this->proxy, $this->proxy->setFlushCount('250'));
			$this->assertSame(250, $logger->getFlushCount());
			$this->assertSame(250, $this->proxy->getFlushCount());
			$this->assertSame(250, $this->backing->getFlushCount());
		} finally {
			$logger->setFlushCount($original);
		}
	}

	public function testTraceLevelForwardsToLogger(): void
	{
		$this->proxy->init(null);
		$logger = Prado::getLogger();
		$original = $logger->getTraceLevel();
		try {
			$this->assertSame($this->proxy, $this->proxy->setTraceLevel('3'));
			$this->assertSame(3, $logger->getTraceLevel());
			$this->assertSame(3, $this->proxy->getTraceLevel());
			$this->assertSame(3, $this->backing->getTraceLevel());
		} finally {
			$logger->setTraceLevel($original);
		}
	}

	// ── isa transparency ────────────────────────────────────────────────────────

	public function testIsaReportsBackingClass(): void
	{
		$this->proxy->init(null);
		$this->assertTrue($this->proxy->isa(TLogRouterProxy::class));
		$this->assertTrue($this->proxy->isa(TLogRouter::class));
		$this->assertTrue($this->proxy->isa(TLogRouterProxyBackingRouter::class));
		$this->assertFalse($this->proxy->isa(TBrowserLogRoute::class));
	}

	public function testIsaWithUnresolvableBackingDoesNotThrow(): void
	{
		$proxy = new TLogRouterProxy();
		$proxy->setBackingLogRouterId('missingModule');
		$this->assertTrue($proxy->isa(TLogRouter::class));
		$this->assertFalse($proxy->isa(TLogRouterProxyBackingRouter::class));
	}

	// ── __call / __get / __set passthrough ──────────────────────────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertSame('custom:hello', $this->proxy->customMethod('hello'));
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	public function testPropertyPassthroughToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backing->getCustomProp());
		$this->assertSame('world', $this->proxy->CustomProp);
		$this->assertTrue(isset($this->proxy->CustomProp));
		unset($this->proxy->CustomProp);
		$this->assertNull($this->backing->getCustomProp());
	}

	public function testProxyIdStaysItsOwn(): void
	{
		$this->proxy->setID('log');
		$this->backing->setID('backingLog');
		$this->proxy->init(null);
		$this->assertSame('log', $this->proxy->getID());
		$this->assertSame('log', $this->proxy->ID);
	}

	// ── Event forwarding ────────────────────────────────────────────────────────

	public function testBackingEventNotExposedBeforeResolution(): void
	{
		$this->proxy->init(null);
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testGetLogRouterAttachesBackingEvents(): void
	{
		$this->proxy->init(null);
		$this->proxy->getLogRouter();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testHandlerOnProxyRunsForBackingRaise(): void
	{
		$this->proxy->init(null);
		$this->proxy->getLogRouter();

		$sender = null;
		$this->proxy->OnTestEvent = function ($s, $p) use (&$sender) {
			$sender = $s;
		};
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertSame($this->backing, $sender);
	}

	public function testDetachProxyStopsForwarding(): void
	{
		$this->proxy->init(null);
		$this->proxy->getLogRouter();

		$fired = 0;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired++;
		};
		$this->proxy->detachProxy();
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertSame(0, $fired);
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingAndReresolves(): void
	{
		$this->proxy->init(null);
		$this->proxy->getLogRouter();

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetLogRouterDirect());
		$this->assertSame('backingLog', $clone->getBackingLogRouterId());
		$this->assertSame($this->backing, $clone->getLogRouter());
		$clone->unlisten();
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableAlwaysExcludesBackingAndForwarders(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TLogRouterProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TLogRouterProxy::class . "\0_proxyEventNames", $exprops);
		$this->assertNotContains("\0" . TLogRouterProxy::class . "\0_backingLogRouterId", $exprops);
	}

	public function testZappableExcludesBackingLogRouterIdWhenEmpty(): void
	{
		$proxy = new TLogRouterProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TLogRouterProxy::class . "\0_backingLogRouterId", $exprops);
	}

	public function testSerializationKeepsIdAndDropsBacking(): void
	{
		$this->proxy->init(null);
		$this->proxy->getLogRouter();

		$restored = unserialize(serialize($this->proxy));

		$this->assertInstanceOf(TLogRouterProxyAccessor::class, $restored);
		$this->assertSame('backingLog', $restored->getBackingLogRouterId());
		$this->assertNull($restored->pubGetLogRouterDirect());
		$this->assertSame($this->backing, $restored->getLogRouter());
		$restored->unlisten();
	}
}
