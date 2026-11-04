<?php

/**
 * TJsonValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TJsonValidator class
 *
 * TJsonValidator validates whether the value of an associated input component
 * is JSON text (RFC 8259): an object, an array, a string, a number, true,
 * false, or null, with optional surrounding whitespace. The value fails when:
 * - it has a syntax error, such as a trailing comma, a single quoted string, an
 *   unquoted key, a leading zero, or a byte order mark;
 * - a string has an unpaired UTF-16 surrogate escape, such as "\ud800";
 * - its arrays and objects nest {@see MAX_DEPTH} levels or deeper.
 *
 * The server uses json_validate() on PHP 8.3 and later, and json_decode() before.
 * The same checks run in the browser with JSON.parse(). The validation succeeds
 * if the associated input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TJsonValidator extends TBaseValidator
{
	/**
	 * The PHP JSON depth: at most MAX_DEPTH - 1 arrays and objects nest.
	 */
	public const MAX_DEPTH = 512;

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TJsonValidator';
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or is JSON text.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && $this->isValidJson($value);
	}

	/**
	 * Checks whether a string is JSON text.
	 * @param string $value the string.
	 * @return bool whether the string is JSON text.
	 */
	public function isValidJson(string $value): bool
	{
		if (function_exists('json_validate')) {
			return json_validate($value, self::MAX_DEPTH);
		}
		json_decode($value, false, self::MAX_DEPTH);
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['MaxDepth'] = self::MAX_DEPTH;
		return $options;
	}
}
