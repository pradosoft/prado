<?php

/**
 * TPermissionsManagerProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Security\Permissions;

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionsBehavior;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\Permissions\TPermissionsManagerProxy;
use Prado\Security\Permissions\TUserPermissionsBehavior;
use Prado\Security\TAuthorizationRule;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Util\Log\TLogger;
use Prado\Util\TDbParameterModule;
use Prado\Xml\TXmlDocument;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TPermissionsManager subclass with a custom property, a custom method, and a
 * public on[A-Z]* event, to exercise forwarding through the proxy.
 */
class TPermissionsManagerProxyBackend extends TPermissionsManager
{
	/** @var null|string used to exercise __get/__set/__isset/__unset forwarding */
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
		return 'perm:' . $arg;
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TModule that is NOT a TPermissionsManager, used to test the invalid-type guard.
 */
class TPermissionsManagerProxyNotAManagerModule extends TModule
{
}

/**
 * Exposes protected internals for direct testing.
 */
class TPermissionsManagerProxyAccessor extends TPermissionsManagerProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetPermissionsManagerDirect(): ?TPermissionsManager
	{
		return $this->getPermissionsManagerDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TPermissionsManagerProxyTest class.
 *
 * Tests TPermissionsManagerProxy: BackingPermissionsManagerId property, lazy
 * resolution, init() validation, rule configuration rejection, IModuleDependency,
 * delegation of the TPermissionsManager API, change logging, isa transparency,
 * cloning, serialization, and event forwarding.
 */
class TPermissionsManagerProxyTest extends \PHPUnit\Framework\TestCase
{
	private const CATEGORY = 'prado.security.permissions';

	private ?TTestApplication $app = null;

	private TPermissionsManagerProxyBackend $backend;

	private TPermissionsManagerProxyAccessor $proxy;

	/** @var TPermissionsManagerProxy[] every proxy built by a test, destructed while the application is alive */
	private array $proxies = [];

	/** @var TPermissionsManager[] every extra backing built by a test, destructed while the application is alive */
	private array $backends = [];

	protected function setUp(): void
	{
		// TTestApplication snapshots the global TComponent state before the
		// fx* handler clears; restoreApplication() writes it back in tearDown.
		$this->app = new TTestApplication(__DIR__ . '/../app');
		Prado::getApplication()->getEventHandlers('fxattachclassbehavior')->clear();
		Prado::getApplication()->getEventHandlers('fxdetachclassbehavior')->clear();

		// A fully initialized backing manager without the automatic rules, so
		// rule counts in the delegation tests are exact.
		$this->backend = new TPermissionsManagerProxyBackend();
		$this->backend->setAutoAllowWithPermission(false);
		$this->backend->setAutoPresetRules(false);
		$this->backend->setAutoDenyAll(false);
		$this->backend->init(null);
		$this->app->setModule('backingPermissions', $this->backend);

		// Build the proxy but do NOT call init(); tests that need it call it.
		$this->proxy = $this->newProxy('backingPermissions');
	}

	protected function tearDown(): void
	{
		// The TPermissionsBehavior attached to each proxy needs the application
		// during clearBehaviors(), so every proxy is destructed before the
		// application is restored.
		foreach ($this->proxies as $proxy) {
			$proxy->__destruct();
		}
		$this->proxies = [];
		foreach ($this->backends as $backend) {
			$backend->__destruct();
		}
		$this->backends = [];
		$this->backend->__destruct();
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTPermissionsManagerProxy(): void
	{
		$this->assertInstanceOf(TPermissionsManagerProxy::class, $this->proxy);
	}

	public function testExtendsTPermissionsManager(): void
	{
		$this->assertInstanceOf(TPermissionsManager::class, $this->proxy);
		$this->assertInstanceOf(IPermissions::class, $this->proxy);
	}

	public function testImplementsIModuleDependencyAndIProxy(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	public function testDefaultBackingPermissionsManagerIdIsEmptyString(): void
	{
		$fresh = $this->newProxy();
		$this->assertSame('', $fresh->getBackingPermissionsManagerId());
	}

	// ── BackingPermissionsManagerId ──────────────────────────────────────────────

	public function testSetGetBackingPermissionsManagerId(): void
	{
		$proxy = $this->newProxy('myManager');
		$this->assertSame('myManager', $proxy->getBackingPermissionsManagerId());
	}

	public function testSetBackingPermissionsManagerIdSameValueProducesNoLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, self::CATEGORY);
		$this->proxy->setBackingPermissionsManagerId('backingPermissions');
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, self::CATEGORY));
	}

