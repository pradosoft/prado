<?php

/**
 * TPermissionsManagerPropertyTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Util\TCallChain;
use WeakReference;

/**
 * TPermissionsManagerPropertyTrait class.
 *
 * These are the methods for having the TPermissionsManager as a property in the
 * the behaviors {@see \Prado\Security\Permissions\TPermissionsBehavior}, {@see \Prado\Security\Permissions\TPermissionsConfigurationBehavior},
 * and {@see \Prado\Security\Permissions\TUserPermissionsBehavior}.
 *
 * The Permissions Manager property is important to zap when sleeping.  On waking
 * up, the PermissionsManager is set to the current instance manager.  When the
 * application has no manager, the behavior detaches from its owner in {@see dyWakeUp},
 * which the owner raises after its behaviors are attached again.
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
	 * Sets the TPermissionsManager to the current singleton instance.  The owner is
	 * not attached yet; {@see dyWakeUp} detaches the behavior when there is no manager.
	 */
	public function __wakeup()
	{
		$this->setPermissionsManager(TPermissionsManager::getManager());
		parent::__wakeup();
	}

	/**
	 * Detaches the behavior from its unserialized owner when the application has no
	 * TPermissionsManager.  The owner raises this dynamic event after it attaches its
	 * behaviors again in {@see \Prado\TComponent::__wakeup()}.
	 * @param TCallChain $chain The chain of dynamic event method handlers.
	 * @return mixed The result of the rest of the chain.
	 * @since 4.4.0
	 */
	public function dyWakeUp(TCallChain $chain)
	{
		if (!$this->getPermissionsManager() && ($owner = $this->getOwner())
			&& ($name = array_search($this, $owner->getBehaviors(), true)) !== false) {
			$owner->detachBehavior($name);
		}
		return $chain->dyWakeUp();
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
