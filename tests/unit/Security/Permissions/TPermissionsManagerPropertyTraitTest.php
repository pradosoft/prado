<?php

namespace Prado\Test\Unit\Security\Permissions;

use Prado\Prado;
use Prado\Security\Permissions\TPermissionsBehavior;
use Prado\Security\Permissions\TPermissionsConfigurationBehavior;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\Permissions\TUserPermissionsBehavior;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;

/**
 * Tests {@see \Prado\Security\Permissions\TPermissionsManagerPropertyTrait} across an
 * owner's serialize() and unserialize().
 */
class TPermissionsManagerPropertyTraitTest extends \PHPUnit\Framework\TestCase
{
	/** @var array the application modules before the test. */
	private array $modules;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->modules = PradoUnit::getProp($app, '_modules');
		PradoUnit::setProp($app, '_modules', array_filter(
			$this->modules,
			fn ($module) => !($module instanceof TPermissionsManager)
		));
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_modules', $this->modules);
	}

	/**
	 * @return array<string, array{0: class-string}> each behavior class that uses the trait.
	 */
	public static function behaviorClasses(): array
	{
		return [
			'TUserPermissionsBehavior' => [TUserPermissionsBehavior::class],
			'TPermissionsBehavior' => [TPermissionsBehavior::class],
			'TPermissionsConfigurationBehavior' => [TPermissionsConfigurationBehavior::class],
		];
	}

	/**
	 * An unserialized owner drops the behavior when the application has no permissions
	 * manager, and keeps its other behaviors.
	 * @dataProvider behaviorClasses
	 */
	public function testWakeUpWithoutManagerDetachesTheBehavior(string $class): void
	{
		self::assertNull(TPermissionsManager::getManager());
		$user = new TUser(new TUserManager());
		$user->attachBehavior('perm', new $class(new TPermissionsManager()));
		$user->attachBehavior('other', new TPermissionsManagerPropertyTraitTestBehavior());

		$copy = unserialize(serialize($user));

		self::assertNull($copy->asa('perm'), 'The behavior without a manager is detached.');
		self::assertInstanceOf(TPermissionsManagerPropertyTraitTestBehavior::class, $copy->asa('other'));
		self::assertInstanceOf($class, $user->asa('perm'), 'The original keeps its behavior.');
	}

	/**
	 * Without a manager, an unserialized user has no `can()`, in place of a `can()` that
	 * fails on a null manager.
	 */
	public function testWakeUpWithoutManagerRemovesCan(): void
	{
		$user = new TUser(new TUserManager());
		$user->attachBehavior('can', new TUserPermissionsBehavior(new TPermissionsManager()));

		$copy = unserialize(serialize($user));

		self::assertFalse($copy->hasMethod('can'));
	}

	/**
	 * With an application manager, the unserialized behavior stays attached and uses it.
	 * @dataProvider behaviorClasses
	 */
	public function testWakeUpWithManagerKeepsTheBehavior(string $class): void
	{
		$manager = new TPermissionsManager();
		Prado::getApplication()->setModule('permissionsManagerPropertyTraitTest', $manager);
		$user = new TUser(new TUserManager());
		$user->attachBehavior('perm', new $class(new TPermissionsManager()));

		$copy = unserialize(serialize($user));

		self::assertInstanceOf($class, $copy->asa('perm'));
		self::assertSame($manager, $copy->asa('perm')->getPermissionsManager());
	}
}

/**
 * Behavior without a permissions manager, kept across unserialize().
 */
class TPermissionsManagerPropertyTraitTestBehavior extends TBehavior
{
}
