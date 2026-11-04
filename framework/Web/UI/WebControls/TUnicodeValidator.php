<?php

/**
 * TUnicodeValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Spoofchecker;

/**
 * TUnicodeValidator class
 *
 * TUnicodeValidator validates that the value of an associated input component
 * has no suspicious Unicode characters, following the Unicode Technical
 * Standard #39 security mechanisms. It suits user names, display names, and
 * other identifiers that people compare by eye. The value fails when:
 * - it is not valid UTF-8;
 * - {@see setCheckInvisible CheckInvisible}: it contains a control character, a
 *   format character such as a zero-width space or a bidirectional override, a
 *   Hangul filler, the combining grapheme joiner, or the same nonspacing mark
 *   twice in a row;
 * - {@see setCheckMixedNumbers CheckMixedNumbers}: it mixes digits of different
 *   numbering systems, such as "١2";
 * - {@see setCheckHiddenOverlay CheckHiddenOverlay}: a combining mark hides in a
 *   character that already shows it, such as "i" followed by a dot above;
 * - its scripts exceed {@see setRestrictionLevel RestrictionLevel}, which
 *   defaults to {@see TUnicodeRestrictionLevel::ModeratelyRestrictive}, so
 *   "pаypal" with a Cyrillic "а" fails;
 * - with {@see setLocales Locales}, it has a character outside the scripts of
 *   those locales.
 *
 * The checks use the intl Spoofchecker. CheckMixedNumbers needs ICU 58 or later
 * and CheckHiddenOverlay needs ICU 62 or later; with an older ICU they are skipped.
 *
 * The validation runs server side only; the client side validator always
 * succeeds, so the server decides on each postback. The validation succeeds if
 * the associated input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUnicodeValidator extends TBaseValidator
{
	/**
	 * Spoofchecker check: the restriction level.
	 */
	public const CHECK_RESTRICTION_LEVEL = (1 << 4);

	/**
	 * Spoofchecker check: the same nonspacing mark twice in a row.
	 */
	public const CHECK_INVISIBLE = (1 << 5);

	/**
	 * Spoofchecker check: characters outside the allowed locales.
	 */
	public const CHECK_CHAR_LIMIT = (1 << 6);

	/**
	 * Spoofchecker check: digits of different numbering systems (ICU 58).
	 */
	public const CHECK_MIXED_NUMBERS = (1 << 7);

	/**
	 * Spoofchecker check: a combining mark hidden in its base character (ICU 62).
	 */
	public const CHECK_HIDDEN_OVERLAY = (1 << 8);

	/**
	 * Invisible characters that CheckInvisible rejects beyond the Spoofchecker:
	 * control and format characters, the combining grapheme joiner, and the Hangul fillers.
	 */
	public const INVISIBLE_REGEXP = '/[\p{Cc}\p{Cf}\x{034F}\x{115F}\x{1160}\x{3164}\x{FFA0}]/u';

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TUnicodeValidator';
	}

	/**
	 * @return bool whether invisible characters fail the value. Defaults to true.
	 */
	public function getCheckInvisible()
	{
		return $this->getViewState('CheckInvisible', true);
	}

	/**
	 * Sets whether control, format, filler, and repeated nonspacing mark characters fail the value.
	 * @param bool $value whether invisible characters fail the value.
	 */
	public function setCheckInvisible($value)
	{
		$this->setViewState('CheckInvisible', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether digits of different numbering systems fail the value. Defaults to true.
	 */
	public function getCheckMixedNumbers()
	{
		return $this->getViewState('CheckMixedNumbers', true);
	}

	/**
	 * Sets whether digits of different numbering systems fail the value.
	 * @param bool $value whether mixed numbering systems fail the value.
	 */
	public function setCheckMixedNumbers($value)
	{
		$this->setViewState('CheckMixedNumbers', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether a combining mark hidden in its base character fails the value. Defaults to true.
	 */
	public function getCheckHiddenOverlay()
	{
		return $this->getViewState('CheckHiddenOverlay', true);
	}

	/**
	 * Sets whether a combining mark hidden in a base character that already shows it fails the value.
	 * @param bool $value whether hidden overlays fail the value.
	 */
	public function setCheckHiddenOverlay($value)
	{
		$this->setViewState('CheckHiddenOverlay', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the {@see TUnicodeRestrictionLevel}. Defaults to ModeratelyRestrictive.
	 */
	public function getRestrictionLevel()
	{
		return $this->getViewState('RestrictionLevel', TUnicodeRestrictionLevel::ModeratelyRestrictive);
	}

	/**
	 * Sets the most permissive mix of scripts that the value may use.
	 * @param string $value a {@see TUnicodeRestrictionLevel}.
	 */
	public function setRestrictionLevel($value)
	{
		$this->setViewState('RestrictionLevel', TPropertyValue::ensureEnum($value, TUnicodeRestrictionLevel::class), TUnicodeRestrictionLevel::ModeratelyRestrictive);
	}

	/**
	 * @return string comma separated locales whose scripts the value may use. Defaults to '', any script.
	 */
	public function getLocales()
	{
		return $this->getViewState('Locales', '');
	}

	/**
	 * Sets the locales, such as "en,ja", whose scripts the value may use.
	 * An empty list allows every script.
	 * @param string $value comma separated locales.
	 */
	public function setLocales($value)
	{
		$this->setViewState('Locales', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string[] the trimmed, non-empty locales.
	 */
	public function getLocaleList(): array
	{
		return array_values(array_filter(array_map('trim', explode(',', $this->getLocales())), fn ($l) => $l !== ''));
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or has no suspicious characters.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && !$this->isSuspicious($value);
	}

	/**
	 * Checks a string against the enabled checks.
	 * @param string $value the string.
	 * @return bool whether the string is suspicious.
	 */
	public function isSuspicious(string $value): bool
	{
		if (preg_match('//u', $value) !== 1) {
			return true;
		}
		if ($this->getCheckInvisible() && preg_match(static::INVISIBLE_REGEXP, $value)) {
			return true;
		}
		$checker = $this->createSpoofchecker();
		return $checker !== null && $checker->isSuspicious($value);
	}

	/**
	 * Creates the Spoofchecker for the enabled checks.
	 * @return ?Spoofchecker the configured checker, or null when no check is enabled.
	 */
	protected function createSpoofchecker(): ?Spoofchecker
	{
		$checker = new Spoofchecker();
		$checks = 0;
		if ($this->getCheckInvisible()) {
			$checks |= self::CHECK_INVISIBLE;
		}
		if ($this->getCheckMixedNumbers() && version_compare(INTL_ICU_VERSION, '58', '>=')) {
			$checks |= self::CHECK_MIXED_NUMBERS;
		}
		if ($this->getCheckHiddenOverlay() && version_compare(INTL_ICU_VERSION, '62', '>=')) {
			$checks |= self::CHECK_HIDDEN_OVERLAY;
		}
		if (($level = $this->getSpoofcheckerLevel()) !== null) {
			$checker->setRestrictionLevel($level);
			$checks |= self::CHECK_RESTRICTION_LEVEL;
		}
		if (($locales = $this->getLocaleList()) !== []) {
			$checker->setAllowedLocales(implode(',', $locales));
			$checks |= self::CHECK_CHAR_LIMIT;
		}
		if ($checks === 0) {
			return null;
		}
		$checker->setChecks($checks);
		return $checker;
	}

	/**
	 * @return ?int the Spoofchecker restriction level constant, or null for Unrestricted.
	 */
	protected function getSpoofcheckerLevel(): ?int
	{
		return match ($this->getRestrictionLevel()) {
			TUnicodeRestrictionLevel::Ascii => Spoofchecker::ASCII,
			TUnicodeRestrictionLevel::SingleScript => Spoofchecker::SINGLE_SCRIPT_RESTRICTIVE,
			TUnicodeRestrictionLevel::HighlyRestrictive => Spoofchecker::HIGHLY_RESTRICTIVE,
			TUnicodeRestrictionLevel::ModeratelyRestrictive => Spoofchecker::MODERATELY_RESTRICTIVE,
			TUnicodeRestrictionLevel::MinimallyRestrictive => Spoofchecker::MINIMALLY_RESTRICTIVE,
			default => null,
		};
	}
}
