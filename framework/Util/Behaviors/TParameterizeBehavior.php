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
 * parameter to then route the change to the property by setting the
 * RouteBehaviorName.
 *
 * The property holds a copy of the parameter value.  A final application
 * parameter ({@see \Prado\TApplicationParameters}) sends no later changes;
 * code may still set the property directly.
 *
 * ```php
 *	<behavior name="PageThemeParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="ThemeName" Property="Theme" DefaultValue="Basic"/>
 *  <behavior name="PageTitle" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="TPageTitle" Property="Title" Localize="true"/>
 *  <behavior name="AuthManagerExpireParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="module:auth" Parameter="prop:TAuthManager.AuthExpire" Property="AuthExpire" RouteBehaviorName="TAuthManagerAuthExpireRouter" />
 *	<behavior name="TSecurityManagerValidationKey" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="TSecurityManager" Parameter="prop:TSecurityManager.ValidationKey" Property="ValidationKey" />
 *	<behavior name="TSecurityManagerEncryptionKey" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachToClass="TSecurityManager" Parameter="prop:TSecurityManager.EncryptionKey" Property="EncryptionKey" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TParameterizeBehavior extends \Prado\Util\TBehavior
{
	/**
	 * @var string the key to the application parameter
	 */
	private $_parameter;

	/**
	 * @var bool whether or not a null parameter value should be set on the property
	 */
	private $_validNullValue;

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
	 * @var ?object {@see \Prado\Util\Behaviors\TMapRouteBehavior} that routes changes from the parameter to the property
	 */
	private $_paramBehavior;

	/**
	 * @var ?string the name of the installed behavior.
	 */
	protected $_routeBehaviorName;

	/**
	 * This method sets the Owner Property to the Application Parameter of Parameter when
	 * the behavior is enabled. When disabled, {@see setEnabled} applies the parameter later.
	 * @param object $owner the object to which this behavior is being attached
	 * @throws TConfigurationException when missing the parameter, property, or property is not able to set
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		if ($this->getEnabled()) {
			$this->applyParameter($owner);
		}
	}

	/**
	 * Sets the owner Property to the Application Parameter, or to DefaultValue when the
	 * parameter is null and ValidNullValue is false. When {@see getRouteBehaviorName} is set,
	 * a {@see \Prado\Util\Behaviors\TMapRouteBehavior} is attached to the Application
	 * Parameters on the key so later changes are routed to the Property.
	 * @param object $owner the object whose Property is set.
	 * @throws TConfigurationException when missing the parameter, property, or property is not able to set
	 * @since 4.4.0
	 */
	protected function applyParameter($owner)
	{
		$parameter = $this->getParameter();
		$property = $this->getProperty();
		if (!$parameter) {
			throw new TConfigurationException('parameterizebehavior_no_parameter');
		}
		if (!$property) {
			throw new TConfigurationException('parameterizebehavior_no_property');
		}
		if (!$owner->canSetProperty($property)) {
			if ($owner->canGetProperty($property)) {
				throw new TConfigurationException('parameterizebehavior_owner_get_only_property', $property);
			} else {
				throw new TConfigurationException('parameterizebehavior_owner_has_no_property', $property);
			}
		}

		$localize = $this->getLocalize();
		$appParams = $this->getApplication()->getParameters();
		if (($value = $appParams->itemAt($parameter)) !== null || $this->getValidNullValue()) {
			if ($localize && $value && is_string($value)) {
				$value = Prado::localize($value);
			}
			$owner->setSubProperty($property, $value);
		} elseif (($value = $this->getDefaultValue()) !== null) {
			if ($localize && is_string($value)) {
				$value = Prado::localize($value);
			}
			$owner->setSubProperty($property, $value);
		}

		$this->attachParamMapRoute();
	}

	/**
	 * Attaches a {@see TMapRouteBehavior} named RouteBehaviorName to the application
	 * parameters, which sets the owner property when the parameter changes.
	 */
	protected function attachParamMapRoute()
	{
		if ($this->_routeBehaviorName) {
			$owner = $this->getOwner();
			if ($this->_localize) {
				$_property = $this->_property;
				$this->_paramBehavior = new TMapRouteBehavior($this->_parameter, function ($v) use ($owner, $_property) {
					$owner->$_property = Prado::localize($v);
				});
			} else {
				$this->_paramBehavior = new TMapRouteBehavior($this->_parameter, [$owner, 'set' . $this->_property]);
			}
			$appParams = $this->getApplication()->getParameters();
			$appParams->attachBehavior($this->_routeBehaviorName, $this->_paramBehavior);
		}
	}


	/**
	 * Enabling an attached, disabled behavior applies the parameter with {@see applyParameter},
	 * which sets the owner Property and attaches the routing behavior. Disabling detaches the
	 * routing behavior. Without an owner, the parameter waits for {@see attach}.
	 * @param bool $enabled whether this behavior is enabled
	 * @throws TConfigurationException when enabling with a missing parameter, property, or property is not able to set
	 */
	public function setEnabled($enabled)
	{
		$enabled = TPropertyValue::ensureBoolean($enabled);
		if ($enabled && !$this->getEnabled() && ($owner = $this->getOwner())) {
			$this->applyParameter($owner);
		} elseif (!$enabled && $this->_paramBehavior) {
			$this->getApplication()->getParameters()->detachBehavior($this->_routeBehaviorName);
			$this->_paramBehavior = null;
		}
		parent::setEnabled($enabled);
	}

	/**
	 * This removes the Application Parameter handler behavior
	 * @param object $owner the object that this behavior is attached to.
	 */
	public function detach($owner)
	{
		if ($this->_paramBehavior) {
			$this->getApplication()->getParameters()->detachBehavior($this->_routeBehaviorName);
			$this->_paramBehavior = null;
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
	 * @param string $value Application parameter key to set the property.
	 */
	public function setParameter($value)
	{
		$parameter = TPropertyValue::ensureString($value);
		if ($this->_paramBehavior) {
			if ($parameter === '') {
				throw new TInvalidOperationException('parameterizebehavior_cannot_set_parameter_to_blank_after_attach');
			}
			$this->_paramBehavior->setParameter($parameter);
		}
		$this->_parameter = $parameter;
	}

	/**
	 * @return bool Application parameter key to set the property.
	 */
	public function getValidNullValue()
	{
		return $this->_validNullValue;
	}

	/**
	 * @param string $value Application parameter key to set the property.
	 */
	public function setValidNullValue($value)
	{
		$this->assertWithoutOwner('ValidNullValue');
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
	 * @param string $value Application parameter key to set the property.
	 */
	public function setProperty($value)
	{
		$this->assertWithoutOwner('Property');
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
	 * @param string $value The default value when there is no property and ValidNullValue is false.
	 */
	public function setDefaultValue($value)
	{
		$this->assertWithoutOwner('DefaultValue');
		$this->_defaultValue = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool should the parameter or defaultValue be localized.
	 */
	public function getLocalize()
	{
		return $this->_localize;
	}

	/**
	 * @param string $value should the parameter or defaultValue be localized.
	 */
	public function setLocalize($value)
	{
		$this->assertWithoutOwner('Localize');
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
	 * @param string $value The TMap Routing Behavior Name for changes on the Parameter key updating the Property.
	 */
	public function setRouteBehaviorName($value)
	{
		$this->assertWithoutOwner('RouteBehaviorName');
		$this->_routeBehaviorName = TPropertyValue::ensureString($value);
	}
}
