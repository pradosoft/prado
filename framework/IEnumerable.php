<?php

/**
 * IEnumerable interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IEnumerable interface.
 *
 * IEnumerable defines the contract for Prado's legacy string-constant enumerable
 * type, complementing the PHP 8.1 {@see \BackedEnum} pattern.
 *
 * Implementors declare named string constants and provide static reflection helpers
 * for checking, resolving, and iterating over those constants.
 * {@see TEnumerable} is the canonical base class.
 *
 * ## Satisfying this interface
 *
 * {@see \Prado\Util\Traits\TConstantReflectionTrait} satisfies all four
 * reflection methods of this interface.  If iteration is not needed, this
 * single trait use is sufficient:
 *
 * ```php
 * class TTextAlign implements \Prado\IEnumerable
 * {
 *     use \Prado\Util\Traits\TConstantReflectionTrait;
 *
 *     const Left  = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 *
 * To also support `\Iterator`, add {@see \Prado\Util\Traits\TArrayIteratorTrait}.
 * {@see TConstantReflectionTrait} already provides the required
 * `getIteratorArray()` method, so no further implementation is needed — the
 * trait lazy-loads the constants on first iterator access:
 *
 * ```php
 * class TTextAlign implements \Prado\IEnumerable, \Iterator
 * {
 *     use \Prado\Util\Traits\TConstantReflectionTrait; // provides getIteratorArray()
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *
 *     const Left  = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 *
 * {@see \Prado\TEnumerable} is the canonical base class and demonstrates this
 * exact pattern.
 *
 * ## Why use this interface
 *
 * Using `IEnumerable` as a type hint instead of the concrete {@see TEnumerable}
 * base class allows any class — including those that cannot or should not extend
 * `TEnumerable` — to participate in framework-level coercion
 * ({@see \Prado\TPropertyValue::coerceToType}) and validation
 * ({@see \Prado\TPropertyValue::ensureEnum}) without requiring inheritance.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEnumerable
{
	/**
	 * Checks whether a constant with the given name exists in the implementing class.
	 *
	 * The second parameter controls matching behaviour:
	 * - `true` (default) — case-sensitive exact name match.
	 * - `false` — case-insensitive exact name match.
	 * - a plain string — prefix filter (constant name must start with the string).
	 * - a string prefixed with `*` or `-` — suffix filter (constant name must end
	 *   with the remainder of the string).
	 *
	 * When `$caseOrAffix` is a string the third parameter `$caseSensitive` governs
	 * the affix comparison.
	 *
	 * @param string $constant The constant name to check for.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for affix comparison. Default `true`.
	 * @return bool `true` if the constant exists, `false` otherwise.
	 */
	public static function hasConstant($constant, $caseOrAffix = true, $caseSensitive = true): bool;

	/**
	 * Checks whether a constant with the given value exists in the implementing class.
	 *
	 * Accepts the same `$caseOrAffix` / `$caseSensitive` options as
	 * {@see hasConstant}, applied to the constant's *name* when affix filtering is used.
	 *
	 * @param string $value The constant value to check for.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for affix comparison. Default `true`.
	 * @return bool `true` if a constant with this value exists, `false` otherwise.
	 */
	public static function hasConstantValue($value, $caseOrAffix = true, $caseSensitive = true): bool;

	/**
	 * Returns the value of the constant with the given name.
	 *
	 * Accepts the same `$caseOrAffix` / `$caseSensitive` options as {@see hasConstant}.
	 *
	 * @param string $constant The constant name to look up.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for affix comparison. Default `true`.
	 * @return ?string The constant value, or `null` if not found.
	 */
	public static function valueOfConstant($constant, $caseOrAffix = true, $caseSensitive = true): ?string;

	/**
	 * Returns the name of the constant that has the given value.
	 *
	 * Accepts the same `$caseOrAffix` / `$caseSensitive` options as {@see hasConstant},
	 * applied to the constant's *name* when affix filtering is used.
	 *
	 * @param string $value The constant value to search for.
	 * @param bool|string $caseOrAffix Case-sensitivity flag or prefix/suffix filter. Default `true`.
	 * @param bool $caseSensitive Case sensitivity for affix comparison. Default `true`.
	 * @return ?string The constant name, or `null` if not found.
	 */
	public static function constantOfValue($value, $caseOrAffix = true, $caseSensitive = true): ?string;
}
