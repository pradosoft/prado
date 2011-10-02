<?php

/**
 * TReCaptchaValidator class file
 *
 * @author Bérczi Gábor <gabor.berczi@devworx.hu>
 * @link http://www.devworx.hu/
 * @copyright Copyright &copy; 2011 DevWorx
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

Prado::using('System.Web.UI.WebControls.TBaseValidator');
Prado::using('System.Web.UI.WebControls.TReCaptcha');

/**
 * TReCaptchaValidator class
 *
 * TReCaptchaValidator validates user input against a reCAPTCHA represented by
 * a {@link TReCaptcha} control. The input control fails validation if its value
 * is not the same as the token displayed in reCAPTCHA. Note, if the user does
 * not enter any thing, it is still considered as failing the validation.
 *
 * To use TReCaptchaValidator, specify the {@link setControlToValidate ControlToValidate}
 * to be the ID path of the {@link TReCaptcha} control.
 *
 * @author Bérczi Gábor <gabor.berczi@devworx.hu>
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
class TReCaptchaValidator extends TBaseValidator
{
	protected $_isvalid = null;

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return '';
	}

	public function getEnableClientScript()
	{
		return false;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input control has the same value
	 * as the one displayed in the corresponding RECAPTCHA control.
	 *
	 * @return boolean whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		// check validity only once (if trying to evaluate multiple times, all redundant checks would fail)
		if (is_null($this->_isvalid))
		{
			$control = $this->getValidationTarget();
			if(!($control instanceof TCaptcha))
				throw new TConfigurationException('recaptchavalidator_captchacontrol_invalid');
			$this->_isvalid = $control->validate();
		}
		return ($this->_isvalid==true);
	}

}

?>