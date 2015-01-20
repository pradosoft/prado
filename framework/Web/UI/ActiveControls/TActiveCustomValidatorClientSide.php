<?php
/**
 * TActiveCustomValidator class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

/**
 * Custom Validator callback client side options class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActiveCustomValidatorClientSide extends TCallbackClientSide
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