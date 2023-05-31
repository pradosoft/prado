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
	 * @var object {@link TMapRouteBehavior} that routes changes from the parameter to the property
	 */
	private $_paramBehavior;

	/**
	 * @var string the name of the installed behavior.
	 */
	protected $_routeBehaviorName;

	/** @var bool is the behavior attached */
	private $_initialized = false;

	/**
	 * This method sets the Owner Property to the Application Parameter of Parameter. When
	 * {@link getRouteBehaviorName} is set, a {@link TMapRouteBehavior} is attached to
	 * the Application Parameter on the key so any changes are also routed to the Property.
	 * @param object $owner the object to which this behavior is being attached
	 * @throws TConfigurationException when missing the parameter, property, or property is not able to set
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		if (!$this->getEnabled()) {
			$this->_initialized = true;
			return;
		}
		if (!$this->_parameter) {
			throw new TConfigurationException('parameterizebehavior_no_parameter');
		}
		if (!$this->_property) {
			throw new TConfigurationException('parameterizebehavior_no_property');
		}
		if (!$owner->canSetProperty($this->_property)) {
			if ($owner->canGetProperty($this->_property)) {
				throw new TConfigurationException('parameterizebehavior_owner_get_only_property', $this->_property);
			} else {
				throw new TConfigurationException('parameterizebehavior_owner_has_no_property', $this->_property);
			}
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
		$this->_initialized = true;

		$this->attachParamMapRoute();
	}

	/**
	 *
	 *
	 * @param $owner The owner to receive parameter changes
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
			$appParams = Prado::getApplication()->getParameters();
			$appParams->attachBehavior($this->_routeBehaviorName, $this->_paramBehavior);
		}
	}


	/**
	 * This attaches and detaches the routing behavior on the Application Parameters.
	 * @param bool $enabled whether this behavior is enabled
	 */
	public function setEnabled($enabled)
	{
		if ($enabled == true && !$this->_paramBehavior) {
			$this->attachParamMapRoute();
		} elseif ($enabled == false && $this->_paramBehavior) {
			Prado::getApplication()->getParameters()->detachBehavior($this->_routeBehaviorName);
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
			Prado::getApplication()->getParameters()->detachBehavior($this->_routeBehaviorName);
			$this->_routeBehaviorName = null;
		}
		$this->_initialized = false;
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
		$this->_parameter = TPropertyValue::ensureString($value);
		if ($this->_paramBehavior) {
			if (!strlen($value)) {
				throw new TInvalidOperationException('parameterizebehavior_cannot_set_parameter_to_blank_after_attach');
			}
			$this->_paramBehavior->setParameter($value);
		}
	}

	/**
	 * @return string Application parameter key to set the property.
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_validNullValue_after_attach');
		}
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_property_after_attach');
		}
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_defaultValue_after_attach');
		}
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
	 * @param string $value should the parameter or defaultValue be localized.
	 */
	public function setLocalize($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_localize_after_attach');
		}
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('parameterizebehavior_cannot_set_routeBehaviorName_after_attach');
		}
		$this->_routeBehaviorName = TPropertyValue::ensureString($value);
	}
}
