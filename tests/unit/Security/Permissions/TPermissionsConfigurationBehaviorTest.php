<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TPermissionsConfigurationBehavior;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUserManager;
use Prado\Security\TUser;
use Prado\TApplication;
use Prado\Util\TBehavior;
use Prado\Web\Services\TPageConfiguration;
use Prado\Xml\TXmlDocument;


class TPermissionsConfigurationBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;

	protected function setUp(): void
	{
		$this->behavior = new TPermissionsConfigurationBehavior();
	}

	protected function tearDown(): void
	{
		$this->behavior = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf('Prado\\Security\\Permissions\\TPermissionsConfigurationBehavior', $this->behavior);
		self::assertNull($this->behavior->getManager());
		
		$this->behavior = new TPermissionsBehavior($v = new stdClass());
		self::assertEquals($v, $this->behavior->getManager());
	}
	
	public function testManager()
	{
		$this->behavior->setManager($v = new stdClass());
		self::assertEquals($v, $this->behavior->getManager());
		$this->behavior->setManager(\WeakReference::create($v));
		self::assertEquals($v, $this->behavior->getManager());
	}
	
	public function testAttachAndEvents()
	{
		$permission = TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES;
		$manager = new TPermissionsManager();
		$manager->setId('perms');
		$manager->setAutoAllowWithPermission(false);
		$this->behavior->setManager($manager);
		
		self::assertNull($manager->getPermissionRules($permission));
		
		$manager->attachBehavior('permissions', new TPermissionsBehavior($manager));
		
		$config = new TPageConfiguration('.');
		$config->attachBehavior('permConfig', $this->behavior);
		$configPhp = ['permissions' => [
			'roles' => [
				'interRole' => [$permission, 'xyz']
			], 
			'permissionrules' => [
				['name' => $permission, 'roles' => 'Administrator', 'action' => 'allow']
			]
		]];
		$configXml = "<configuration><permissions>
			<role name='interRole2' children='{$permission}, abc' />
			<permissionrule name='{$permission}' action='allow' roles='Developer' />
		</permissions></configuration>";
		
		$dom = new TXmlDocument;
		$dom->loadFromString($configXml);
		
		$config->loadFromPhp($configPhp, '', '');
		$config->loadFromXml($dom, '', '');
		
		self::assertEquals(['all'], $manager->getHierarchyRoles());
		
		$config->dyApplyConfiguration();
		
		self::assertEquals(['all','interrole', 'interrole2'], $manager->getHierarchyRoles());
		self::assertEquals([$permission, 'xyz'], $manager->getHierarchyRoleChildren('interrole'));
		self::assertEquals([$permission, 'abc'], $manager->getHierarchyRoleChildren('interrole2'));
		
		$rules = $manager->getPermissionRules($permission);
		self::assertEquals(3, count($rules));
		self::assertEquals(['administrator'], $rules[0]->getRoles());
		self::assertEquals(['developer'], $rules[1]->getRoles());
		
		$manager->__destruct();
		$manager = null;
		//Test attach
		unset($config);
		unset($manager);
	}
	

}
