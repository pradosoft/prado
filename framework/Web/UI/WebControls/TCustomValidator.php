<?php
/**
 * TCustomValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Using TBaseValidator class
 */
Prado::using('System.Web.UI.WebControls.TBaseValidator');

/**
 * TCustomValidator class
 *
 * TCustomValidator performs user-defined validation (either
 * server-side or client-side or both) on an input component.
 *
 * To create a server-side validation function, provide a handler for
 * the <b>OnServerValidate</b> event that performs the validation.
 * The data string of the input component to validate can be accessed
 * by the <b>value</b> property of the event parameter which is of type
 * <b>TServerValidateEventParameter</b>. The result of the validation
 * should be stored in the <b>isValid</b> property of the event parameter.
 *
 * To create a client-side validation function, add the client-side
 * validation javascript function to the page template.
 * The function should have the following signature:
 * <code>
 * <script type="text/javascript"><!--
 * function ValidationFunctionName(sender, parameter)
 * {
 *    // if(parameter == ...)
 *    //    return true;
 *    // else
 *    //    return false;
 * }
 * --></script>
 * </code>
 * Use the <b>ClientValidationFunction</b> property to specify the name of
 * the client-side validation script function associated with the TCustomValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TCustomValidator extends TBaseValidator
{
	/**
	 * @return string the name of the custom client-side script function used for validation.
	 */
	public function getClientValidationFunction()
	{
		return $this->getViewState('ClientValidationFunction','');
	}

	/**
	 * Sets the name of the custom client-side script function used for validation.
	 * @param string the script function name
	 */
	public function setClientValidationFunction($value)
	{
		$this->setViewState('ClientValidationFunction',$value,'');
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if {@link onServerValidate} returns true.
	 * @return boolean whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		if(($id=$this->getControlToValidate())!=='')
		{
			if(($control=$this->findControl($id))!==null)
				$value=$this->getValidationValue($control);
			else
				throw new TInvalidDataValueException('customvalidator_controltovalidate_invalid');
			return $this->onServerValidate($value);
		}
		else
			throw new TInvalidDataValueException('customvalidator_controltovalidate_required');
	}

	/**
	 * This method is invoked when the server side validation happens.
	 * It will raise the <b>OnServerValidate</b> event.
	 * The method also allows derived classes to handle the event without attaching a delegate.
	 * <b>Note</b> The derived classes should call parent implementation
	 * to ensure the <b>OnServerValidate</b> event is raised.
	 * @param string the value to be validated
	 * @return boolean whether the value is valid
	 */
	public function onServerValidate($value)
	{
		$param=new TServerValidateEventParameter($value,true);
		$this->raiseEvent('ServerValidate',$this,$param);
		return $param->getIsValid();
	}


	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options=parent::getClientScriptOptions();
		if(($clientJs=$this->getClientValidationFunction())!=='')
			$options['clientvalidationfunction']=$clientJs;
		return $options;
	}
}

/**
 * TServerValidateEventParameter class
 *
 * TServerValidateEventParameter encapsulates the parameter data for
 * <b>ServerValidate</b> event of TCustomValidator components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TServerValidateEventParameter extends TEventParameter
{
	/**
	 * the value to be validated
	 * @var string
	 */
	private $_value='';
	/**
	 * whether the value is valid
	 * @var boolean
	 */
	private $_isValid=true;

	public function __construct($value,$isValid)
	{
		$this->_value=$value;
		$this->setIsValid($isValid);
	}

	public function getValue()
	{
		return $this->_value;
	}

	public function getIsValid()
	{
		return $this->_isValid;
	}

	public function setIsValid($value)
	{
		$this->_isValid=TPropertyValue::ensureBoolean($value);
	}
}
?>