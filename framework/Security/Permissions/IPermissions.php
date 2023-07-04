<?php
/**
 * IPermissions interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

/**
 * IPermissions interface
 *
 * IPermissions specifies the interface for implementation by any class
 * that wants permission authorization built in through dynamic events.
 * This interface will have the {@see \Prado\Security\Permissions\TPermissionsBehavior} attached by
 * {@see \Prado\Security\Permissions\TPermissionsManager}.  When attached, the behavior will register
 * the owners permissions, and check a permission rules on specified dynamic
 * events.
 *
 * {@see getPermissions} returns an array of {@see \Prado\Security\Permissions\TPermissionEvent}.  These
 * are automatically registered permissions.  If nothing is returned, then
 * the implementation will need to register its own permissions through
 * $manager->{@see \Prado\Security\Permissions\TPermissionsManager::registerPermissions}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
interface IPermissions
{
	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager);
}
