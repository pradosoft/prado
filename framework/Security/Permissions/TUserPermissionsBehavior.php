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
 * TUserPermissionsBehavior is designed to attach to {@link TUser}.
 * This class adds {@link can} permissions functionality. It also
 * handles {@link dyDefaultRoles} and {@link dyIsInRole} of TUser.
 *
 * This passes through dyDefaultRoles and dyIsInRole to the {@link TPermissionsManager}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method \Prado\Security\TUser getOwner()
 */
class TUserPermissionsBehavior extends TBehavior
{
	/** @var \Prado\Security\Permissions\TPermissionsManager manager object for the behavior */
	private $_manager;

	/**
	 * @param null|\Prado\Security\Permissions\TPermissionsManager $manager
	 */
	public function __construct($manager = null)
	{
		if ($manager) {
			$this->setPermissionsManager($manager);
		}
		parent::__construct();
	}

	/**
	 * Sets the TPermissionsManager to the current singleton instance.
	 */
	public function __wakeup()
	{
		$this->setPermissionsManager(TPermissionsManager::getManager());
		parent::__wakeup();
		if(!$this->getPermissionsManager()) {
			$this->getOwner()->detachBehavior($this->getName());
		}
	}

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

	/**
	 * @return \Prado\Security\Permissions\TPermissionsManager application permissions manager
	 */
	public function getPermissionsManager()
	{
		return $this->_manager;
	}

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager|\WeakReference $manager manages application permissions
	 */
	public function setPermissionsManager($manager)
	{
		if ($manager instanceof \WeakReference) {
			$manager = $manager->get();
		}
		$this->_manager = $manager;
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_manager";
	}
}
