<?php

/**
 * TTimeZoneParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TTimeZoneParameterBehavior class.
 *
 * TTimeZoneParameterBehavior sets the date_default_timezone_set.
 * This parameterizes the TimeZone.   {@link TimeZoneParameter} is
 * the key to the Application Parameter for setting the TimeZone.
 *
 * This Behavior is designed to attach to TApplication, but can be
 * attached to any TComponent.
 *
 * <code>
 *		<behavior name="TimeZoneParameter" Class="Prado\Util\Behaviors\TTimeZoneParameterBehavior" AttachTo="Application" TimeZoneParameter="TimeZone" TimeZone="America/New_York"/>
 * </code>
 * This code will set the default timeZone to "America/New_York", and then
 * if there is any Application Parameter in "TimeZone", then that takes
 * precedence.  Setting the TimeZoneParameter to "" will disable the
 * parameter functionality and set the TimeZone from the attribute TimeZone.
 *
 * This routes changes in the Application Parameter {@link TimeZoneParameter}
 * to {@link setTimeZone}. The default TimeZoneParameter is 'prop:TimeZone'.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TTimeZoneParameterBehavior extends TBehavior
{
	/**
	 * Name of the Application Parameter Routing Behavior
	 */
	public const APP_PARAM_ROUTE_BEHAVIOR_NAME = 'TimeZoneParameter';
	
	/**
	 * Default TimeZoneParameter
	 */
	public const TIMEZONE_PARAMETER_NAME = 'prop:TimeZone';
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_timeZoneParameter = self::TIMEZONE_PARAMETER_NAME;
	
	/**
	 * @var object {@link TMapRouteBehavior} that routes changes to the parameter
	 * is handled by setTimeZone.
	 */
	private $_paramBehavior;
	
	/**
	 * This sets the date_default_timezone_set with the value of the TimeZoneParameter
	 * in the application parameters.  It attaches the Application Parameter handler behavior.
	 * @param $owner object the object that this behavior is attached to.
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (!$this->_timeZoneParameter) {
			return;
		}
		$appParams = Prado::getApplication()->getParameters();
		if ($default_timezone = $appParams->itemAt($this->_timeZoneParameter)) {
			$this->setTimeZone($default_timezone);
		}
		$this->_paramBehavior = new TMapRouteBehavior($this->_timeZoneParameter, [$this, 'setTimeZone']);
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
	 * @return string Application parameter key to set the php TimeZone.
	 */
	public function getTimeZoneParameter()
	{
		return $this->_timeZoneParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the php TimeZone.
	 */
	public function setTimeZoneParameter($value)
	{
		if ($this->_paramBehavior) {
			$this->_paramBehavior->setParameter($value);
		}
		$this->_timeZoneParameter = $value;
	}
	
	/**
	 * @return string the timeZone from date_default_timezone_get.
	 */
	public function getTimeZone()
	{
		return date_default_timezone_get();
	}
	
	/**
	 * @param $value string passthrough to date_default_timezone_set
	 */
	public function setTimeZone($value)
	{
		$set = true;
		try {
			date_default_timezone_set($value);
		} catch (\Exception $e) {
			$set = false;
		}
		return $set;
	}
}
