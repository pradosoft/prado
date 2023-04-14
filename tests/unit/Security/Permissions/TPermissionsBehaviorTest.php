<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TPermissionsBehavior;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUserManager;
use Prado\Security\TUser;
use Prado\TApplication;
use Prado\Util\TBehavior;
use Prado\Xml\TXmlDocument;


class TPermissionsBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;
	
	protected $manager;
	protected $usermanager;
	protected $user;

	protected function setUp(): void
	{
		$this->behavior = new TPermissionsBehavior();
	}

	protected function tearDown(): void
	{
		$this->behavior = null;
		if ($this->user) {
			$this->user->__destruct();
			$this->user = null;
		}
		if ($this->usermanager) {
			$this->usermanager->__destruct();
			$this->usermanager = null;
		}
		if ($this->manager) {
			$this->manager->__destruct();
			$this->manager = null;
		}
	}

	public function testConstruct()
	{
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionsBehavior', $this->behavior);
		self::assertNull($this->behavior->getPermissionsManager());
		
		$this->behavior = new TPermissionsBehavior($v = new stdClass());
		self::assertEquals($v, $this->behavior->getPermissionsManager());
	}
	
	public function testManager()
	{
		$this->behavior->setPermissionsManager($v = new stdClass());
		self::assertEquals($v, $this->behavior->getPermissionsManager());
		$this->behavior->setPermissionsManager(\WeakReference::create($v));
		self::assertEquals($v, $this->behavior->getPermissionsManager());
	}
	
	public function testAttachAndEvent()
	{
		$permission = TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES;
		$this->manager = $manager = new TPermissionsManager();
		$manager->setId('perms');
		$this->behavior->setPermissionsManager($manager);
		
		self::assertEquals([], $this->behavior->getPermissionEvents());
		self::assertNull($manager->getPermissionRules($permission));
		self::assertFalse($manager->dyAddRoleChildren(false));
		self::assertFalse($manager->dyRemoveRoleChildren(false));
		
		$manager->attachBehavior('permissions', $this->behavior);
		
		//Test attach
		self::assertEquals(3,count($this->behavior->getPermissionEvents()));
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionEvent', $manager->getPermissionEvents()[0]);
		self::assertEquals(2, count($manager->getPermissionRules($permission)));
		
		$userName = 'developer3';
		$this->usermanager = $userManager = new TUserManager();
		$this->user = $user = new TUser($userManager);
		$user->setName($userName);
		Prado::getApplication()->setUser($user);
		$user->attachBehavior('can', ['class' => 'Prado\Security\Permissions\TUserPermissionsBehavior', 'permissionsmanager' => $manager]);
		
		//Test dynamic event permission
		$user->setRoles($permission);
		$role = 'Developer';
		$children = ['cron_shell', 'permissions_manage_xyz'];
		self::assertFalse($manager->dyAddRoleChildren(false, $role, $children));
		self::assertFalse($manager->dyRemoveRoleChildren(false, $role, $children));
		
		$user->setRoles([]);
		self::assertTrue($manager->dyAddRoleChildren(false, $role, $children));
		self::assertTrue($manager->dyRemoveRoleChildren(false, $role, $children));
		
		$rules = $manager->getPermissionRules($permission);
		self::assertEquals(2, count($rules));
		
		// Test the extra parameter of the behavior.
		$manager->loadPermissionsData(['permissionrules' => [
			['name' => $permission, 'class' => 'Prado\\Security\\Permissions\\TUserOwnerRule', 'action' => 'allow']
		]]);
		self::assertInstanceof('Prado\\Security\\TAuthorizationRuleCollection', $rules);
		self::assertEquals(3, count($rules));
		
		self::assertInstanceof('Prado\\Security\\Permissions\\TUserOwnerRule', $rules[1]);
		$extra = ['username' => $userName];
		$_extra = ['extra' => $extra];
		self::assertTrue($rules->isUserAllowed($user, 'get', '192.168.0.10', $extra));
		self::assertTrue($user->can($permission, $extra));
		self::assertFalse($manager->dyAddRoleChildren(false, $role, $children, $_extra));
		self::assertFalse($manager->dyRemoveRoleChildren(false, $role, $children, $_extra));
		
		$extra = ['username' => $userName . '_'];
		$_extra = ['extra' => $extra];
		
		self::assertTrue($manager->dyAddRoleChildren(false, $role, $children, $_extra));
		self::assertTrue($manager->dyRemoveRoleChildren(false, $role, $children, $_extra));
	}
	

}
