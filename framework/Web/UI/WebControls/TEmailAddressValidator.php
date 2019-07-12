<?php
/**
 * TEmailAddressValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailAddressValidator class
 *
 * TEmailAddressValidator validates whether the value of an associated
 * input component is a valid email address. If {@link getCheckMXRecord CheckMXRecord}
 * is true, it will check MX record for the email adress, provided
 * checkdnsrr() is available in the installed PHP.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TEmailAddressValidator extends TRegularExpressionValidator
{
	/**
	 * Regular expression used to validate the email address
	 * @see http://www.regular-expressions.info/email.html
	 */
	const EMAIL_REGEXP = '[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?';

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
	 * Returns an array of javascript validator options.
	 * @return bool javascript validator options.
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		$valid = is_string($value) && strlen($value) <= 254 && parent::evaluateIsValid();

		if ($valid && $this->getCheckMXRecord() && function_exists('checkdnsrr')) {
			if ($value !== '') {
				if (($pos = strpos($value, '@')) !== false) {
					$domain = substr($value, $pos + 1);
					return $domain === '' ? false : checkdnsrr($domain, 'MX');
				} else {
					return false;
				}
			}
		}
		return $valid;
	}

	/**
	 * @return bool whether to check MX record for the email address being validated. Defaults to true.
	 */
	public function getCheckMXRecord()
	{
		return $this->getViewState('CheckMXRecord', false);
	}

	/**
	 * @param bool $value whether to check MX record for the email address being validated.
	 * Note, if {@link checkdnsrr} is not available, this check will not be performed.
	 */
	public function setCheckMXRecord($value)
	{
		$this->setViewState('CheckMXRecord', TPropertyValue::ensureBoolean($value), false);
	}
}
