<?php
/**
 * TPermissionEvent interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Security\TAuthorizationRule;

/**
 * TPermissionEvent interface
 *
 * TPermissionEvent links a permission to dynamic events for checking permission.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security
 * @since 4.2.0
 */
class TPermissionEvent extends \Prado\TComponent
{
	/** @var string permission */
	private $_name;
	
	/** @var object component mapping the permission to the dynamic event */
	private $_component;
	
	/** @var string[] dynamic events  */
	private $_events;
	
	/** @var Prado\Security\TAuthorizationRule[] the built in rules from the module  */
	private $_rules;
	
	/**
	 * All permissions are made lower case.
	 * @param string $permissionName the permission linked to the dynamic events.
	 * @param string|string[] $events the events that the permission is linked.
	 * @param null|Prado\Security\TAuthorizationRule[] $rules default rules from the module.
	 */
	public function __construct($permissionName = '', $events = [], $rules = null)
	{
		$this->setName($permissionName);
		$this->setEvents($events);
		$this->setRules($rules);
	}
	
	/**
	 * @return string the permission name
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	/**
	 * @param string $permission the permission name
	 */
	public function setName($permission)
	{
		$this->_name = strtolower(TPropertyValue::ensureString($permission));
	}
	
	/**
	 * @return string[] the dynamic events tied to the permission
	 */
	public function getEvents()
	{
		return $this->_events;
	}
	
	/**
	 * this will take an array of string dynamic events, or a ',' comma separated
	 * list of dynamic events
	 * @param string|string[] $events the dynamic events tied to the permission
	 */
	public function setEvents($events)
	{
		if (is_string($events)) {
			$events = array_map('trim', explode(',', $events));
		} elseif ($events !== null && !is_array($events)) {
			throw new TConfigurationException('permission_events_invalid', $events);
		}
		$this->_events = array_map('strtolower', array_filter($events ?? []));
	}
	
	/**
	 * @return Prado\TComponent the object registering the permission
	 */
	public function getRules()
	{
		return $this->_rules;
	}
	
	/**
	 * @param Prado\TComponent $component the object registering the permission
	 * @param null|Prado\Security\TAuthorizationRule[] $rules
	 */
	public function setRules($rules)
	{
		if ($rules instanceof TAuthorizationRule) {
			$rules = [$rules];
		}
		if ($rules !== null && !is_array($rules)) {
			throw new TConfigurationException('permission_rules_invalid', $rules);
		}
		$this->_rules = $rules ?? [];
	}
}
