<?php
/**
 * TRangeValidator class file
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
 * TRangeValidator class
 *
 * TRangeValidator tests whether an input value is within a specified range.
 *
 * TRangeValidator uses three key properties to perform its validation.
 * The {@link setMinValue MinValue} and {@link setMaxValue MaxValue}
 * properties specify the minimum and maximum values of the valid range.
 * The {@link setDataType DataType} property is used to specify the
 * data type of the value and the minimum and maximum range values.
 * These values are converted to this data type before the validation
 * operation is performed. The following value types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Currency</b> A decimal data type that can contain currency symbols.
 * - <b>Date</b> A date data type. The date format can be specified by
 *   setting {@link setDateFormat DateFormat} property, which must be recognizable
 *   by {@link TSimpleDateFormatter}. If the property is not set,
 *   the GNU date syntax is assumed.
 * - <b>String</b> A string data type.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRangeValidator extends TBaseValidator
{
	/**
	 * @return string the minimum value of the validation range.
	 */
	public function getMinValue()
	{
		return $this->getViewState('MinValue','');
	}

	/**
	 * Sets the minimum value of the validation range.
	 * @param string the minimum value
	 */
	public function setMinValue($value)
	{
		$this->setViewState('MinValue',$value,'');
	}

	/**
	 * @return string the maximum value of the validation range.
	 */
	public function getMaxValue()
	{
		return $this->getViewState('MaxValue','');
	}

	/**
	 * Sets the maximum value of the validation range.
	 * @param string the maximum value
	 */
	public function setMaxValue($value)
	{
		$this->setViewState('MaxValue',$value,'');
	}

	/**
	 * @return string the data type that the values being compared are
	 * converted to before the comparison is made. Defaults to String.
	 */
	public function getDataType()
	{
		return $this->getViewState('DataType','String');
	}

	/**
	 * Sets the data type (Integer, Float, Currency, Date, String) that the values
	 * being compared are converted to before the comparison is made.
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
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input data is within the range.
	 * The validation always succeeds if the input data is empty.
	 * @return boolean whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		$value=$this->getValidationValue($this->getValidationTarget());
		if($value==='')
			return true;

		switch($this->getDataType())
		{
			case 'Integer':
				return $this->isValidInteger($value);
			case 'Float':
				return $this->isValidFloat($value);
			case 'Currency':
				return $this->isValidCurrency($value);
			case 'Date':
				return $this->isValidDate($value);
			default:
				return $this->isValidString($value);
		}
	}

	/**
	* Determine if the value is within the integer range.
	* @param string value to validate true
	* @return boolean true if within integer range.
	*/
	protected function isValidInteger($value)
	{
		$minValue=$this->getMinValue();
		$maxValue=$this->getMaxValue();

		$value=intval($value);
		$valid=true;
		if($minValue!=='')
			$valid=$valid && ($value>=intval($minValue));
		if($maxValue!=='')
			$valid=$valid && ($value<=intval($maxValue));
		return $valid;
	}

	/**
	 * Determine if the value is within the specified float range.
	 * @param string value to validate
	 * @return boolean true if within range.
	 */
	protected function isValidFloat($value)
	{
		$minValue=$this->getMinValue();
		$maxValue=$this->getMaxValue();

		$value=floatval($value);
		$valid=true;
		if($minValue!=='')
			$valid=$valid && ($value>=floatval($minValue));
		if($maxValue!=='')
			$valid=$valid && ($value<=floatval($maxValue));
		return $valid;
	}

	/**
	 * Determine if the value is a valid currency range,
	 * @param string currency value
	 * @return boolean true if within range.
	 */
	protected function isValidCurrency($value)
	{
		$minValue=$this->getMinValue();
		$maxValue=$this->getMaxValue();

		$valid=true;
		$value = $this->getCurrencyValue($value);
		if($minValue!=='')
			$valid=$valid && ($value>= $this->getCurrencyValue($minValue));
		if($maxValue!=='')
			$valid=$valid && ($value<= $this->getCurrencyValue($minValue));
		return $valid;
	}

	/**
	 * Parse the string into a currency value, return the float value of the currency.
	 * @param string currency as string
	 * @return float currency value.
	 */
	protected function getCurrencyValue($value)
	{
		if(preg_match('/[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?/',$value,$matches))
			return floatval($matches[0]);
		else
			return 0.0;
	}

	/**
	 * Determine if the date is within the specified range.
	 * Uses pradoParseDate and strtotime to get the date from string.
	 * @param string date as string to validate
	 * @return boolean true if within range.
	 */
	protected function isValidDate($value)
	{
		$minValue=$this->getMinValue();
		$maxValue=$this->getMaxValue();

		$valid=true;

		$dateFormat = $this->getDateFormat();
		if($dateFormat!=='')
		{
			$formatter=Prado::createComponent('System.Data.TSimpleDateFormatter', $dateFormat);
			$value = pradoParseDate($value, $dateFormat);
			if($minValue!=='')
				$valid=$valid && ($value>=$formatter->parse($minValue));
			if($maxValue!=='')
				$valid=$valid && ($value<=$formatter->parse($maxValue));
			return $valid;
		}
		else
		{
			$value=strtotime($value);
			if($minValue!=='')
				$valid=$valid && ($value>=strtotime($minValue));
			if($maxValue!=='')
				$valid=$valid && ($value<=strtotime($maxValue));
			return $valid;
		}
	}

	/**
	 * Compare the string with a minimum and a maxiumum value.
	 * Uses strcmp for comparision.
	 * @param string value to compare with.
	 * @return boolean true if the string is within range.
	 */
	protected function isValidString($value)
	{
		$minValue=$this->getMinValue();
		$maxValue=$this->getMaxValue();

		$valid=true;
		if($minValue!=='')
			$valid=$valid && (strcmp($value,$minValue)>=0);
		if($maxValue!=='')
			$valid=$valid && (strcmp($value,$maxValue)<=0);
		return $valid;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options=parent::getClientScriptOptions();
		$options['minimumvalue']=$this->getMinValue();
		$options['maximumvalue']=$this->getMaxValue();
		$options['type']=$this->getDataType();
		if(($dateFormat=$this->getDateFormat())!=='')
			$options['dateformat']=$dateFormat;
		return $options;
	}
}

?>