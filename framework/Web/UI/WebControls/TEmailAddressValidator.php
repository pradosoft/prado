<?php

/**
 * TEmailAddressValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailAddressValidator class
 *
 * TEmailAddressValidator validates whether the value of an associated
 * input component is a valid email address of at most 254 bytes. If
 * {@see getCheckMXRecord CheckMXRecord} is true, the domain after the last `@`
 * must also have an MX record, which {@see hasMxRecord()} looks up, provided
 * checkdnsrr() is available in the installed PHP.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TEmailAddressValidator extends TRegularExpressionValidator
{
	/**
	 * Regular expression used to validate the email address
	 * @see http://www.regular-expressions.info/email.html
	 */
	public const EMAIL_REGEXP = '[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?';

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TEmailAddressValidator';
	}

	/**
	 * @return string the regular expression that determines the pattern used to validate a field.
	 */
	public function getRegularExpression()
	{
		$regex = parent::getRegularExpression();
		return $regex === '' ? self::EMAIL_REGEXP : $regex;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty, or is an email address of at
	 * most 254 bytes whose domain has an MX record when {@see getCheckMXRecord CheckMXRecord} is true.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if (!is_string($value) || strlen($value) > 254 || !parent::evaluateIsValid()) {
			return false;
		}
		if ($value === '' || !$this->getCheckMXRecord() || !function_exists('checkdnsrr')) {
			return true;
		}
		if (($pos = strrpos($value, '@')) === false || ($domain = substr($value, $pos + 1)) === '') {
			return false;
		}
		return $this->hasMxRecord($domain);
	}

	/**
	 * Looks up an MX record of a domain name.
	 * @param string $domain the domain name.
	 * @return bool whether the domain name has an MX record.
	 * @since 4.4.0
	 */
	protected function hasMxRecord(string $domain): bool
	{
		return checkdnsrr($domain, 'MX');
	}

	/**
	 * @return bool whether to check MX record for the email address being validated. Defaults to false.
	 */
	public function getCheckMXRecord()
	{
		return $this->getViewState('CheckMXRecord', false);
	}

	/**
	 * @param bool $value whether to check MX record for the email address being validated.
	 * Note, if {@see checkdnsrr} is not available, this check will not be performed.
	 */
	public function setCheckMXRecord($value)
	{
		$this->setViewState('CheckMXRecord', TPropertyValue::ensureBoolean($value), false);
	}
}
