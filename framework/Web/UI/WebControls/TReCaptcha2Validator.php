<?php

/**
 * TReCaptcha2Validator class file
 *
 * @author Cristian Camilo Naranjo Valencia
 * @link http://icolectiva.co
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Exception;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TReCaptcha2Validator class
 *
 * TReCaptcha2Validator validates a reCAPTCHA represented by a {@link TReCaptcha} control.
 * The input control fails validation if th user did not pass the humanity test.
 *
 * To use TReCaptcha2Validator, specify the {@link setCaptchaControl CaptchaControl}
 * to be the ID path of the {@link TReCaptcha} control.
 *
 * @author Cristian Camilo Naranjo Valencia
 * @package Prado\Web\UI\WebControls
 * @since 3.3.1
 */

class TReCaptcha2Validator extends TBaseValidator
{
	protected $_isvalid;

	protected function getClientClassName()
	{
		return 'Prado.WebUI.TReCaptcha2Validator';
	}
	public function getEnableClientScript()
	{
		return true;
	}
	protected function getCaptchaControl()
	{
		$control = $this->getValidationTarget();
		if (!$control) {
			throw new Exception('No target control specified for TReCaptcha2Validator');
		}
		if (!($control instanceof TReCaptcha2)) {
			throw new Exception('TReCaptcha2Validator only works with TReCaptcha2 controls');
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
				'jQuery("#' . $control->getClientID() . '").on("change", ' . TJavaScript::quoteString('#' . $control->getResponseFieldName()) . ', function() { ',
					$fn . '("1");',
				'});',
			]));
		}
	}
}
