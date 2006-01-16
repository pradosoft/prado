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
 * TRegularExpressionValidator class
 *
 * TRegularExpressionValidator validates whether the value of an associated
 * input component matches the pattern specified by a regular expression.
 *
 * You can specify the regular expression by setting the <b>RegularExpression</b>
 * property. Some commonly used regular expressions include:
 * <pre>
 * French Phone Number: (0( \d|\d ))?\d\d \d\d(\d \d| \d\d )\d\d
 * French Postal Code: \d{5}
 * German Phone Number: ((\(0\d\d\) |(\(0\d{3}\) )?\d )?\d\d \d\d \d\d|\(0\d{4}\) \d \d\d-\d\d?)
 * German Postal Code: (D-)?\d{5}
 * Email Address: \w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*
 * Internal URL: http://([\w-]+\.)+[\w-]+(/[\w- ./?%&=]*)?
 * Japanese Phone Number: (0\d{1,4}-|\(0\d{1,4}\) ?)?\d{1,4}-\d{4}
 * Japanese Postal Code: \d{3}(-(\d{4}|\d{2}))?
 * P.R.C. Phone Number: (\(\d{3}\)|\d{3}-)?\d{8}
 * P.R.C. Postal Code: \d{6}
 * P.R.C. Social Security Number: \d{18}|\d{15}
 * U.S. Phone Number: ((\(\d{3}\) ?)|(\d{3}-))?\d{3}-\d{4}
 * U.S. ZIP Code: \d{5}(-\d{4})?
 * U.S. Social Security Number: \d{3}-\d{2}-\d{4}
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRegularExpressionValidator extends TBaseValidator
{
	/**
	 * @return string the regular expression that determines the pattern used to validate a field.
	 */
	public function getRegularExpression()
	{
		return $this->getViewState('RegularExpression','');
	}

	/**
	 * Sets the regular expression that determines the pattern used to validate a field.
	 * @param string the regular expression
	 */
	public function setRegularExpression($value)
	{
		$this->setViewState('RegularExpression',$value,'');
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input data matches the regular expression.
	 * The validation always succeeds if ControlToValidate is not specified
	 * or the regular expression is empty, or the input data is empty.
	 * @return boolean whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		if(($control=$this->getValidationTarget())!==null)
		{
			if(($value=$this->getValidationValue($control))==='')
				return true;
			if(($expression=$this->getRegularExpression())!=='')
				return preg_match("/^$expression\$/",$value);
			else
				return true;
		}
		else
			throw new TInvalidDataValueException('regularexpressionvalidator_controltovalidate_invalid');
	}

	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['validationexpression']=$this->getRegularExpression();
		return $options;
	}
}

?>