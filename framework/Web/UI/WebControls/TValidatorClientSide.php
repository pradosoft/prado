<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TValidatorClientSide class.
 *
 * Client-side validator events can be modified through the {@link
 * TBaseValidator::getClientSide ClientSide} property of a validator. The
 * subproperties of ClientSide are those of the TValidatorClientSide
 * properties. The client-side validator supports the following events.
 *
 * The <tt>OnValidate</tt> event is raise before the validator validation
 * functions are called.
 *
 * The <tt>OnValidationSuccess</tt> event is raised after the validator has successfully
 * validate the control.
 *
 * The <tt>OnValidationError</tt> event is raised after the validator fails validation.
 *
 * See the quickstart documentation for further details.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TValidatorClientSide extends TClientSideOptions
{
	/**
	 * @return string javascript code for client-side OnValidate event.
	 */
	public function getOnValidate()
	{
		return $this->getOption('OnValidate');
	}

	/**
	 * Client-side OnValidate validator event is raise before the validators
	 * validation functions are called.
	 * @param string javascript code for client-side OnValidate event.
	 */
	public function setOnValidate($javascript)
	{
		$this->setFunction('OnValidate', $javascript);
	}

	/**
	 * Client-side OnSuccess event is raise after validation is successfull.
	 * This will override the default client-side validator behaviour.
	 * @param string javascript code for client-side OnSuccess event.
	 */
	public function setOnValidationSuccess($javascript)
	{
		$this->setFunction('OnValidationSuccess', $javascript);
	}

	/**
	 * @return string javascript code for client-side OnSuccess event.
	 */
	public function getOnValidationSuccess()
	{
		return $this->getOption('OnValidationSuccess');
	}

	/**
	 * Client-side OnError event is raised after validation failure.
	 * This will override the default client-side validator behaviour.
	 * @param string javascript code for client-side OnError event.
	 */
	public function setOnValidationError($javascript)
	{
		$this->setFunction('OnValidationError', $javascript);
	}

	/**
	 * @return string javascript code for client-side OnError event.
	 */
	public function getOnValidationError()
	{
		return $this->getOption('OnValidationError');
	}

	/**
	 * @param boolean true to revalidate when the control to validate changes value.
	 */
	public function setObserveChanges($value)
	{
		$this->setOption('ObserveChanges', TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return boolean true to observe changes.
	 */
	public function getObserveChanges()
	{
		$changes = $this->getOption('ObserveChanges');
		return ($changes===null) ? true : $changes;
	}
}