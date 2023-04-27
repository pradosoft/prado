<?php
/**
 * TBehaviorsModule class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TBehaviorsModule class.
 *
 * TBehaviorsModule loads and attaches {@link TBehavior}.  This attaches
 * Behaviors to classes and to application components like the TApplication,
 * individual modules, and TPage of the TPageService.
 *
 * Contents enclosed within the module tag are treated as behaviors, e.g.,
 * <code>
 * <module class="Prado\Util\TBehaviorsModule" Parameter="AdditionalBehaviors">
 *   <behavior Name="pagethemeparameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="Prade\Web\UI\TPage" Priority="10" Parameter="ThemeName" Property="Theme"/>
 *   <behavior Name="sharedModuleBehavior" Class="FooModuleBehavior" AttachToClass="Prado\TModule" Attribute1="abc"/>
 *   <behavior name="TimeZoneBehavior" Class="Prado\Util\Behaviors\TTimeZoneParameterBehavior" AttachTo="Application" Priority="10" TimeZone="America/New York" TimeZoneParameter="prop:TimeZone" />
 *   <behavior name="MyModuleBehavior" Class="MyModuleBehavior" AttachTo="Module:page" Property1="Value1" Property2="Value2" ... />
 *   <behavior name="MyPageTitleBehavior" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Priority="10" Parameter="PageTitle" Property="Title" Localize="true"/>
 * </module>
 * </code>
 *
 * When the Service is not TPageService, page behaviors are not installed and have no effect other than be ignored.
 *
 * When {@link setAdditionalBehaviors AdditionalBehaviors} is set, this module
 * loads the behaviors from that property. It can be an array of php behavior definition arrays.
 * or a string that is then passed through unserialize or json_decode; otherwise is treated as
 * an xml document of behavior like above.
 *
 * The format is in the PHP style module configuration:
 * </code>
 *		[['name' => 'behaviorname', 'class' => 'TMyBehaviorClass', 'attachto' => 'page', 'priority' => '10', 'behaviorProperty'=>"value1'], ...]
 * </code>
 *
 * This allows TBehaviorsModule to load behaviors, dynamically, from parameters with the TParameterizeBehavior.
 *
 * Multiple TBehaviorsModules can be instanced, for instance, a second set of behaviors
 * could be configured in TPageService.  The second TBehaviorsModule can reference
 * behaviors from the first to attach its behaviors.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TBehaviorsModule extends \Prado\TModule
{
	/**
	 * @var IBaseBehavior[] loaded behaviors.
	 */
	private static $_behaviors = [];

	/**
	 * @var IBaseBehavior[] behaviors attaching to the TPage
	 */
	private $_pageBehaviors = [];

	/**
	 * @var array[] delayed behaviors attaching to the loaded behaviors
	 */
	private $_behaviorBehaviors = [];

	/**
	 * @var array[] additional behaviors in a configuration format: array[], serialized php object, json object, string of xml
	 */
	private $_additionalBehaviors;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Initializes the module by loading behaviors.  If there are page behaviors, this
	 * attaches behaviors to TPage through TApplication::onBeginRequest and then
	 * TPageService::onPreRunPage.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
	 */
	public function init($config)
	{
		$this->loadBehaviors($config);
		$this->loadBehaviors(['behaviors' => $this->getAdditionalBehaviors()]);

		if (count($this->_pageBehaviors)) {
			$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachTPageServiceHandler']);
		}
		foreach ($this->_behaviorBehaviors as $target => $behaviors) {
			if (!isset(self::$_behaviors[$target])) {
				throw new TConfigurationException('behaviormodule_behaviorowner_required', 'behavior:' . $target);
			}
			$owner = self::$_behaviors[$target];
			$owner = is_a($owner, '\WeakReference') ? $owner->get() : $owner;
			foreach ($behaviors as $properties) {
				$priority = $properties['priority'] ?? null;
				unset($properties['priority']);
				$name = $properties['name'];
				unset($properties['name']);
				$owner->attachBehavior($name, $properties, $priority);
			}
		}
		$this->_behaviorBehaviors = [];
		parent::init($config);
	}

	/**
	 * TApplication::onBeginRequest Handler that adds {@link attachTPageBehaviors} to
	 * TPageService::onPreRunPage. In turn, this attaches {@link attachTPageBehaviors}
	 * to TPageService to then adds the page behaviors.
	 * @param object $sender the object that raised the event
	 * @param mixed $param parameter of the event
	 */
	public function attachTPageServiceHandler($sender, $param)
	{
		$service = $this->getService();
		if ($service instanceof \Prado\Web\Services\TPageService) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachTPageBehaviors']);
		}
	}

	/**
	 * This method attaches page behaviors to the TPage handling the TPageService::OnPreInitPage event.
	 * @param object $sender the object that raised the event
	 * @param \Prado\Web\UI\TPage $page the page being initialized
	 */
	public function attachTPageBehaviors($sender, $page)
	{
		foreach ($this->_pageBehaviors as $name => $properties) {
			$priority = $properties['priority'] ?? null;
			unset($properties['priority']);
			$page->attachBehavior($name, $properties, $priority);
		}
		$this->_pageBehaviors = [];
	}

	/**
	 * Loads behaviors and attach to the proper object. behaviors for pages are
	 * attached separately if and when the TPage is loaded on TPageSerivce::onPreRunPage
	 * @param mixed $config XML of PHP representation of the behaviors
	 * @throws \Prado\Exceptions\TConfigurationException if the parameter file format is invalid
	 */
	protected function loadBehaviors($config)
	{
		$isXml = false;
		if ($config instanceof TXmlElement) {
			$isXml = true;
			$config = $config->getElementsByTagName('behavior');
		} elseif (is_array($config)) {
			$config = $config['behaviors'];
		} elseif (!$config) {
			return;
		}
		foreach ($config as $properties) {
			$element = $properties;
			if ($isXml) {
				$properties = array_change_key_case($properties->getAttributes()->toArray());
			} else {
				if (!is_array($properties)) {
					throw new TConfigurationException('behaviormodule_behavior_as_array_required');
				}
				if (isset($properties['properties'])) {
					$properties = $properties['properties'];
					unset($element['properties']);
					$properties['class'] = $element['class'];
					unset($element['class']);
				} else {
					unset($element['name']);
					unset($element['class']);
					unset($element['attachto']);
					unset($element['attachtoclass']);
					unset($element['priority']);
				}
			}
			$properties[IBaseBehavior::CONFIG_KEY] = $element;
			$name = $properties['name'] ?? null;
			unset($properties['name']);
			if (!$name) {
				throw new TConfigurationException('behaviormodule_behaviorname_required');
			}

			$attachTo = $properties['attachto'] ?? null;
			$attachToClass = $properties['attachtoclass'] ?? null;
			unset($properties['attachto']);
			unset($properties['attachtoclass']);
			if ($attachTo === null && $attachToClass === null) {
				throw new TConfigurationException('behaviormodule_attachto_class_required');
			} elseif ($attachTo !== null && $attachToClass !== null) {
				throw new TConfigurationException('behaviormodule_attachto_and_class_only_one');
			}
			if ($attachToClass) {
				$priority = $properties['priority'] ?? null;
				unset($properties['priority']);
				$behavior = TComponent::attachClassBehavior($name, $properties, $attachToClass, $priority);
			} else {
				if (strtolower($attachTo) == "page") {
					$this->_pageBehaviors[$name] = $properties;
					continue;
				} elseif (strncasecmp($attachTo, 'module:', 7) === 0) {
					$owner = $this->getApplication()->getModule(trim(substr($attachTo, 7)));
				} elseif (strncasecmp($attachTo, 'behavior:', 9) === 0) {
					$properties['name'] = $name;
					$this->_behaviorBehaviors[trim(substr($attachTo, 9))][] = $properties;
					continue;
				} else {
					$owner = $this->getSubProperty($attachTo);
				}
				$priority = $properties['priority'] ?? null;
				unset($properties['priority']);
				if (!$owner) {
					throw new TConfigurationException('behaviormodule_behaviorowner_required', $attachTo);
				}
				$behavior = $owner->attachBehavior($name, $properties, $priority);
			}
			if (!is_array($behavior)) {
				self::$_behaviors[$name] = \WeakReference::create($behavior);
			}
		}
	}

	/**
	 * @return array additional behaviors in a list.
	 */
	public function getAdditionalBehaviors()
	{
		return $this->_additionalBehaviors ?? [];
	}

	/**
	 * this will take a string that is an array of behaviors that has been
	 * through serialize(), or json array of behaviors.  If one behavior is
	 * set as an array, then it is automatically placed into an array.
	 * @param array[]|string $behaviors additional behaviors
	 */
	public function setAdditionalBehaviors($behaviors)
	{
		if (is_string($behaviors)) {
			if (($b = @unserialize($behaviors)) !== false) {
				$behaviors = $b;
			} elseif (($b = json_decode($behaviors, true)) !== null) {
				$behaviors = $b;
			} else {
				$xmldoc = new TXmlDocument('1.0', 'utf-8');
				$xmldoc->loadFromString($behaviors);
				$behaviors = $xmldoc;
			}
		}
		if (is_array($behaviors) && isset($behaviors['class'])) {
			$behaviors = [$behaviors];
		}
		if (!is_array($behaviors) && !($behaviors instanceof TXmlDocument) && $behaviors !== null) {
			throw new TInvalidDataTypeException('behaviormodule_additional_behaviors_invalid', $behaviors);
		}
		$this->_additionalBehaviors = $behaviors ?? [];
	}
}
