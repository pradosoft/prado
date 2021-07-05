<?php
/**
 * IPermissions interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security\Permissions;

/**
 * IPermissions interface
 *
 * IPermissions specifies the interface for implementation by any class
 * that wants permission authorization built in.  This interface will have the
 * TPermissionsBehavior attached by {@link TPermissionsManager}.  When attached,
 * the behavior will register the owners permissions, and check a permission
 * rules on specified dynamic events.
 *
 * {@link getPermissions} returns an array of {@link TPermissionEvent}.  These
 * are automatically registered permissions.  If nothing is returned, then
 * the method will need to register its own permissions through
 * $manager->{@link TPermissionsManager::registerPermissions}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @since 4.2.0
 */
interface IPermissions
{
	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return TPermissionEvent[]
	 */
	public function getPermissions($manager);
}
