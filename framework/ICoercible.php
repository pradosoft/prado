<?php

/**
 * ICoercible interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * ICoercible interface.
 *
 * ICoercible lets a class participate in {@see \Prado\TPropertyValue::coerceToType}
 * as a self-constructing target for a typed property's union member.  When the
 * coercion chain reaches a union member that implements this interface,
 * {@see coerceFromValue()} is given the incoming value and may return either a
 * fully-constructed instance of the class or `null` to decline.
 *
 * ## Declining vs. throwing
 *
 * `coerceFromValue()` returns `null` to decline an input it cannot interpret;
 * the coercion chain then tries the next union member.  A returned `null` is
 * *not* a failure — it is the contract for "this is not my input."  Throw a
 * {@see \Prado\Exceptions\TInvalidDataValueException} only when the input is
 * shape-recognized but semantically broken (for example, the right array keys
 * but values out of range).  Never let a foreign exception escape.
 *
 * ## Union member ordering
 *
 * Inside a union type the implementers are tried in PHP reflection order (the
 * declaration order on the property).  The first non-`null` result wins, so a
 * class can be placed earlier in the union to claim ambiguous inputs ahead of
 * later members.  Classes that also implement {@see \Prado\IEnumerable},
 * `\UnitEnum`, or `\BackedEnum` are tried *before* the enum-name validation
 * step, so a coercer can override pure name matching when it has a richer
 * understanding of the input.
 *
 * ## Example
 *
 * ```php
 * class TPoint implements \Prado\ICoercible
 * {
 *     public function __construct(int $x, int $y) {}
 *
 *     public static function coerceFromValue(mixed $value): ?static
 *     {
 *         if ($value instanceof static) {
 *             return $value;
 *         }
 *         if (is_array($value) && isset($value['x'], $value['y'])) {
 *             return new static((int) $value['x'], (int) $value['y']);
 *         }
 *         if (is_string($value) && preg_match('/^(-?\d+),\s*(-?\d+)$/', $value, $m)) {
 *             return new static((int) $m[1], (int) $m[2]);
 *         }
 *         return null; // decline — let the chain try the next member
 *     }
 * }
 *
 * // Property typed as `TPoint|string` accepts '3,4', ['x'=>3,'y'=>4], or a TPoint.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ICoercible
{
	/**
	 * Constructs an instance of the implementing class from `$value`, or
	 * returns `null` to decline.
	 *
	 * Returning `null` lets {@see \Prado\TPropertyValue::coerceToType} fall
	 * through to the next union member.  Throw
	 * {@see \Prado\Exceptions\TInvalidDataValueException} only for inputs that
	 * are recognized but semantically invalid (range, parse, etc.); never
	 * leak unrelated exceptions.
	 *
	 * Implementations should accept an instance of `static` as a pass-through
	 * so a property of this type accepts both freshly-coerced inputs and
	 * already-constructed instances uniformly.
	 *
	 * @param mixed $value the incoming value to coerce.
	 * @return ?static an instance of the implementing class on success, or
	 *   `null` to decline.
	 */
	public static function coerceFromValue(mixed $value): ?static;
}
