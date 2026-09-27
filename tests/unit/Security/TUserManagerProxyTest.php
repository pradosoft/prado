<?php

/**
 * TUserManagerProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\Security\IUserManager;
use Prado\Security\TUserManager;
use Prado\Security\TUserManagerPasswordMode;
use Prado\Security\TUserManagerProxy;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Web\THttpCookie;
use Prado\Xml\TXmlDocument;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TUserManager that exposes an event, for forwarding tests.
 */
class TUserManagerProxyBackingManager extends TUserManager
{
	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TModule that is not an IUserManager, for the invalid-type guard.
 */
class TUserManagerProxyNotAManager extends TModule
{
}

/**
 * Exposes protected internals of TUserManagerProxy.
 */
class TUserManagerProxyAccessor extends TUserManagerProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetUserManagerDirect(): ?TComponent
	{
		return $this->getUserManagerDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TUserManagerProxyTest class.
 *
 * Tests TUserManagerProxy: BackingUserManagerId, lazy resolution, init validation,
 * IModuleDependency, IUserManager delegation, transparency to TUserManager
 * properties, isa, clone, serialization, and event forwarding.
 *
 * @package Prado\Test\Unit\Security
 */
class TUserManagerProxyTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;
	private TUserManagerProxyBackingManager $backing;
	private TUserManagerProxyAccessor $proxy;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/../Caching/mockapp');

		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<users><user name="Joe" password="demo" roles="Reader, Writer"/></users>');
		$this->backing = new TUserManagerProxyBackingManager();
		$this->backing->setPasswordMode(TUserManagerPasswordMode::Clear);
		$this->backing->setGuestName('Visitor');
		$this->backing->init($config);
		$this->app->setModule('backingUsers', $this->backing);

		$this->proxy = new TUserManagerProxyAccessor();
		$this->proxy->setBackingUserManagerId('backingUsers');
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
		$this->assertInstanceOf(TUserManagerProxy::class, $this->proxy);
		$this->assertInstanceOf(IUserManager::class, $this->proxy);
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
		$this->assertInstanceOf(IProxy::class, $this->proxy);
		$this->assertNotInstanceOf(TUserManager::class, $this->proxy);
	}

	// ── BackingUserManagerId ─────────────────────────────────────────────────────

	public function testDefaultBackingUserManagerIdIsEmpty(): void
	{
		$this->assertSame('', (new TUserManagerProxy())->getBackingUserManagerId());
	}

	public function testSetBackingUserManagerIdChangeLogsWarningAndInvalidates(): void
	{
		$before = $this->countLogs();
		$this->proxy->init(null);
		$this->assertSame($this->backing, $this->proxy->getUserManager());

		$second = new TUserManager();
		$second->init(new TXmlDocument('1.0', 'utf8'));
		$this->app->setModule('secondUsers', $second);
		$this->proxy->setBackingUserManagerId('secondUsers');

		$this->assertSame($before + 1, $this->countLogs());
		$this->assertNull($this->proxy->pubGetUserManagerDirect());
		$this->assertSame($second, $this->proxy->getUserManager());
	}

	public function testSetBackingUserManagerIdSameValueDoesNotLog(): void
	{
		$before = $this->countLogs();
		$this->proxy->setBackingUserManagerId('backingUsers');
		$this->assertSame($before, $this->countLogs());
	}

	// ── Dependencies / init ──────────────────────────────────────────────────────

	public function testGetModuleDependencies(): void
	{
		$this->assertNull((new TUserManagerProxy())->getModuleDependencies());
		$this->assertSame('backingUsers', $this->proxy->getModuleDependencies());
		$this->assertSame('backingUsers', $this->proxy->getModuleDependencies(true));
	}

	public function testInitThrowsWhenBackingUserManagerIdIsEmpty(): void
	{
		try {
			(new TUserManagerProxy())->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('usermanagerproxy_backing_user_manager_id_required', $e->getErrorCode());
		}
	}

	// ── getUserManager() ─────────────────────────────────────────────────────────

