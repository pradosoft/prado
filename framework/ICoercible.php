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
 * ICoercible interface
 *
 * ICoercible lets any class act as a self-constructing target inside
 * {@see \Prado\TPropertyValue::coerceToType}'s coercion chain.  When that
 * chain reaches a non-builtin union member implementing this interface,
 * `TPropertyValue` first checks whether `$value` already is an instance of
 * the member and, if so, returns it untouched; otherwise it calls
 * {@see coerceFromValue()}, which constructs and returns an instance from a
 * richer input form — a config array, a parsed string, an int code — or
 * returns `null` to decline.  The two halves of that contract — when to
 * decline and how decliners coexist in a union — are detailed below.
 *
 * ## Declining vs. throwing
 *
 * A `null` return is not failure; it tells the chain "this is not my input"
 * and lets the next union member have a turn.  That is how a `TPoint|string`
 * property, or a union of several coercibles, can share one setter without
 * fighting over inputs.  An implementation should throw
 * {@see \Prado\Exceptions\TInvalidDataValueException} only when an input is
 * shape-recognized but semantically broken — the right array keys with
 * out-of-range values, a parseable string whose numbers are nonsensical —
 * and should never let an unrelated exception escape.
 *
 * ## Union member ordering
 *
 * When several coercibles share a union, they are tried in PHP reflection
 * (declaration) order, and the first identity match or non-`null` factory
 * return wins.  Placing a class earlier in the union therefore lets it claim
 * ambiguous inputs ahead of later members.  The pass runs *before* enum-name
 * validation, so a class that also implements {@see \Prado\IEnumerable},
 * `\UnitEnum`, or `\BackedEnum` can override pure name matching with richer
 * logic when it has it.
 *
 * ## Example
 *
 * ```php
 * class TPoint implements \Prado\ICoercible
 * {
 *     public int $x;
 *     public int $y;
 *
 *     public function __construct(int $x, int $y)
 *     {
 *         $this->x = $x;
 *         $this->y = $y;
 *     }
 *
 *     public static function coerceFromValue(mixed $value): ?static
 *     {
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
 * // A property typed `TPoint|string` accepts '3,4', ['x'=>3,'y'=>4], or a
 * // TPoint instance (identity pass-through, no factory call).
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ICoercible
{
	/**
	 * Constructs an instance of the implementing class from `$value`, or
	 * returns `null` to decline so the coercion chain can try the next union
	 * member.  See the class docblock for the decline-vs-throw contract.
	 *
	 * Because {@see \Prado\TPropertyValue} short-circuits the identity
	 * pass-through before invoking this method, `$value` is guaranteed not
	 * to be an instance of the implementing class — no `instanceof static`
	 * guard is needed.
	 *
	 * @param mixed $value the incoming value to coerce.
	 * @return ?static an instance of the implementing class on success, or
	 *   `null` to decline.
	 */
	public static function coerceFromValue(mixed $value): ?static;
}
