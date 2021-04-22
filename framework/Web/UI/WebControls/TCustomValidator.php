<?php
/**
 * TCustomValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TCustomValidator class
 *
 * TCustomValidator performs user-defined validation (either
 * server-side or client-side or both) on an input component.
 *
 * To create a server-side validation function, provide a handler for
 * the {@link onServerValidate OnServerValidate} event that performs the validation.
 * The data string of the input control to validate can be accessed
 * by {@link TServerValidateEventParameter::getValue Value} of the event parameter.
 * The result of the validation should be stored in the
 * {@link TServerValidateEventParameter::getIsValid IsValid} property of the event
 * parameter.
 *
 * To create a client-side validation function, add the client-side
 * validation javascript function to the page template.
 * The function should have the following signature:
 * <code>
 * <script><!--
 * function ValidationFunctionName(sender, parameter)
 * {
 *    // if(parameter == ...)
 *    //    return true;
 *    // else
 *    //    return false;
 * }
 * --></script>
 * </code>
 * Use the {@link setClientValidationFunction ClientValidationFunction} property
 * to specify the name of the client-side validation script function associated
 * with the TCustomValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TCustomValidator extends TBaseValidator
{
	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TCustomValidator';
	}

	/**
	 * @return string the name of the custom client-side script function used for validation.
	 */
	public function getClientValidationFunction()
	{
		return $this->getViewState('ClientValidationFunction', '');
	}

	/**
	 * Sets the name of the custom client-side script function used for validation.
	 * @param string $value the script function name
	 */
	public function setClientValidationFunction($value)
	{
		$this->setViewState('ClientValidationFunction', $value, '');
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if {@link onServerValidate} returns true.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = '';
		if ($this->getValidationTarget() !== null) {
			$value = $this->getValidationValue($this->getValidationTarget());
		}
		return $this->onServerValidate($value);
	}

	/**
	 * This method is invoked when the server side validation happens.
	 * It will raise the <b>OnServerValidate</b> event.
	 * The method also allows derived classes to handle the event without attaching a delegate.
	 * <b>Note</b> The derived classes should call parent implementation
	 * to ensure the <b>OnServerValidate</b> event is raised.
	 * @param string $value the value to be validated
	 * @return bool whether the value is valid
	 */
	public function onServerValidate($value)
	{
		$param = new TServerValidateEventParameter($value, true);
		$this->raiseEvent('OnServerValidate', $this, $param);
		return $param->getIsValid();
	}

	/**
	 * @throws TInvalidDataTypeException
	 * @return null|\Prado\Web\UI\TControl control to be validated. Null if no control is found.
	 */
	public function getValidationTarget()
	{
		if (($id = $this->getControlToValidate()) !== '' && ($control = $this->findControl($id)) !== null) {
			return $control;
		} elseif (($id = $this->getControlToValidate()) !== '') {
			throw new TInvalidDataTypeException('basevalidator_validatable_required', get_class($this));
		} else {
			return null;
		}
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		if (($clientJs = $this->getClientValidationFunction()) !== '') {
			$options['ClientValidationFunction'] = $clientJs;
		}
		return $options;
	}

	/**
	 * Only register the client-side validator if
	 * {@link setClientValidationFunction ClientValidationFunction} is set.
	 */
	protected function registerClientScriptValidator()
	{
		if ($this->getClientValidationFunction() !== '') {
			parent::registerClientScriptValidator();
		}
	}
}
