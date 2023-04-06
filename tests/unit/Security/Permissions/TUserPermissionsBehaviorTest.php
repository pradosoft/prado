<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUserManager;

class TUserPermissionsBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;
	
	protected function setUp(): void
	{
		$this->behavior = new TUserPermissionsBehavior();
		
	}

	protected function tearDown(): void
	{
		$this->behavior = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf(TUserPermissionsBehavior::class, $this->behavior);
		
		$this->behavior = new TUserPermissionsBehavior($v = new stdClass());
		self::assertEquals($v, $this->behavior->getPermissionsManager());
	}
	
	public function testManager()
	{
		$this->behavior->setPermissionsManager($v = new stdClass());
		self::assertEquals($v, $this->behavior->getPermissionsManager());
		$this->behavior->setPermissionsManager(\WeakReference::create($v));
		self::assertEquals($v, $this->behavior->getPermissionsManager());
	}
	
	public function testBehavior()
	{
		$userManager = new TUserManager();
		$user = new TUser($userManager);
		
		$manager = new TPermissionsManager();
		//$manager->init(null);
		$this->behavior->setPermissionsManager($manager);
		
		//$manager->addPermissionRule('*', new TAuthorizationRule('deny', '*', '*', '*', '*', 999999));
		
		$manager->setDefaultRoles([]);
		$manager->attachBehavior('permissions', new TPermissionsBehavior($manager));
		
		$user->attachBehavior('can', $this->behavior);
		
		$permission = TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES;
		
		// test can & dyDefaultRoles
		self::assertFalse($user->can($permission));
		
		//default roles are roles
		$manager->setDefaultRoles([$permission]);
		self::assertTrue($user->can($permission));
		
		$user->setRoles([]);
		self::assertTrue($user->can($permission));
		self::assertEquals([$permission], $user->getRoles());
		
		//remove default roles removed frome roles
		$manager->setDefaultRoles([]);
		self::assertEquals([], $user->getRoles());
		self::assertFalse($user->can($permission));
		
		//test roles make can method true
		$user->setRoles([$permission]);
		self::assertTrue($user->can($permission));
		
		$user->setRoles([]);
		self::assertFalse($user->can($permission));
		
		//Adding a default role doesn't stick when removing default role
		$manager->setDefaultRoles([$permission]);
		$user->setRoles([$permission]);
		self::assertTrue($user->can($permission));
		self::assertEquals([$permission], $user->getRoles());
		
		$manager->setDefaultRoles([]);
		self::assertFalse($user->can($permission));
		self::assertEquals([], $user->getRoles());
		
		//----------
		//dyIsInRole
		$manager->loadPermissionsData(['roles' => [
			'Manager' => 'Editor, Subscriber',
			'Editor' => 'Contributor',
			'Default' => 'user_register',
			'Contributor' => 'blog_create',
			'Subscriber' => ['comment_create'],
			'comment_create' => ['comment_update', 'comment_delete']
		]]);
		
		self::assertFalse($user->isInRole('user_register'));
		$manager->setDefaultRoles(['Default']);
		self::assertTrue($user->isInRole('user_register'));
		
		self::assertFalse($user->isInRole('comment_create'));
		self::assertFalse($user->isInRole('comment_update'));
		$user->setRoles('Subscriber');
		self::assertTrue($user->isInRole('comment_create'));
		self::assertTrue($user->isInRole('comment_update'));
		
		self::assertFalse($user->isInRole('blog_create'));
		$user->setRoles('Manager');
		self::assertTrue($user->isInRole('blog_create'));
		self::assertTrue($user->isInRole('comment_create'));
		self::assertTrue($user->isInRole('comment_update'));
		
		$user->setRoles([]);
		self::assertFalse($user->isInRole('blog_create'));
		self::assertFalse($user->isInRole('comment_create'));
		self::assertFalse($user->isInRole('comment_update'));
		
		$manager->__destruct();
		$manager = null;
		$user = null;
		$userManager = null;
	}
}