	public function testGetUserManagerResolvesAndCaches(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetUserManagerDirect());
		$this->assertSame($this->backing, $this->proxy->getUserManager());
		$this->assertSame($this->backing, $this->proxy->pubGetUserManagerDirect());
		$this->assertSame($this->backing, $this->proxy->getProxyBacking());
	}

	public function testGetUserManagerThrowsWhenModuleNotFound(): void
	{
		$this->proxy->setBackingUserManagerId('noSuchModule');
		try {
			$this->proxy->getUserManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('usermanagerproxy_user_manager_not_found', $e->getErrorCode());
		}
	}

	public function testGetUserManagerThrowsWhenModuleIsNotAUserManager(): void
	{
		$other = new TUserManagerProxyNotAManager();
		$other->init(null);
		$this->app->setModule('notAManager', $other);
		$this->proxy->setBackingUserManagerId('notAManager');
		try {
			$this->proxy->getUserManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('usermanagerproxy_invalid_user_manager_type', $e->getErrorCode());
		}
	}

	// ── IUserManager delegation ──────────────────────────────────────────────────

	public function testGuestNameDelegates(): void
	{
		$this->assertSame('Visitor', $this->proxy->getGuestName());
	}

	public function testGetUserDelegates(): void
	{
		$user = $this->proxy->getUser('joe');
		$this->assertNotNull($user);
		$this->assertSame('joe', $user->getName());
		$this->assertSame($this->backing, $user->getManager());
		$this->assertTrue($this->proxy->getUser()->getIsGuest());
		$this->assertNull($this->proxy->getUser('nobody'));
	}

	public function testValidateUserDelegates(): void
	{
		$this->assertTrue($this->proxy->validateUser('joe', 'demo'));
		$this->assertFalse($this->proxy->validateUser('joe', 'wrong'));
	}

	public function testGetUserFromCookieDelegates(): void
	{
		$cookie = new THttpCookie('auth', '');
		$this->assertNull($this->proxy->getUserFromCookie($cookie));
	}

	public function testSaveUserToCookieDelegates(): void
	{
		// TUserManager::saveUserToCookie() reads the application user; without one it fails on
		// the backing, which proves the call reached the backing rather than the proxy.
		$this->expectException(\Error::class);
		$this->proxy->saveUserToCookie(new THttpCookie('auth', ''));
	}

	public function testTUserManagerPropertiesReachTheBacking(): void
	{
		$this->assertSame(TUserManagerPasswordMode::Clear, $this->proxy->PasswordMode);
		$this->assertArrayHasKey('joe', $this->proxy->getUsers());
		$this->assertSame(['Reader', 'Writer'], $this->proxy->getRoles()['joe']);
	}

	// ── isa / clone / serialization ──────────────────────────────────────────────

	public function testIsaIncludesBackingClass(): void
	{
		$this->assertTrue($this->proxy->isa(TUserManager::class));
		$this->assertTrue($this->proxy->isa(IUserManager::class));
		$this->assertFalse($this->proxy->isa(\stdClass::class));
	}

	public function testCloneClearsBackingAndKeepsId(): void
	{
		$this->proxy->getUserManager();
		$clone = clone $this->proxy;
		$this->assertNull($clone->pubGetUserManagerDirect());
		$this->assertSame('backingUsers', $clone->getBackingUserManagerId());
		$this->assertSame($this->backing, $clone->getUserManager());
	}

	public function testZappableSleepProps(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);
		$this->assertContains("\0" . TUserManagerProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TUserManagerProxy::class . "\0_proxyEventNames", $exprops);
		$this->assertNotContains("\0" . TUserManagerProxy::class . "\0_backingUserManagerId", $exprops);

		$exprops = [];
		(new TUserManagerProxyAccessor())->pubGetZappableSleepProps($exprops);
		$this->assertContains("\0" . TUserManagerProxy::class . "\0_backingUserManagerId", $exprops);
	}

	// ── Events ───────────────────────────────────────────────────────────────────

	public function testBackingEventForwardsToProxyHandlers(): void
	{
		$this->proxy->getUserManager();
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
		return count(Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.security'));
	}
}
