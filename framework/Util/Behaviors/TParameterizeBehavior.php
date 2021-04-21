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
 * TParameterizeBehavior sets a specific Property on the owner object
 * to a specific application parameter.  It also can install a behavior
 * on the Application parameters to apply any changes to the application
 * parameter to then route the change to the property.
 *
 * <code>
 *	<behavior name="PageThemeParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="ThemeName" Property="Theme" />
 *  <behavior name="PageTitle" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="Page" Parameter="TPageTitle" Property="Title" />
 *  <behavior name="AuthManagerExpireParameter" Class="Prado\Util\Behaviors\TParameterizeBehavior" AttachTo="module:auth" Parameter="prop:TAuthManager.AuthExpire" Property="AuthExpire" RouteBehaviorName="TAuthManagerAuthExpireRouter" />
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
	 * @var object {@link TMapRouteBehavior} that routes changes from the parameter to the property
	 */
	private $_paramBehavior;
	
	/**
	 * @var the name of the installed behavior.
	 */
	protected $_routeBehaviorName;
	
	/**
	 * This method sets the Owner (TAuthManager) AuthExpire to the Application Parameter of
	 * AuthExpireParameter, and the Owner (TAuthManager) AllowAutoLogin to the Application Parameter of
	 * AuthExpireParameter.
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
		if (($value = $appParams->itemAt($this->_parameter)) || $this->getValidNullValue()) {
			$owner->setSubProperty($this->_property, $value);
		}
		
		if ($this->_routeBehaviorName) {
			$this->_paramBehavior = new TMapRouteBehavior($this->_parameter, [$owner, $this->_property]);
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
	 * @return string Application parameter key to set the TPage.Theme.
	 */
	public function getProperty()
	{
		return $this->_property;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Theme.
	 */
	public function setProperty($value)
	{
		$this->_property = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Theme.
	 */
	public function getRouteBehaviorName()
	{
		return $this->_routeBehaviorName;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Theme.
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
