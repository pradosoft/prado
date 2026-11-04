<?php

/**
 * TLuhnValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TLuhnValidator class
 *
 * TLuhnValidator validates whether the value of an associated input component
 * passes the Luhn (mod 10) checksum, used by payment card numbers, IMEI numbers,
 * and other identification numbers. From the rightmost digit, every second
 * digit is doubled, 9 is subtracted from a doubled digit above 9, and the sum
 * of all digits must be a multiple of 10. The value fails when:
 * - it has fewer than 2 digits;
 * - it has a character other than a digit, or, with
 *   {@see setAllowSeparators AllowSeparators}, other than a digit, a space, or a hyphen;
 * - the checksum is not a multiple of 10.
 *
 * The checksum confirms that a number was typed correctly; it does not confirm
 * that the number exists. The same checks run in the browser. The validation
 * succeeds if the associated input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLuhnValidator extends TBaseValidator
{
	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TLuhnValidator';
	}

	/**
	 * @return bool whether spaces and hyphens between the digits are ignored. Defaults to false.
	 */
	public function getAllowSeparators()
	{
		return $this->getViewState('AllowSeparators', false);
	}

	/**
	 * Sets whether spaces and hyphens between the digits, such as in
	 * "4111 1111 1111 1111", are ignored.
	 * @param bool $value whether separators are ignored.
	 */
	public function setAllowSeparators($value)
	{
		$this->setViewState('AllowSeparators', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or passes the Luhn checksum.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		if (!is_string($value)) {
			return false;
		}
		if ($this->getAllowSeparators()) {
			$value = str_replace([' ', '-'], '', $value);
		}
		return static::isValidLuhn($value);
	}

	/**
	 * Checks a string of digits against the Luhn checksum.
	 * @param string $digits the digits.
	 * @return bool whether the string has 2 or more digits only and passes the checksum.
	 */
	public static function isValidLuhn(string $digits): bool
	{
		if (!preg_match('/^[0-9]{2,}$/', $digits)) {
			return false;
		}
		$sum = 0;
		$double = false;
		for ($i = strlen($digits) - 1; $i >= 0; $i--) {
			$digit = (int) $digits[$i];
			if ($double && ($digit *= 2) > 9) {
				$digit -= 9;
			}
			$sum += $digit;
			$double = !$double;
		}
		return $sum % 10 === 0;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['AllowSeparators'] = $this->getAllowSeparators();
		return $options;
	}
}
