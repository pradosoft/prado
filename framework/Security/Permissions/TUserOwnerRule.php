<?php
/**
 * TUserOwnerRule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\IUser;
use Prado\Security\TAuthorizationRule;

/**
 * TUserOwnerRule class
 *
 * TUserOwnerRule will check if the extra data sent to isUserAllowed
 * has a user name that matches the the user
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @since 4.2.0
 */
class TUserOwnerRule extends TAuthorizationRule
{
	/**
	 * Constructor.
	 * @param string $action action, either 'deny' or 'allow'
	 * @param string $users a comma separated user list
	 * @param string $roles a comma separated role list
	 * @param string $verb verb, can be empty, 'get', or 'post'
	 * @param string $ipRules IP rules (separated by comma, can contain wild card *)
	 * @param null|mixed $priority
	 */
	public function __construct($priority = '')
	{
		parent::__construct('allow', '*', '*', '*', '*', $priority);
	}

	/**
	 * @param \Prado\Security\IUser $user the user object
	 * @param string $verb the request verb (GET, PUT)
	 * @param string $ip the request IP address
	 * @param null|array $extra extra data username to validate
	 * @return int 1 if the user is allowed, -1 if the user is denied, 0 if the rule does not apply to the user
	 */
	public function isUserAllowed(IUser $user, $verb, $ip, $extra = null)
	{
		if (parent::isUserAllowed($user, $verb, $ip, $extra) !== 0 && strcasecmp($user->getName(), $extra['username'] ?? '') === 0) {
			return ($this->getAction() === 'allow') ? 1 : -1;
		} else {
			return 0;
		}
	}
}
