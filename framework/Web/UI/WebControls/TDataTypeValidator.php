<?php
/**
 * TDataTypeValidator class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
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
 * TDataTypeValidator class
 *
 * TDataTypeValidator verifies if the input data is of the type specified
 * by {@link setDataType DataType}.
 * The following data types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Currency</b> A decimal data type that can contain currency symbols.
 * - <b>Date</b> A date data type.
 * - <b>String</b> A string data type.
 * For <b>Date</b> type, the property {@link setDateFormat DateFormat}
 * will be used to determine how to parse the date string. If it is not
 * provided, the string will be assumed to be in GNU datetime format.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataTypeValidator extends TBaseValidator
{
	/**
	 * @return string the data type that the values being compared are converted to before the comparison is made. Defaults to String.
	 */
	public function getDataType()
	{
		return $this->getViewState('DataType','String');
	}

	/**
	 * Sets the data type (Integer, Float, Currency, Date, String) that the values being compared are converted to before the comparison is made.
	 * @param string the data type
	 */
	public function setDataType($value)
	{
		$this->setViewState('DataType',TPropertyValue::ensureEnum($value,'Integer','Float','Date','Currency','String'),'String');
	}

	/**
     * Sets the date format for a date validation
     * @param string the date format value
     */
	public function setDateFormat($value)
	{
		$this->setViewState('DateFormat', $value, '');
	}

	/**
	 * @return string the date validation date format if any
	 */
	public function getDateFormat()
	{
		return $this->getViewState('DateFormat', '');
	}


	/**
	 * Determine if the given value is of a particular type using RegExp.
	 * @param string value to check
	 * @return boolean true if value fits the type expression.
	 */
	protected function evaluateDataTypeCheck($value)
	{
		switch($this->getDataType())
		{
			case 'Integer':
				return preg_match('/^[-+]?[0-9]+$/',trim($value));
			case 'Float':
				return preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/',trim($value));
			case 'Currency':
				return preg_match('/[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/',trim($value));
			case 'Date':
				$dateFormat = $this->getDateFormat();
				if(strlen($dateFormat))
				{
					$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',$dateFormat);
					return $formatter->isValidDate($value);
				}
				else
					return strtotime($value) > 0;
		}
		return true;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input data is of valid type.
	 * The validation always succeeds if ControlToValidate is not specified
	 * or the input data is empty.
	 * @return boolean whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		if(($value=$this->getValidationValue($this->getValidationTarget()))==='')
			return true;

		return $this->evaluateDataTypeCheck($value);
	}
}

?>