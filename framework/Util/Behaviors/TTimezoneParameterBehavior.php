<?php

/**
 * TTimezoneParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TTimezoneParameterBehavior sets the date_default_timezone_set.
 * This parameterizes the TimeZone.   {@link TimezoneParameter} is
 * the key to the Application Parameter for setting the Timezone.
 *
 * This Behavior is designed to attach to TApplication, but can be
 * attached to any TComponent.
 *
 * <code>
 *		<behavior name="TimezoneParameter" Class="Prado\Util\Behaviors\TTimezoneParameterBehavior" AttachTo="Application" TimezoneParameter="Timezone" Timezone="America/New_York"/>
 * </code>
 * This code will set the default timezone to "America/New_York", and then
 * if there is any Application Parameter in "Timezone", then that takes
 * precedence.  Setting the TimezoneParameter to "" will disable the
 * parameter functionality and set the Timezone from the attribute Timezone.
 *
 * This routes changes in the Application Parameter {@link TimezoneParameter}
 * to {@link setTimezone}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TTimezoneParameterBehavior extends TBehavior 
{
	/**
	 * Name of the Application Parameter Routing Behavior
	 */
	const APP_PARAM_ROUTE_BEHAVIOR_NAME = 'TimezoneParameter';
	
	/**
	 * Default TimezoneParameter
	 */
	const TIMEZONE_PARAMETER_NAME = 'prop:Timezone';
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_timezoneParameter = self::TIMEZONE_PARAMETER_NAME;
	
	/**
	 * @var object {@link TMapRouteBehavior} that routes changes to the parameter
	 * is handled by setPHPTimezone.
	 */
	private $_paramBehavior;
	
	/**
	 * This sets the date_default_timezone_set with the value of the TimezoneParameter
	 * in the application parameters.  It attaches the Application Parameter handler behavior.
	 * @param $owner object the object that this behavior is attached to.
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (!$this->_timezoneParameter) {
			return;
		}
		$appParams = Prado::getApplication()->getParameters();
		if ($default_timezone = $appParams->itemAt($this->_timezoneParameter)) {
			$this->setTimezone($default_timezone);
		}
		$this->_paramBehavior = new TMapRouteBehavior($this->_timezoneParameter, [$this, 'setTimezone']);
		$appParams->attachBehavior(self::APP_PARAM_ROUTE_BEHAVIOR_NAME, $this->_paramBehavior);
	}
	
	/**
	 * This removes the Application Parameter handler behavior
	 * @param $owner object the object that this behavior is attached to.
	 */
	public function detach($owner)
	{
		if ($this->_paramBehavior) {
			Prado::getApplication()->getParameters()->detachBehavior(self::APP_PARAM_ROUTE_BEHAVIOR_NAME);
		}
		parent::detach($owner);
	}
	
	/**
	 * @return string Application parameter key to set the php Timezone.
	 */
	public function getTimezoneParameter()
	{
		return $this->_timezoneParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the php Timezone.
	 */
	public function setTimezoneParameter($value)
	{
		if ($this->_paramBehavior) {
			$this->_paramBehavior->_parameter = $value;
		}
		$this->_timezoneParameter = $value;
	}
	
	/**
	 * @return string the timezone from date_defaulte_timezone_get.
	 */
	public function getTimezone()
	{
		return date_default_timezone_get();
	}
	
	/**
	 * @param $value string passthrough to date_default_timezone_set
	 */
	public function setTimezone($value)
	{
		try {
			date_default_timezone_set($value);
		} catch (Exception $e) {
		}
	}
}
