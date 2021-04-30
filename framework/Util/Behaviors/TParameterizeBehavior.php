<?php

/**
 * TParameterizeBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TParameterizeBehavior class.
 *
 * TParameterizeBehavior sets a specific Property on the owner object
 * to a specific application parameter.  It also can install a behavior
 * on the Application parameters to apply any changes to the application
 * parameter to then route the change to the property.
 *
 * <code>
 *	<behavior name="PageThemeParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="ThemeName" Property="Theme" DefaultValue="Basic"/>
 *  <behavior name="PageTitle" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="TPageTitle" Property="Title" Localize="true"/>
 *  <behavior name="AuthManagerExpireParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="module:auth" Parameter="prop:TAuthManager.AuthExpire" Property="AuthExpire" RouteBehaviorName="TAuthManagerAuthExpireRouter" />
 *	<behavior name="TSecurityManagerValidationKey" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="TSecurityManager" Parameter="prop:TSecurityManager.ValidationKey" Property="ValidationKey" />
 *	<behavior name="TSecurityManagerEncryptionKey" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="TSecurityManager" Parameter="prop:TSecurityManager.EncryptionKey" Property="EncryptionKey" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TParameterizeBehavior extends \Prado\Util\TBehavior
{
	/**
	 * @var string the key to the application parameter
	 */
	private $_parameter;
	
	/**
	 * @var bool whether or not a null/false/''/0 parameter value should be set on the property
	 */
	private $_validNullValue = false;
	
	/**
	 * @var string the key to the application parameter
	 */
	protected $_property;
	
	/**
	 * @var string the default value of the property if there is no Parameter
	 */
	protected $_defaultValue;
	
	/**
	 * @var bool should the value be localized
	 */
	protected $_localize;
	
	/**
	 * @var object {@link TMapRouteBehavior} that routes changes from the parameter to the property
	 */
	private $_paramBehavior;
	
	/**
	 * @var the name of the installed behavior.
	 */
	protected $_routeBehaviorName;
	
	/**
	 * This method sets the Owner Property to the Application Parameter of Parameter. When
	 * {@link getRouteBehaviorName} is set, a {@link TMapRouteBehavior} is attached to
	 * the Application Parameter on the key so any changes are also routed to the Property.
	 * @param $owner object the object to which this behavior is being attached
	 * @throws TConfigurationException when missing the parameter, property, or property is not able to set
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		
		if (!$this->_parameter) {
			throw new TConfigurationException('parameterizebehavior_no_parameter');
		}
		if (!$this->_property) {
			throw new TConfigurationException('parameterizebehavior_no_property');
		}
		if (!$owner->canSetProperty($this->_property)) {
			throw new TConfigurationException('parameterizebehavior_owner_has_no_set_property', $this->_property);
		}
		
		$appParams = Prado::getApplication()->getParameters();
		if (($value = $appParams->itemAt($this->_parameter)) !== null || $this->getValidNullValue()) {
			if ($this->_localize && $value && is_string($value)) {
				$value = Prado::localize($value);
			}
			$owner->setSubProperty($this->_property, $value);
		} elseif ($this->_defaultValue !== null) {
			$value = $this->_defaultValue;
			if ($this->_localize && is_string($value)) {
				$value = Prado::localize($value);
			}
			$owner->setSubProperty($this->_property, $value);
		}
		
		if ($this->_routeBehaviorName) {
			if ($this->_localize) {
				$this->_paramBehavior = new TMapRouteBehavior($this->_parameter, function ($v) {
					$owner->$this->_property = Prado::localize($v);
				});
			} else {
				$this->_paramBehavior = new TMapRouteBehavior($this->_parameter, [$owner, $this->_property]);
			}
			$appParams->attachBehavior($this->_routeBehaviorName, $this->_paramBehavior);
		}
	}
	
	/**
	 * This removes the Application Parameter handler behavior
	 * @param $owner object the object that this behavior is attached to.
	 */
	public function detach($owner)
	{
		if ($this->_paramBehavior) {
			Prado::getApplication()->getParameters()->detachBehavior($this->_routeBehaviorName);
		}
		parent::detach($owner);
	}
	
	/**
	 * @return string Application parameter key to set the property.
	 */
	public function getParameter()
	{
		return $this->_parameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the property.
	 */
	public function setParameter($value)
	{
		$this->_parameter = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return string Application parameter key to set the property.
	 */
	public function getValidNullValue()
	{
		return $this->_validNullValue;
	}
	
	/**
	 * @param $value string Application parameter key to set the property.
	 */
	public function setValidNullValue($value)
	{
		$this->_validNullValue = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @return string Application parameter key to set the property.
	 */
	public function getProperty()
	{
		return $this->_property;
	}
	
	/**
	 * @param $value string Application parameter key to set the property.
	 */
	public function setProperty($value)
	{
		$this->_property = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return string The default value when there is no property and ValidNullValue is false.
	 */
	public function getDefaultValue()
	{
		return $this->_defaultValue;
	}
	
	/**
	 * @param $value string The default value when there is no property and ValidNullValue is false.
	 */
	public function setDefaultValue($value)
	{
		$this->_defaultValue = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return string should the parameter or defaultValue be localized.
	 */
	public function getLocalize()
	{
		return $this->_localize;
	}
	
	/**
	 * @param $value string should the parameter or defaultValue be localized.
	 */
	public function setLocalize($value)
	{
		$this->_localize = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @return string The TMap Routing Behavior Name for changes on the Parameter key updating the Property.
	 */
	public function getRouteBehaviorName()
	{
		return $this->_routeBehaviorName;
	}
	
	/**
	 * @param $value string The TMap Routing Behavior Name for changes on the Parameter key updating the Property.
	 */
	public function setRouteBehaviorName($value)
	{
		if (!$this->_paramBehavior) {
			$this->_routeBehaviorName = TPropertyValue::ensureString($value);
		} else {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_name_after_initialize');
		}
	}
}
