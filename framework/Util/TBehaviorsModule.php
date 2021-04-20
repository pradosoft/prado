<?php
/**
 * TBehaviorsModule class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TBehaviorsModule class
 *
 * TBehaviorsModule loads and attaches {@link TBehaviors}.  This attaches
 * Behaviors to classes and to application objects like the TApplication,
 * individual modules, and TPage of the TPageService.
 *
 * Content enclosed within the module tag is treated as behaviors, e.g.,
 * <code>
 * <module class="Prado\Util\TBehaviorsModule">
 *   <behavior Name="pagethemeparameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="Prade\Web\UI\TPage" Priority="10" Parameter="ThemeName" Property="Theme"/>
 *   <behavior Name="sharedModuleBehavior" Class="FooModuleBehavior" AttachToClass="Prado\TModule" Attribute1="abc"/>
 *   <behavior name="MyAppBehavior" Class="MyAppBehavior" AttachTo="Application" Priority="10" Property1="Value1" Property2="Value2" ... />
 *   <behavior name="MyModuleBehavior" Class="MyModuleBehavior" AttachTo="Module:page" Property1="Value1" Property2="Value2" ... />
 *   <behavior name="MyPageBehavior" Class="Prado\Util\Behaviors\TPageThemeParameterBehavior" AttachTo="Page" Priority="10" ThemeParameter="ThemeName" />
 * </module>
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util
 * @since 4.2.0
 */
class TBehaviorsModule extends \Prado\TModule
{
	/**
	 * @var behavior instance attaching to the TPage, if available
	 */
	private $_pageBehaviors = [];
	
	/**
	 * Initializes the module by loading parameters.
	 * @param mixed $config content enclosed within the module tag
	 */
	public function init($config)
	{
		$this->loadBehaviors($config);
		
		if (count($this->_pageBehaviors)) {
			$this->getApplication()->attachEventHandler('onInitComplete', [$this, 'attachTPageServiceHandler']);
		}
	}
	
	/**
	 * Handler that adds handler {@link attachTPageBehaviors} to
	 * TPageService::onPreRunPage. In turn, {@link attachTPageBehaviors}
	 * adds the page behaviors.
	 * @param $sender object the object that raised the event
	 * @param $param object parameter of the event
	 */
	public function attachTPageServiceHandler($sender, $param)
	{
		$service = $this->getService();
		if ($service->isa('Prado\Web\Services\TPageService')) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachTPageBehaviors']);
		}
	}
	
	/**
	 * This method attaches page behaviors to the TPage in the TPageService::OnPreInitPage event.
	 * @param $sender object the object that raised the event
	 * @param $page TPage the page being initialized
	 */
	public function attachTPageBehaviors($sender, $page)
	{
		foreach ($this->_pageBehaviors as $name => $properties) {
			$priority = $properties['priority'];
			unset($properties['priority']);
			$page->attachBehavior($name, $properties, $priority);
		}
	}

	/**
	 * Loads behaviors and attach to the proper object.
	 * @param $config mixed XML of PHP representation of the behaviors
	 * @throws prado\Exceptions\TConfigurationException if the parameter file format is invalid
	 */
	protected function loadBehaviors($config)
	{
		if (is_array($config)) {
			foreach ($config as $properties) {
				if (!is_array($properties)) {
					throw new TConfigurationException('behaviormodule_behavior_as_array_required');
				}
				$name = $properties['name'];
				unset($properties['name']);
				if (!$name) {
					throw new TConfigurationException('behaviormodule_behaviorname_required');
				}
				
				$attachTo = $properties['attachto']??null;
				$attachToClass = $properties['attachtoclass']??null;
				unset($properties['attachto']);
				unset($properties['attachtoclass']);
				if ($attachTo === null && $attachToClass === null) {
					throw new TConfigurationException('behaviormodule_attachto_class_required');
				} elseif ($attachTo !== null && $attachToClass !== null) {
					throw new TConfigurationException('behaviormodule_attachto_and_class_only_one');
				}
				if ($attachToClass) {
					$priority = $properties['priority']??null;
					unset($properties['priority']);
					TComponent::attachClassBehavior($name, $properties, $attachToClass, $priority);
				} else {
					if (strtolower($attachTo) == "page") {
						$this->_pageBehaviors[$name] = $properties;
						continue;
					} elseif (strncasecmp($attachTo, 'module:', 7) === 0) {
						$owner = $this->getApplication()->getModule(trim(substr($attachTo, 7)));
					} else {
						$owner = $this->getSubProperty($attachTo);
					}
					$priority = $properties['priority']??null;
					unset($properties['priority']);
					if (!$owner) {
						throw new TConfigurationException('behaviormodule_behaviorowner_required', $attachTo);
					}
					$owner->attachBehavior($name, $properties, $priority);
				}
			}
		} elseif ($config instanceof TXmlElement) {
			foreach ($config->getElementsByTagName('behavior') as $node) {
				$properties = array_change_key_case($node->getAttributes()->toArray());
				
				$name = $properties['name'];
				unset($properties['name']);
				if (!$name) {
					throw new TConfigurationException('behaviormodule_behaviorname_required');
				}
				
				$class = $properties['class'];
				$attachTo = $properties['attachto']??null;
				$attachToClass = $properties['attachtoclass']??null;
				unset($properties['attachto']);
				unset($properties['attachtoclass']);
				if (!$class) {
					throw new TConfigurationException('behaviormodule_behaviorclass_required');
				}
				if ($attachTo === null && $attachToClass === null) {
					throw new TConfigurationException('behaviormodule_attachto_class_required');
				} elseif ($attachTo !== null && $attachToClass !== null) {
					throw new TConfigurationException('behaviormodule_attachto_and_class_only_one');
				}
				if ($attachToClass) {
					$priority = $properties['priority']??null;
					unset($properties['priority']);
					TComponent::attachClassBehavior($name, $properties, $attachToClass, $priority);
				} else {
					if (strtolower($attachTo) == "page") {
						$this->_pageBehaviors[$name] = $properties;
						continue;
					} elseif (strncasecmp($attachTo, 'module:', 7) === 0) {
						$owner = $this->getApplication()->getModule(trim(substr($attachTo, 7)));
					} else {
						$owner = $this->getSubProperty($attachTo);
					}
					$priority = $properties['priority']??null;
					unset($properties['priority']);
					if (!$owner) {
						throw new TConfigurationException('behaviormodule_behaviorowner_required', $attachTo);
					}
					$owner->attachBehavior($name, $properties, $priority);
				}
			}
		}
	}
}
