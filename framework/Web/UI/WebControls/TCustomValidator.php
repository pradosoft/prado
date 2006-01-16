<?php
/**
 * TCustomValidator class file
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: 1.7 $  $Date: 2005/06/13 07:04:28 $
 * @package System.Web.UI.WebControls
 */

/**
 * TValidator class file
 */
require_once(dirname(__FILE__).'/TValidator.php');

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
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>ClientValidationFunction</b>, string, kept in viewstate
 *   <br>Gets or sets the name of the custom client-side script function used for validation.
 *
 * Events
 * - <b>OnServerValidate</b> Occurs when validation is performed on the server.
 *   <br>Event delegates must set the event parameter TServerValidateEventParameter.isValid
 *   to false if they find the value is invalid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TCustomValidator extends TValidator
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
		$idPath=$this->getControlToValidate();
		if(strlen($idPath))
		{
			$control=$this->getTargetControl($idPath);
			$value=$control->getValidationPropertyValue($idPath);
			return $this->onServerValidate($value);
		}
		else
			return true;
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
		$param=new TServerValidateEventParameter;
		$param->value=$value;
		$param->isValid=true;
		$this->raiseEvent('OnServerValidate',$this,$param);
		return $param->isValid;
	}

	/**
	 * Get a list of options for the client-side javascript validator
	 * @return array list of options for the validator 
	 */
	protected function getJsOptions()
	{
		$options = parent::getJsOptions();
		$clientJs = $this->getClientValidationFunction();
		if(strlen($clientJs))
			$options['clientvalidationfunction']=$clientJs;
		return $options;
	}
}

/**
 * TServerValidateEventParameter class
 *
 * TServerValidateEventParameter encapsulates the parameter data for
 * <b>OnServerValidate</b> event of TCustomValidator components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TServerValidateEventParameter extends TEventParameter
{
	/**
	 * the value to be validated
	 * @var string
	 */
	public $value='';
	/**
	 * whether the value is valid
	 * @var boolean
	 */
	public $isValid=true;
}
?>