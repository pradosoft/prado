<?php
/**
 * TCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util\Cron
 */

namespace Prado\Security\Permissions;

use Prado\Prado;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

/**
 * TPermissionsBehavior class.
 *
 * TPermissionsBehavior installs permissions through an {@link IPermissions}
 * and checks the specified dynamic events for those permission authorization
 * rules.
 * The {@link TUserPermissionsBehavior::can} attaches to {@link TUser} to
 * provide whether or note a user has authorization for a permission.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @since 4.2.0
 */
class TPermissionsBehavior extends TBehavior implements IDynamicMethods
{
	/** @var TPermissionsManager manager object for the behavior */
	private $_manager;
	
	/** @var array the */
	private $_permissionEvents;
	
	private $_events;
	
	/**
	 * @param TPermissionsManager
	 * @param null|Prado\Security\Permissions\TPermissionsManager $manager
	 */
	public function __construct($manager = null)
	{
		if ($manager) {
			$this->setManager($manager);
		}
		parent::__construct();
	}
	
	/**
	 * @param Prado\TComponent $owner the object being attached to
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (method_exists($owner, 'getPermissions')) {
			$this->_permissionEvents = [];
			$this->_events = $owner->getPermissions($this->getManager()) ?? [];
			foreach ($this->_events as $permEvent) {
				$perm = $permEvent->getName();
				foreach ($permEvent->getEvents() as $event) {
					$this->_permissionEvents[$event][] = $perm;
				}
				$this->getManager()->registerPermission($perm, $permEvent->getRules());
			}
		}
	}
	
	/**
	 * @param string $method the dynamic event method being called
	 * @param array $args the arguments to the method
	 */
	public function __dycall($method, $args)
	{
		$callchain = array_pop($args);
		if (!$callchain instanceof \Prado\Util\TCallChain) {
			array_push($args, $callchain);
			return $args[0] ?? null;
		}
		$event = strtolower($method);
		
		$can = true;
		$handled = false;
		$user = Prado::getApplication()->getUser();
		if (isset($this->_permissionEvents[$event]) && $user) {
			$extra = array_slice($args, -1)[0] ?? null;
			if (is_array($extra) && isset($extra['extra'])) {
				$extra = $extra['extra'];
			} else {
				$extra = null;
			}
			$handled = true;
			foreach ($this->_permissionEvents[$event] as $permission) {
				$can &= $user->can($permission, $extra);
			}
		}
		if ($handled) {
			return call_user_func_array([$callchain, $method], $args) === true || !$can;
		} else {
			return call_user_func_array([$callchain, $method], $args);
		}
	}
	
	/**
	 * @return Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissionEvents()
	{
		return $this->_events ?? [];
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
