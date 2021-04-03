<?php
/**
 * TUser class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

use Prado\TPropertyValue;

/**
 * TUser class
 *
 * TUser implements basic user functionality for a Prado application.
 * To get the name of the user, use {@link getName Name} property.
 * The property {@link getIsGuest IsGuest} tells if the user a guest/anonymous user.
 * To obtain or test the roles that the user is in, use property
 * {@link getRoles Roles} and call {@link isInRole()}, respectively.
 *
 * TUser is meant to be used together with {@link IUserManager}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0
 */
class TUser extends \Prado\TComponent implements IUser
{
	/**
	 * @var array persistent state
	 */
	private $_state;
	/**
	 * @var bool whether user state is changed
	 */
	private $_stateChanged = false;
	/**
	 * @var IUserManager user manager
	 */
	private $_manager;

	/**
	 * Constructor.
	 * @param IUserManager $manager user manager
	 */
	public function __construct(IUserManager $manager)
	{
		$this->_state = [];
		$this->_manager = $manager;
		$this->setName($manager->getGuestName());
		parent::__construct();
	}

	/**
	 * @return IUserManager user manager
	 */
	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * @return string username, defaults to empty string.
	 */
	public function getName()
	{
		return $this->getState('Name', '');
	}

	/**
	 * @param string $value username
	 */
	public function setName($value)
	{
		$this->setState('Name', $value, '');
	}

	/**
	 * @return bool if the user is a guest, defaults to true.
	 */
	public function getIsGuest()
	{
		return $this->getState('IsGuest', true);
	}

	/**
	 * @param bool $value if the user is a guest
	 */
	public function setIsGuest($value)
	{
		if ($isGuest = TPropertyValue::ensureBoolean($value)) {
			$this->setName($this->_manager->getGuestName());
			$this->setRoles([]);
		}
		$this->setState('IsGuest', $isGuest);
	}

	/**
	 * @return array list of roles that the user is of
	 */
	public function getRoles()
	{
		return $this->getState('Roles', []);
	}

	/**
	 * @param mixed $value
	 * @return array|string list of roles that the user is of. If it is a string, roles are assumed by separated by comma
	 */
	public function setRoles($value)
	{
		if (is_array($value)) {
			$this->setState('Roles', $value, []);
		} else {
			$roles = [];
			foreach (explode(',', $value) as $role) {
				if (($role = trim($role)) !== '') {
					$roles[] = $role;
				}
			}
			$this->setState('Roles', $roles, []);
		}
	}

	/**
	 * @param string $role role to be tested. Note, role is case-insensitive.
	 * @return bool whether the user is of this role
	 */
	public function isInRole($role)
	{
		foreach ($this->getRoles() as $r) {
			if (strcasecmp($role, $r) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return string user data that is serialized and will be stored in session
	 */
	public function saveToString()
	{
		return serialize($this->_state);
	}

	/**
	 * @param string $data user data that is serialized and restored from session
	 * @return IUser the user object
	 */
	public function loadFromString($data)
	{
		if (!empty($data)) {
			$this->_state = unserialize($data);
		}
		if (!is_array($this->_state)) {
			$this->_state = [];
		}
		return $this;
	}

	/**
	 * Returns the value of a variable that is stored in user session.
	 *
	 * This function is designed to be used by TUser descendant classes
	 * who want to store additional user information in user session.
	 * A variable, if stored in user session using {@link setState} can be
	 * retrieved back using this function.
	 *
	 * @param string $key variable name
	 * @param null|mixed $defaultValue default value
	 * @return mixed the value of the variable. If it doesn't exist, the provided default value will be returned
	 * @see setState
	 */
	protected function getState($key, $defaultValue = null)
	{
		return $this->_state[$key] ?? $defaultValue;
	}

	/**
	 * Stores a variable in user session.
	 *
	 * This function is designed to be used by TUser descendant classes
	 * who want to store additional user information in user session.
	 * By storing a variable using this function, the variable may be retrieved
	 * back later using {@link getState}. The variable will be persistent
	 * across page requests during a user session.
	 *
	 * @param string $key variable name
	 * @param mixed $value variable value
	 * @param null|mixed $defaultValue default value. If $value===$defaultValue, the variable will be removed from persistent storage.
	 * @see getState
	 */
	protected function setState($key, $value, $defaultValue = null)
	{
		if ($value === $defaultValue) {
			unset($this->_state[$key]);
		} else {
			$this->_state[$key] = $value;
		}
		$this->_stateChanged = true;
	}

	/**
	 * @return bool whether user session state is changed (i.e., setState() is called)
	 */
	public function getStateChanged()
	{
		return $this->_stateChanged;
	}

	/**
	 * @param bool $value whether user session state is changed
	 */
	public function setStateChanged($value)
	{
		$this->_stateChanged = TPropertyValue::ensureBoolean($value);
	}
}
