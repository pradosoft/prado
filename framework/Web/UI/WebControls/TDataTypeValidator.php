<?php
/**
 * TDataTypeValidator class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\TSimpleDateFormatter;

/**
 * TDataTypeValidator class
 *
 * TDataTypeValidator verifies if the input data is of the type specified
 * by {@see setDataType DataType}.
 * The following data types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Date</b> A date data type.
 * - <b>String</b> A string data type.
 * For <b>Date</b> type, the property {@see setDateFormat DateFormat}
 * will be used to determine how to parse the date string. If it is not
 * provided, the string will be assumed to be in GNU datetime format.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0
 */
class TDataTypeValidator extends TBaseValidator
{
	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TDataTypeValidator';
	}

	/**
	 * @return TValidationDataType the data type that the values being compared are converted to before the comparison is made. Defaults to TValidationDataType::String.
	 */
	public function getDataType()
	{
		return $this->getViewState('DataType', 'String');
	}

	/**
	 * Sets the data type that the values being compared are converted to before the comparison is made.
	 * @param TValidationDataType $value the data type
	 */
	public function setDataType($value)
	{
		$this->setViewState('DataType', TPropertyValue::ensureEnum($value, TValidationDataType::class), TValidationDataType::String);
	}

	/**
	 * Sets the date format for a date validation
	 * @param string $value the date format value
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
	 * @param string $value value to check
	 * @return bool true if value fits the type expression.
	 */
	protected function evaluateDataTypeCheck($value)
	{
		if ($value == '') {
			return true;
		}

		switch ($this->getDataType()) {
			case TValidationDataType::Integer:
				return preg_match('/^[-+]?[0-9]+$/', trim($value));
			case TValidationDataType::Float:
				return preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/', trim($value));
			case TValidationDataType::Date:
				$dateFormat = $this->getDateFormat();
				if (strlen($dateFormat)) {
					$formatter = new TSimpleDateFormatter($dateFormat);
					return $formatter->isValidDate($value);
				} else {
					return strtotime($value) > 0;
				}
		}
		return true;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['DataType'] = $this->getDataType();
		if (($dateFormat = $this->getDateFormat()) !== '') {
			$options['DateFormat'] = $dateFormat;
		}
		return $options;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input data is of valid type.
	 * The validation always succeeds if ControlToValidate is not specified
	 * or the input data is empty.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		if (($value = $this->getValidationValue($this->getValidationTarget())) === '') {
			return true;
		}

		return $this->evaluateDataTypeCheck($value);
	}
}
