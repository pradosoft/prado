<?php

/**
 * TUuidValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TUuidValidator class
 *
 * TUuidValidator validates whether the value of an associated input component
 * is an RFC 9562 UUID, such as "f81d4fae-7dec-11d0-a765-00a0c91e6bf6". The hex
 * digits are case-insensitive. The value fails when:
 * - its form is not accepted: with {@see setStrict Strict} (the default), only
 *   the hyphenated 8-4-4-4-12 form; otherwise also the 32 digit form without
 *   hyphens, either form in braces, and either form after "urn:uuid:";
 * - its version digit is not 1 to 8, or not in {@see setVersions Versions};
 * - its variant is not the RFC 9562 variant (the first digit of the fourth
 *   group is 8, 9, a, or b);
 * - it is the nil UUID "00000000-0000-0000-0000-000000000000" and
 *   {@see setAllowNil AllowNil} is false (the default).
 *
 * The same checks run in the browser. The validation succeeds if the
 * associated input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUuidValidator extends TBaseValidator
{
	/**
	 * The hyphenated 8-4-4-4-12 form.
	 */
	public const CANONICAL_REGEXP = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

	/**
	 * The 32 hex digit form without hyphens.
	 */
	public const COMPACT_REGEXP = '/^[0-9a-f]{32}$/i';

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TUuidValidator';
	}

	/**
	 * @return string comma separated allowed UUID versions. Defaults to '', every version from 1 to 8.
	 */
	public function getVersions()
	{
		return $this->getViewState('Versions', '');
	}

	/**
	 * Sets the UUID versions that the value may have, such as "4,7".
	 * An empty list allows every version from 1 to 8.
	 * @param string $value comma separated UUID versions.
	 */
	public function setVersions($value)
	{
		$this->setViewState('Versions', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int[] the allowed versions from 1 to 8; empty to allow every version.
	 */
	public function getVersionList(): array
	{
		$versions = [];
		foreach (explode(',', $this->getVersions()) as $version) {
			if (($version = trim($version)) !== '' && ($number = (int) $version) >= 1 && $number <= 8) {
				$versions[$number] = $number;
			}
		}
		return array_values($versions);
	}

	/**
	 * @return bool whether only the hyphenated form is accepted. Defaults to true.
	 */
	public function getStrict()
	{
		return $this->getViewState('Strict', true);
	}

	/**
	 * Sets whether only the hyphenated 8-4-4-4-12 form is accepted. When false,
	 * the 32 digit form, braces, and the "urn:uuid:" prefix are also accepted.
	 * @param bool $value whether only the hyphenated form is accepted.
	 */
	public function setStrict($value)
	{
		$this->setViewState('Strict', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the nil UUID is valid. Defaults to false.
	 */
	public function getAllowNil()
	{
		return $this->getViewState('AllowNil', false);
	}

	/**
	 * Sets whether the nil UUID, all zeros, is valid.
	 * @param bool $value whether the nil UUID is valid.
	 */
	public function setAllowNil($value)
	{
		$this->setViewState('AllowNil', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or is a valid UUID.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && $this->isValidUuid($value);
	}

	/**
	 * Checks a UUID against the form, version, variant, and nil restrictions.
	 * @param string $value the UUID.
	 * @return bool whether the UUID is valid.
	 */
	public function isValidUuid(string $value): bool
	{
		if (!$this->getStrict()) {
			if (strncasecmp($value, 'urn:uuid:', 9) === 0) {
				$value = substr($value, 9);
			}
			if (strlen($value) > 2 && $value[0] === '{' && $value[strlen($value) - 1] === '}') {
				$value = substr($value, 1, -1);
			}
			if (!preg_match(self::CANONICAL_REGEXP, $value) && !preg_match(self::COMPACT_REGEXP, $value)) {
				return false;
			}
		} elseif (!preg_match(self::CANONICAL_REGEXP, $value)) {
			return false;
		}
		$hex = strtolower(str_replace('-', '', $value));
		if ($hex === str_repeat('0', 32)) {
			return $this->getAllowNil();
		}
		$version = (int) $hex[12];
		if ($version < 1 || $version > 8 || strpos('89ab', $hex[16]) === false) {
			return false;
		}
		$versions = $this->getVersionList();
		return $versions === [] || in_array($version, $versions, true);
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['Versions'] = $this->getVersionList();
		$options['Strict'] = $this->getStrict();
		$options['AllowNil'] = $this->getAllowNil();
		return $options;
	}
}
