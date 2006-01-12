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
 * Validation is performed when a button control, such a TButton, a TLinkButton
 * or a TImageButton is clicked and the <b>CausesValidation</b> of these controls is true.
 * You can also manually perform validation by using the validate() method of the TPage class.
 *
 * Validator controls always validate the associated input control on the server.
 * TValidation controls also have complete client-side implementation that allow
 * DHTML supported browsers to perform validation on the client via Javascript.
 * Client-side validation will validate user input before it is sent to the server.
 * The form data will not be submitted if any error is detected. This avoids
 *  the round-trip of information necessary for server-side validation.
 *
 * You can use multiple validator controls to validate an individual input control,
 * each responsible for validating different criteria. For example, on a user registration
 * form, you may want to make sure the user enters a value in the username text box,
 * and the input must consist of only word characters. You can use a TRequiredFieldValidator
 * to ensure the input of username and a TRegularExpressionValidator to ensure the proper
 * input.
 *
 * If an input control fails validation, the text specified by the <b>ErrorMessage</b>
 * property is displayed in the validation control. If the <b>Text</b> property is set
 * it will be displayed instead, however. If both <b>ErrorMessage</b> and <b>Text</b>
 * are empty, the body content of the validator will be displayed.
 *
 * You can also place a <b>TValidationSummary</b> control on the page to display error messages
 * from the validators together. In this case, only the <b>ErrorMessage</b> property of the
 * validators will be displayed in the TValidationSummary control.
 *
 * Note, the <b>IsValid</b> property of the current TPage instance will be automatically
 * updated by the validation process which occurs after <b>OnLoad</b> of TPage and
 * before the postback events. Therefore, if you use the <b>IsValid</b>
 * property in the <b>OnLoad</b> event of TPage, you must first explicitly call
 * the validate() method of TPage. As an alternative, you can place your code
 * in the postback event handler, such as <b>OnClick</b> or <b>OnCommand</b>,
 * instead of <b>OnLoad</b> event.
 *
 * Note, to use validators derived from this control, you have to
 * copy the file "<framework>/js/prado_validator.js" to the "js" directory
 * which should be under the directory containing the entry script file.
 *
 * <b>Notes to Inheritors</b>  When you inherit from the TBaseValidator class,
 * you must override the method {@link evaluateIsValid}.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>EnableClientScript</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets a value indicating whether client-side validation is enabled.
 * - <b>Display</b>, string, default=Static, kept in viewstate
 *   <br>Gets or sets the display behavior (None, Static, Dynamic) of the error message in a validation control.
 * - <b>ControlToValidate</b>, string, kept in viewstate
 *   <br>Gets or sets the input control to validate. This property must be set to
 *   the ID path of an input control. The ID path is the dot-connected IDs of
 *   the controls reaching from the validator's parent control to the target control.
 *   For example, if HomePage is the parent of Validator and SideBar controls, and
 *   SideBar is the parent of UserName control, then the ID path for UserName
 *   would be "SideBar.UserName" if UserName is to be validated by Validator.
 * - <b>Text</b>, string, kept in viewstate
 *   <br>Gets or sets the text of TBaseValidator control.
 * - <b>ErrorMessage</b>, string, kept in viewstate
 *   <br>Gets or sets the text for the error message.
 * - <b>EncodeText</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets the value indicating whether Text and ErrorMessage should be HTML-encoded when rendering.
 * - <b>IsValid</b>, boolean, default=true
 *   <br>Gets or sets a value that indicates whether the associated input control passes validation.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
abstract class TBaseValidator extends TLabel implements IValidator
{
	/**
	 * whether the validation succeeds
	 * @var boolean
	 */
	private $_isValid=true;
	private $_registered=false;

	public function __construct()
	{
		parent::__construct();
		$this->setForeColor('red');
	}

	protected function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->getValidators()->add($this);
		$this->_registered=true;
	}

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
		$scriptKey = "TBaseValidator";
		if($this->getEnableClientScript() && !$scripts->isEndScriptRegistered($scriptKey))
		{
			$scripts->registerPradoScript('validator');
			$formID=$this->getPage()->getForm()->getClientID();
			$js = "Prado.Validation.AddForm('$formID');";
			$scripts->registerEndScript($scriptKey, $js);
		}
		if($this->getEnableClientScript())
			$this->renderClientScriptValidator();
		parent::onPreRender($param);
	}

	/**
	 * Renders the individual validator client-side javascript code.
	 */
	protected function renderClientScriptValidator()
	{
		if($this->getEnabled(true) && $this->getEnableClientScript())
		{
			$class = get_class($this);
			$scriptKey = "prado:".$this->getClientID();
			$scripts = $this->getPage()->getClientScript();
			$options = TJavascript::toList($this->getClientScriptOptions());
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
	 * Sets the value indicating whether client-side validation is enabled.
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
		return $this->getText();
		//return $this->getViewState('ErrorMessage','');
	}

	/**
	 * Sets the text for the error message.
	 * @param string the error message
	 */
	public function setErrorMessage($value)
	{
		$this->setText($value);
		//$this->setViewState('ErrorMessage',$value,'');
	}

	/**
	 * @return string the ID path of the input control to validate
	 */
	public function getControlToValidate()
	{
		return $this->getViewState('ControlToValidate','');
	}

	/**
	 * Sets the ID path of the input control to validate
	 * @param string the ID path
	 */
	public function setControlToValidate($value)
	{
		$this->setViewState('ControlToValidate',$value,'');
	}

	/**
	 * @return boolean whether to set focus at the validating place if the validation fails. Defaults to true.
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

	protected function getValidationTarget()
	{
		if(($id=$this->getControlToValidate())!=='')
			return $this->findControl($id);
		else
			return null;
	}

	protected function getValidationValue($control)
	{
		if($control instanceof IValidatable)
		{
			$value=$control->getValidationPropertyValue();
			//if($value instanceof TListItem)
			//	return $value->getValue();
			//else
				return TPropertyValue::ensureString($value);
		}
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
}
?>