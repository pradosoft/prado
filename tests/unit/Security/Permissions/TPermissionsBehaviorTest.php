<?php

namespace Prado\Test\Unit\Security\Permissions;

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
use Prado\Prado;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\Security\Permissions\IPermissions;
use Prado\Shell\TShellWriter;
use Prado\IO\TTextWriter;
use Prado\TApplicationMode;
use Prado\TComponent;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\TTestShellApplication;

/**
 * {@see IPermissions} owner whose permission events are set by the test.
 */
class TTestPermissionsOwner extends TComponent implements IPermissions
{
	public ?array $events = [];

	public function getPermissions($manager)
	{
		return $this->events;
	}
}


class TPermissionsBehaviorTest extends \PHPUnit\Framework\TestCase
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
		self::assertInstanceOf(TPermissionsBehavior::class, $this->behavior);
		self::assertNull($this->behavior->getPermissionsManager());
		
		$this->behavior = new TPermissionsBehavior($v = new \stdClass());
		self::assertEquals($v, $this->behavior->getPermissionsManager());
	}
	
	public function testManager()
	{
		$this->behavior->setPermissionsManager($v = new \stdClass());
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
		self::assertInstanceOf(TPermissionEvent::class, $manager->getPermissionEvents()[0]);
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
			['name' => $permission, 'class' => TUserOwnerRule::class, 'action' => 'allow']
		]]);
		self::assertInstanceof(TAuthorizationRuleCollection::class, $rules);
		self::assertEquals(3, count($rules));
		
		self::assertInstanceof(TUserOwnerRule::class, $rules[1]);
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

	public function testAttachToAnOwnerWithoutPermissionsRegistersNothing()
	{
		$this->manager = $manager = new TPermissionsManager();
		$this->behavior->setPermissionsManager($manager);
		(new TComponent())->attachBehavior('permissions', $this->behavior);
		self::assertSame([], $this->behavior->getPermissionEvents());
		self::assertNull(\Prado\Test\Unit\PradoUnit::getProp($this->behavior, '_permissionEvents'));
	}

	public function testAttachWithoutAManagerRegistersNothing()
	{
		$owner = new TTestPermissionsOwner();
		$owner->events = [new TPermissionEvent('perm_unmanaged', 'Unmanaged', 'dyUnmanaged')];
		$owner->attachBehavior('permissions', $this->behavior);
		self::assertSame([], $this->behavior->getPermissionEvents());
	}

	public function testAttachRegistersEachPermissionEvent()
	{
		$this->manager = $manager = new TPermissionsManager();
		foreach ([null, [], [new TPermissionEvent('perm_no_events', 'No events')], [new TPermissionEvent('perm_one_event', 'One event', 'dyOneEvent')]] as $events) {
			$behavior = new TPermissionsBehavior($manager);
			$owner = new TTestPermissionsOwner();
			$owner->events = $events;
			$owner->attachBehavior('permissions', $behavior);
			self::assertSame($events ?? [], $behavior->getPermissionEvents());
		}
		self::assertSame('No events', $manager->getPermissionDescription('perm_no_events'));
		self::assertSame('One event', $manager->getPermissionDescription('perm_one_event'));
		self::assertSame(['dyoneevent' => ['perm_one_event']], \Prado\Test\Unit\PradoUnit::getProp($behavior, '_permissionEvents'));
	}

	public function testLogPermissionFailedWritesTheShellAndHidesThePermissionOutsideDebug()
	{
		$app = new TTestShellApplication();
		try {
			$text = new TTextWriter();
			$app->setWriter(new TShellWriter($text));
			$owner = new TComponent();
			$owner->attachBehavior('permissions', $this->behavior);

			$app->setMode(TApplicationMode::Debug);
			self::assertSame('s ("perm_a", "perm_b")', $owner->dyLogPermissionFailed(['perm_a', 'perm_b'], 'acting'));
			self::assertStringContainsString('@(undefined) failed permissions ("perm_a", "perm_b") when "acting"', $text->flush());

			$this->usermanager = new TUserManager();
			$this->user = $user = new TUser($this->usermanager);
			$user->setName('joe');
			$app->setUser($user);
			$app->setMode(TApplicationMode::Normal);
			self::assertSame('s', $owner->dyLogPermissionFailed('perm_a', 'acting'));
			$output = $text->flush();
			self::assertStringContainsString('@joe failed permissions when "acting"', $output);
			self::assertStringNotContainsString('perm_a', $output);
		} finally {
			$app->restoreApplication();
		}
	}

	public function testLogPermissionFailedOutsideTheShellOnlyLogs()
	{
		$app = new TTestApplication();
		try {
			$app->setMode(TApplicationMode::Debug);
			$owner = new TComponent();
			$owner->attachBehavior('permissions', $this->behavior);
			self::assertSame(' ("perm_a")', $owner->dyLogPermissionFailed('perm_a', 'acting'));
		} finally {
			$app->restoreApplication();
		}
	}
}
