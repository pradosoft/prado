<?php

/**
 * TPermissionsManagerPropertyTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use WeakReference;

/**
 * TPermissionsManagerPropertyTrait class.
 *
 * These are the methods for having the TPermissionsManager as a property in the
 * the behaviors {@see \Prado\Security\Permissions\TPermissionsBehavior}, {@see \Prado\Security\Permissions\TPermissionsConfigurationBehavior},
 * and {@see \Prado\Security\Permissions\TUserPermissionsBehavior}.
 *
 * The Permissions Manager property is important to zap when sleeping.  On waking
 * up, the PermissionsManager is set to the current instance manager.
 *
 * This functionality is important to replicate if-when overriding.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
trait TPermissionsManagerPropertyTrait
{
	/** @var TPermissionsManager manager object for the behavior */
	private $_permissionsManager;

	/**
	 * @param ?TPermissionsManager $manager
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
		if (!$this->getPermissionsManager() && ($owner = $this->getOwner())) {
			$owner->detachBehavior($this->getName());
		}
	}

	/**
	 * @return \Prado\Security\Permissions\TPermissionsManager application permissions manager
	 */
	public function getPermissionsManager()
	{
		return $this->_permissionsManager;
	}

	/**
	 * @param null|TPermissionsManager|WeakReference $manager manages application permissions
	 */
	public function setPermissionsManager($manager)
	{
		if ($manager instanceof WeakReference) {
			$manager = $manager->get();
		}
		$this->_permissionsManager = $manager;
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
		$exprops[] = "\0" . __CLASS__ . "\0_permissionsManager";
	}
}
