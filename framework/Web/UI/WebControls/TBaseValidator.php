<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\IValidator;

/**
 * TBaseValidator class
 *
 * TBaseValidator serves as the base class for validator controls.
 *
 * Validation is performed when a postback control, such as a TButton, a TLinkButton
 * or a TTextBox (under AutoPostBack mode) is submitting the page and
 * its <b>CausesValidation</b> property is true.
 * You can also manually perform validation by calling {@link TPage::validate()}.
 * The input control to be validated is specified by {@link setControlToValidate ControlToValidate}.
 *
 * Validator controls always validate the associated input control on the serve side.
 * In addition, if {@link getEnableClientScript EnableClientScript} is true,
 * validation will also be performed on the client-side using javascript.
 * Client-side validation will validate user input before it is sent to the server.
 * The form data will not be submitted if any error is detected. This avoids
 * the round-trip of information necessary for server-side validation.
 *
 * You can use multiple validator controls to validate a single input control,
 * each responsible for validating against a different criteria.
 * For example, on a user registration form, you may want to make sure the user
 * enters a value in the username text box, and the input must consist of only word
 * characters. You can use a {@link TRequiredFieldValidator} to ensure the input
 * of username and a {@link TRegularExpressionValidator} to ensure the proper input.
 *
 * If an input control fails validation, the text specified by the {@link setErrorMessage ErrorMessage}
 * property is displayed in the validation control. However, if the {@link setText Text}
 * property is set, it will be displayed instead. If both {@link setErrorMessage ErrorMessage}
 * and {@link setText Text} are empty, the body content of the validator will
 * be displayed. Error display is controlled by {@link setDisplay Display} property.
 *
 * You can also customized the client-side behaviour by adding javascript
 * code to the subproperties of the {@link getClientSide ClientSide}
 * property. See quickstart documentation for further details.
 *
 * You can also place a {@link TValidationSummary} control on a page to display error messages
 * from the validators together. In this case, only the {@link setErrorMessage ErrorMessage}
 * property of the validators will be displayed in the {@link TValidationSummary} control.
 *
 * Validators can be partitioned into validation groups by setting their
 * {@link setValidationGroup ValidationGroup} property. If the control causing the
 * validation also sets its ValidationGroup property, only those validators having
 * the same ValidationGroup value will do input validation.
 *
 * Note, the {@link TPage::getIsValid IsValid} property of the current {@link TPage}
 * instance will be automatically updated by the validation process which occurs
 * after {@link TPage::onLoad onLoad} of {@link TPage} and before the postback events.
 * Therefore, if you use the {@link TPage::getIsValid()} property in
 * the {@link TPage::onLoad()} method, you must first explicitly call
 * the {@link TPage::validate()} method.
 *
 * <b>Notes to Inheritors</b>  When you inherit from TBaseValidator, you must
 * override the method {@link evaluateIsValid}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
abstract class TBaseValidator extends TLabel implements IValidator
{
	/**
	 * @var bool whether the validation succeeds
	 */
	private $_isValid = true;
	/**
	 * @var bool whether the validator has been registered with the page
	 */
	private $_registered = false;
	/**
	 * @var TValidatorClientSide validator client-script options.
	 */
	private $_clientSide;
	/**
	 * Controls for which the client-side validation3.js file needs to handle
	 * them specially.
	 * @var array list of control class names
	 */
	private static $_clientClass = [
		// normal controls needing special handling to extract their values
		'Prado\Web\UI\WebControls\TCheckBox' => 'TCheckBox',
		'Prado\Web\UI\WebControls\TDatePicker' => 'TDatePicker',
		'Prado\Web\UI\WebControls\THtmlArea' => 'THtmlArea',
		'Prado\Web\UI\WebControls\THtmlArea4' => 'THtmlArea4',
		'Prado\Web\UI\WebControls\TReCaptcha2' => 'TReCaptcha2',
		// list controls
		'Prado\Web\UI\WebControls\TCheckBoxList' => 'TCheckBoxList',
		'Prado\Web\UI\WebControls\TListBox' => 'TListBox',
		'Prado\Web\UI\WebControls\TRadioButton' => 'TRadioButton',
	];

	/**
	 * Constructor.
	 * This method sets the foreground color to red.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setForeColor('red');
	}

	/**
	 * Registers the validator with page.
	 * @param mixed $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->getValidators()->add($this);
		$this->_registered = true;
	}

	/**
	 * Unregisters the validator from page.
	 * @param mixed $param event parameter
	 */
	public function onUnload($param)
	{
		if ($this->_registered && ($page = $this->getPage()) !== null) {
			$page->getValidators()->remove($this);
		}
		$this->_registered = false;
		parent::onUnload($param);
	}

	/**
	 * Adds attributes to renderer. Calls parent implementation and renders the
	 * client control scripts.
	 * @param THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$display = $this->getDisplay();
		$visible = $this->getEnabled(true) && !$this->getIsValid();
		if ($display === TValidatorDisplayStyle::None || (!$visible && $display === TValidatorDisplayStyle::Dynamic)) {
			$writer->addStyleAttribute('display', 'none');
		} elseif (!$visible) {
			$writer->addStyleAttribute('visibility', 'hidden');
		}
		$writer->addAttribute('id', $this->getClientID());
		parent::addAttributesToRender($writer);
		$this->renderClientControlScript($writer);
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$control = $this->getValidationTarget();
		$options['ID'] = $this->getClientID();
		$options['FormID'] = $this->getPage()->getForm()->getClientID();
		$options['Display'] = $this->getDisplay();
		$options['ErrorMessage'] = $this->getErrorMessage();
		if ($this->getFocusOnError()) {
			$options['FocusOnError'] = $this->getFocusOnError();
			$options['FocusElementID'] = $this->getFocusElementID();
		}
		$options['ValidationGroup'] = $this->getValidationGroup();
		if ($control) {
			$options['ControlToValidate'] = $control->getClientID();
		}
		$options['ControlCssClass'] = $this->getControlCssClass();

		$options['ControlType'] = $this->getClientControlClass($control);
		$options['Enabled'] = $this->getEnabled(true);

		//get date format from date picker target control
		if ($control instanceof TDatePicker) {
			$options['DateFormat'] = $control->getDateFormat();
		}

		$options = array_merge($options, $this->getClientSide()->getOptions()->toArray());

		return $options;
	}

	/**
	 * Gets the Control type for client-side validation. If new cases exists in
	 * TBaseValidator::$_clientClass, be sure to update the corresponding
	 * "Javascript/validation3.js" file as well.
	 * @param TControl $control control to validate.
	 * @return string control type for client-side validation.
	 */
	private function getClientControlClass($control)
	{
		foreach (self::$_clientClass as $fullName => $shortName) {
			if ($control instanceof $fullName) {
				return $shortName;
			}
		}
		$reflectionClass = new \ReflectionClass($control);
		return $reflectionClass->getShortName();
	}

	/**
	 * Gets the TValidatorClientSide that allows modification of the client-
	 * side validator events.
	 *
	 * The client-side validator supports the following events.
	 * # <tt>OnValidate</tt> -- raised before client-side validation is
	 * executed.
	 * # <tt>OnValidationSuccess</tt> -- raised after client-side validation is completed
	 * and is successfull, overrides default validator error messages updates.
	 * # <tt>OnValidationError</tt> -- raised after client-side validation is completed
	 * and failed, overrides default validator error message updates.
	 *
	 * You can attach custom javascript code to each of these events
	 *
	 * @return TValidatorClientSide javascript validator event options.
	 */
	public function getClientSide()
	{
		if ($this->_clientSide === null) {
			$this->_clientSide = $this->createClientSide();
		}
		return $this->_clientSide;
	}

	/**
	 * @return TValidatorClientSide javascript validator event options.
	 */
	protected function createClientSide()
	{
		return new TValidatorClientSide;
	}

	/**
	 * Renders the javascript code to the end script.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param THtmlWriter $writer the renderer
	 */
	public function renderClientControlScript($writer)
	{
		$scripts = $this->getPage()->getClientScript();
		if ($this->getEnableClientScript()) {
			$scripts->registerPradoScript('validator');
		}
		$formID = $this->getPage()->getForm()->getClientID();
		$scriptKey = "TBaseValidator:$formID";
		if ($this->getEnableClientScript() && !$scripts->isEndScriptRegistered($scriptKey)) {
			$manager['FormID'] = $formID;
			$options = TJavaScript::encode($manager);
			$scripts->registerEndScript($scriptKey, "new Prado.ValidationManager({$options});");
		}
		if ($this->getEnableClientScript()) {
			$this->registerClientScriptValidator();
		}
	}

	/**
	 * Override parent implementation to update the control CSS Class before
	 * the validated control is rendered
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->updateControlCssClass();
	}

	/**
	 * Update the ControlToValidate component's css class depending
	 * if the ControlCssClass property is set, and whether this is valid.
	 * @return bool true if change, false otherwise.
	 */
	protected function updateControlCssClass()
	{
		if (($cssClass = $this->getControlCssClass()) !== '') {
			$control = $this->getValidationTarget();
			if ($control instanceof TWebControl) {
				$class = preg_replace('/ ' . preg_quote($cssClass) . '/', '', $control->getCssClass());
				if (!$this->getIsValid()) {
					$class .= ' ' . $cssClass;
					$control->setCssClass($class);
				} elseif ($control->getIsValid()) {
					$control->setCssClass($class);
				}
			}
		}
	}

	/**
	 * Registers the individual validator client-side javascript code.
	 */
	protected function registerClientScriptValidator()
	{
		$key = 'prado:' . $this->getClientID();
		if (!$this->getPage()->getClientScript()->isEndScriptRegistered($key)) {
			$options = TJavaScript::encode($this->getClientScriptOptions());
			$script = 'new ' . $this->getClientClassName() . '(' . $options . ');';
			$this->getPage()->getClientScript()->registerEndScript($key, $script);
		}
	}

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	abstract protected function getClientClassName();

	/**
	 * This method overrides the parent implementation to forbid setting ForControl.
	 * @param string $value the associated control ID
	 * @throws TNotSupportedException whenever this method is called
	 */
	public function setForControl($value)
	{
		throw new TNotSupportedException('basevalidator_forcontrol_unsupported', get_class($this));
	}

	/**
	 * This method overrides parent's implementation by setting {@link setIsValid IsValid} to true if disabled.
	 * @param bool $value whether the validator is enabled.
	 */
	public function setEnabled($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		parent::setEnabled($value);
		if (!$value) {
			$this->_isValid = true;
		}
	}

	/**
	 * @return TValidatorDisplayStyle the style of displaying the error message. Defaults to TValidatorDisplayStyle::Fixed.
	 */
	public function getDisplay()
	{
		return $this->getViewState('Display', TValidatorDisplayStyle::Fixed);
	}

	/**
	 * @param TValidatorDisplayStyle $value the style of displaying the error message
	 */
	public function setDisplay($value)
	{
		$this->setViewState('Display', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TValidatorDisplayStyle'), TValidatorDisplayStyle::Fixed);
	}

	/**
	 * @return bool whether client-side validation is enabled.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * @param bool $value whether client-side validation is enabled.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the text for the error message.
	 */
	public function getErrorMessage()
	{
		return $this->getViewState('ErrorMessage', '');
	}

	/**
	 * Sets the text for the error message.
	 * @param string $value the error message
	 */
	public function setErrorMessage($value)
	{
		$this->setViewState('ErrorMessage', $value, '');
	}

	/**
	 * @return string the ID path of the input control to validate
	 */
	public function getControlToValidate()
	{
		return $this->getViewState('ControlToValidate', '');
	}

	/**
	 * Sets the ID path of the input control to validate.
	 * The ID path is the dot-connected IDs of the controls reaching from
	 * the validator's naming container to the target control.
	 * @param string $value the ID path
	 */
	public function setControlToValidate($value)
	{
		$this->setViewState('ControlToValidate', $value, '');
	}

	/**
	 * @return bool whether to set focus at the validating place if the validation fails. Defaults to false.
	 */
	public function getFocusOnError()
	{
		return $this->getViewState('FocusOnError', false);
	}

	/**
	 * @param bool $value whether to set focus at the validating place if the validation fails
	 */
	public function setFocusOnError($value)
	{
		$this->setViewState('FocusOnError', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Gets the ID of the HTML element that will receive focus if validation fails and {@link getFocusOnError FocusOnError} is true.
	 * Defaults to the client ID of the {@link getControlToValidate ControlToValidate}.
	 * @return string the ID of the HTML element to receive focus
	 */
	public function getFocusElementID()
	{
		if (($id = $this->getViewState('FocusElementID', '')) === '') {
			$target = $this->getValidationTarget();
			/* Workaround: TCheckBoxList and TRadioButtonList nests the actual
			 * inputs inside a table; we ensure the first input gets focused
			 */
			if ($target instanceof TCheckBoxList && $target->getItemCount() > 0) {
				$id = $target->getClientID() . '_c0';
			} else {
				$id = $target->getClientID();
			}
		}
		return $id;
	}

	/**
	 * Sets the ID of the HTML element that will receive focus if validation fails and {@link getFocusOnError FocusOnError} is true.
	 * @param string $value the ID of the HTML element to receive focus
	 */
	public function setFocusElementID($value)
	{
		$this->setViewState('FocusElementID', $value, '');
	}

	/**
	 * @return string the group which this validator belongs to
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group which this validator belongs to
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * @return bool whether the validation succeeds
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * Sets the value indicating whether the validation succeeds
	 * @param bool $value whether the validation succeeds
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @throws TConfigurationException if {@link getControlToValidate
	 * ControlToValidate} is empty or does not point to a valid control
	 * @return TControl control to be validated. Null if no control is found.
	 */
	public function getValidationTarget()
	{
		if (($id = $this->getControlToValidate()) !== '' && ($control = $this->findControl($id)) !== null) {
			return $control;
		} else {
			throw new TConfigurationException('basevalidator_controltovalidate_invalid', get_class($this));
		}
	}

	/**
	 * Retrieves the property value of the control being validated.
	 * @param TControl $control control being validated
	 * @throws TInvalidDataTypeException if the control to be validated does not implement {@link \Prado\Web\UI\IValidatable}.
	 * @return string property value to be validated
	 */
	protected function getValidationValue($control)
	{
		if ($control instanceof \Prado\Web\UI\IValidatable) {
			return $control->getValidationPropertyValue();
		} else {
			throw new TInvalidDataTypeException('basevalidator_validatable_required', get_class($this));
		}
	}

	/**
	 * Validates the specified control.
	 * Do not override this method. Override {@link evaluateIsValid} instead.
	 * @return bool whether the validation succeeds
	 */
	public function validate()
	{
		$this->onValidate();
		if ($this->getVisible(true) && $this->getEnabled(true)) {
			$target = $this->getValidationTarget();
			// if the target is not a disabled web control
			if ($target === null ||
				($target !== null &&
				!($target instanceof TWebControl && !$target->getEnabled(true)))) {
				if ($this->evaluateIsValid()) {
					$this->setIsValid(true);
					$this->onValidationSuccess();
				} else {
					if ($target) {
						$target->setIsValid(false);
					}
					$this->setIsValid(false);
					$this->onValidationError();
				}
			} else {
				$this->evaluateIsValid();
				$this->setIsValid(true);
				$this->onValidationSuccess();
			}
		} else {
			$this->setIsValid(true);
		}
		return $this->getIsValid();
	}

	/**
	 * @return string the css class that is applied to the control being validated in case the validation fails
	 */
	public function getControlCssClass()
	{
		return $this->getViewState('ControlCssClass', '');
	}

	/**
	 * @param string $value the css class that is applied to the control being validated in case the validation fails
	 */
	public function setControlCssClass($value)
	{
		$this->setViewState('ControlCssClass', $value, '');
	}

	/**
	 * This is the major method for validation.
	 * Derived classes should implement this method to provide customized validation.
	 * @return bool whether the validation succeeds
	 */
	abstract protected function evaluateIsValid();

	/**
	 * This event is raised when the validator succeeds in validation.
	 */
	public function onValidationSuccess()
	{
		$this->raiseEvent('OnValidationSuccess', $this, null);
	}

	/**
	 * This event is raised when the validator fails in validation.
	 */
	public function onValidationError()
	{
		$this->raiseEvent('OnValidationError', $this, null);
	}

	/**
	 * This event is raised right before the validator starts to perform validation.
	 * You may use this event to change the behavior of validation.
	 * For example, you may disable the validator if certain condition is satisfied.
	 * Note, the event will NOT be raised if the validator is invisible.
	 */
	public function onValidate()
	{
		$this->raiseEvent('OnValidate', $this, null);
	}

	/**
	 * Renders the validator control.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) !== '') {
			$writer->write($text);
		} elseif (($text = $this->getErrorMessage()) !== '') {
			$writer->write($text);
		} else {
			parent::renderContents($writer);
		}
	}
}
