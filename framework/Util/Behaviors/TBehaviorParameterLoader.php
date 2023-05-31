<?php

/**
 * TBehaviorParameterLoader class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Collections\TPriorityPropertyTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Util\IBaseBehavior;

/**
 * TBehaviorParameterLoader class.
 *
 * TBehaviorParameterLoader implements attaching Behaviors from Parameters
 * before any work has been done.  Here is an example of how to attach a behavior
 * via parameter within an application.xml:
 * ```xml
 * <application id="prado-app">
 *		<parameters>
 *		<parameter id="pagethemebehaviorloader" class="Prado\Util\Behaviors\TBehaviorParameterLoader" BehaviorName="testBehavior" BehaviorClass="Prado\Util\Behaviors\TParameterizeBehavior" Priority="10" AttachToClass="Prado\Web\UI\TPage" Parameter="ThemeName" Property="Theme" DefaultValue="ColoradoBlue2022" />
 *		<parameters>
 * 	  ...
 * ```
 *
 * TBehaviorParameterLoader can be used in parameters to load behaviors through the
 * application configuration parameters, {@see TParameterModule}, as well in each
 * folder through the config.xml/php files.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TBehaviorParameterLoader extends TComponent
{
	use TPriorityPropertyTrait;

	/** @var string name of the behavior attaching to the owner */
	private $_behaviorName;

	/** @var string class of the behavior attaching to the owner */
	private $_behaviorClass;

	/** @var string what object to attach the behavior */
	private $_attachto;

	/** @var string what class to attach the behavior */
	private $_attachtoclass;

	/** @var array<string, string> additional properties to feed the behavior */
	private $_properties = [];

	/** @var array<string, array<string, string>> the list of behaviors to attach to the page */
	private static $_pageBehaviors = [];

	/** @var array<string, array<object>> the list of behaviors to attach to the page */
	private static $_moduleBehaviors = [];

	/**
	 * Install the behavior via dynamic event dyInit, called after a parameter
	 * class is loaded in TParameterModule or TApplication configurations.
	 * @param null|mixed $config for Parameters this is null.
	 */
	public function dyInit($config)
	{
		if (!$this->_behaviorClass) {
			throw new TConfigurationException('behaviorparameterloader_no_behavior_class');
		}

		if ($this->_attachto === null && $this->_attachtoclass === null) {
			throw new TConfigurationException('behaviorparameterloader_attachto_class_required');
		} elseif ($this->_attachto !== null && $this->_attachtoclass !== null) {
			throw new TConfigurationException('behaviorparameterloader_attachto_and_class_only_one');
		}
		$this->_properties['class'] = $this->_behaviorClass;
		$this->_properties[IBaseBehavior::CONFIG_KEY] = $config;
		$name = $this->getBehaviorName();
		if ($this->_attachtoclass) {
			TComponent::attachClassBehavior($name, $this->_properties, $this->_attachtoclass, $this->_priority);
		} else {
			if (strtolower($this->_attachto) == "page") {
				if (!count(self::$_pageBehaviors)) {
					Prado::getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachTPageServiceHandler']);
				}
				self::$_pageBehaviors[$name] = $this->_properties;
				return;
			} elseif (strncasecmp(strtolower($this->_attachto), 'module:', 7) === 0) {
				if (!count(self::$_moduleBehaviors)) {
					Prado::getApplication()->attachEventHandler('onInitComplete', [$this, 'attachModulesBehaviors'], -20);
				}
				$moduleid = trim(substr($this->_attachto, 7));
				if (!$moduleid) {
					throw new TConfigurationException('behaviorparameterloader_moduleid_required', $moduleid);
				}
				self::$_moduleBehaviors[$moduleid] = self::$_moduleBehaviors[$moduleid] ?? [];
				self::$_moduleBehaviors[$moduleid][$name] = $this->_properties;
				return;
			} elseif (strtolower($this->_attachto) == "application") {
				$owner = Prado::getApplication();
			} else {
				$owner = Prado::getApplication()->getSubProperty($this->_attachto);
			}
			$priority = $this->_properties['priority'] ?? null;
			unset($this->_properties['priority']);
			if (!$owner) {
				throw new TConfigurationException('behaviorparameterloader_behaviorowner_required', $this->_attachto);
			}
			$owner->attachBehavior($name, $this->_properties, $this->_priority);
		}
	}

	/**
	 * TApplication::onBeginRequest Handler that adds {@see attachTPageBehaviors} to
	 * TPageService::onPreRunPage. In turn, this attaches {@see attachTPageBehaviors}
	 * to TPageService to then adds the page behaviors.
	 * @param object $sender the object that raised the event
	 * @param mixed $param parameter of the event
	 */
	public function attachTPageServiceHandler($sender, $param)
	{
		$service = Prado::getApplication()->getService();
		if ($service instanceof \Prado\Web\Services\TPageService) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachTPageBehaviors'], -20);
		}
	}

	/**
	 * This method attaches page behaviors to the TPage handling the TPageService::OnPreInitPage event.
	 * @param object $sender the object that raised the event
	 * @param \Prado\Web\UI\TPage $page the page being initialized
	 */
	public function attachModuleBehaviors($sender, $page)
	{
		foreach (self::$_moduleBehaviors as $id => $behaviors) {
			$owner = Prado::getApplication()->getModule($id);
			if (!$owner) {
				throw new TConfigurationException('behaviorparameterloader_behaviormodule_not_found', $id);
			}
			foreach ($behaviors as $name => $properties) {
				$priority = $properties['priority'] ?? null;
				unset($properties['priority']);
				$owner->attachBehavior($name, $properties, $priority);
			}
		}
		self::$_moduleBehaviors = [];
	}

	/**
	 * This method attaches page behaviors to the TPage handling the TPageService::OnPreInitPage event.
	 * @param object $sender the object that raised the event
	 * @param \Prado\Web\UI\TPage $page the page being initialized
	 */
	public function attachTPageBehaviors($sender, $page)
	{
		foreach (self::$_pageBehaviors as $name => $properties) {
			$priority = $properties['priority'] ?? null;
			unset($properties['priority']);
			$page->attachBehavior($name, $properties, $priority);
		}
		self::$_pageBehaviors = [];
	}

	/**
	 * This resets the module and page behavior cache data.
	 */
	public function reset()
	{
		if ($this->_attachtoclass) {
			TComponent::detachClassBehavior($this->_behaviorName, $this->_attachtoclass);
		}
		Prado::getApplication()->detachEventHandler('onInitComplete', [$this, 'attachModulesBehaviors']);
		Prado::getApplication()->detachEventHandler('onBeginRequest', [$this, 'attachTPageServiceHandler']);
		self::$_moduleBehaviors = [];
		self::$_pageBehaviors = [];

		$this->_behaviorName = null;
		$this->_behaviorClass = null;
		$this->_priority = null;
		$this->_attachto = null;
		$this->_attachtoclass = null;
		$this->_properties = [];
	}

	/**
	 * gets the name of the attaching behavior.
	 * @return null|numeric|string the name of the attaching behavior.
	 */
	public function getBehaviorName()
	{
		return $this->_behaviorName;
	}

	/**
	 * sets the name of the attaching behavior.
	 * @param numeric|string $name the name of the attaching behavior.
	 */
	public function setBehaviorName($name)
	{
		if (empty($name) || is_numeric($name)) {
			$this->_behaviorName = null;
		} else {
			$this->_behaviorName = TPropertyValue::ensureString($name);
		}
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
	 * gets the Additional Properties of the behavior.
	 * @return array additional behaviors for the behavior class.
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * magic method for storing the properties for the behavior. If there is no
	 * set Property then it stores the property to set on the behavior.
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
