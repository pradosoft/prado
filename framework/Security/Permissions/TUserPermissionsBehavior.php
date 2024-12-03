<?php

/**
 * TUserPermissionsBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TUserPermissionsBehavior class.
 *
 * TUserPermissionsBehavior is designed to attach to {@see \Prado\Security\TUser}.
 * This class adds {@see can} permissions functionality. It also
 * handles {@see dyDefaultRoles} and {@see dyIsInRole} of TUser.
 *
 * This passes through dyDefaultRoles and dyIsInRole to the {@see \Prado\Security\Permissions\TPermissionsManager}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method \Prado\Security\TUser getOwner()
 */
class TUserPermissionsBehavior extends TBehavior
{
	use TPermissionsManagerPropertyTrait;

	/**
	 * Gets all the rules for the permission and checks against the TUser.
	 * @param string $permission
	 * @param null|mixed $extraData
	 */
	public function can($permission, $extraData = null)
	{
		$rules = $this->getPermissionsManager()->getPermissionRules($permission);
		if (!$rules) {
			return true; //Default from TAuthorizationRuleCollection::isUserAllowed
		}
		$request = Prado::getApplication()->getRequest();
		return $rules->isUserAllowed($this->getOwner(), $request->getRequestType(), $request->getUserHostAddress(), $extraData);
	}

	/**
	 * @param string[] $roles The default roles of all users
	 * @param \Prado\Util\TCallChain $callchain
	 * @return string[] the default roles of all users
	 */
	public function dyDefaultRoles($roles, $callchain)
	{
		$roles = array_merge($roles, $this->getPermissionsManager()->getDefaultRoles() ?? []);
		return $callchain->dyDefaultRoles($roles);
	}

	/**
	 * This handles the dynamic event where the $role does not match the user
	 * roles.  It checks the hierarchy of roles/permissions
	 * @param bool $return the return value, initially false
	 * @param string $role
	 * @param \Prado\Util\TCallChain $callchain
	 */
	public function dyIsInRole($return, $role, $callchain)
	{
		$inRole = $this->getPermissionsManager()->isInHierarchy($this->getOwner()->getRoles(), $role);
		return $callchain->dyIsInRole($return, $role) || $inRole;
	}
}
