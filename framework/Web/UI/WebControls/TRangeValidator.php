<?php

/**
 * TRangeValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DateTimeImmutable;
use DateTimeZone;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\TSimpleDateFormatter;

/**
 * TRangeValidator class
 *
 * TRangeValidator tests whether an input value is within a specified range.
 *
 * TRangeValidator uses three key properties to perform its validation.
 * The {@see setMinValue MinValue} and {@see setMaxValue MaxValue}
 * properties specify the minimum and maximum values of the valid range.
 * The {@see setDataType DataType} property is used to specify the
 * data type of the value and the minimum and maximum range values.
 * These values are converted to this data type before the validation
 * operation is performed. The following value types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Date</b> A date data type. The date format can be specified by
 *   setting {@see setDateFormat DateFormat} property, which must be recognizable
 *   by {@see \Prado\Util\TSimpleDateFormatter}. If the property is not set,
 *   the GNU date syntax is assumed.
 * - <b>String</b> A string data type.
 * - <b>StringLength</b> check for string length.
 * - <b>Time</b>, <b>Month</b>, <b>Week</b>, <b>DateTimeLocal</b> The values a
 *   {@see TTextBox} of the same {@see TTextBoxMode} posts: `HH:mm[:ss[.sss]]`,
 *   `yyyy-MM`, `yyyy-Www`, and `yyyy-MM-ddTHH:mm[:ss[.sss]]`. MinValue and
 *   MaxValue use the same format. A value in another format is invalid.
 * {@see setStep Step} requires the value to be MinValue plus a whole number of
 * steps, as the HTML `step` attribute does; without MinValue, the steps count from
 * 0, 1970-01-01, 1970-01, 1970-W01, or 00:00. The unit is the data type's: a
 * number for Integer and Float, days for Date, seconds for Time and DateTimeLocal,
 * months for Month, and weeks for Week. String and StringLength have no steps.
 *
 * If {@see setStrictComparison StrictComparison} is true, then the ranges
 * are compared as strictly less than the max value and/or strictly greater than the min value.
 *
 * The TRangeValidator allows a special DataType "StringLength" that
 * can be used to verify minimum and maximum string length. The
 * {@see setCharset Charset} property can be used to force a particular
 * charset for comparison. Otherwise, the application charset is used and is
 * defaulted as UTF-8.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TRangeValidator extends TBaseValidator
{
	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TRangeValidator';
	}

	/**
	 * @return string the minimum value of the validation range.
	 */
	public function getMinValue()
	{
		return $this->getViewState('MinValue', '');
	}

	/**
	 * Sets the minimum value of the validation range.
	 * @param string $value the minimum value
	 */
	public function setMinValue($value)
	{
		$this->setViewState('MinValue', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the maximum value of the validation range.
	 */
	public function getMaxValue()
	{
		return $this->getViewState('MaxValue', '');
	}

	/**
	 * Sets the maximum value of the validation range.
	 * @param string $value the maximum value
	 */
	public function setMaxValue($value)
	{
		$this->setViewState('MaxValue', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @param bool $value true to perform strict comparison (i.e. strictly less than max and/or strictly greater than min).
	 */
	public function setStrictComparison($value)
	{
		$this->setViewState('StrictComparison', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool true to perform strict comparison.
	 */
	public function getStrictComparison()
	{
		return $this->getViewState('StrictComparison', false);
	}

	/**
	 * @return TRangeValidationDataType the data type that the values being compared are
	 * converted to before the comparison is made. Defaults to TRangeValidationDataType::String.
	 */
	public function getDataType()
	{
		return $this->getViewState('DataType', TRangeValidationDataType::String);
	}

	/**
	 * Sets the data type that the values being compared are converted to before the comparison is made.
	 * @param TRangeValidationDataType $value the data type
	 */
	public function setDataType($value)
	{
		$this->setViewState('DataType', TPropertyValue::ensureEnum($value, TRangeValidationDataType::class), TRangeValidationDataType::String);
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
	 * @return string the step between valid values, in the unit of the data type, or ''
	 *   for no step. Defaults to ''.
	 * @since 4.4.0
	 */
	public function getStep()
	{
		return $this->getViewState('Step', '');
	}

	/**
	 * Sets the step between valid values. A value that is not a positive number, such
	 * as `any`, checks no step.
	 * @param string $value the step, in the unit of the data type.
	 * @since 4.4.0
	 */
	public function setStep($value)
	{
		$this->setViewState('Step', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @param string $value charset for string length comparison.
	 */
	public function setCharset($value)
	{
		$this->setViewState('Charset', $value, '');
	}

	/**
	 * @return string charset for string length comparison.
	 */
	public function getCharset()
	{
		return $this->getViewState('Charset', '');
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input data is within the range.
	 * The validation always succeeds if the input data is empty.
	 * @return bool whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '') {
			return true;
		}

		switch ($dataType = $this->getDataType()) {
			case TRangeValidationDataType::Integer:
				$valid = $this->isValidInteger($value);
				break;
			case TRangeValidationDataType::Float:
				$valid = $this->isValidFloat($value);
				break;
			case TRangeValidationDataType::Date:
				$valid = $this->isValidDate($value);
				break;
			case TRangeValidationDataType::StringLength:
				return $this->isValidStringLength($value);
			case TRangeValidationDataType::Time:
			case TRangeValidationDataType::Month:
			case TRangeValidationDataType::Week:
			case TRangeValidationDataType::DateTimeLocal:
				$valid = $this->isValidHtmlValue($value);
				break;
			default:
				return $this->isValidString($value);
		}
		return $valid && $this->isValidStep($value);
	}

	/**
	 * Determines if a Time, Month, Week, or DateTimeLocal value is within the range.
	 * @param string $value the value to validate
	 * @return bool true if the value has the format of the data type and is within range.
	 * @since 4.4.0
	 */
	protected function isValidHtmlValue($value)
	{
		$dataType = $this->getDataType();
		if (($number = $this->convertHtmlValue($dataType, $value)) === null) {
			return false;
		}
		$valid = true;
		if (($minValue = $this->getMinValue()) !== '') {
			$min = $this->convertHtmlValue($dataType, $minValue);
			$valid = $min !== null && $this->isGreaterThan($number, $min);
		}
		if (($maxValue = $this->getMaxValue()) !== '') {
			$max = $this->convertHtmlValue($dataType, $maxValue);
			$valid = $valid && $max !== null && $this->isLessThan($number, $max);
		}
		return $valid;
	}

	/**
	 * Determines if the value is MinValue plus a whole number of {@see getStep Step}s.
	 * @param string $value the value to validate
	 * @return bool true when there is no step, or the value is on a step.
	 * @since 4.4.0
	 */
	protected function isValidStep($value)
	{
		$step = $this->getStep();
		if (!is_numeric($step) || ($step = (float) $step) <= 0) {
			return true;
		}
		if (($number = $this->convertStepValue($value)) === null) {
			return false;
		}
		$base = 0.0;
		if (($minValue = $this->getMinValue()) !== '' && ($min = $this->convertStepValue($minValue)) !== null) {
			$base = $min;
		}
		$steps = ($number - $base) / $step;
		return abs($steps - round($steps)) <= 1e-9 * max(1.0, abs($steps));
	}

	/**
	 * Converts a value to a number in the step unit of the data type.
	 * @param string $value the value
	 * @return ?float the number, or null when the value does not convert.
	 * @since 4.4.0
	 */
	protected function convertStepValue($value)
	{
		switch ($dataType = $this->getDataType()) {
			case TRangeValidationDataType::Integer:
			case TRangeValidationDataType::Float:
				return is_numeric(trim($value)) ? (float) $value : null;
			case TRangeValidationDataType::Date:
				$dateFormat = $this->getDateFormat();
				$timestamp = $dateFormat !== '' ? (new TSimpleDateFormatter($dateFormat))->parse($value) : strtotime($value);
				if (!is_numeric($timestamp)) {
					return null;
				}
				$date = getdate((int) $timestamp);
				return (float) (gmmktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) / 86400);
			default:
				return $this->convertHtmlValue($dataType, $value);
		}
	}

	/**
	 * Converts a Time, Month, Week, or DateTimeLocal value to a number: seconds since
	 * midnight, months since 1970-01, weeks since 1970-W01, or seconds since
	 * 1970-01-01T00:00.
	 * @param string $dataType the data type
	 * @param string $value the value
	 * @return ?float the number, or null when the value does not have the format.
	 * @since 4.4.0
	 */
	protected function convertHtmlValue($dataType, $value)
	{
		$value = trim($value);
		switch ($dataType) {
			case TRangeValidationDataType::Time:
				if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d)(?:\.(\d{1,3}))?)?$/', $value, $m)) {
					return null;
				}
				return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) ($m[3] ?? 0) + (float) ('0.' . ($m[4] ?? '0'));
			case TRangeValidationDataType::Month:
				if (!preg_match('/^(\d{4,})-(0[1-9]|1[0-2])$/', $value, $m)) {
					return null;
				}
				return (float) (((int) $m[1] - 1970) * 12 + (int) $m[2] - 1);
			case TRangeValidationDataType::Week:
				if (!preg_match('/^(\d{4,})-W(\d{2})$/', $value, $m) || ($week = (int) $m[2]) < 1) {
					return null;
				}
				$monday = (new DateTimeImmutable('@0'))->setTimezone(new DateTimeZone('UTC'))->setISODate((int) $m[1], $week);
				if ((int) $monday->format('o') !== (int) $m[1] || (int) $monday->format('W') !== $week) {
					return null;
				}
				return ($monday->getTimestamp() + 3 * 86400) / (7 * 86400);
			case TRangeValidationDataType::DateTimeLocal:
				if (!preg_match('/^(\d{4,})-(\d{2})-(\d{2})[T ]([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d)(?:\.(\d{1,3}))?)?$/', $value, $m)
					|| !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
					return null;
				}
				return gmmktime((int) $m[4], (int) $m[5], (int) ($m[6] ?? 0), (int) $m[2], (int) $m[3], (int) $m[1]) + (float) ('0.' . ($m[7] ?? '0'));
		}
		return null;
	}

	/**
	 * Determine if the value is within the integer range.
	 * @param string $value value to validate true
	 * @return bool true if within integer range.
	 */
	protected function isValidInteger($value)
	{
		$minValue = $this->getMinValue();
		$maxValue = $this->getMaxValue();

		$valid = (bool) preg_match('/^[-+]?[0-9]+$/', trim($value));
		$value = (int) $value;
		if ($minValue !== '') {
			$valid = $valid && $this->isGreaterThan($value, (int) $minValue);
		}
		if ($maxValue !== '') {
			$valid = $valid && $this->isLessThan($value, (int) $maxValue);
		}
		return $valid;
	}

	protected function isLessThan($left, $right)
	{
		return $this->getStrictComparison() ? $left < $right : $left <= $right;
	}

	protected function isGreaterThan($left, $right)
	{
		return $this->getStrictComparison() ? $left > $right : $left >= $right;
	}

	/**
	 * Determine if the value is within the specified float range.
	 * @param string $value value to validate
	 * @return bool true if within range.
	 */
	protected function isValidFloat($value)
	{
		$minValue = $this->getMinValue();
		$maxValue = $this->getMaxValue();

		$valid = (bool) preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/', trim($value));
		$value = (float) $value;
		if ($minValue !== '') {
			$valid = $valid && $this->isGreaterThan($value, (float) $minValue);
		}
		if ($maxValue !== '') {
			$valid = $valid && $this->isLessThan($value, (float) $maxValue);
		}
		return $valid;
	}

	/**
	 * Determine if the date is within the specified range.
	 * Uses pradoParseDate and strtotime to get the date from string.
	 * @param string $value date as string to validate
	 * @return bool true if within range.
	 */
	protected function isValidDate($value)
	{
		$minValue = $this->getMinValue();
		$maxValue = $this->getMaxValue();

		$valid = true;

		$dateFormat = $this->getDateFormat();
		if ($dateFormat !== '') {
			$formatter = new TSimpleDateFormatter($dateFormat);
			$value = $formatter->parse($value);
			if ($minValue !== '') {
				$valid = $this->isGreaterThan($value, $formatter->parse($minValue));
			}
			if ($maxValue !== '') {
				$valid = $valid && $this->isLessThan($value, $formatter->parse($maxValue));
			}
			return $valid;
		} else {
			$value = strtotime($value);
			if ($minValue !== '') {
				$valid = $this->isGreaterThan($value, strtotime($minValue));
			}
			if ($maxValue !== '') {
				$valid = $valid && $this->isLessThan($value, strtotime($maxValue));
			}
			return $valid;
		}
	}

	/**
	 * Compare the string with a minimum and a maxiumum value.
	 * Uses strcmp for comparision.
	 * @param string $value value to compare with.
	 * @return bool true if the string is within range.
	 */
	protected function isValidString($value)
	{
		$minValue = $this->getMinValue();
		$maxValue = $this->getMaxValue();

		$valid = true;
		if ($minValue !== '') {
			$valid = $this->isGreaterThan(strcmp($value, $minValue), 0);
		}
		if ($maxValue !== '') {
			$valid = $valid && $this->isLessThan(strcmp($value, $maxValue), 0);
		}
		return $valid;
	}

	/**
	 * @param string $value string for comparision
	 * @return bool true if min and max string length are satisfied.
	 */
	protected function isValidStringLength($value)
	{
		$minValue = $this->getMinValue();
		$maxValue = $this->getMaxValue();

		$valid = true;
		$charset = $this->getCharset();
		if ($charset === '') {
			$app = $this->getApplication()->getGlobalization();
			$charset = $app ? $app->getCharset() : null;
			if (!$charset) {
				$charset = 'UTF-8';
			}
		}

		$length = iconv_strlen($value, $charset);
		if ($minValue !== '') {
			$valid = $this->isGreaterThan($length, (int) $minValue);
		}
		if ($maxValue !== '') {
			$valid = $valid && $this->isLessThan($length, (int) $maxValue);
		}
		return $valid;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['MinValue'] = $this->getMinValue();
		$options['MaxValue'] = $this->getMaxValue();
		$options['DataType'] = $this->getDataType();
		$options['StrictComparison'] = $this->getStrictComparison();
		if (($dateFormat = $this->getDateFormat()) !== '') {
			$options['DateFormat'] = $dateFormat;
		}
		if (($step = $this->getStep()) !== '') {
			$options['Step'] = $step;
		}
		return $options;
	}
}
