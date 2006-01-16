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
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRequiredFieldValidator extends TBaseValidator
{
	public function getInitialValue()
	{
		$this->getViewState('InitialValue','');
	}

	public function setInitialValue($value)
	{
		$this->setViewState('InitialValue',TPropertyValue::ensureString($value),'');
	}

	protected function evaluateIsValid()
	{
		if(($control=$this->getValidationTarget())!==null)
		{
			$value=$this->getValidationValue($control);
			return trim($value)!==trim($this->getInitialValue());
		}
		else
			throw new TInvalidDataValueException('requiredfieldvalidator_controltovalidate_invalid');
	}

	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['initialvalue']=$this->getInitialValue();
		return $options;
	}
}

?>