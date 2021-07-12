<?php
/**
 * TUserPermissionsBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util\Cron
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TUserPermissionsBehavior class.
 *
 * TUserPermissionsBehavior adds {@link can} permissions functionality. It also
 * handles {@link dyDefaultRoles} and {@link dyIsInRole} from TUser.
 *
 * This passes through dyDefaultRoles and dyIsInRole to the manager.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @since 4.2.0
 */
class TUserPermissionsBehavior extends TBehavior
{
	/** @var TPermissionsManager manager object for the behavior */
	private $_manager;
	
	/**
	 * @param TPermissionsManager
	 * @param null|mixed $manager
	 */
	public function __construct($manager = null)
	{
		if ($manager) {
			$this->setManager($manager);
		}
		parent::__construct();
	}
	
	/**
	 * Gets all the rules for the permission and checks against the TUser.
	 * @param string $permission
	 * @param null|mixed $extraData
	 */
	public function can($permission, $extraData = null)
	{
		$rules = $this->getManager()->getPermissionRules($permission);
		if (!$rules) {
			return true; //Default from TAuthorizationRuleCollection::isUserAllowed
		}
		$request = Prado::getApplication()->getRequest();
		return $rules->isUserAllowed($this->getOwner(), $request->getRequestType(), $request->getUserHostAddress(), $extraData);
	}
	
	/**
	 * @param string[] $roles
	 * @param Prado\Util\TCallChain $callchain
	 * @return string[] the default roles of all users
	 */
	public function dyDefaultRoles($roles, $callchain)
	{
		$roles = array_merge($roles, $this->getManager()->getDefaultRoles() ?? []);
		return $callchain->dyDefaultRoles($roles);
	}
	
	/**
	 * This handles the dynamic event where the $role does not match the user
	 * roles.  It checks the hierarchy of roles/permissions
	 * @param bool $return
	 * @param string $role
	 * @param Prado\Util\TCallChain $callchain
	 */
	public function dyIsInRole($return, $role, $callchain)
	{
		$inRole = $this->getManager()->isInHierarchy($this->getOwner()->getRoles(), $role);
		return $callchain->dyIsInRole($return, $role) || $inRole;
	}
	
	/**
	 * @param TPermissionsManager $manager manages permissions
	 */
	public function getManager()
	{
		return $this->_manager;
	}
	
	/**
	 * @param TPermissionsManager $manager manages permissions
	 */
	public function setManager($manager)
	{
		if ($manager instanceof \WeakReference) {
			$manager = $manager->get();
		}
		$this->_manager = $manager;
	}
}