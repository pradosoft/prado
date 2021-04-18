<?php

/**
 * TAuthManagerParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TAuthManagerParameterBehavior sets the TAuthManager.AuthExpire to an
 * application parameter.  
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TAuthManagerParameterBehavior extends TBehavior 
{
	/**
	 * Name of the Application Parameter Routing Behavior
	 */
	const APP_PARAM_AUTH_EXPIRE_BEHAVIOR_NAME = 'TAuthManagerAuthExpireParameter';
	
	/**
	 * Default AuthExpire Parameter
	 */
	const AUTH_EXPIRE_PARAMETER_NAME = 'prop:TAuthManager.AuthExpire';
	
	/**
	 * @var string the parameter key of the parameter that TAuthManager.AuthExpire is set
	 */
	private $_authExpireParameter = self::AUTH_EXPIRE_PARAMETER_NAME;
	
	/**
	 * @var object {@link TMapRouteBehavior} that routes changes to the parameter
	 * is handled by setPHPTimezone.
	 */
	private $_paramBehavior = null;
	
	/**
	 * This method sets the Owner (TAuthManager) AuthExpire to the Application Parameter of
	 * AuthExpireParameter, and the Owner (TAuthManager) AllowAutoLogin to the Application Parameter of
	  * AuthExpireParameter.
	 * @param $owner object the object to which this behavior is being attached
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		$appParams = Prado::getApplication()->getParameters();
		if($authExpire = $appParams->itemAt($this->_authExpireParameter))
			$owner->setAuthExpire($authExpire);
		
		$this->_paramBehavior = new TMapRouteBehavior($this->_authExpireParameter, [$owner, 'setAuthExpire']);
		$appParams->attachBehavior(self::APP_PARAM_AUTH_EXPIRE_BEHAVIOR_NAME, $this->_paramBehavior);
	}
	
	/**
	 * This removes the Application Parameter handler behavior
	 * @param $owner object the object that this behavior is attached to.
	 */
	public function detach($owner)
	{
		if($this->_paramBehavior) {
			Prado::getApplication()->getParameters()->detachBehavior(self::APP_PARAM_AUTH_EXPIRE_BEHAVIOR_NAME);
		}
		parent::detach($owner);
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Theme.
	 */
	public function getAuthExpireParameter()
	{
		return $this->_authExpireParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Theme.
	 */
	public function setAuthExpireParameter($value)
	{
		$this->_authExpireParameter = $value;
	}
}
