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
 *
 * This trait can be used by any class with string constants.
 * For example,
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
 * $value = TTextAlign::valueOfConstant('Left');  // returns 'Left'
 * $constant = TTextAlign::constantOfValue('Left');    // returns 'Left'
 * $hasLeft = TTextAlign::hasConstant('Left');    // true
 * $hasLeftValue = TTextAlign::hasConstantValue('Left'); // true
 * ```
 *
 * Then, one can use the reflection methods:
 * ```php
 * $value = TTextAlign::valueOfConstant('Left');  // returns 'Left'
 * $constant = TTextAlign::constantOfValue('Left');    // returns 'Left'
 * $hasLeft = TTextAlign::hasConstant('Left');    // true
 * $hasLeftValue = TTextAlign::hasConstantValue('Left'); // true
 * ```
 *
 * This trait can be used by any class with string constants.
 * For example,
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
 * $value = TTextAlign::valueOfConstant('Left');  // returns 'Left'
 * $constant = TTextAlign::constantOfValue('Left');    // returns 'Left'
 * $hasLeft = TTextAlign::hasConstant('Left');    // true
 * $hasLeftValue = TTextAlign::hasConstantValue('Left'); // true
 * ```
 *
 * <b>Case Sensitivity</b>
 * The second parameter can be a boolean for case sensitivity. Default is true (case sensitive).
 * ```php
 * $yes = TTextAlign::hasConstant('Left');         // true
 * $no = TTextAlign::hasConstant('left');        // false
 * $yes = TTextAlign::hasConstant('left', false);  // true (case insensitive)
 * ```
 *
 * <b>Affix Filtering</b>
 * The second parameter can be a string to filter constants by prefix or suffix.
 * - Prefix: string starting with letter/number (e.g., 'Align', 'Display')
 * - Suffix: string starting with '*' or '-' (e.g., '*Algorithm', '-Margin')
 * When using affix filtering, the third parameter controls case sensitivity.
 * ```php
 * // With const AlignLeft = 'AlignLeft', const Left = 'Left' and const AlignRight = 'AlignRight'
 * $yes = TTextAlign::hasConstant('AlignLeft', 'Align');    // starts with 'Align'
 * $yes = TTextAlign::hasConstant('AlignRight', 'align');     // starts with 'Align'
 * $no = TTextAlign::hasConstant('Left', 'align');
 *
 * // With const TopMargin = 'TopMargin' and const BottomMargin = 'BottomMargin'
 * $yes = TTextAlign::hasConstant('TopMargin', '*Margin');  // ends with 'Margin'
 * $yes = TTextAlign::hasConstant('BottomMargin', '-margin');  // ends with 'Margin'
 *
 * // Combine affix filter with case insensitive
 * $has = TTextAlign::hasConstant('Margin', '*margin', false);  // ends with 'margin'
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TConstantReflectionTrait
{
	/** @var \ReflectionClass[] Cache of ReflectionClass instances. */
	private static $_reflection_cache = [];

	/**
	 * Check if a constant exists in this class.
	 *
	 * This method checks for the existence of a class constant by its name.
	 * It supports case-sensitive/insensitive matching and affix filtering
	 * (prefix or suffix) for flexible constant name matching.
	 *
	 * @param string $constant The constant name to check.
	 * @param bool|string $caseOrAffix The prefix or suffix filter, or case sensitivity. Default is true
	 * @param bool $caseSensitive Whether to perform case-sensitive check when using affix filter. Default is true.
	 * @return bool True if the constant exists, false otherwise.
	 */
	public static function hasConstant($constant, $caseOrAffix = true, $caseSensitive = true): bool
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = $caseOrAffix[0] === '*' || $caseOrAffix[0] === '-';
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$ref = self::getReflectionClass();
		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';
		$consts = $ref->getConstants();

		foreach ($consts as $name => $value) {
			if ($cmp($name, $constant) !== 0) {
				continue;
			}

			if (empty($affix)) {
				return true;
			}

			$affixLen = strlen($affix);
			$match = $cmp(substr($name, $isSuffix ? -$affixLen : 0, $affixLen), $affix) === 0;
			if ($match) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a constant exists in this class.
	 *
	 * This method checks for the existence of a class constant by its value.
	 * It supports case-sensitive/insensitive matching and affix filtering
	 * (prefix or suffix) for flexible constant value matching.
	 *
	 * @param string $value The constant value to check.
	 * @param bool|string $caseOrAffix The prefix or suffix filter, or case sensitivity. Default is true
	 * @param bool $caseSensitive Whether to perform case-sensitive check when using affix filter. Default is true.
	 * @return bool True if the constant exists, false otherwise.
	 */
	public static function hasConstantValue($value, $caseOrAffix = true, $caseSensitive = true): bool
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = $caseOrAffix[0] === '*' || $caseOrAffix[0] === '-';
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$ref = self::getReflectionClass();
		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach ($ref->getConstants() as $name => $constValue) {
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
	 * Gets the constant value by name.
	 *
	 * This method retrieves the value of a class constant by its name.
	 * It supports case-sensitive/insensitive matching and affix filtering
	 * (prefix or suffix) for flexible constant name matching.
	 *
	 * @param string $constant The constant name to get the value of.
	 * @param bool|string $caseOrAffix The prefix or suffix filter, or case sensitivity. Default is true
	 * @param bool $caseSensitive Whether to perform case-sensitive check when using affix filter. Default is true.
	 * @return ?string The constant value or null if not found.
	 */
	public static function valueOfConstant($constant, $caseOrAffix = true, $caseSensitive = true): ?string
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = $caseOrAffix[0] === '*' || $caseOrAffix[0] === '-';
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$ref = self::getReflectionClass();
		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';
		$consts = $ref->getConstants();

		foreach ($consts as $name => $value) {
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
	 * Gets the constant name by value.
	 *
	 * This method retrieves the name of a class constant by its value.
	 * It supports case-sensitive/insensitive matching and affix filtering
	 * (prefix or suffix) for flexible constant value matching.
	 *
	 * @param string $value The constant value to search for.
	 * @param bool|string $caseOrAffix The prefix or suffix filter, or case sensitivity. Default is true
	 * @param bool $caseSensitive Whether to perform case-sensitive check when using affix filter. Default is true.
	 * @return ?string The constant name or null if not found.
	 */
	public static function constantOfValue($value, $caseOrAffix = true, $caseSensitive = true): ?string
	{
		$affix = null;
		$isSuffix = false;
		if (is_string($caseOrAffix)) {
			$isSuffix = $caseOrAffix[0] === '*' || $caseOrAffix[0] === '-';
			$affix = $isSuffix ? substr($caseOrAffix, 1) : $caseOrAffix;
		} else {
			$caseSensitive = (bool) $caseOrAffix;
		}

		$ref = self::getReflectionClass();
		$cmp = $caseSensitive ? 'strcmp' : 'strcasecmp';

		foreach ($ref->getConstants() as $name => $constValue) {
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

	// ----- Private Helpers

	/**
	 * Gets or creates the ReflectionClass for the current class.
	 * Uses a static cache to store ReflectionClass instances per class.
	 * @return \ReflectionClass The reflection class instance.
	 */
	private static function getReflectionClass(): \ReflectionClass
	{
		$class = static::class;
		if (!isset(self::$_reflection_cache[$class])) {
			self::$_reflection_cache[$class] = new \ReflectionClass($class);
		}
		return self::$_reflection_cache[$class];
	}
}
