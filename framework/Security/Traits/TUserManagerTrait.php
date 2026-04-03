<?php

/**
 * TUserManagerTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Traits;

use Prado\Security\IUser;

/**
 * TUserManagerTrait class.
 *
 * This trait provides common functionality for user managers with the additions to IUserManager.
 *
 * All future additions to IUserManager should have the base implementation here.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TUserManagerTrait
{
	/** @var string The name of users who are not logged in. */
	private $_guestName = 'Guest';

	/**
	 * @return string the user class name in namespace format.
	 */
	public function getUserClass()
	{
		return IUser::class;
	}

	/**
	 * @return string guest name, defaults to 'Guest'
	 */
	public function getGuestName()
	{
		return $this->_guestName;
	}

	/**
	 * @param string $value name to be used for guest users.
	 */
	public function setGuestName($value)
	{
		$this->_guestName = $value;
	}

	/**
	 * @return array The unique roles in the User Manager. The trait returns `[]`.
	 */
	public function getUniqueRoles()
	{
		return [];
	}

	/**
	 * @return int The number of unique roles. The trait returns `0`.
	 */
	public function getUniqueRoleCount()
	{
		return 0;
	}

	/**
	 * Finalizes a user with the application after it is set up but before it is returned
	 * from {@see getUser()}.
	 * @param IUser $user The user to finalize.
	 */
	public function onFinalizeUser($user): void
	{
		$this->raiseEvent('onFinalizeUser', $this, $user);
	}
}
