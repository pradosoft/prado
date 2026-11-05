<?php

/**
 * TAuthViewTemplate class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Security\IUser;
use Prado\Security\TAuthorizationRule;
use Prado\TComponent;
use Prado\TPropertyValue;

/**
 * TAuthViewTemplate class.
 *
 * TAuthViewTemplate is one case of a {@see TAuthView}: content and the conditions a
 * user must meet for it to render.  TAuthView renders the first case that matches.
 * A TAuthViewTemplate renders no tag of its own and must be a child of TAuthView.
 *
 * | Property | Matches when |
 * |---|---|
 * | {@see setUsers Users} | the user is in the list: a name, `*` everyone, `?` a guest, `@` an authenticated user. Defaults to `@`. |
 * | {@see setRoles Roles} | {@see \Prado\Security\IUser::isInRole()} is true for any listed role. |
 * | {@see setPermissions Permissions} | `can()` of the user is true for any listed permission. |
 *
 * Each property is a comma-separated list.  A case matches when every property
 * that is set matches.  `Users` and `Roles` have the syntax and meaning of
 * {@see \Prado\Security\TAuthorizationRule}, which matches them.
 *
 * `Permissions` requires {@see \Prado\Security\Permissions\TPermissionsManager}, which
 * gives users `can()`.  `can()` allows a permission that has no rules, so a
 * misspelled permission matches.
 *
 * ```php
 * <com:TAuthViewTemplate Roles="admin, editor" Permissions="blog.post.edit">
 *     <a href="...">Edit posts</a>
 * </com:TAuthViewTemplate>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAuthViewTemplate extends \Prado\Web\UI\TControl
{
	/**
	 * The default {@see getUsers Users}: authenticated users.
	 */
	public const DEFAULT_USERS = '@';

	/** @var string the users the case matches. */
	private string $_users = self::DEFAULT_USERS;
	/** @var string the roles the case matches. */
	private string $_roles = '';
	/** @var string the permissions the case matches. */
	private string $_permissions = '';

	/**
	 * @return string the comma-separated users the case matches.  Defaults to '@', authenticated users.
	 */
	public function getUsers()
	{
		return $this->_users;
	}

	/**
	 * @param string $value the comma-separated users the case matches: names, `*`, `?`, or `@`;
	 *   '' restores '@'.
	 */
	public function setUsers($value)
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->_users = $value === '' ? self::DEFAULT_USERS : $value;
	}

	/**
	 * @return string the comma-separated roles the case matches.  Defaults to '', any role.
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @param string $value the comma-separated roles the case matches.
	 */
	public function setRoles($value)
	{
		$this->_roles = trim(TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the comma-separated permissions the case matches.  Defaults to '', no check.
	 */
	public function getPermissions()
	{
		return $this->_permissions;
	}

	/**
	 * @param string $value the comma-separated permissions the case matches.
	 */
	public function setPermissions($value)
	{
		$this->_permissions = trim(TPropertyValue::ensureString($value));
	}

	/**
	 * Returns whether a user meets the conditions of the case.
	 * @param ?IUser $user the user; null for none.
	 * @throws TConfigurationException if Permissions is set and the user has no `can()`.
	 * @return bool whether the user matches; false when there is no user.
	 */
	public function isUserMatched(?IUser $user)
	{
		if ($user === null) {
			return false;
		}
		$rule = new TAuthorizationRule('allow', $this->getUsers(), $this->getRoles());
		if ($rule->isUserAllowed($user, '', '') !== 1) {
			return false;
		}
		return $this->isPermissionMatched($user);
	}

	/**
	 * Returns whether the user has any permission in {@see getPermissions Permissions}.
	 * @param IUser $user the user.
	 * @throws TConfigurationException if the user has no `can()`.
	 * @return bool whether the user has a listed permission; true when none is listed.
	 */
	protected function isPermissionMatched(IUser $user)
	{
		$permissions = array_filter(array_map('trim', explode(',', $this->getPermissions())), 'strlen');
		if (empty($permissions)) {
			return true;
		}
		if (!($user instanceof TComponent)) {
			throw new TConfigurationException('authviewtemplate_permissions_unsupported', $this->getPermissions());
		}
		return $this->canAny($user, $permissions);
	}

	/**
	 * Returns whether `can()` of a component is true for any of the permissions.
	 * @param TComponent $user the user.
	 * @param string[] $permissions the permissions.
	 * @throws TConfigurationException if the user has no `can()`.
	 * @return bool whether the user has a permission.
	 */
	protected function canAny(TComponent $user, array $permissions)
	{
		if (!$user->hasMethod('can')) {
			throw new TConfigurationException('authviewtemplate_permissions_unsupported', $this->getPermissions());
		}
		foreach ($permissions as $permission) {
			if ($user->can($permission)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Initializes the case.
	 * @param \Prado\TEventParameter $param the event parameter.
	 * @throws TConfigurationException if the parent is not a {@see TAuthView}.
	 */
	public function onInit($param)
	{
		if (!($this->getParent() instanceof TAuthView)) {
			throw new TConfigurationException('authviewtemplate_parent_invalid');
		}
		parent::onInit($param);
	}
}
