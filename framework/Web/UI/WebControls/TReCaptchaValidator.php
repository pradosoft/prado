<?php

/**
 * TReCaptchaValidator class file
 *
 * @author Bérczi Gábor <gabor.berczi@devworx.hu>
 * @link http://www.devworx.hu/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Exception;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TReCaptchaValidator class
 *
 * TReCaptchaValidator validates user input against a reCAPTCHA represented by
 * a {@link TReCaptcha} control. The input control fails validation if its value
 * is not the same as the token displayed in reCAPTCHA. Note, if the user does
 * not enter any thing, it is still considered as failing the validation.
 *
 * To use TReCaptchaValidator, specify the {@link setCaptchaControl CaptchaControl}
 * to be the ID path of the {@link TReCaptcha} control.
 *
 * @author Bérczi Gábor <gabor.berczi@devworx.hu>
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */
class TReCaptchaValidator extends TBaseValidator
{
	protected $_isvalid;

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TReCaptchaValidator';
	}

	public function getEnableClientScript()
	{
		return true;
	}

	protected function getCaptchaControl()
	{
		$control = $this->getValidationTarget();
		if (!$control) {
			throw new Exception('No target control specified for TReCaptchaValidator');
		}
		if (!($control instanceof TReCaptcha)) {
			throw new Exception('TReCaptchaValidator only works with TReCaptcha controls');
		}
		return $control;
	}

	public function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['ResponseFieldName'] = $this->getCaptchaControl()->getResponseFieldName();
		return $options;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input control has the same value
	 * as the one displayed in the corresponding RECAPTCHA control.
	 *
	 * @return bool whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		// check validity only once (if trying to evaulate multiple times, all redundant checks would fail)
		if (null === $this->_isvalid) {
			$control = $this->getCaptchaControl();
			$this->_isvalid = $control->validate();
		}
		return ($this->_isvalid == true);
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$cs = $this->Page->getClientScript();
		$cs->registerPradoScript('validator');

		// communicate validation status to the client side
		$value = $this->_isvalid === false ? '0' : '1';
		$cs->registerHiddenField($this->getClientID() . '_1', $value);

		// update validator display
		if ($control = $this->getValidationTarget()) {
			$fn = 'captchaUpdateValidatorStatus_' . $this->getClientID();

			// check if we need to request a new captcha too
			if ($this->Page->IsCallback) {
				if ($control->getVisible(true)) {
					if (null !== $this->_isvalid) {
						// if the response has been tested and we reach the pre-render phase
						// then we need to regenerate the token, because it won't test positive
						// anymore, even if solves correctly

						$control->regenerateToken();
					}
				}
			}

			$cs->registerEndScript($this->getClientID() . '::validate', implode(' ', [
				// this function will be used to update the validator
				'function ' . $fn . '(valid)',
				'{',
				'  jQuery(' . TJavaScript::quoteString('#' . $this->getClientID() . '_1') . ').val(valid);',
				'  Prado.Validation.validateControl(' . TJavaScript::quoteString($control->ClientID) . '); ',
				'}',
				'',
				// update the validator to the result if we're in a callback
				// (if we're in initial rendering or a postback then the result will be rendered directly to the page html anyway)
				$this->Page->IsCallback ? $fn . '(' . $value . ');' : '',
				'',
				// install event handler that clears the validation error when user changes the captcha response field
				'jQuery("#' . $control->getClientID() . '").on("keyup", ' . TJavaScript::quoteString('#' . $control->getResponseFieldName()) . ', function() { ',
					$fn . '("1");',
				'});',
			]));
		}
	}
}
