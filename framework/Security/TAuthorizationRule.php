<?php
/**
 * TAuthorizationRule, TAuthorizationRuleCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TAuthorizationRule class
 *
 * TAuthorizationRule represents a single authorization rule.
 * A rule is specified by an action (required), a list of users (optional),
 * a list of roles (optional), a verb (optional), and a list of IP rules (optional).
 * Action can be either 'allow' or 'deny'.
 * Guest (anonymous, unauthenticated) users are represented by question mark '?'.
 * All users (including guest users) are represented by asterisk '*'.
 * Authenticated users are represented by '@'.
 * Users/roles are case-insensitive.
 * Different users/roles are separated by comma ','.
 * Verb can be either 'get' or 'post'. If it is absent, it means both.
 * IP rules are separated by comma ',' and can contain wild card in the rules (e.g. '192.132.23.33, 192.122.*.*')
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0
 */
class TAuthorizationRule extends \Prado\TComponent
{
	/**
	 * @var string action, either 'allow' or 'deny'
	 */
	private $_action;
	/**
	 * @var array list of user IDs
	 */
	private $_users;
	/**
	 * @var array list of roles
	 */
	private $_roles;
	/**
	 * @var string verb, may be empty, 'get', or 'post'.
	 */
	private $_verb;
	/**
	 * @var string IP patterns
	 */
	private $_ipRules;
	/**
	 * @var bool if this rule applies to everyone
	 */
	private $_everyone;
	/**
	 * @var bool if this rule applies to guest user
	 */
	private $_guest;
	/**
	 * @var bool if this rule applies to authenticated users
	 */
	private $_authenticated;

	/**
	 * Constructor.
	 * @param string $action action, either 'deny' or 'allow'
	 * @param string $users a comma separated user list
	 * @param string $roles a comma separated role list
	 * @param string $verb verb, can be empty, 'get', or 'post'
	 * @param string $ipRules IP rules (separated by comma, can contain wild card *)
	 */
	public function __construct($action, $users, $roles, $verb = '', $ipRules = '')
	{
		$action = strtolower(trim($action));
		if ($action === 'allow' || $action === 'deny') {
			$this->_action = $action;
		} else {
			throw new TInvalidDataValueException('authorizationrule_action_invalid', $action);
		}
		$this->_users = [];
		$this->_roles = [];
		$this->_ipRules = [];
		$this->_everyone = false;
		$this->_guest = false;
		$this->_authenticated = false;

		if (trim($users) === '') {
			$users = '*';
		}
		foreach (explode(',', $users) as $user) {
			if (($user = trim(strtolower($user))) !== '') {
				if ($user === '*') {
					$this->_everyone = true;
					break;
				} elseif ($user === '?') {
					$this->_guest = true;
				} elseif ($user === '@') {
					$this->_authenticated = true;
				} else {
					$this->_users[] = $user;
				}
			}
		}

		if (trim($roles) === '') {
			$roles = '*';
		}
		foreach (explode(',', $roles) as $role) {
			if (($role = trim(strtolower($role))) !== '') {
				$this->_roles[] = $role;
			}
		}

		if (($verb = trim(strtolower($verb))) === '') {
			$verb = '*';
		}
		if ($verb === '*' || $verb === 'get' || $verb === 'post') {
			$this->_verb = $verb;
		} else {
			throw new TInvalidDataValueException('authorizationrule_verb_invalid', $verb);
		}

		if (trim($ipRules) === '') {
			$ipRules = '*';
		}
		foreach (explode(',', $ipRules) as $ipRule) {
			if (($ipRule = trim($ipRule)) !== '') {
				$this->_ipRules[] = $ipRule;
			}
		}
	}

	/**
	 * @return string action, either 'allow' or 'deny'
	 */
	public function getAction()
	{
		return $this->_action;
	}

	/**
	 * @return array list of user IDs
	 */
	public function getUsers()
	{
		return $this->_users;
	}

	/**
	 * @return array list of roles
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @return string verb, may be empty, 'get', or 'post'.
	 */
	public function getVerb()
	{
		return $this->_verb;
	}

	/**
	 * @return array list of IP rules.
	 * @since 3.1.1
	 */
	public function getIPRules()
	{
		return $this->_ipRules;
	}

	/**
	 * @return bool if this rule applies to everyone
	 */
	public function getGuestApplied()
	{
		return $this->_guest || $this->_everyone;
	}

	/**
	 * @return bool if this rule applies to everyone
	 */
	public function getEveryoneApplied()
	{
		return $this->_everyone;
	}

	/**
	 * @return bool if this rule applies to authenticated users
	 */
	public function getAuthenticatedApplied()
	{
		return $this->_authenticated || $this->_everyone;
	}

	/**
	 * @param IUser $user the user object
	 * @param string $verb the request verb (GET, PUT)
	 * @param string $ip the request IP address
	 * @return int 1 if the user is allowed, -1 if the user is denied, 0 if the rule does not apply to the user
	 */
	public function isUserAllowed(IUser $user, $verb, $ip)
	{
		if ($this->isVerbMatched($verb) && $this->isIpMatched($ip) && $this->isUserMatched($user) && $this->isRoleMatched($user)) {
			return ($this->_action === 'allow') ? 1 : -1;
		} else {
			return 0;
		}
	}

	private function isIpMatched($ip)
	{
		if (empty($this->_ipRules)) {
			return 1;
		}
		foreach ($this->_ipRules as $rule) {
			if ($rule === '*' || $rule === $ip || (($pos = strpos($rule, '*')) !== false && strncmp($ip, $rule, $pos) === 0)) {
				return 1;
			}
		}
		return 0;
	}

	private function isUserMatched($user)
	{
		return ($this->_everyone || ($this->_guest && $user->getIsGuest()) || ($this->_authenticated && !$user->getIsGuest()) || in_array(strtolower($user->getName()), $this->_users));
	}

	private function isRoleMatched($user)
	{
		foreach ($this->_roles as $role) {
			if ($role === '*' || $user->isInRole($role)) {
				return true;
			}
		}
		return false;
	}

	private function isVerbMatched($verb)
	{
		return ($this->_verb === '*' || strcasecmp($verb, $this->_verb) === 0);
	}
}
