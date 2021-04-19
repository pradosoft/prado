<?php

/**
 * TSecurityManagerParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;

/**
 * TSecurityManagerParameterBehavior sets TSecurityManager validation
 * key and encryption key from a parameter of choice.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TSecurityManagerParameterBehavior extends \Prado\Util\TBehavior 
{
	/**
	 * Default ThemeParameter
	 */
	const VALIDATION_KEY_PARAMETER_NAME = 'prop:TSecurityManager.ValidationKey';
	/**
	 * Default ThemeParameter
	 */
	const ENCRYPTION_KEY_PARAMETER_NAME = 'prop:TSecurityManager.EncryptionKey';
	
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_validationKeyParameter = self::VALIDATION_KEY_PARAMETER_NAME;
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_encryptionKeyParameter = self::ENCRYPTION_KEY_PARAMETER_NAME;
	
	/**
	 * This method sets the Owner ({@link TSecurityManager}) EncryptionKey and ValidationKey
	 * to the Application Variable of ThemeParameter.
	 * @param $owner object object which this behavior is being attached
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if ($validationKey = Prado::getApplication()->getParameters()->itemAt($this->_validationKeyParameter)) {
			$owner->setValidationKey($validationKey);
		}
		if ($encryptionKey = Prado::getApplication()->getParameters()->itemAt($this->_encryptionKeyParameter)) {
			$owner->setEncryptionKey($encryptionKey);
		}
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Title.
	 */
	public function getValidationKeyParameter()
	{
		return $this->_encryptionKeyParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Title.
	 */
	public function setValidationKeyParameter($value)
	{
		$this->_encryptionKeyParameter = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Title.
	 */
	public function getEncryptionKeyParameter()
	{
		return $this->_encryptionKeyParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Title.
	 */
	public function setEncryptionKeyParameter($value)
	{
		$this->_encryptionKeyParameter = TPropertyValue::ensureString($value);
	}
}
