<?php

/**
 * TBehaviorParameterLoader class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TBehaviorParameterLoader implements attaching Behaviors from Parameters
 * before any work is done.  Here is an example of how to attach a behavior
 * via parameter:
 * <code>
 *		<parameter id="pagethemebehaviorloader" class="Prado\Util\Behaviors\TBehaviorParameterLoader" BehaviorName="testBehavior" BehaviorClass="Prado\Util\Behaviors\TParameterizeBehavior" Priority="10" AttachToClass="Prado\Web\UI\TPage" Parameter="ThemeName" Property="Theme" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */

class TBehaviorParameterLoader extends \Prado\TComponent
{
	/** @var string name of the behavior attaching to the owner */
	private $_behaviorName;
	
	/** @var string class of the behavior attaching to the owner */
	private $_behaviorClass;
	
	/** @var numeric priority of the behavior attaching to the owner */
	private $_priority;
	
	/** @var string what object to attach the behavior */
	private $_attachto;
	
	/** @var string what class to attach the behavior */
	private $_attachtoclass;
	
	/** @var array<string, string> additional properties to feed the behavior */
	private $_properties = [];
	
	/** @var array<string, array> the list of behaviors to attach to the page */
	private static $_pageBehaviors = [];
	
	/**
	 * Install the behavior
	 */
	public function dyInit()
	{
		if (!$this->_behaviorName) {
			throw new TConfigurationException('behaviorParameterLoader_no_behavior_name');
		}
		if (!$this->_behaviorClass) {
			throw new TConfigurationException('behaviorParameterLoader_no_behavior_class');
		}
		
		if ($this->_attachto === null && $this->_attachtoclass === null) {
			throw new TConfigurationException('behaviorParameterLoader_attachto_class_required');
		} elseif ($this->_attachto !== null && $this->_attachtoclass !== null) {
			throw new TConfigurationException('behaviorParameterLoader_attachto_and_class_only_one');
		}
		$this->_properties['class'] = $this->_behaviorClass;
		if ($this->_attachtoclass) {
			\Prado\TComponent::attachClassBehavior($this->_behaviorName, $this->_properties, $this->_attachtoclass, $this->_priority);
		} else {
			if (strtolower($this->_attachto) == "page") {
				if (!count(self::$_pageBehaviors)) {
					\Prado::getApplication()->onInitComplete[] = [$this, 'attachTPageServiceHandler'];
				}
				self::$_pageBehaviors[$this->_behaviorName] = $this->_properties;
				return;
			} elseif (strncasecmp($this->_attachto, 'module:', 7) === 0) {
				$owner = \Prado::getApplication()->getModule(trim(substr($this->_attachto, 7)));
			} else {
				$owner = \Prado::getApplication()->getSubProperty($this->_attachto);
			}
			$priority = $this->_properties['priority'] ?? null;
			unset($this->_properties['priority']);
			if (!$owner) {
				throw new TConfigurationException('behaviormodule_behaviorowner_required', $this->_attachto);
			}
			$owner->attachBehavior($this->_behaviorName, $this->_properties, $this->_priority);
		}
	}
	
	/**
	 * TApplication::onInitComplete Handler that adds {@link attachTPageBehaviors} to
	 * TPageService::onPreRunPage. In turn, {@link attachTPageBehaviors}
	 * adds the page behaviors.
	 * @param object $sender the object that raised the event.
	 * @param object $param parameter of the event.
	 */
	public function attachTPageServiceHandler($sender, $param)
	{
		$service = \Prado::getApplication()->getService();
		if ($service->isa('Prado\\Web\\Services\\TPageService')) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachTPageBehaviors']);
		}
	}
	
	/**
	 * This method attaches page behaviors to the TPage in the TPageService::OnPreInitPage event.
	 * @param object $sender the object that raised the event.
	 * @param TPage $page the page being initialized.
	 */
	public function attachTPageBehaviors($sender, $page)
	{
		foreach (self::$_pageBehaviors as $name => $properties) {
			$priority = $properties['priority'];
			unset($properties['priority']);
			$page->attachBehavior($name, $properties, $priority);
		}
		self::$_pageBehaviors = [];
	}
	
	/**
	 * gets the name of the attaching behavior.
	 * @return string the name of the attaching behavior.
	 */
	public function getBehaviorName()
	{
		return $this->_behaviorName;
	}
	
	/**
	 * sets the name of the attaching behavior.
	 * @param string $name the name of the attaching behavior.
	 */
	public function setBehaviorName($name)
	{
		$this->_behaviorName = TPropertyValue::ensureString($name);
	}
	
	/**
	 * gets the class of the attaching behavior.
	 * @return string the class of the attaching behavior.
	 */
	public function getBehaviorClass()
	{
		return $this->_behaviorClass;
	}
	
	/**
	 * sets the class of the attaching behavior.
	 * @param string $className the class of the attaching behavior.
	 */
	public function setBehaviorClass($className)
	{
		$this->_behaviorClass = TPropertyValue::ensureString($className);
	}
	
	/**
	 * gets the priority of the attaching behavior.
	 * @return numeric the priority of the attaching behavior.
	 */
	public function getPriority()
	{
		return $this->_priority;
	}
	
	/**
	 * sets the priority of the attaching behavior.
	 * @param numeric $priority the priority of the attaching behavior.
	 */
	public function setPriority($priority)
	{
		$this->_priority = TPropertyValue::ensureFloat($priority);
	}
	
	/**
	 * gets the AttachTo value.
	 * @return string the AttachTo value.
	 */
	public function getAttachTo()
	{
		return $this->_attachto;
	}
	
	/**
	 * Sets the AttachTo property.
	 * @param string $attachto the new AttachTo value.
	 */
	public function setAttachTo($attachto)
	{
		$this->_attachto = TPropertyValue::ensureString($attachto);
	}
	
	/**
	 * gets the AttachToClass value.
	 * @return string the AttachToClass value.
	 */
	public function getAttachToClass()
	{
		return $this->_attachtoclass;
	}
	
	/**
	 * Sets the AttachToClass property.
	 * @param string $attachto the new AttachToClass value.
	 */
	public function setAttachToClass($attachto)
	{
		$this->_attachtoclass = TPropertyValue::ensureString($attachto);
	}
	
	/**
	 * magic method for storing the properties for the behavior.
	 * @param string $name name of the property being set.
	 * @param string $value value of the property being set.
	 */
	public function __set($name, $value)
	{
		if (method_exists($this, $setter = 'set' . $name)) {
			return $this->$setter($value);
		} else {
			$this->_properties[$name] = $value;
		}
	}
}