	public function testSetBackingPermissionsManagerIdFromEmptyDoesNotLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, self::CATEGORY);
		$proxy = $this->newProxy('backingPermissions');
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, self::CATEGORY));
	}

	public function testSetBackingPermissionsManagerIdChangeLogsWarningWithBothIds(): void
	{
		$before = $this->countLogs(TLogger::WARNING, self::CATEGORY);
		$this->proxy->setBackingPermissionsManagerId('replacementManager');
		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, self::CATEGORY));

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, self::CATEGORY);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingPermissions', $msg);
		$this->assertStringContainsString('replacementManager', $msg);
	}

	public function testMultipleBackingPermissionsManagerIdChangesEachLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, self::CATEGORY);
		$this->proxy->setBackingPermissionsManagerId('first');
		$this->proxy->setBackingPermissionsManagerId('second');
		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, self::CATEGORY));
	}

	public function testSetBackingPermissionsManagerIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getPermissionsManager();

		$second = $this->newBackend('secondPermissions');
		$this->proxy->setBackingPermissionsManagerId('secondPermissions');

		$this->assertNotSame($first, $this->proxy->getPermissionsManager());
		$this->assertSame($second, $this->proxy->getPermissionsManager());
	}

	public function testGetProxyOwnPropertyUsesProxyGetter(): void
	{
		$this->assertSame('backingPermissions', $this->proxy->BackingPermissionsManagerId);
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = $this->newProxy();
		$this->assertNull($proxy->getModuleDependencies());
		$this->assertNull($proxy->getModuleDependencies(true));
	}

	public function testGetModuleDependenciesReturnsBackingIdInBothPasses(): void
	{
		$this->assertSame('backingPermissions', $this->proxy->getModuleDependencies());
		$this->assertSame('backingPermissions', $this->proxy->getModuleDependencies(true));
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertTrue($this->proxy->getIsInitialized());
	}

	public function testInitThrowsWhenIdIsEmpty(): void
	{
		$proxy = $this->newProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_backing_permissions_manager_id_required', $e->getErrorCode());
		}
	}

	public function testInitTwiceThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->init(null);
	}

	public function testInitDoesNotAttachSecondClassBehaviorSet(): void
	{
		$attachBefore = TPermissionsBehavior::class;
		$this->proxy->init(null);

		// The proxy holds the class behavior the backing attached, and nothing more.
		$this->assertInstanceOf($attachBefore, $this->proxy->asa(TPermissionsManager::PERMISSIONS_BEHAVIOR));
		$this->assertCount(1, $this->proxy->getBehaviors());
	}

	public function testInitDoesNotRegisterDuplicatePermissions(): void
	{
		// The backend registered the three built-in permissions in its init();
		// the proxy's TPermissionsBehavior registers none.
		$this->proxy->init(null);
		$this->assertSame([], $this->proxy->getPermissions($this->backend));
		$this->assertSame(
			[
				TPermissionsManager::PERM_PERMISSIONS_SHELL,
				TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES,
				TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES,
			],
			array_keys($this->backend->getPermissionRules(null))
		);
	}

	public function testInitDoesNotAttachAuthenticationHandler(): void
	{
		$count = $this->app->getEventHandlers('onAuthenticationComplete')->getCount();
		$this->proxy->init(null);
		$this->assertSame($count, $this->app->getEventHandlers('onAuthenticationComplete')->getCount());
	}

	// ── init() rule configuration rejection ─────────────────────────────────────

	public function testInitThrowsWhenXmlConfigCarriesRole(): void
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module><role name="Editor" children="author" /></module>');
		try {
			$this->proxy->init($config);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_rules_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitThrowsWhenXmlConfigCarriesPermissionRule(): void
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module><permissionrule name="*" action="deny" /></module>');
		try {
			$this->proxy->init($config);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_rules_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitThrowsWhenPhpConfigCarriesRoles(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->proxy->init(['roles' => ['Editor' => 'author']]);
	}

	public function testInitThrowsWhenPhpConfigCarriesPermissionRules(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->proxy->init(['permissionrules' => [['name' => '*', 'action' => 'deny']]]);
	}

	public function testInitAcceptsXmlConfigWithoutRules(): void
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module />');
		$this->proxy->init($config);
		$this->assertTrue($this->proxy->getIsInitialized());
	}

	public function testSetPermissionFileThrows(): void
	{
		try {
			$this->proxy->setPermissionFile('Application.Permissions');
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_rules_not_allowed', $e->getErrorCode());
		}
		$this->assertNull($this->proxy->getPermissionFile());
	}

	// ── getPermissionsManager() lazy resolution ─────────────────────────────────

	public function testGetPermissionsManagerReturnsBackingModule(): void
	{
		$this->assertSame($this->backend, $this->proxy->getPermissionsManager());
		$this->assertSame($this->backend, $this->proxy->getProxyBacking());
	}

	public function testGetPermissionsManagerCachesResolvedReference(): void
	{
		$this->assertNull($this->proxy->pubGetPermissionsManagerDirect());
		$first = $this->proxy->getPermissionsManager();
		$this->assertSame($first, $this->proxy->pubGetPermissionsManagerDirect());
		$this->assertSame($first, $this->proxy->getPermissionsManager());
	}

	public function testGetPermissionsManagerThrowsWhenIdIsEmpty(): void
	{
		$proxy = $this->newProxy();
		try {
			$proxy->getPermissionsManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_backing_permissions_manager_id_required', $e->getErrorCode());
		}
	}

	public function testGetPermissionsManagerThrowsWhenModuleNotFound(): void
	{
		$proxy = $this->newProxy('missingModule');
		try {
			$proxy->getPermissionsManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_permissions_manager_not_found', $e->getErrorCode());
		}
	}

	public function testGetPermissionsManagerThrowsWhenModuleIsNotAManager(): void
	{
		$notAManager = new TPermissionsManagerProxyNotAManagerModule();
		$this->app->setModule('notAManager', $notAManager);

		$proxy = $this->newProxy('notAManager');
		try {
			$proxy->getPermissionsManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('permissionsmanagerproxy_invalid_permissions_manager_type', $e->getErrorCode());
		} finally {
			$notAManager->unlisten();
		}
	}

	// ── getManager() / getModulesByType ─────────────────────────────────────────

	public function testGetManagerResolvesToBackingNotProxy(): void
	{
		$this->app->setModule('permissionsProxy', $this->proxy);
		$this->proxy->init(null);

		$this->assertSame($this->backend, TPermissionsManager::getManager());
		$ids = array_keys($this->app->getModulesByType(TPermissionsManager::class));
		$this->assertContains('backingPermissions', $ids);
		$this->assertNotContains('permissionsProxy', $ids);
	}

	// ── Delegation: permissions and rules ───────────────────────────────────────

	public function testRegisterPermissionDelegatesToBacking(): void
	{
		$this->proxy->init(null);
		$this->proxy->registerPermission('proxy_perm', 'Proxied permission');

		$this->assertSame('Proxied permission', $this->backend->getPermissionDescription('proxy_perm'));
		$this->assertSame('Proxied permission', $this->proxy->getPermissionDescription('proxy_perm'));
		$this->assertContains('proxy_perm', $this->backend->getHierarchyRoleChildren('all'));
		$this->assertInstanceOf(TAuthorizationRuleCollection::class, $this->proxy->getPermissionRules('proxy_perm'));
		$this->assertSame($this->backend->getPermissionRules('proxy_perm'), $this->proxy->getPermissionRules('proxy_perm'));
	}

	public function testGetPermissionRulesNullReturnsAllBackingRules(): void
	{
		$this->assertSame($this->backend->getPermissionRules(null), $this->proxy->getPermissionRules(null));
	}

	public function testLoadPermissionsDataAndHierarchyDelegate(): void
	{
		$this->proxy->loadPermissionsData([
			'roles' => ['Editor' => 'author, commenter'],
			'permissionrules' => [['name' => 'editor', 'action' => 'deny', 'roles' => 'Default']],
		]);

		$this->assertSame(['author', 'commenter'], $this->proxy->getHierarchyRoleChildren('Editor'));
		$this->assertSame($this->backend->getHierarchyRoles(), $this->proxy->getHierarchyRoles());
		$this->assertContains('editor', $this->proxy->getHierarchyRoles());
		$this->assertTrue($this->proxy->isInHierarchy('Editor', 'commenter'));
		$this->assertFalse($this->proxy->isInHierarchy('Editor', 'publisher'));
	}

	public function testIsInHierarchyPassesCheckedByReference(): void
	{
		$this->proxy->loadPermissionsData(['roles' => ['Editor' => 'author']]);
		$checked = [];
		$this->assertTrue($this->proxy->isInHierarchy(['editor'], 'author', $checked));
		$this->assertArrayHasKey('editor', $checked);
	}

	public function testAddAndRemovePermissionRuleDelegate(): void
	{
		$dbparam = new TDbParameterModule();
		$dbparam->unlisten();
		$dbparam->init(null);
		$backend = $this->newBackend('dbPermissions', fn ($m) => $m->setDbParameter($dbparam));
		$proxy = $this->newProxy('dbPermissions');

		try {
			$this->assertSame([], $proxy->getDbConfigPermissionRules());

			$rule = new TAuthorizationRule('deny', '', '', '', '', 5000);
			$this->assertTrue($proxy->addPermissionRule('*', $rule));
			$this->assertSame(['*' => [$rule]], $proxy->getDbConfigPermissionRules());
			$this->assertSame($backend->getDbConfigPermissionRules(), $proxy->getDbConfigPermissionRules());

			$this->assertTrue($proxy->removePermissionRule('*', $rule));
			$this->assertSame([], $proxy->getDbConfigPermissionRules());
		} finally {
			$dbparam->remove($backend->getLoadParameter());
			unset($this->app->getParameters()[$backend->getLoadParameter()]);
			$dbparam->__destruct();
		}
	}

	public function testAddAndRemoveRoleChildrenDelegate(): void
	{
		$dbparam = new TDbParameterModule();
		$dbparam->unlisten();
		$dbparam->init(null);
		$backend = $this->newBackend('dbPermissions', fn ($m) => $m->setDbParameter($dbparam));
		$proxy = $this->newProxy('dbPermissions');

		try {
			$this->assertSame([], $proxy->getDbConfigRoles());

			$this->assertTrue($proxy->addRoleChildren('Administrator', 'Developer, Manager'));
			$this->assertSame(['developer', 'manager'], $backend->getHierarchyRoleChildren('Administrator'));
			$this->assertSame(['administrator' => ['developer', 'manager']], $proxy->getDbConfigRoles());

			$this->assertTrue($proxy->removeRoleChildren('Administrator', ['developer', 'manager']));
			$this->assertNull($proxy->getHierarchyRoleChildren('Administrator'));
			$this->assertSame([], $proxy->getDbConfigRoles());
		} finally {
			$dbparam->remove($backend->getLoadParameter());
			unset($this->app->getParameters()[$backend->getLoadParameter()]);
			$dbparam->__destruct();
		}
	}

	public function testRuntimeMutationWithoutDbParameterReturnsFalse(): void
	{
		$this->assertFalse($this->proxy->addRoleChildren('Administrator', 'Developer'));
		$this->assertFalse($this->proxy->addPermissionRule('*', new TAuthorizationRule('deny')));
	}

	public function testRegisterShellActionDelegatesWithoutError(): void
	{
		$this->proxy->init(null);
		$this->proxy->registerShellAction($this->app, null);
		$this->assertTrue(true);
	}

	// ── Delegation: properties ──────────────────────────────────────────────────

	public function testPropertyGettersReadBacking(): void
	{
		$this->assertSame($this->backend->getSuperRoles(), $this->proxy->getSuperRoles());
		$this->assertSame($this->backend->getDefaultRoles(), $this->proxy->getDefaultRoles());
		$this->assertSame($this->backend->getPermissionFile(), $this->proxy->getPermissionFile());
		$this->assertSame($this->backend->getAutoRulePriority(), $this->proxy->getAutoRulePriority());
		$this->assertFalse($this->proxy->getAutoAllowWithPermission());
		$this->assertFalse($this->proxy->getAutoPresetRules());
		$this->assertFalse($this->proxy->getAutoDenyAll());
		$this->assertSame($this->backend->getAutoDenyAllPriority(), $this->proxy->getAutoDenyAllPriority());
		$this->assertSame($this->backend->getDbParameter(), $this->proxy->getDbParameter());
		$this->assertSame($this->backend->getLoadParameter(), $this->proxy->getLoadParameter());
		$this->assertSame($this->backend->getLoadParameter(), $this->proxy->LoadParameter);
	}

	public function testPropertySettersWriteUninitializedBacking(): void
	{
		$backend = $this->newBackend('freshPermissions', null, false);
		$proxy = $this->newProxy('freshPermissions');

		$proxy->setSuperRoles('Administrator, Manager');
		$proxy->setDefaultRoles(['Default']);
		$proxy->setAutoRulePriority(7);
		$proxy->setAutoAllowWithPermission(false);
		$proxy->setAutoPresetRules(false);
		$proxy->setAutoDenyAll(false);
		$proxy->setAutoDenyAllPriority(4000);
		$proxy->setDbParameter('someDbParameterId');
		$proxy->setLoadParameter('config:proxy:runtime');

		$this->assertSame(['Administrator', 'Manager'], $backend->getSuperRoles());
		$this->assertSame(['Default'], $backend->getDefaultRoles());
		$this->assertSame(7, $backend->getAutoRulePriority());
		$this->assertFalse($backend->getAutoAllowWithPermission());
		$this->assertFalse($backend->getAutoPresetRules());
		$this->assertFalse($backend->getAutoDenyAll());
		$this->assertSame(4000, $backend->getAutoDenyAllPriority());
		$this->assertSame('someDbParameterId', $backend->getDbParameter());
		$this->assertSame('config:proxy:runtime', $backend->getLoadParameter());
	}

	public function testPropertySettersOnInitializedBackingThrow(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->setSuperRoles('Administrator');
	}

	// ── __call / __get / __set / __isset / __unset passthrough ─────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertSame('perm:hello', $this->proxy->customMethod('hello'));
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
		$this->proxy->init(null);
		$this->assertSame('value', $this->proxy->dyCustomEvent('value'));
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	public function testMagicPropertyAccessForwardsToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));

		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backend->getCustomProp());
		$this->assertSame('world', $this->proxy->CustomProp);
		$this->assertTrue(isset($this->proxy->CustomProp));

		unset($this->proxy->CustomProp);
		$this->assertNull($this->backend->getCustomProp());
	}

	// ── Event forwarding ────────────────────────────────────────────────────────

	public function testBackingEventIsExposedAfterResolution(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
		$this->proxy->getPermissionsManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->assertInstanceOf(TWeakCallableCollection::class, $this->proxy->OnTestEvent);
		$this->assertNotSame($this->backend->getEventHandlers('OnTestEvent'), $this->proxy->OnTestEvent);
	}

	public function testHandlerAttachedOnProxyFiresForBackingEvent(): void
	{
		$this->proxy->getPermissionsManager();
		$fired = false;
		$sender = null;
		$this->proxy->OnTestEvent = function ($s) use (&$fired, &$sender) {
			$fired = true;
			$sender = $s;
		};
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
		$this->assertSame($this->backend, $sender);
	}

	public function testBackingIdChangeDetachesForwarders(): void
	{
		$this->proxy->getPermissionsManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->proxy->setBackingPermissionsManagerId('someOtherManager');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	// ── isa() transparency ──────────────────────────────────────────────────────

	public function testIsaReturnsTrueForOwnHierarchy(): void
	{
		$this->assertTrue($this->proxy->isa(TPermissionsManagerProxy::class));
		$this->assertTrue($this->proxy->isa(TPermissionsManager::class));
		$this->assertTrue($this->proxy->isa(IProxy::class));
	}

	public function testIsaLazilyResolvesBackingClass(): void
	{
		$this->assertNull($this->proxy->pubGetPermissionsManagerDirect());
		$this->assertTrue($this->proxy->isa(TPermissionsManagerProxyBackend::class));
		$this->assertNotNull($this->proxy->pubGetPermissionsManagerDirect());
	}

	public function testIsaReturnsFalseWhenUnresolvable(): void
	{
		$proxy = $this->newProxy();
		$this->assertFalse($proxy->isa(TPermissionsManagerProxyBackend::class));
		$this->assertFalse($this->proxy->isa(TPermissionsManagerProxyNotAManagerModule::class));
	}

	// ── __destruct ──────────────────────────────────────────────────────────────

	public function testDestructKeepsBackingClassBehaviors(): void
	{
		$this->proxy->init(null);
		$this->proxy->__destruct();

		$user = new TUser(new TUserManager());
		$this->assertInstanceOf(TUserPermissionsBehavior::class, $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR));
		$this->assertInstanceOf(TPermissionsBehavior::class, $this->backend->asa(TPermissionsManager::PERMISSIONS_BEHAVIOR));
	}

	// ── __clone ─────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingAndReresolves(): void
	{
		$this->proxy->init(null);
		$this->proxy->getPermissionsManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;
		$this->proxies[] = $clone;

		$this->assertNull($clone->pubGetPermissionsManagerDirect());
		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->assertSame('backingPermissions', $clone->getBackingPermissionsManagerId());
		$this->assertSame($this->backend, $clone->getPermissionsManager());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;
		$this->proxies[] = $clone;

		$second = $this->newBackend('secondPermissions');
		$clone->setBackingPermissionsManagerId('secondPermissions');

		$this->assertSame($this->backend, $this->proxy->getPermissionsManager());
		$this->assertSame($second, $clone->getPermissionsManager());
	}

	// ── _getZappableSleepProps ──────────────────────────────────────────────────

	public function testZappableAlwaysExcludesBackingAndForwarders(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TPermissionsManagerProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TPermissionsManagerProxy::class . "\0_proxyEventNames", $exprops);
	}

	public function testZappableExcludesBackingIdWhenEmpty(): void
	{
		$proxy = $this->newProxy();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);
		$this->assertContains("\0" . TPermissionsManagerProxy::class . "\0_backingPermissionsManagerId", $exprops);
	}

	public function testZappableKeepsBackingIdWhenNonEmpty(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);
		$this->assertNotContains("\0" . TPermissionsManagerProxy::class . "\0_backingPermissionsManagerId", $exprops);
	}

	public function testSerializationRoundTripReresolvesBacking(): void
	{
		$this->proxy->init(null);
		$this->proxy->getPermissionsManager();

		$restored = unserialize(serialize($this->proxy));
		$this->proxies[] = $restored;

		$this->assertInstanceOf(TPermissionsManagerProxy::class, $restored);
		$this->assertSame('backingPermissions', $restored->getBackingPermissionsManagerId());
		$this->assertSame($this->backend, $restored->getPermissionsManager());
	}

	// ── Helpers ─────────────────────────────────────────────────────────────────

	private function newProxy(string $backingId = ''): TPermissionsManagerProxyAccessor
	{
		$proxy = new TPermissionsManagerProxyAccessor();
		if ($backingId !== '') {
			$proxy->setBackingPermissionsManagerId($backingId);
		}
		$this->proxies[] = $proxy;
		return $proxy;
	}

	/**
	 * Registers another backing manager. One initialized TPermissionsManager owns
	 * the class behaviors at a time: a second IPermissions instance would register
	 * the built-in permissions with the first manager again, so the setUp backing
	 * is destructed (detaching its class behaviors) before the new one is built.
	 * @param string $id the module ID to register the backing under
	 * @param ?callable $configure receives the backing before init()
	 * @param bool $init whether to initialize the backing
	 */
	private function newBackend(string $id, ?callable $configure = null, bool $init = true): TPermissionsManagerProxyBackend
	{
		$this->backend->__destruct();
		$backend = new TPermissionsManagerProxyBackend();
		$backend->setAutoAllowWithPermission(false);
		$backend->setAutoPresetRules(false);
		$backend->setAutoDenyAll(false);
		if ($configure !== null) {
			$configure($backend);
		}
		if ($init) {
			$backend->init(null);
		}
		$this->app->setModule($id, $backend);
		$this->backends[] = $backend;
		return $backend;
	}

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
