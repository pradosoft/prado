<?php
/**
 * TRequiredFieldValidator class file
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
 * TRequiredFieldValidator class
 *
 * TRequiredFieldValidator makes the associated input control a required field.
 * The input control fails validation if its value does not change from
 * the {@link setInitialValue InitialValue} property upon losing focus.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRequiredFieldValidator extends TBaseValidator
{
	/**
	 * @return string the initial value of the associated input control. Defaults to empty string.
	 * If the associated input control does not change from this initial value
	 * upon postback, the validation fails.
	 */
	public function getInitialValue()
	{
		return $this->getViewState('InitialValue','');
	}

	/**
	 * @param string the initial value of the associated input control.
	 * If the associated input control does not change from this initial value
	 * upon postback, the validation fails.
	 */
	public function setInitialValue($value)
	{
		$this->setViewState('InitialValue',TPropertyValue::ensureString($value),'');
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input component changes its data
	 * from the {@link getInitialValue InitialValue} or the input control is not given.
	 * @return boolean whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		$value=$this->getValidationValue($this->getValidationTarget());
		return trim($value)!==trim($this->getInitialValue()) || (is_bool($value) && $value);
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['InitialValue']=$this->getInitialValue();
		return $options;
	}
}

?>