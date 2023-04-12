<?php
/**
 * TPermissionsBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Prado;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

/**
 * TPermissionsBehavior class.
 *
 * TPermissionsBehavior class is a class behavior attached to {@link IPermissions}.
 * This class calls getPermissions to get an array of {@link TPermissionEvent}
 * and/or to have the implementation register their own permissions.
 * Any returned TPermissionEvents will have their permission registered for rules.
 *
 * This class also handles all dynamic events and when a listed Event from a
 * TPermissionEvent is raised, this code checks if the current application
 * user permission is checked.
 *
 * Example getPermissions method:
 * <code>
 *	public function getPermissions($manager) {
 * 		$manager->registerPermission('module_perm_edit', 'Short Description');
 *		return [ new TPermissionEvent('module_perm_name', 'Short Description.', ['dyPermissionAction', 'dyOtherAction']) ];
 *	}
 * </code>
 *
 * In this example, the methods dyPermissionAction and dyOtherAction would have an
 * authorization check on the given permission.
 *
 * The way to implement a dynamic event is like this, from the example above:
 * the first return value parameter is always false.
 * <code>
 *	public function myFunctionToAuth($param1, $param2) {
 *		if ($this->dyPermissionAction(false, $param1, $param2) === true)
 *			return false;
 *		....
 *		return true;
 *	}
 *	</code>
 * Together, TPermissionsBehavior will check the user for the 'module_perm_name'
 * permission.
 *
 * This can be alternatively implemented as a call to the user::can, eg
 * <code>
 *  	if(!Prado::getApplication()->getUser()->can('module_perm_name'))
 *			return false;
 * </code>
 *
 * The application user is available on and after the onAuthenticationComplete
 * in the application stack.
 *
 * The default is to allow without any rules in place.  To automatically
 * block functionality, there needs to be a (final) Permission Rule to deny all.
 * The TPermissionsManager, by default, adds a final rule to deny all on all
 * permissions via {@link TPermissionsManager::setAutoDenyAll}.
 *
 * The {@link TUserPermissionsBehavior} attaches to {@link TUser} to
 * provide {@link TUserPermissionsBehavior::can}, whether or note a user has authorization for a
 * permission.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPermissionsBehavior extends TBehavior implements IDynamicMethods
{
	/** @var TPermissionsManager manager object for the behavior */
	private $_manager;

	/** @var array<string, string[]> key is the dynamic event, values are the permission names to check */
	private $_permissionEvents;

	/** @var \Prado\Security\Permissions\TPermissionEvent[] */
	private $_events;

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
	 * @param \Prado\TComponent $owner the object being attached to
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (method_exists($owner, 'getPermissions')) {
			$manager = $this->getPermissionsManager();
			$this->_permissionEvents = [];
			$this->_events = $owner->getPermissions($manager) ?? [];
			foreach ($this->_events as $permEvent) {
				$perm = $permEvent->getName();
				foreach ($permEvent->getEvents() as $event) {
					$this->_permissionEvents[$event][] = $perm;
				}
				$manager->registerPermission($perm, $permEvent->getDescription(), $permEvent->getRules());
			}
		}
	}

	/**
	 * If in a proper dynamic event, checks if the application user
	 * can perform a permission, if it can't, flag as handled.
	 * @param string $method the dynamic event method being called
	 * @param array $args the arguments to the method
	 * @return bool|mixed if the $method is handled (where appropriate)
	 */
	public function __dycall($method, $args)
	{
		$callchain = array_pop($args);
		if (!$callchain instanceof \Prado\Util\TCallChain) {
			array_push($args, $callchain);
			return $args[0] ?? null;
		}

		$event = strtolower($method);
		/** @var TUserPermissionsBehavior $user */
		$user = Prado::getApplication()->getUser();
		if (isset($this->_permissionEvents[$event]) && $user) {
			$can = true;
			$extra = array_slice($args, -1)[0] ?? null;
			if (is_array($extra) && isset($extra['extra'])) {
				$extra = $extra['extra'];
			} else {
				$extra = null;
			}
			foreach ($this->_permissionEvents[$event] as $permission) {
				$can &= $user->can($permission, $extra);
			}
			return call_user_func_array([$callchain, $method], $args) === true || !$can;
		}
		return call_user_func_array([$callchain, $method], $args);
	}

	/**
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissionEvents()
	{
		return $this->_events ?? [];
	}

	/**
	 * Gets the TPermissionsManager for the behavior
	 * @return \Prado\Security\Permissions\TPermissionsManager manages application permissions
	 */
	public function getPermissionsManager()
	{
		return $this->_manager;
	}

	/**
	 * Sets the TPermissionsManager for the behavior
	 * @param \Prado\Security\Permissions\TPermissionsManager|\WeakReference $manager manages application permissions
	 */
	public function setPermissionsManager($manager)
	{
		if (class_exists('\WeakReference', false) && $manager instanceof \WeakReference) {
			$manager = $manager->get();
		}
		$this->_manager = $manager;
	}
}
