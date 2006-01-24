<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

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
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
abstract class TBaseValidator extends TLabel implements IValidator
{
	/**
	 * @var boolean whether the validation succeeds
	 */
	private $_isValid=true;
	/**
	 * @var boolean whether the validator has been registered with the page
	 */
	private $_registered=false;

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
	 * @param mixed event parameter
	 */
	protected function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->getValidators()->add($this);
		$this->_registered=true;
	}

	/**
	 * Unregisters the validator from page.
	 * @param mixed event parameter
	 */
	protected function onUnload($param)
	{
		if($this->_registered && ($page=$this->getPage())!==null)
			$page->getValidators()->remove($this);
		$this->_registered=false;
		parent::onUnload($param);
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$display=$this->getDisplay();
		$visible=$this->getEnabled(true) && !$this->getIsValid();
		if($display==='None' || (!$visible && $display==='Dynamic'))
			$writer->addStyleAttribute('display','none');
		else if(!$visible)
			$writer->addStyleAttribute('visibility','hidden');
		$writer->addAttribute('id',$this->getClientID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options['id'] = $this->getClientID();
		$options['display'] = $this->getDisplay();
		$options['errormessage'] = $this->getErrorMessage();
		$options['focusonerror'] = $this->getFocusOnError();
		$options['focuselementid'] = $this->getFocusElementID();
		$options['validationgroup'] = $this->getValidationGroup();
		$options['controltovalidate'] = $this->getValidationTarget()->getClientID();
		return $options;
	}

	/**
	 * Renders the javascript code to the end script.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onPreRender($param)
	{
		$scripts = $this->getPage()->getClientScript();
		$formID=$this->getPage()->getForm()->getClientID();
		$scriptKey = "TBaseValidator:$formID";
		if($this->getEnableClientScript() && !$scripts->isEndScriptRegistered($scriptKey))
		{
			$scripts->registerClientScript('validator');
			$scripts->registerEndScript($scriptKey, "Prado.Validation.AddForm('$formID');");
		}
		if($this->getEnableClientScript())
			$this->registerClientScriptValidator();
		parent::onPreRender($param);
	}

	/**
	 * Registers the individual validator client-side javascript code.
	 */
	protected function registerClientScriptValidator()
	{
		if($this->getEnabled(true))
		{
			$class = get_class($this);
			$scriptKey = "prado:".$this->getClientID();
			$scripts = $this->getPage()->getClientScript();
			$serializer = new TJavascriptSerializer($this->getClientScriptOptions());
			$options = $serializer->toJavascript();
			$js = "new Prado.Validation(Prado.Validation.{$class}, {$options});";
			$scripts->registerEndScript($scriptKey, $js);
		}
	}

	/**
	 * This method overrides the parent implementation to forbid setting AssociatedControlID.
	 * @param string the associated control ID
	 * @throws TNotSupportedException whenever this method is called
	 */
	public function setAssociatedControlID($value)
	{
		throw new TNotSupportedException('basevalidator_associatedcontrolid_unsupported',get_class($this));
	}

	/**
	 * This method overrides parent's implementation by setting {@link setIsValid IsValid} to true if disabled.
	 * @param boolean whether the validator is enabled.
	 */
	public function setEnabled($value)
	{
		$value=TPropertyValue::ensureBoolean($value);
		parent::setEnabled($value);
		if(!$value)
			$this->_isValid=true;
	}

	/**
	 * @return string the display behavior (None, Static, Dynamic) of the error message in a validation control. Defaults to Static.
	 */
	public function getDisplay()
	{
		return $this->getViewState('Display','Static');
	}

	/**
	 * Sets the display behavior (None, Static, Dynamic) of the error message in a validation control.
	 * @param string the display behavior (None, Static, Dynamic)
	 */
	public function setDisplay($value)
	{
		$this->setViewState('Display',TPropertyValue::ensureEnum($value,array('None','Static','Dynamic')),'Static');
	}

	/**
	 * @return boolean whether client-side validation is enabled.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript',true);
	}

	/**
	 * @param boolean whether client-side validation is enabled.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the text for the error message.
	 */
	public function getErrorMessage()
	{
		return $this->getViewState('ErrorMessage','');
	}

	/**
	 * Sets the text for the error message.
	 * @param string the error message
	 */
	public function setErrorMessage($value)
	{
		$this->setViewState('ErrorMessage',$value,'');
	}

	/**
	 * @return string the ID path of the input control to validate
	 */
	public function getControlToValidate()
	{
		return $this->getViewState('ControlToValidate','');
	}

	/**
	 * Sets the ID path of the input control to validate.
	 * The ID path is the dot-connected IDs of the controls reaching from
	 * the validator's naming container to the target control.
	 * @param string the ID path
	 */
	public function setControlToValidate($value)
	{
		$this->setViewState('ControlToValidate',$value,'');
	}

	/**
	 * @return boolean whether to set focus at the validating place if the validation fails. Defaults to false.
	 */
	public function getFocusOnError()
	{
		return $this->getViewState('FocusOnError',false);
	}

	/**
	 * @param boolean whether to set focus at the validating place if the validation fails
	 */
	public function setFocusOnError($value)
	{
		$this->setViewState('FocusOnError',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * Gets the ID of the HTML element that will receive focus if validation fails and {@link getFocusOnError FocusOnError} is true.
	 * Defaults to the client ID of the {@link getControlToValidate ControlToValidate}.
	 * @return string the ID of the HTML element to receive focus
	 */
	public function getFocusElementID()
	{
		if(($id=$this->getViewState('FocusElementID',''))==='')
			$id=$this->getValidationTarget()->getClientID();
		return $id;
	}

	/**
	 * Sets the ID of the HTML element that will receive focus if validation fails and {@link getFocusOnError FocusOnError} is true.
	 * @param string the ID of the HTML element to receive focus
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
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group which this validator belongs to
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * @return boolean whether the validation succeeds
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * Sets the value indicating whether the validation succeeds
	 * @param boolean whether the validation succeeds
	 */
	public function setIsValid($value)
	{
		$this->_isValid=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return TControl control to be validated. Null if no control is found.
	 * @throw TConfigurationException if {@link getControlToValidate ControlToValidate} is empty or does not point to a valid control
	 */
	protected function getValidationTarget()
	{
		if(($id=$this->getControlToValidate())!=='' && ($control=$this->findControl($id))!==null)
			return $control;
		else
			throw new TConfigurationException('basevalidator_controltovalidate_invalid');
	}

	/**
	 * Retrieves the property value of the control being validated.
	 * @param TControl control being validated
	 * @return string property value to be validated
	 * @throws TInvalidDataTypeException if the control to be validated does not implement {@link IValidatable}.
	 */
	protected function getValidationValue($control)
	{
		if($control instanceof IValidatable)
			return $control->getValidationPropertyValue();
		else
			throw new TInvalidDataTypeException('basevalidator_validatable_required');
	}

	/**
	 * Validates the specified control.
	 * Do not override this method. Override {@link evaluateIsValid} instead.
	 * @return boolean whether the validation succeeds
	 */
	public function validate()
	{
		$this->setIsValid(true);
		$control=$this->getValidationTarget();
		if($control && $this->getVisible(true) && $this->getEnabled())
			$this->setIsValid($this->evaluateIsValid());
		return $this->getIsValid();
	}

	/**
	 * This is the major method for validation.
	 * Derived classes should implement this method to provide customized validation.
	 * @return boolean whether the validation succeeds
	 */
	abstract protected function evaluateIsValid();

	/**
	 * Renders the validator control.
	 * @param THtmlWriter writer for the rendering purpose
	 */
	protected function renderContents($writer)
	{
		if(($text=$this->getText())!=='')
			$writer->write($text);
		else if(($text=$this->getErrorMessage())!=='')
			$writer->write($text);
		else
			parent::renderContents($writer);
	}
}
?>