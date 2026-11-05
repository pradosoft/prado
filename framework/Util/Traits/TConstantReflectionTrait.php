<?php

/**
 * TConstantReflectionTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

/**
 * TConstantReflectionTrait trait.
 *
 * TConstantReflectionTrait provides static reflection methods for checking,
 * getting, and iterating over class constants similar to {@see \ReflectionClass}.
 * It also implements {@see getIteratorArrayCopy()} so that any class using both
 * this trait and {@see TArrayCopyIteratorTrait} gains full `\Iterator` support
 * over its constants with no additional implementation.  Because
 * `TArrayCopyIteratorTrait` declares `getIteratorArrayCopy()` as abstract and
 * this trait provides a concrete implementation, PHP resolves the contract
 * automatically.
 *
 * This trait can be used by any class with string constants.  For example:
 * ```php
 * class TTextAlign
 * {
 *     use TConstantReflectionTrait;
 *
 *     const Left = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 * Then, one can use the reflection methods:
 * ```php
 * $value = TTextAlign::valueOfConstant('Left');       // returns 'Left'
 * $constant = TTextAlign::constantOfValue('Left');    // returns 'Left'
 * $hasLeft = TTextAlign::hasConstant('Left');         // true
 * $hasLeftValue = TTextAlign::hasConstantValue('Left'); // true
 * ```
 *
 * ## Case Sensitivity
 *
 * Pass `false` as the second argument for a case-insensitive name (or value) match:
 * ```php
 * $yes = TTextAlign::hasConstant('Left');          // true  (case-sensitive)
 * $no  = TTextAlign::hasConstant('left');          // false
 * $yes = TTextAlign::hasConstant('left', false);   // true  (case-insensitive)
 * ```
 *
 * ## Affix Filtering
 *
 * Pass a non-empty string as the second argument to restrict the match to
 * constants whose *name* starts with (prefix) or ends with (suffix) the given
 * string.  The third argument controls whether the affix comparison itself is
 * case-sensitive.
 *
 * - Prefix: a string starting with a letter or number (e.g., `'Align'`, `'Display'`)
 * - Suffix: a string starting with `*` or `-` (e.g., `'*Margin'`, `'-Algorithm'`)
 *
 * ```php
 * // const AlignLeft = 'AlignLeft', const AlignRight = 'AlignRight', const Left = 'Left'
 * $yes = TTextAlign::hasConstant('AlignLeft',  'Align');          // prefix match
 * $no  = TTextAlign::hasConstant('Left',       'Align');          // value lacks prefix
 * $yes = TTextAlign::hasConstant('AlignRight', 'align', false);   // case-insensitive prefix
 *
 * // const TopMargin = 'TopMargin', const BottomMargin = 'BottomMargin'
 * $yes = TTextAlign::hasConstant('TopMargin',    '*Margin');        // suffix match (* form)
 * $yes = TTextAlign::hasConstant('BottomMargin', '-Margin');        // suffix match (- form)
 * $yes = TTextAlign::hasConstant('BottomMargin', '-margin', false); // case-insensitive suffix
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TReflectionClassTrait
 * @since 4.3.3
 */
trait TConstantReflectionTrait
{
	use TReflectionClassTrait;

	/**
	 * Checks whether a constant with the given name exists in this class.
	 *
	 * Supports case-sensitive/insensitive matching and affix filtering (prefix or
	 * suffix) for flexible constant-name matching.
	 *
	 * @param string $constant The constant name to check.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter string. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for the affix comparison. Default `true`.
	 * @return bool `true` if a matching constant name is found, `false` otherwise.
	 */
	public static function hasConstant($constant, $caseOrAffix = true, $caseSensitive = true): bool
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = strlen($caseOrAffix) > 0 && ($caseOrAffix[0] === '*' || $caseOrAffix[0] === '-');
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach (self::getReflectionClass()->getConstants() as $name => $value) {
			if ($cmp($name, $constant) !== 0) {
				continue;
			}

			if (empty($affix)) {
				return true;
			}

			$affixLen = strlen($affix);
			if ($cmp(substr($name, $isSuffix ? -$affixLen : 0, $affixLen), $affix) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether a constant with the given value exists in this class.
	 *
	 * Supports case-sensitive/insensitive value matching and optional affix
	 * filtering on the constant's *name* (prefix or suffix).
	 *
	 * @param string $value The constant value to check.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter string. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for the affix comparison. Default `true`.
	 * @return bool `true` if a constant with this value is found, `false` otherwise.
	 */
	public static function hasConstantValue($value, $caseOrAffix = true, $caseSensitive = true): bool
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = strlen($caseOrAffix) > 0 && ($caseOrAffix[0] === '*' || $caseOrAffix[0] === '-');
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach (self::getReflectionClass()->getConstants() as $name => $constValue) {
			if ($cmp($value, $constValue) !== 0) {
				continue;
			}

			if (empty($affix)) {
				return true;
			}
			$affixLen = strlen($affix);
			if ($cmp(substr($name, $isSuffix ? -$affixLen : 0, $affixLen), $affix) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the value of the constant with the given name.
	 *
	 * Supports case-sensitive/insensitive name matching and affix filtering
	 * (prefix or suffix) for flexible constant-name lookup.
	 *
	 * @param string $constant The constant name to look up.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter string. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for the affix comparison. Default `true`.
	 * @return ?string The constant value, or `null` if not found.
	 */
	public static function valueOfConstant($constant, $caseOrAffix = true, $caseSensitive = true): ?string
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = strlen($caseOrAffix) > 0 && ($caseOrAffix[0] === '*' || $caseOrAffix[0] === '-');
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach (self::getReflectionClass()->getConstants() as $name => $value) {
			if ($cmp($name, $constant) !== 0) {
				continue;
			}

			if (empty($affix)) {
				return $value;
			}
			$affixLen = strlen($affix);
			if ($cmp(substr($name, $isSuffix ? -$affixLen : 0, $affixLen), $affix) === 0) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * Returns the name of the constant with the given value.
	 *
	 * Supports case-sensitive/insensitive value matching and optional affix
	 * filtering on the constant's *name* (prefix or suffix).
	 *
	 * @param string $value The constant value to search for.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter string. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for the affix comparison. Default `true`.
	 * @return ?string The constant name, or `null` if not found.
	 */
	public static function constantOfValue($value, $caseOrAffix = true, $caseSensitive = true): ?string
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = strlen($caseOrAffix) > 0 && ($caseOrAffix[0] === '*' || $caseOrAffix[0] === '-');
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach (self::getReflectionClass()->getConstants() as $name => $constValue) {
			if ($cmp($value, $constValue) !== 0) {
				continue;
			}

			if (empty($affix)) {
				return $name;
			}
			$affixLen = strlen($affix);
			if ($cmp(substr($name, $isSuffix ? -$affixLen : 0, $affixLen), $affix) === 0) {
				return $name;
			}
		}
		return null;
	}

	/**
	 * Returns a copy of all class constants as the backing array for iteration.
	 *
	 * Satisfies the abstract {@see \Prado\Util\Traits\TArrayCopyIteratorTrait::getIteratorArrayCopy()}
	 * contract so that any class using both traits gains full `\Iterator` support over
	 * its constants with no additional implementation.
	 *
	 * @return array<string,mixed> Map of constant name ⇒ constant value.
	 * @see \Prado\Util\Traits\TArrayCopyIteratorTrait
	 * @since 4.4.0
	 */
	protected function getIteratorArrayCopy(): array
	{
		return self::getReflectionClass()->getConstants();
	}
}
