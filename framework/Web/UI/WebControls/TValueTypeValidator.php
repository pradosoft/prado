<?php

/**
 * TRequiredValueTypeValidator class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TValueTypeValidator
{
	/**
	 * @return string the data type that the values being compared are converted to before the comparison is made. Defaults to String.
	 */
	public function getValueType()
	{
		return $this->getViewState('ValueType','String');
	}

	/**
	 * Sets the data type (Integer, Double, Currency, Date, String) that the values being compared are converted to before the comparison is made.
	 * @param string the data type
	 */
	public function setValueType($value)
	{
		$this->setViewState('ValueType',TPropertyValue::ensureEnum($value,'Integer','Double','Date','Currency','String'),'String');
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
		switch($this->getValueType())
		{
			case 'Integer':
				return preg_match('/^[-+]?[0-9]+$/',trim($value));
			case 'Float':
			case 'Double':
				return preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/',trim($value));
			case 'Currency':
				return preg_match('/[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/',trim($value));
			case 'Date':
				$dateFormat = $this->getDateFormat();
				if(strlen($dateFormat))
					return pradoParseDate($value, $dateFormat) !== null;
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