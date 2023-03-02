<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Web\Services\TPageConfiguration;
use Prado\Security\TUserManager;
use Prado\Util\TDbParameterModule;

class TPermissionsManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	protected $obj;
	
	protected function setUp(): void
	{
		// ini_set('session.use_cookies',0);
		// ini_set('session.cache_limiter', 'none');
		if (self::$app === null) {
			Prado::getApplication()->getEventHandlers('fxattachclassbehavior')->clear();
			Prado::getApplication()->getEventHandlers('fxdetachclassbehavior')->clear();
			self::$app = new TApplication(__DIR__ . '/../app');
		}
		
		$this->obj = new TPermissionsManager();
	}

	protected function tearDown(): void
	{
		$this->obj->__destruct();
		$this->obj = null;
	}
	
	public function testConstruct()
	{
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionsManager', $this->obj);
	}
	
	public function testInit()
	{
		self::assertNull($this->obj->asa(TPermissionsManager::PERMISSIONS_BEHAVIOR));
		$this->obj->init(null);
		
		//check class behaviors
		self::assertNotNull($this->obj->asa(TPermissionsManager::PERMISSIONS_BEHAVIOR));
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionsBehavior', $this->obj->asa(TPermissionsManager::PERMISSIONS_BEHAVIOR));
		
		$userManager = new TUserManager();
		$user = new TUser($userManager);
		self::assertNotNull($user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR));
		self::assertInstanceOf('Prado\\Security\\Permissions\\TUserPermissionsBehavior', $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR));
		
		$pageConfig = new TPageConfiguration('.');
		self::assertNotNull($pageConfig->asa(TPermissionsManager::PERMISSIONS_CONFIG_BEHAVIOR));
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionsConfigurationBehavior', $pageConfig->asa(TPermissionsManager::PERMISSIONS_CONFIG_BEHAVIOR));
		
		try {
			//Cannot re-initialize
			$this->obj->init(null);
			self::fail('failed to throw TInvalidOperationException when calling init twice or more');
		} catch(TInvalidOperationException $e) {}
		
		$this->obj->__destruct();
		
		$this->obj = new TPermissionsManager();
		
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		//load data from $config
		$this->obj->init(['roles' =>[
				'initRole' => 'Admin, Manager, Developer, Subscriber'
			],
			'permissionrules' => [
				['name' => '*', 'action' => 'deny', 'roles' => 'Default']
			]
		]);
		
		self::assertEquals(['all', 'initrole'], $this->obj->getHierarchyRoles());
		self::assertEquals(['admin', 'manager', 'developer', 'subscriber'], $this->obj->getHierarchyRoleChildren('initRole'));
		self::assertNotNull($rules = $this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES));
		self::assertInstanceOf('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(1, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals(['default'], $rules[0]->getRoles());
		
		// testPermissionFile tests load Data from PermissionFile
		// testDBParameter tests load Data from DBParameter
		// testSuperRoles tests Super Roles
	}
	
	public function testRegisterPermission()
	{
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		self::assertEquals(null, $this->obj->getHierarchyRoleChildren('all'));
		$this->obj->registerPermission('test_param', $descr = 'description of the parameter');
		
		// permission added to 'all'
		self::assertEquals(['test_param'], $this->obj->getHierarchyRoleChildren('all'));
		self::assertEquals($descr, $this->obj->getPermissionDescription('test_param'));
		
		// No duplicate permissions, throws error
		try {
			$this->obj->registerPermission('test_param', 'description');
			self::fail('failed to throw TInvalidOperationException when registering a permission a second time');
		} catch(TInvalidOperationException $e) {}
		
		// testAutoAllowWithPermission tests Auto role allow with permission
		// testAutoPresetRules tests Preset rules, and without preset rules
		// testAutoDenyAll tests Auto Deny All Rule & Priority.
		
		// apply autorule
		$this->obj->loadPermissionsData([
			'permissionrules' => [
				'test_permissions_2' => [$rule1 = new TAuthorizationRule('deny', '', 'Default')],
				'test_permissions*' => [$rule2 = new TUserOwnerRule()],
			]
		]);
		$this->obj->registerPermission('test_permissions_2', 'description');
		
		$rules = $this->obj->getPermissionRules('test_permissions_2');
		
		self::assertNotNull($rules);
		self::assertInstanceOf('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals([$rule1, $rule2], $rules->toArray());
	}
	
	public function testLoadPermissionsData()
	{
		$perm1 = TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES;
		$perm2 = TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES;
		$configXml = "<module id='permissions' superRoles='Administrator'>
		<role name='AdministratorXML' children='ManagerXML, DeveloperXML, Editor, Contributor, Subscriber, cron' />
		<role name='ManagerXML' children='{$perm1}, {$perm2}' />
		<role name='DeveloperXML' children='{$perm1}, {$perm2}' />
			<permissionrule name='{$perm1}' action='deny' roles='{$perm1}' />
		</module>";
		
		$dom = new TXmlDocument;
		$dom->loadFromString($configXml);
		
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		// Load XML
		$this->obj->loadPermissionsData($dom);
		$this->obj->init(null);
		
		//Test XML roles
		self::assertEquals(['administratorxml', 'managerxml', 'developerxml', 'all'], $this->obj->getHierarchyRoles());
		self::assertEquals(['managerxml', 'developerxml', 'editor', 'contributor', 'subscriber', 'cron'], $this->obj->getHierarchyRoleChildren('AdministratorXML'));
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES, TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], $this->obj->getHierarchyRoleChildren('ManagerXML'));
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES, TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], $this->obj->getHierarchyRoleChildren('DeveloperXML'));
		
		
		//Test XML Permission Rules
		$rules = $this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES);
		
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(1, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES], $rules[0]->getRoles());
		
		$this->obj->__destruct();
		
		$this->obj = new TPermissionsManager();
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		// Load php
		$phpData = [
			'roles' => [
				'AdministratorPHP' => "Managerphp, Developerphp, Editor, Contributor, Subscriber, cron",
				'ManagerPHP' => [$perm1, $perm2],
				'DeveloperPHP' => [$perm1, $perm2]
				
			], 'permissionrules' => [
				['name' => $perm2, 'action' => 'deny', 'roles' => $perm2]
			]
		];
		$this->obj->loadPermissionsData($phpData);
		$this->obj->init(null);
		
		//Test php roles
		//	children as string and array
		self::assertEquals(['administratorphp', 'managerphp', 'developerphp', 'all'], $this->obj->getHierarchyRoles());
		self::assertEquals(['managerphp', 'developerphp', 'editor', 'contributor', 'subscriber', 'cron'], $this->obj->getHierarchyRoleChildren('Administratorphp'));
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES, TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], $this->obj->getHierarchyRoleChildren('Managerphp'));
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES, TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], $this->obj->getHierarchyRoleChildren('Developerphp'));
		//Test php Permission Rules
		$rules = $this->obj->getPermissionRules($perm2);
		
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(1, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], $rules[0]->getRoles());
		
		$this->obj->__destruct();
		
		$this->obj = new TPermissionsManager();
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		$phpData = [
			'roles' => [
				'AdministratorDB' => "ManagerDb, DeveloperDB, Editor, Contributor, Subscriber, cron",
				'ManagerDb' => [$perm1, $perm2],
				'Developerdb' => [$perm1, $perm2]
				
			], 'permissionrules' => [
				$perm2 => [$rule = new TAuthorizationRule('deny', '', "subscriber")]
			]
		];
		//Test php TAuthorizationRule[] - for DbParameter
		$this->obj->loadPermissionsData($phpData);
		$this->obj->init(null);
		//Test php Permission Rules
		$rules = $this->obj->getPermissionRules($perm2);
		
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(1, count($rules));
		self::assertEquals($rule, $rules[0]);
		
		// testGetHierarchyRoles()
		// testGetHierarchyRoleChildren()
	}
	
	public function testAddRemovePermissionRuleInternal()
	{
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		$this->obj->registerPermission($perm1 = 'test_perm_1', 'description');
		$this->obj->registerPermission($perm2 = 'test_perm_2', 'description');
		$this->obj->registerPermission($perm3 = 'test_shell_1', 'description');
		$this->obj->registerPermission($perm4 = 'my_perm_1', 'description');
		$this->obj->registerPermission($perm5 = 'my_perm_2', 'description');
		
		$this->obj->loadPermissionsData([
			'roles' => [
				'Default' => 'my_perm_2, new_perm_2'
			],
			'permissionrules' => [
				// with * as name, * in name
				['name' => '*', 'action' => 'deny', 'priority' => '2000'],
				['name' => 'test_*', 'action' => 'allow', 'roles' => 'Developer'],
				// existing permission
				['name' => 'my_perm_1', 'action' => 'deny', 'roles' => 'Default'],
				// non-existing permission added to autorules
				['name' => 'new_perm_1', 'action' => 'allow', 'roles' => 'Administrator'],
				// rules apply down the hierarchy
				['name' => 'Default', 'action' => 'deny', 'roles' => 'Subscribers']
			]
		]);
		$rules = $this->obj->getPermissionRules($perm1);
		
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals(['developer'], $rules[0]->getRoles());
		self::assertEquals('allow', $rules[0]->getAction());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		$rules = $this->obj->getPermissionRules($perm2);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('allow', $rules[0]->getAction());
		self::assertEquals(['developer'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		$rules = $this->obj->getPermissionRules($perm3);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('allow', $rules[0]->getAction());
		self::assertEquals(['developer'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		
		$rules = $this->obj->getPermissionRules($perm4);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals(['default'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		$rules = $this->obj->getPermissionRules($perm5);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals(['subscribers'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		
		$rules = $this->obj->getPermissionRules($perm6 = 'new_perm_1');
		self::assertNull($rules);
		
		$rules = $this->obj->getPermissionRules($perm7 = 'new_perm_2');
		self::assertNull($rules);
		
		//		check autorules, apply on registerPermission
		$this->obj->registerPermission($perm6, 'description');
		$this->obj->registerPermission($perm7, 'description');
		
		$rules = $this->obj->getPermissionRules($perm6);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('allow', $rules[0]->getAction());
		self::assertEquals(['administrator'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		$rules = $this->obj->getPermissionRules($perm7);
		self::assertNotNull($rules);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
		self::assertEquals('deny', $rules[0]->getAction());
		self::assertEquals(['subscribers'], $rules[0]->getRoles());
		self::assertEquals('deny', $rules[1]->getAction());
		self::assertEquals(['*'], $rules[1]->getRoles());
		self::assertEquals(2000, $rules[1]->getPriority());
		
		//-- testRemovePermissionRuleInternal
		$this->obj->setDbParameter($dbparam = new TDbParameterModule());
		$dbparam->unlisten();
		$dbparam->init(null);
		
		// rules remove down the hierarchy
		$this->obj->removePermissionRule('Default', $this->obj->getPermissionRules($perm7)[0]);
		self::assertEquals(2, count($this->obj->getPermissionRules($perm1)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm2)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm3)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm4)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm5)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm6)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm7)));
		
		// with * in name
		$this->obj->removePermissionRule('test_*', $this->obj->getPermissionRules($perm1)[0]);
		self::assertEquals(1, count($this->obj->getPermissionRules($perm1)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm2)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm3)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm4)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm5)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm6)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm7)));
		
		$this->obj->removePermissionRule($perm4, $this->obj->getPermissionRules($perm4)[0]);
		self::assertEquals(1, count($this->obj->getPermissionRules($perm1)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm2)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm3)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm4)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm5)));
		self::assertEquals(2, count($this->obj->getPermissionRules($perm6)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm7)));
		
		$this->obj->removePermissionRule($perm6, $this->obj->getPermissionRules($perm6)[0]);
		self::assertEquals(1, count($this->obj->getPermissionRules($perm1)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm2)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm3)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm4)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm5)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm6)));
		self::assertEquals(1, count($this->obj->getPermissionRules($perm7)));
		
		// with * as name
		$this->obj->removePermissionRule('*', $this->obj->getPermissionRules($perm1)[0]);
		self::assertEquals(0, count($this->obj->getPermissionRules($perm1)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm2)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm3)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm4)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm5)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm6)));
		self::assertEquals(0, count($this->obj->getPermissionRules($perm7)));
		
		$dbparam->__destruct();
	}
	
	public function testIsInHierarchy()
	{
		$phpData = [
			'roles' => [
				'Administrator' => "Manager, Developer, Editor, Subscriber, cron",
				'Manager' => ['Editor', 'Developer'],
				'Super' => ['Administrator'],
				'Developer' => ['Super'],
				'Contributor' => 'blog_add, blog_update, blog_remove',
				'cron' => ['cron_shell', 'cron_log_read', 'cron_log_delete', 'cron_add_task', 'cron_update_task', 'cron_remove_task'],
				'Default' => 'register_user, blog_read'
			]
		];
		
		$this->obj->loadPermissionsData($phpData);
		$this->obj->init(null);
		
		//check direct in roles given
		self::assertTrue($this->obj->isInHierarchy('Administrator', 'Administrator'));
		
		//check hierarchy
		self::assertTrue($this->obj->isInHierarchy('Administrator', 'Developer'));
		self::assertTrue($this->obj->isInHierarchy('Administrator', 'cron_shell'));
		self::assertFalse($this->obj->isInHierarchy('Administrator', 'blog_add'));
		self::assertTrue($this->obj->isInHierarchy('Administrator, Contributor', 'blog_add'));
		self::assertTrue($this->obj->isInHierarchy(['Administrator', 'Contributor'], 'blog_add'));
		
		//check circular references, 
		self::assertTrue($this->obj->isInHierarchy(['Developer', 'Super'], 'cron_shell'));
		
		//check null, 
		self::assertFalse($this->obj->isInHierarchy(null, 'cron_shell'));
	}
	
	
	public function testAddRemoveRoleChildren()
	{
		$dbparam = new TDbParameterModule();
		$dbparam->unlisten();
		$dbparam->init(null);
		$this->obj->setDbParameter($dbparam);
		
		self::assertEquals([], $this->obj->getDbConfigRoles());
		
		$this->obj->addRoleChildren('Administrator', 'Developer, Manager');
		$this->obj->addRoleChildren('Administrator', ['SysAdmin', 'Contributor']);
		
		self::assertEquals(['developer', 'manager', 'sysadmin', 'contributor'], $this->obj->getHierarchyRoleChildren('Administrator'));
		self::assertEquals(['administrator' => ['developer', 'manager', 'sysadmin', 'contributor']], $this->obj->getDbConfigRoles());
		
		unset($this->obj->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		
		self::assertEquals(['administrator' => ['developer', 'manager', 'sysadmin', 'contributor']], $this->obj->getDbConfigRoles());
		
		$this->obj->removeRoleChildren('Administrator', 'Manager, sysAdmin');
		self::assertEquals(['developer','contributor'], $this->obj->getHierarchyRoleChildren('Administrator'));
		self::assertEquals(['administrator' => ['developer', 'contributor']], $this->obj->getDbConfigRoles());
		
		$this->obj->removeRoleChildren('Administrator', ['developer', 'contributor']);
		self::assertNull($this->obj->getHierarchyRoleChildren('Administrator'));
		self::assertEquals([], $this->obj->getDbConfigRoles());
		
		$dbparam->remove($this->obj->getLoadParameter());
		unset($this->obj->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		
		$dbparam->__destruct();
	}
	
	public function testAddRemovePermissionRule()
	{
		$dbparam = new TDbParameterModule();
		$dbparam->unlisten();
		$dbparam->init(null);
		$this->obj->setDbParameter($dbparam);
		
		self::assertEquals([], $this->obj->getDbConfigPermissionRules());
		
		self::assertTrue($this->obj->addPermissionRule('*', $rule = new TAuthorizationRule('deny', '', '', '', '', 5000)));
		self::assertEquals(['*' => [$rule]], $this->obj->getDbConfigPermissionRules());
		
		unset($this->obj->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		
		$rule =  $this->obj->getDbConfigPermissionRules()['*'][0];
		self::assertEquals('deny', $rule->getAction());
		self::assertEquals(5000, $rule->getPriority());
		
		self::assertTrue($this->obj->removePermissionRule('*', $rule));
		self::assertEquals([], $this->obj->getDbConfigPermissionRules());
			
		unset($this->obj->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		self::assertEquals([], $this->obj->getDbConfigPermissionRules());
		
		$dbparam->remove($this->obj->getLoadParameter());
		unset($this->obj->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		
		$dbparam->__destruct();
	}
	
	public function testGetPermissionRules()
	{
		self::assertEquals([], $this->obj->getPermissionRules(null));
		self::assertNull($this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES));
		
		$this->obj->init(null);
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_SHELL, TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES, TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES], array_keys($this->obj->getPermissionRules(null)));
		self::assertNotNull($rules = $this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES));
		self::assertInstanceOf('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(2, count($rules));
	}
	
	public function testSuperRoles()
	{
		$this->obj->setSuperRoles($v = 'Administrator, Manager, Developer');
		self::assertEquals(['Administrator', 'Manager', 'Developer'], $this->obj->getSuperRoles());
		
		$this->obj->setSuperRoles($v = ['Admin', 'Supervisor', 'Dev']);
		self::assertEquals(['Admin', 'Supervisor', 'Dev'], $this->obj->getSuperRoles());
		
		$this->obj->init(null);
		// throw TInvalidOperationException after initialize
		try {
			$this->obj->setSuperRoles([]);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
		
		self::assertEquals(['all'], $this->obj->getHierarchyRoleChildren("ADMIN"));
		self::assertEquals(['all'], $this->obj->getHierarchyRoleChildren("Supervisor"));
		self::assertEquals(['all'], $this->obj->getHierarchyRoleChildren("Dev"));
		self::assertNull($this->obj->getHierarchyRoleChildren("Administrator"));
		self::assertNull($this->obj->getHierarchyRoleChildren("Manager"));
		self::assertNull($this->obj->getHierarchyRoleChildren("Developer"));
	}
	
	public function testDefaultRoles()
	{
		$this->obj->setDefaultRoles($v = 'DefaultRole, user_register, mailing_signup');
		self::assertEquals(['DefaultRole', 'user_register', 'mailing_signup'], $this->obj->getDefaultRoles());
		
		$this->obj->setDefaultRoles($v = ['Default', 'subscriber']);
		self::assertEquals(['Default', 'subscriber'], $this->obj->getDefaultRoles());
		
		$this->obj->init(null);
		// throw TInvalidOperationException after initialize
		try {
			$this->obj->setDefaultRoles([]);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testPermissionFile()
	{
		try {
			$this->obj->setPermissionFile('App.NoAPathNamespace');
			self::fail('failed to throw TConfigurationException when not a valid namespace');
		} catch (TConfigurationException $e) {}
		
		try {
			$this->obj->setPermissionFile('Application.Permission');
			self::fail('failed to throw TConfigurationException when file is not valid');
		} catch (TConfigurationException $e) {}
		
		$this->obj->setPermissionFile('Application.Permissions');
		
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		$this->obj->init(null);
		
		try {
			$this->obj->setPermissionFile(true);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
		
		self::assertEquals(['all', 'filerole'], $this->obj->getHierarchyRoles());
		self::assertEquals(['cron', 'manager'], $this->obj->getHierarchyRoleChildren('fileRole'));
		
		$rules = $this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES);
		self::assertNotNull($rules);
		self::assertEquals(1, count($rules));
		self::assertInstanceOf('Prado\\Security\\TAuthorizationRule', $rules[0]);
		self::assertEquals(['cron'], $rules[0]->getRoles());
	}
	
	public function testAutoRulePriority()
	{
		$this->obj->setAutoRulePriority($v = 1.618);
		self::assertEquals($v, $this->obj->getAutoRulePriority());
		
		$this->obj->setAutoRulePriority($v = 2);
		self::assertEquals($v, $this->obj->getAutoRulePriority());
		
		$this->obj->registerPermission($perm = 'test_perm_1', 'description', new TUserOwnerRule());
		
		$rules = $this->obj->getPermissionRules($perm);
		
		self::assertEquals(2, count($rules->itemsAtPriority(2)));
		self::assertEquals(3, count($rules));
		
		$this->obj->init(null);
		try {
			$this->obj->setAutoRulePriority(true);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testAutoAllowWithPermission()
	{
		$this->obj->setAutoAllowWithPermission(false);
		self::assertFalse($this->obj->getAutoAllowWithPermission());
		
		$this->obj->setAutoAllowWithPermission(true);
		self::assertTrue($this->obj->getAutoAllowWithPermission());
		
		$this->obj->setAutoAllowWithPermission('false');
		self::assertFalse($this->obj->getAutoAllowWithPermission());
		
		$this->obj->setAutoAllowWithPermission('true');
		self::assertTrue($this->obj->getAutoAllowWithPermission());
		
		//
		$this->obj->setAutoDenyAll(false);
		
		//Check if permission as allowed role when true
		$this->obj->registerPermission('test_perm_1', 'description');
		self::assertEquals(1, count($this->obj->getPermissionRules('test_perm_1')));
		self::assertInstanceOf('Prado\\Security\\TAuthorizationRule', $this->obj->getPermissionRules('test_perm_1')[0]);
		self::assertEquals(['test_perm_1'], $this->obj->getPermissionRules('test_perm_1')[0]->getRoles());
		
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->registerPermission('test_perm_2', 'description');
		self::assertEquals(0, count($this->obj->getPermissionRules('test_perm_2')));
		
		//check that property can't change after init
		$this->obj->init(null);
		try {
			$this->obj->setAutoAllowWithPermission(true);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testAutoPresetRules()
	{
		$this->obj->setAutoPresetRules(false);
		self::assertFalse($this->obj->getAutoPresetRules());
		
		$this->obj->setAutoPresetRules(true);
		self::assertTrue($this->obj->getAutoPresetRules());
		
		$this->obj->setAutoPresetRules('false');
		self::assertFalse($this->obj->getAutoPresetRules());
		
		$this->obj->setAutoPresetRules('true');
		self::assertTrue($this->obj->getAutoPresetRules());
		
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoDenyAll(false);
		
		//check that registering permissions adds and doesn't add on AutoPresetRules property
		$this->obj->registerPermission('test_perm_1', 'description', new TUserOwnerRule());
		self::assertEquals(1, count($this->obj->getPermissionRules('test_perm_1')));
		self::assertInstanceOf('Prado\\Security\\Permissions\\TUserOwnerRule', $this->obj->getPermissionRules('test_perm_1')[0]);
		
		$this->obj->setAutoPresetRules(false);
		$this->obj->registerPermission('test_perm_2', 'description', new TUserOwnerRule());
		self::assertEquals(0, count($this->obj->getPermissionRules('test_perm_2')));
		
		//check that property can't change after init
		$this->obj->init(null);
		try {
			$this->obj->setAutoPresetRules(true);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testAutoDenyAll()
	{
		$this->obj->setAutoDenyAll(false);
		self::assertFalse($this->obj->getAutoDenyAll());
		
		$this->obj->setAutoDenyAll(true);
		self::assertTrue($this->obj->getAutoDenyAll());
		
		$this->obj->setAutoDenyAll('false');
		self::assertFalse($this->obj->getAutoDenyAll());
		
		$this->obj->setAutoDenyAll('true');
		self::assertTrue($this->obj->getAutoDenyAll());
		
		//Test that deny all is added to pre-registered permissions and post init permissions
		$this->obj->registerPermission($v = 'test_perm', 'description');
		$rules = $this->obj->getPermissionRules($v);
		self::assertEquals(2, count($rules));
		
		$this->obj->init(null);
		self::assertEquals(2, count($rules));
		self::assertEquals('deny', $rules[1]->getAction());
		
		$this->obj->registerPermission($v = 'test_perm_2', 'description');
		$rules = $this->obj->getPermissionRules($v);
		self::assertEquals(2, count($rules));
		self::assertEquals('deny', $rules[1]->getAction());
		try {
			$this->obj->setAutoDenyAll(true);
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testAutoDenyAllPriority()
	{
		$this->obj->setAutoDenyAllPriority($v = '1.618');
		self::assertEquals($v, $this->obj->getAutoDenyAllPriority());
		
		$this->obj->init(null);
		try {
			$this->obj->setAutoDenyAllPriority($v = '0.618');
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testDbParameter()
	{
		self::assertNull($this->obj->getDbParameter());
		
		$dbparam = new TDbParameterModule();
		$dbparam->unlisten();
		$this->obj->setDbParameter($dbparam);
		self::assertEquals($dbparam, $this->obj->getDbParameter());
		
		try {
			$this->obj->setDbParameter($v = new stdClass());
			self::fail('failed to throw TConfigurationException when parameter not string and not a TDbParameterModule');
		} catch (TConfigurationException $e) {}
		
		$this->obj->setDbParameter($dbparamId = 'testPermManager_DbParam');
		self::assertEquals($dbparamId, $this->obj->getDbParameter());
		
		try {
			$this->obj->init(null);
			self::fail('failed to throw TConfigurationException when dbparameter not a module');
		} catch(TConfigurationException $e) {}
		
		$app = Prado::getApplication();
		$app->setModule($dbparamId, $dbparam);
		$dbparam->init(null);
		$dbparam->set($this->obj->getLoadParameter(), [
			'roles' => [
				'paramRole' => ['paramAdmin', 'paramDev']
			],
			'permissionrules' => [
				TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES => [
					$aRule = new TAuthorizationRule('deny', '*', 'Default')
				]
			]
		]);
		$this->obj->setAutoAllowWithPermission(false);
		$this->obj->setAutoPresetRules(false);
		$this->obj->setAutoDenyAll(false);
		
		$this->obj->init(null);
		self::assertEquals($dbparam, $this->obj->getDbParameter());
		
		self::assertEquals(['all', 'paramrole'], $this->obj->getHierarchyRoles());
		self::assertEquals(['paramadmin', 'paramdev'], $this->obj->getHierarchyRoleChildren('paramrole'));
		self::assertEquals([$aRule], $this->obj->getPermissionRules(TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES)->toArray());
		
		// testGetDbConfigRoles testGetDbConfigPermissionRules
		self::assertEquals(['paramRole' => ['paramAdmin', 'paramDev']], $this->obj->getDbConfigRoles());
		self::assertEquals([TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES => [$aRule]], $this->obj->getDbConfigPermissionRules());
		
		try {
			$this->obj->setDbParameter('string_ModuleName');
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
		
		$dbparam->remove($this->obj->getLoadParameter());
		unset($dbparam->getApplication()->getParameters()[$this->obj->getLoadParameter()]);
		
		$userManager = new TUserManager();
		$this->obj->__destruct();
		
		$obj = new TPermissionsManager();
		$obj->setDbParameter($userManagerId = 'testPermManager_UserManager');
		$app->setModule($userManagerId, $userManager);
		try {
			$obj->init(null);
			self::fail('failed to throw TConfigurationException when dbparameter not a TDbParameterModule');
		} catch(TConfigurationException $e) {}
		self::assertEquals($userManagerId, $obj->getDbParameter());
		
		$obj->__destruct();
		$dbparam->__destruct();
		unset($dbparam);
		unset($userManager);
	}
	
	public function testLoadParameter()
	{
		$this->obj->setLoadParameter($v = 'config:TPermissionsManager:loaddata');
		self::assertEquals($v, $this->obj->getLoadParameter());
		
		$this->obj->init(null);
		try {
			$this->obj->setLoadParameter($v = 'config:TPermissionsManager:data');
			self::fail('failed to throw TInvalidOperationException when already initialized');
		} catch (TInvalidOperationException $e) {}
	}
	
	//
	// This checks that class behaviors can instance with Interfaces
	//
	public function testDestruct()
	{
		$this->obj->__destruct();
		$obj = new TPermissionsManager();
		$obj->init(null);
		
		$userManager = new TUserManager();
		$user = new TUser($userManager);
		self::assertInstanceOf('Prado\\Security\\Permissions\\TUserPermissionsBehavior', $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR));
		
		$obj->__destruct();
		$obj = null;
		
		$user = new TUser($userManager);
		self::assertNull($user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR));
		
		$user = null;
		$userManager = null;
	}

}
