<?php

/**
 * TUrlManagerProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Web\TUrlManager;
use Prado\Web\TUrlManagerProxy;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TUrlManager that records its calls and exposes a property and an event.
 */
class TUrlManagerProxyBackingManager extends TUrlManager
{
	public array $parsedCalls = [];
	private ?string $_customProp = null;

	public function getCustomProp(): ?string
	{
		return $this->_customProp;
	}

	public function setCustomProp(?string $value): void
	{
		$this->_customProp = $value;
	}

	public function constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems)
	{
		return 'backing:' . parent::constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems);
	}

	public function parseUrl()
	{
		$this->parsedCalls[] = true;
		return ['parsed' => 'by-backing'];
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TModule that is not a TUrlManager, for the invalid-type guard.
 */
class TUrlManagerProxyNotAManager extends TModule
{
}

/**
 * Exposes protected internals of TUrlManagerProxy.
 */
class TUrlManagerProxyAccessor extends TUrlManagerProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetUrlManagerDirect(): ?TUrlManager
	{
		return $this->getUrlManagerDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TUrlManagerProxyTest class.
 *
 * Tests TUrlManagerProxy: BackingUrlManagerId, lazy resolution, init validation,
 * IModuleDependency, constructUrl and parseUrl delegation, transparency, isa,
 * clone, serialization, and event forwarding.
 *
 * @package Prado\Test\Unit\Web
 */
class TUrlManagerProxyTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;
	private TUrlManagerProxyBackingManager $backing;
	private TUrlManagerProxyAccessor $proxy;

	protected function setUp(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/index.php?page=Home';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Home';

		$this->app = new TApplication(__DIR__ . '/../Caching/mockapp');
		$this->backing = new TUrlManagerProxyBackingManager();
		$this->backing->init(null);
		$this->app->setModule('backingUrl', $this->backing);

		$this->proxy = new TUrlManagerProxyAccessor();
		$this->proxy->setBackingUrlManagerId('backingUrl');
	}

	protected function tearDown(): void
	{
		$this->proxy->unlisten();
		$this->backing->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testInstanceAndInterfaces(): void
	{
		$this->assertInstanceOf(TUrlManagerProxy::class, $this->proxy);
		$this->assertInstanceOf(TUrlManager::class, $this->proxy);
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	// ── BackingUrlManagerId ──────────────────────────────────────────────────────

	public function testDefaultBackingUrlManagerIdIsEmpty(): void
	{
		$this->assertSame('', (new TUrlManagerProxy())->getBackingUrlManagerId());
	}

	public function testSetBackingUrlManagerIdChangeLogsWarningAndInvalidates(): void
	{
		$before = $this->countLogs();
		$this->proxy->init(null);
		$this->assertSame($this->backing, $this->proxy->getUrlManager());

		$second = new TUrlManagerProxyBackingManager();
		$second->init(null);
		$this->app->setModule('secondUrl', $second);
		$this->proxy->setBackingUrlManagerId('secondUrl');

		$this->assertSame($before + 1, $this->countLogs());
		$this->assertNull($this->proxy->pubGetUrlManagerDirect());
		$this->assertSame($second, $this->proxy->getUrlManager());
	}

	public function testSetBackingUrlManagerIdSameValueDoesNotLog(): void
	{
		$before = $this->countLogs();
		$this->proxy->setBackingUrlManagerId('backingUrl');
		$this->assertSame($before, $this->countLogs());
	}

	// ── Dependencies / init ──────────────────────────────────────────────────────

	public function testGetModuleDependencies(): void
	{
		$this->assertNull((new TUrlManagerProxy())->getModuleDependencies());
		$this->assertSame('backingUrl', $this->proxy->getModuleDependencies());
		$this->assertSame('backingUrl', $this->proxy->getModuleDependencies(true));
	}

	public function testInitThrowsWhenBackingUrlManagerIdIsEmpty(): void
	{
		try {
			(new TUrlManagerProxy())->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('urlmanagerproxy_backing_url_manager_id_required', $e->getErrorCode());
		}
	}

	// ── getUrlManager() ──────────────────────────────────────────────────────────

	public function testGetUrlManagerResolvesAndCaches(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetUrlManagerDirect());
		$this->assertSame($this->backing, $this->proxy->getUrlManager());
		$this->assertSame($this->backing, $this->proxy->pubGetUrlManagerDirect());
		$this->assertSame($this->backing, $this->proxy->getProxyBacking());
	}

	public function testGetUrlManagerThrowsWhenModuleNotFound(): void
	{
		$this->proxy->setBackingUrlManagerId('noSuchModule');
		try {
			$this->proxy->getUrlManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('urlmanagerproxy_url_manager_not_found', $e->getErrorCode());
		}
	}

	public function testGetUrlManagerThrowsWhenModuleIsNotAUrlManager(): void
	{
		$other = new TUrlManagerProxyNotAManager();
		$other->init(null);
		$this->app->setModule('notAManager', $other);
		$this->proxy->setBackingUrlManagerId('notAManager');
		try {
			$this->proxy->getUrlManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('urlmanagerproxy_invalid_url_manager_type', $e->getErrorCode());
		}
	}

	// ── Delegation ───────────────────────────────────────────────────────────────

	public function testConstructUrlDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$expected = $this->backing->constructUrl('page', 'Home', ['a' => '1'], false, true);
		$this->assertSame($expected, $this->proxy->constructUrl('page', 'Home', ['a' => '1'], false, true));
		$this->assertStringStartsWith('backing:', $expected);
	}

	public function testParseUrlDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertSame(['parsed' => 'by-backing'], $this->proxy->parseUrl());
		$this->assertCount(1, $this->backing->parsedCalls);
	}

	public function testPropertyAndMethodPassthrough(): void
	{
		$this->proxy->CustomProp = 'value';
		$this->assertSame('value', $this->backing->getCustomProp());
		$this->assertSame('value', $this->proxy->CustomProp);
		$this->assertTrue(isset($this->proxy->CustomProp));
	}

	public function testProxyIdIsItsOwn(): void
	{
		$this->proxy->setID('proxyId');
		$this->backing->setID('backingId');
		$this->assertSame('proxyId', $this->proxy->getID());
	}

	// ── isa / clone / serialization ──────────────────────────────────────────────

	public function testIsaIncludesBackingClass(): void
	{
		$this->assertTrue($this->proxy->isa(TUrlManagerProxyBackingManager::class));
		$this->assertTrue($this->proxy->isa(TUrlManager::class));
		$this->assertFalse($this->proxy->isa(\stdClass::class));
	}

	public function testCloneClearsBackingAndKeepsId(): void
	{
		$this->proxy->getUrlManager();
		$clone = clone $this->proxy;
		$this->assertNull($clone->pubGetUrlManagerDirect());
		$this->assertSame('backingUrl', $clone->getBackingUrlManagerId());
		$this->assertSame($this->backing, $clone->getUrlManager());
	}

	public function testZappableSleepProps(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);
		$this->assertContains("\0" . TUrlManagerProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TUrlManagerProxy::class . "\0_proxyEventNames", $exprops);
		$this->assertNotContains("\0" . TUrlManagerProxy::class . "\0_backingUrlManagerId", $exprops);

		$exprops = [];
		(new TUrlManagerProxyAccessor())->pubGetZappableSleepProps($exprops);
		$this->assertContains("\0" . TUrlManagerProxy::class . "\0_backingUrlManagerId", $exprops);
	}

	// ── Events ───────────────────────────────────────────────────────────────────

	public function testBackingEventForwardsToProxyHandlers(): void
	{
		$this->proxy->getUrlManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$sender = null;
		$this->proxy->OnTestEvent = function ($s) use (&$sender) {
			$sender = $s;
		};
		$this->backing->onTestEvent(new TEventParameter());
		$this->assertSame($this->backing, $sender);
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(): int
	{
		return count(Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.web'));
	}
}
