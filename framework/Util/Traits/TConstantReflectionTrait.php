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
 * It also implements {@see \Prado\Util\Traits\TArrayIteratorTrait::getIteratorArray()}
 * so that any class using both this trait and {@see TArrayIteratorTrait} gains full
 * `\Iterator` support over its constants with no additional implementation.
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
 * **Case sensitivity** — pass `false` as the second argument for a
 * case-insensitive name (or value) match:
 * ```php
 * $yes = TTextAlign::hasConstant('Left');          // true  (case-sensitive)
 * $no  = TTextAlign::hasConstant('left');          // false
 * $yes = TTextAlign::hasConstant('left', false);   // true  (case-insensitive)
 * ```
 *
 * **Affix filtering** — pass a non-empty string as the second argument to
 * restrict the match to constants whose *name* starts with (prefix) or ends
 * with (suffix) the given string.  Prefix is the default; suffix is indicated
 * by a leading `*` or `-` character.  The third argument controls whether the
 * affix comparison itself is case-sensitive.
 * ```php
 * // const AlignLeft = 'AlignLeft', const AlignRight = 'AlignRight', const Left = 'Left'
 * $yes = TTextAlign::hasConstant('AlignLeft',  'Align');          // prefix match
 * $no  = TTextAlign::hasConstant('Left',       'Align');          // no prefix
 * $yes = TTextAlign::hasConstant('AlignRight', 'align', false);   // case-insensitive prefix
 *
 * // const TopMargin = 'TopMargin', const BottomMargin = 'BottomMargin'
 * $yes = TTextAlign::hasConstant('TopMargin',    '*Margin');        // suffix match
 * $yes = TTextAlign::hasConstant('BottomMargin', '-margin', false); // case-insensitive suffix
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TReflectionCacheTrait
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
	 * Returns all class constants as the backing array for {@see TArrayIteratorTrait}.
	 *
	 * This method satisfies the `getIteratorArray()` requirement of
	 * {@see \Prado\Util\Traits\TArrayIteratorTrait} so that any class using both
	 * this trait and `TArrayIteratorTrait` gains full `\Iterator` support over its
	 * constants without any additional implementation.  A using class may
	 * override this method to supply a different array for iteration.
	 *
	 * @return array<string,mixed> Map of constant name ⇒ constant value.
	 * @see \Prado\Util\Traits\TArrayIteratorTrait
	 * @since 4.4.0
	 */
	public function getIteratorArray(): array
	{
		return self::getReflectionClass()->getConstants();
	}
}
