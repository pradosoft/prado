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
use Prado\TApplicationMode;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

/**
 * TPermissionsBehavior class.
 *
 * TPermissionsBehavior class is a class behavior attached to {@see \Prado\Security\Permissions\IPermissions}.
 * This class calls getPermissions to get an array of {@see \Prado\Security\Permissions\TPermissionEvent}
 * and/or to have the implementation register their own permissions.
 * Any returned TPermissionEvents will have their permission registered for rules.
 *
 * This class also handles all dynamic events and when a listed Event from a
 * TPermissionEvent is raised, this code checks if the current application
 * user permission is checked.
 *
 * Example getPermissions method:
 * ```php
 *	public function getPermissions($manager) {
 * 		$manager->registerPermission('module_perm_edit', 'Short Description');
 *		return [ new TPermissionEvent('module_perm_name', 'Short Description.', ['dyPermissionAction', 'dyOtherAction']) ];
 *	}
 * ```
 *
 * In this example, the methods dyPermissionAction and dyOtherAction would have an
 * authorization check on the given permission.
 *
 * The way to implement a dynamic event is like this, from the example above:
 * the first return value parameter is always false.
 * ```php
 *	public function myFunctionToAuth($param1, $param2) {
 *		if ($this->dyPermissionAction(false, $param1, $param2) === true)
 *			return false;
 *		....
 *		return true;
 *	}
 * ```
 * Together, TPermissionsBehavior will check the user for the 'module_perm_name'
 * permission.
 *
 * This can be alternatively implemented as a call to the user::can, eg
 * ```php
 *  	if(!Prado::getApplication()->getUser()->can('module_perm_name'))
 *			return false;
 * ```
 *
 * The application user is available on and after the onAuthenticationComplete
 * in the application stack.
 *
 * The default is to allow without any rules in place.  To automatically
 * block functionality, there needs to be a (final) Permission Rule to deny all.
 * The TPermissionsManager, by default, adds a final rule to deny all on all
 * permissions via {@see \Prado\Security\Permissions\TPermissionsManager::setAutoDenyAll}.
 *
 * The {@see \Prado\Security\Permissions\TUserPermissionsBehavior} attaches to {@see \Prado\Security\TUser} to
 * provide {@see \Prado\Security\Permissions\TUserPermissionsBehavior::can}, whether or note a user has authorization for a
 * permission.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPermissionsBehavior extends TBehavior implements IDynamicMethods
{
	use TPermissionsManagerPropertyTrait;

	/** @var array<string, string[]> key is the dynamic event, values are the permission names to check */
	private $_permissionEvents;

	/** @var \Prado\Security\Permissions\TPermissionEvent[] */
	private $_events;

	/**
	 * @param \Prado\TComponent $owner the object being attached to
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (method_exists($owner, 'getPermissions')) {
			$manager = $this->getPermissionsManager();
			if (!$manager) {
				return;
			}
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
		if (!($callchain instanceof \Prado\Util\TCallChain)) {
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
	 * This logs permissions failures in the Prado::log and with the shell when
	 * the Application is a TShellApplication.  When the Application is in Debug
	 * mode, the failed permission is written to shell-cli, otherwise the exact
	 * permission is keep hidden from the shell user for security purposes.
	 * @param array|string $permission the permission(s) that failed.
	 * @param string $action short description of the action that failed.
	 * @param \Prado\Util\TCallChain $callchain the series of dynamic events being raised.
	 * @param mixed $permission
	 */
	public function dyLogPermissionFailed($permission, $action, $callchain)
	{
		$app = Prado::getApplication();
		$user = $app->getUser();
		$name = '(undefined)';
		if ($user) {
			$name = $user->getName();
		}
		$permission = ($a = is_array($permission)) ? implode('", "', $permission) : $permission;
		$s = $a ? 's' : '';
		$permission = $s . ' ("' . $permission . '")';

		Prado::log('@' . $name . ' failed permission' . $permission . ' when "' . $action . '"', TLogger::WARNING, TPermissionsBehavior::class);

		$permission = ($app->getMode() == TApplicationMode::Debug) ? $permission : 's';
		if ($app instanceof \Prado\Shell\TShellApplication) {
			$writer = $app->getWriter();
			$writer->writeError('@' . $name . ' failed permission' . $permission . ' when "' . $action . '"');
		}
		return $callchain->dyLogPermissionFailed($permission, $action);
	}
}
