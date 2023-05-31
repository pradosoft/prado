<?php
/**
 * TPermissionEvent class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Security\TAuthorizationRule;

/**
 * TPermissionEvent class
 *
 * TPermissionEvent links a permission to dynamic events to check permission.
 * This class acts as a data container object for the Permission, Description,
 * and module preset rules.  Preset Rules can be turned off from the
 * TPermissionManager with setting setAutoPresetRules to false.
 * This class works with {@see \Prado\Security\Permissions\TPermissionsBehavior}
 * to register the Permission, Description, and Preset Rules and also to
 * link the dynamic events to check user permission.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPermissionEvent extends \Prado\TComponent
{
	/** @var string permission */
	private $_name;

	/** @var string short description of the permission */
	private $_description;

	/** @var string[] dynamic events  */
	private $_events;

	/** @var \Prado\Security\TAuthorizationRule[] the preset rules from the module  */
	private $_rules;

	/**
	 * Constructs the class. All permission names are made lower case.
	 * @param string $permissionName the permission name linked to the dynamic events.
	 * @param string $description short description of the permission
	 * @param string|string[] $events the events that the permission is linked.
	 * @param null|\Prado\Security\TAuthorizationRule[] $rules default rules from the module.
	 */
	public function __construct($permissionName = '', $description = '', $events = [], $rules = null)
	{
		$this->setName($permissionName);
		$this->setDescription($description);
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
	 * @param string $permissionName the permission name
	 */
	public function setName($permissionName)
	{
		$this->_name = strtolower(TPropertyValue::ensureString($permissionName));
	}

	/**
	 * @return string the permission description
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * @param string $description the permission description
	 */
	public function setDescription($description)
	{
		$this->_description = TPropertyValue::ensureString($description);
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
	 * @param null|string|string[] $events the dynamic events tied to the permission
	 */
	public function setEvents($events)
	{
		if (is_string($events)) {
			$events = array_map('trim', explode(',', $events));
		} elseif ($events !== null && !is_array($events)) {
			throw new TConfigurationException('permissions_events_invalid', is_object($events) ? $events::class : $events);
		}
		$this->_events = array_map('strtolower', array_filter($events ?? []));
	}

	/**
	 * these are the preset rules for when registering the permission name.
	 * @return \Prado\Security\TAuthorizationRule[] the preset permission rules
	 */
	public function getRules()
	{
		return $this->_rules;
	}

	/**
	 * these are the preset rules for when registering the permission name.
	 * @param null|\Prado\Security\TAuthorizationRule|\Prado\Security\TAuthorizationRule[] $rules the preset permission rules
	 */
	public function setRules($rules)
	{
		if ($rules instanceof TAuthorizationRule) {
			$rules = [$rules];
		}
		if ($rules !== null && !is_array($rules)) {
			throw new TConfigurationException('permissions_rules_invalid', is_object($rules) ? $rules::class : $rules);
		}
		$this->_rules = $rules ?? [];
	}
}
