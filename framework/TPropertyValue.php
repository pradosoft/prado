<?php

/**
 * TPropertyValue class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TWebColor;
use Prado\TComponentReflection;

/**
 * TPropertyValue class
 *
 * TPropertyValue is a utility class that provides static methods to convert component
 * property values to specific types.  It is commonly used in setter methods:
 * ```php
 * public function setFoo($value): void {
 *     $this->_foo = TPropertyValue::ensureBoolean($value);
 * }
 * ```
 *
 * **Conversion rules by type**
 *
 * | Type | Rule |
 * |---|---|
 * | `string` | `bool` → `'true'`/`'false'`; `TJavaScriptLiteral` passed through; everything else via `(string)` cast |
 * | `bool` | `'true'` (case-insensitive) or numeric string `!= 0` (loose) → `true`; all else → `false` |
 * | `int` | `(int)` cast |
 * | `float` | `(float)` cast |
 * | `array` | String parsed as PHP array literal; non-string via `(array)` cast |
 * | `object` | `(object)` cast |
 * | `enum` | PHP `\BackedEnum` exact backing value or case-insensitive case name; {@see IEnumerable} case-insensitive constant name → value |
 *
 * **Constants**
 *
 * *PHP type-name strings* — used by {@see coerceToType} and {@see coerceForSetter}:
 *
 * |   Constant      |    Value     |
 * |-----------------|--------------|
 * | `TYPE_NULL`     | `'null'`     |
 * | `TYPE_BOOL`     | `'bool'`     |
 * | `TYPE_INT`      | `'int'`      |
 * | `TYPE_FLOAT`    | `'float'`    |
 * | `TYPE_STRING`   | `'string'`   |
 * | `TYPE_ARRAY`    | `'array'`    |
 * | `TYPE_ITERABLE` | `'iterable'` |
 * | `TYPE_OBJECT`   | `'object'`   |
 * | `TYPE_MIXED`    | `'mixed'`    |
 *
 * *Boolean string representations* — canonical values produced by {@see ensureString} and
 * recognized by {@see ensureBoolean}:
 *
 * |   Constant   |    Value  |
 * |--------------|-----------|
 * | `BOOL_TRUE`  | `'true'`  |
 * | `BOOL_FALSE` | `'false'` |
 *
 * *Color channel keys* — array keys accepted by {@see ensureHexColor}:
 *
 * |   Constant    |    Value  |
 * |---------------|-----------|
 * | `COLOR_RED`   | `'red'`   |
 * | `COLOR_GREEN` | `'green'` |
 * | `COLOR_BLUE`  | `'blue'`  |
 *
 * *Array parser flags* — composable bit flags for {@see ensureArray}:
 *
 * | Constant | Bit | Effect |
 * |---|---|---|
 * | `ARRAY_STRICT_GRAMMAR` | 0 | Restrict to `[...]` / `array(...)` — no bare `(...)`, no bare words, no legacy octal, no auto-wrap |
 * | `ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN` | 1 | Also accept Prado `(...)` form; requires `ARRAY_STRICT_GRAMMAR` |
 * | `ARRAY_STRICT_ERRORS` | 2 | Throw {@see TInvalidDataValueException} on parse failure instead of wrapping |
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TPropertyValue
{
	/** PHP type name for `null`. @since 4.4.0 */
	public const TYPE_NULL = 'null';

	/** PHP type name for `bool`. @since 4.4.0 */
	public const TYPE_BOOL = 'bool';

	/** PHP type name for `int`. @since 4.4.0 */
	public const TYPE_INT = 'int';

	/** PHP type name for `float`. @since 4.4.0 */
	public const TYPE_FLOAT = 'float';

	/** PHP type name for `string`. @since 4.4.0 */
	public const TYPE_STRING = 'string';

	/** PHP type name for `array`. @since 4.4.0 */
	public const TYPE_ARRAY = 'array';

	/** PHP type name for `iterable`. @since 4.4.0 */
	public const TYPE_ITERABLE = 'iterable';

	/** PHP type name for `object`. @since 4.4.0 */
	public const TYPE_OBJECT = 'object';

	/** PHP type name for `mixed`. @since 4.4.0 */
	public const TYPE_MIXED = 'mixed';

	/** Array key / channel name for the red component in color arrays. @since 4.4.0 */
	public const COLOR_RED = 'red';

	/** Array key / channel name for the green component in color arrays. @since 4.4.0 */
	public const COLOR_GREEN = 'green';

	/** Array key / channel name for the blue component in color arrays. @since 4.4.0 */
	public const COLOR_BLUE = 'blue';

	/**
	 * The string representation of PHP `true` produced by {@see ensureString} and
	 * recognized (case-insensitively) by {@see ensureBoolean}.
	 * @since 4.4.0
	 */
	public const BOOL_TRUE = 'true';

	/**
	 * The string representation of PHP `false` produced by {@see ensureString} and
	 * recognized (case-insensitively) by {@see ensureBoolean}.
	 * @since 4.4.0
	 */
	public const BOOL_FALSE = 'false';

	/**
	 * Flag for {@see ensureArray()} that has restricted the parser to PHP-literal grammar:
	 * `[...]` or `array(...)` only — no bare `(...)`, no unquoted strings, no legacy octal, no auto-wrap.
	 * @since 4.4.0
	 */
	public const ARRAY_STRICT_GRAMMAR = (1 << 0);

	/**
	 * Flag for {@see ensureArray()} that has extended {@see ARRAY_STRICT_GRAMMAR} to also accept
	 * the Prado `(...)` bare-paren form alongside `[...]` and `array(...)`.  Has no effect without
	 * {@see ARRAY_STRICT_GRAMMAR}.
	 * @since 4.4.0
	 */
	public const ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN = (1 << 1);

	/**
	 * Flag for {@see ensureArray()} that has converted a parse failure from the silent
	 * single-element wrap into a thrown {@see TInvalidDataValueException}.
	 * @since 4.4.0
	 */
	public const ARRAY_STRICT_ERRORS = (1 << 2);

	/**
	 * No-op bitmask — shared zero across {@see ensureNullIf()} (no
	 * filters apply, value returned unchanged) and {@see ensureArrayOfType()}
	 * (no transforms, no filters; coercion only).  Named alias for `0` so
	 * callers can opt out of defaults without an unexplained literal.
	 * @since 4.4.0
	 */
	public const FILTER_NONE = 0;

	/**
	 * {@see ensureArrayOfType()} flag — trims string elements after
	 * coercion, before {@see FILTER_EMPTY}.  No effect on non-strings.
	 * @since 4.4.0
	 */
	public const AOT_TRIM = (1 << 0);

	/**
	 * {@see ensureArrayOfType()} flag — `strtolower` on string elements
	 * after {@see AOT_TRIM}.  No effect on non-strings.
	 * @since 4.4.0
	 */
	public const AOT_LOWERCASE = (1 << 1);

	/**
	 * Emptiness flag — matches `null`.  Used by {@see ensureNullIf()} (drops
	 * to `null`) and {@see ensureArrayOfType()} (drops the element).  In
	 * the array path the test is short-circuited before coercion runs.
	 * @since 4.4.0
	 */
	public const FILTER_NULL = (1 << 2);

	/**
	 * Emptiness flag — matches `false`.  In {@see ensureArrayOfType()} the
	 * test runs both before coercion (catches literal `false` before
	 * TYPE_INT turns it into `0`) and after coercion (catches a string
	 * `'false'` or numeric `0` that TYPE_BOOL turns into real false).
	 * @since 4.4.0
	 */
	public const FILTER_FALSE = (1 << 3);

	/**
	 * Emptiness flag — matches a string whose trimmed value is `''`.
	 * Catches both literal `''` and whitespace-only strings; non-string
	 * values bypass this check.
	 * @since 4.4.0
	 */
	public const FILTER_BLANK = (1 << 4);

	/**
	 * Emptiness composite — the union of {@see FILTER_NULL},
	 * {@see FILTER_FALSE}, and {@see FILTER_BLANK}.  Matches PHP `empty()`'s
	 * sense for the three values that show up most often in normalization
	 * pipelines: `null`, `false`, `''` (or whitespace-only).
	 * @since 4.4.0
	 */
	public const FILTER_EMPTY = self::FILTER_NULL
		| self::FILTER_FALSE
		| self::FILTER_BLANK;

	/**
	 * Step-11 fallback order for {@see _coerceUnionType}: non-null union members are tried in this
	 * sequence — int → float → string → bool → aggregate/catch-all — so resolution is deterministic
	 * regardless of reflection order.  `null` is absent; step 1 handles it before any fallback.
	 * @var string[]
	 * @since 4.4.0
	 */
	private const TYPE_COERCE_ORDER = [
		self::TYPE_INT,
		self::TYPE_FLOAT,
		self::TYPE_STRING,
		self::TYPE_BOOL,
		self::TYPE_ARRAY,
		self::TYPE_ITERABLE,
		self::TYPE_OBJECT,
		self::TYPE_MIXED,
	];

	/**
	 * Converts a value to boolean type.
	 *
	 * Strings: `'true'` (case-insensitive) or a numeric string whose value is `!= 0` (loose
	 * comparison) returns `true`; everything else — including `'false'`, `'yes'`, `'no'`,
	 * `'on'`, `'off'`, `'0.0'`, `'0e0'` — returns `false`.
	 * Non-strings: PHP's `(bool)` cast is applied directly.
	 *
	 * ```php
	 * TPropertyValue::ensureBoolean('true');   // true (case-insensitive)
	 * TPropertyValue::ensureBoolean('TRUE');   // true
	 * TPropertyValue::ensureBoolean('1');      // true   (numeric != 0)
	 * TPropertyValue::ensureBoolean('false');  // false  (only 'true' recognized as string)
	 * TPropertyValue::ensureBoolean('yes');    // false
	 * TPropertyValue::ensureBoolean(0);        // false  (PHP (bool) cast)
	 * TPropertyValue::ensureBoolean([1, 2]);   // true   (non-empty array)
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @return bool
	 */
	public static function ensureBoolean($value): bool
	{
		if (is_string($value)) {
			return strcasecmp($value, static::BOOL_TRUE) === 0 || (is_numeric($value) && $value != 0);
		} else {
			return (bool) $value;
		}
	}

	/**
	 * Converts a value to string type.
	 *
	 * `bool` is converted to `'true'` or `'false'`.  A {@see TJavaScriptLiteral} is returned
	 * as-is (pass-through) because it carries an opaque JavaScript expression that must not
	 * be re-encoded; callers that consume it as a plain string rely on its `__toString` cast.
	 * All other values are cast via `(string)`.
	 *
	 * ```php
	 * TPropertyValue::ensureString(true);     // 'true'   (not '1')
	 * TPropertyValue::ensureString(false);    // 'false'  (not '')
	 * TPropertyValue::ensureString(42);       // '42'
	 * TPropertyValue::ensureString(1.5);      // '1.5'
	 * TPropertyValue::ensureString(null);     // ''
	 * TPropertyValue::ensureString(new TJavaScriptLiteral('alert(1)')); // pass-through, not stringified
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @return string|\Stringable
	 */
	public static function ensureString($value): string|\Stringable
	{
		if (TJavaScript::isJsLiteral($value)) {
			return $value;
		}
		if (is_bool($value)) {
			return $value ? static::BOOL_TRUE : static::BOOL_FALSE;
		} else {
			return (string) $value;
		}
	}

	/**
	 * Converts a value to integer type via PHP's `(int)` cast.
	 *
	 * ```php
	 * TPropertyValue::ensureInteger('42');    // 42
	 * TPropertyValue::ensureInteger('3.7');   // 3       (truncation)
	 * TPropertyValue::ensureInteger('abc');   // 0
	 * TPropertyValue::ensureInteger(true);    // 1
	 * TPropertyValue::ensureInteger(null);    // 0
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @return int
	 */
	public static function ensureInteger($value): int
	{
		return (int) $value;
	}

	/**
	 * Converts a value to float type via PHP's `(float)` cast.
	 *
	 * ```php
	 * TPropertyValue::ensureFloat('1.5');     // 1.5
	 * TPropertyValue::ensureFloat('1e3');     // 1000.0
	 * TPropertyValue::ensureFloat('abc');     // 0.0
	 * TPropertyValue::ensureFloat(true);      // 1.0
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @return float
	 */
	public static function ensureFloat($value): float
	{
		return (float) $value;
	}

	/**
	 * Converts a value to object type via PHP's `(object)` cast.
	 *
	 * ```php
	 * TPropertyValue::ensureObject(['a' => 1, 'b' => 2]); // stdClass { a: 1, b: 2 }
	 * TPropertyValue::ensureObject('hello');               // stdClass { scalar: 'hello' }
	 * TPropertyValue::ensureObject($existingObj);          // pass-through
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @return object
	 */
	public static function ensureObject($value): object
	{
		return (object) $value;
	}

	/**
	 * Converts the value to `null` if the given value is empty (per PHP `empty()`).
	 *
	 * ```php
	 * TPropertyValue::ensureNullIfEmpty('');        // null
	 * TPropertyValue::ensureNullIfEmpty('0');       // null   (PHP empty() drops '0')
	 * TPropertyValue::ensureNullIfEmpty(0);         // null
	 * TPropertyValue::ensureNullIfEmpty([]);        // null
	 * TPropertyValue::ensureNullIfEmpty(false);     // null
	 * TPropertyValue::ensureNullIfEmpty('hello');   // 'hello'
	 * TPropertyValue::ensureNullIfEmpty(42);        // 42
	 * ```
	 *
	 * @param mixed $value value to be converted
	 * @return mixed input or NULL if input is empty
	 */
	public static function ensureNullIfEmpty($value): mixed
	{
		return empty($value) ? null : $value;
	}

	/**
	 * Converts the value to `null` when it matches any of the selected
	 * emptiness flags; otherwise returns the value unchanged.
	 *
	 * Stricter and more selective than {@see ensureNullIfEmpty()}, which
	 * fires for every value PHP `empty()` considers empty (including `0`,
	 * `'0'`, `[]`).  This method only tests for the three specific shapes
	 * the {@see FILTER_NULL}, {@see FILTER_FALSE}, {@see FILTER_BLANK} flags
	 * select; `0` and `'0'` survive unless the caller pairs them with a
	 * preliminary coercion.
	 *
	 * ```php
	 * // Defaults: FILTER_EMPTY — null / false / '' or whitespace-only.
	 * TPropertyValue::ensureNullIf('');         // null
	 * TPropertyValue::ensureNullIf('   ');      // null  (whitespace-only)
	 * TPropertyValue::ensureNullIf(null);       // null
	 * TPropertyValue::ensureNullIf(false);      // null
	 * TPropertyValue::ensureNullIf(0);          // 0     (not selected by FILTER_EMPTY)
	 * TPropertyValue::ensureNullIf('0');        // '0'   (not blank)
	 * TPropertyValue::ensureNullIf('hello');    // 'hello'
	 *
	 * // Selective: nulls only.
	 * TPropertyValue::ensureNullIf(false, TPropertyValue::FILTER_NULL);  // false (kept)
	 *
	 * // Selective: blanks only (catches '' and whitespace).
	 * TPropertyValue::ensureNullIf(null,  TPropertyValue::FILTER_BLANK); // null kept as null
	 * TPropertyValue::ensureNullIf('  ',  TPropertyValue::FILTER_BLANK); // null
	 * ```
	 *
	 * @param mixed $value the value to test.
	 * @param int $flags any combination of {@see FILTER_NULL},
	 *   {@see FILTER_FALSE}, {@see FILTER_BLANK}, {@see FILTER_EMPTY}.
	 *   Defaults to {@see FILTER_EMPTY}.
	 * @return mixed the original value, or `null` when it matches one of
	 *   the selected emptiness flags.
	 * @since 4.4.0
	 */
	public static function ensureNullIf(mixed $value, int $flags = self::FILTER_EMPTY): mixed
	{
		if (($flags & static::FILTER_NULL) !== 0 && $value === null) {
			return null;
		}
		if (($flags & static::FILTER_FALSE) !== 0 && $value === false) {
			return null;
		}
		if (($flags & static::FILTER_BLANK) !== 0 && is_string($value) && trim($value) === '') {
			return null;
		}
		return $value;
	}

	/**
	 * Converts the value to a web "#RRGGBB" hex color.
	 * The value[s] could be as an A) Web Color or # Hex Color string, or B) as a color
	 * encoded integer, eg 0x00RRGGBB, C) a triple ($value [red], $green, $blue), or D)
	 * an array of red, green, and blue, and index 0, 1, 2 or 'red', 'green', 'blue'.
	 * In instance (A), $green is treated as a boolean flag for whether to convert
	 * any web colors to their # hex color.  When red, green, or blue colors are specified
	 * they are assumed to be bound [0...255], inclusive.
	 *
	 * ```php
	 * // A — hex / web color string
	 * TPropertyValue::ensureHexColor('#3366ff');                  // '#3366FF'
	 * TPropertyValue::ensureHexColor('DeepSkyBlue');              // '#00BFFF'
	 *
	 * // B — 0x00RRGGBB encoded int
	 * TPropertyValue::ensureHexColor(0x336699);                   // '#336699'
	 *
	 * // C — (red, green, blue) triple
	 * TPropertyValue::ensureHexColor(51, 102, 255);               // '#3366FF'
	 *
	 * // D — array, numeric or named keys
	 * TPropertyValue::ensureHexColor([51, 102, 255]);             // '#3366FF'
	 * TPropertyValue::ensureHexColor(['red' => 51, 'green' => 102, 'blue' => 255]);
	 *
	 * // Values are clamped to 0..255
	 * TPropertyValue::ensureHexColor(-5, 999, 100);               // '#00FF64'
	 * ```
	 *
	 * @param null|array|float|int|string $value String Web Color name or Hex Color (eg. '#336699'),
	 *   array of [$r, $g, $b] or ['red' => $red, 'green' => $green, 'blue' => $blue], or
	 *   int color (0x00RRGGBB [$blue is null]), or int red [0..255] when $blue is not null.
	 * @param bool|float|int $green When $blue !== null, $green is an int color; otherwise it's
	 *   the flag to allow converting Web Color names to their web colors. Default true,
	 *   to allow web colors to translate into their # hex color.
	 * @param null|float|int $blue The blue color. Default null for (A) or (B)
	 * @return string The valid # hex color.
	 * @since 4.3.0
	 */
	public static function ensureHexColor(array|float|int|null|string $value, bool|float|int $green = true, float|int|null $blue = null): string
	{
		if (is_array($value)) {
			$blue = array_key_exists(static::COLOR_BLUE, $value) ? $value[static::COLOR_BLUE] : (array_key_exists(2, $value) ? $value[2] : null);
			$green = array_key_exists(static::COLOR_GREEN, $value) ? $value[static::COLOR_GREEN] : (array_key_exists(1, $value) ? $value[1] : true);
			$value = array_key_exists(static::COLOR_RED, $value) ? $value[static::COLOR_RED] : (array_key_exists(0, $value) ? $value[0] : null);
		}
		if (is_numeric($value)) {
			$value = (int) $value;
			if ($blue === null) {
				$blue = $value & 0xFF;
				$green = ($value >> 8) & 0xFF;
				$value = ($value >> 16) & 0xFF;
			} else {
				$green = (int) $green;
				$blue = (int) $blue;
			}
			return '#' . strtoupper(
				str_pad(dechex(max(0, min($value, 255))), 2, '0', STR_PAD_LEFT) .
						 str_pad(dechex(max(0, min($green, 255))), 2, '0', STR_PAD_LEFT) .
						 str_pad(dechex(max(0, min($blue, 255))), 2, '0', STR_PAD_LEFT)
			);
		}
		$value = static::ensureString($value);
		$len = strlen($value);
		if ($green && $len > 0 && $value[0] !== '#') {
			$hex = TWebColor::valueOfConstant($value, false);
			if ($hex !== null) {
				$value = $hex;
				$len = strlen($value);
			}
		}
		if ($len === 0 || $value[0] !== '#' || ($len !== 4 && $len !== 7) || !preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $value)) {
			throw new TInvalidDataValueException('propertyvalue_invalid_hex_color', $value);
		}
		if ($len === 4) {
			$value = preg_replace('/^#(.)(.)(.)$/', '#${1}${1}${2}${2}${3}${3}', $value);
		}
		return strtoupper($value);
	}

	// =========================================================================
	// ensureEnum() + ensureEnumValue() + Helpers
	// =========================================================================

	/**
	 * Validates a value against a class's declared constants (case-insensitive)
	 * or against a list of permitted values, returning the canonical constant
	 * name on a class match or the matching extras element otherwise.  Companion
	 * to {@see ensureEnumValue()} which performs the name → value translation
	 * step instead.
	 *
	 * Three call shapes share the one method:
	 *
	 * - **Class** — `ensureEnum($v, MyEnum::class)`.  Any casing of a constant
	 *   name resolves to the canonical name (`'alpha'` → `'Alpha'` for
	 *   `const Alpha = 'a'`).  PHP enums flow through the same path since
	 *   their cases are class constants; an enum instance passed as `$v` is
	 *   unwrapped to its `->name`.
	 * - **Class + extras** — `ensureEnum($v, MyEnum::class, 'Auto', null)`
	 *   or `ensureEnum($v, MyEnum::class, ['Auto', null, 'Custom' => 'x'])`.
	 *   Additional permitted values consulted after the class lookup misses;
	 *   passed either variadically or as a single array.  Entry semantics
	 *   match {@see ensureEnumValue()} (see {@see _matchEnumExtra()}): string
	 *   keys are case-insensitive aliases that return the canonical key as
	 *   the name; int-keyed strings are case-insensitive names that return
	 *   themselves; int-keyed non-strings (`null`, `false`, `0`, …) are
	 *   strict-equality sentinels.
	 * - **Value list** (no class) — `ensureEnum($v, ['a', 'b'])` or
	 *   `ensureEnum($v, 'a', 'b', 'c')`.  Triggered when `$enums` is an
	 *   array, or a string that doesn't resolve to a reflectable class.
	 *   Strict-equality membership check; matching element returned
	 *   unchanged.
	 *
	 * `$enums` also accepts an instance whose class is used.  The reflection
	 * cache is shared across calls.
	 *
	 * ```php
	 * // Class form — canonical NAME returned (any casing accepted).
	 * TPropertyValue::ensureEnum('alpha', MyEnum::class);   // 'Alpha'
	 * TPropertyValue::ensureEnum(MyEnum::Beta, MyEnum::class); // 'Beta' (instance → name)
	 *
	 * // Class + extras — sentinel pass-through after class miss.
	 * TPropertyValue::ensureEnum(null, MyEnum::class, null, false);   // null
	 * TPropertyValue::ensureEnum('Auto', MyEnum::class, ['Auto']);    // 'Auto'
	 *
	 * // Value list — strict-equality membership.
	 * TPropertyValue::ensureEnum('b', ['a', 'b', 'c']);     // 'b'
	 * TPropertyValue::ensureEnum('b', 'a', 'b', 'c');       // 'b'
	 * ```
	 *
	 * @param mixed $value the value to be validated.
	 * @param mixed $enums class name, instance, array of values, or first
	 *   element of a value list.
	 * @param mixed ...$extras additional permitted values (class form only);
	 *   variadic or a single array; shared semantics with `ensureEnumValue`.
	 * @throws TInvalidDataValueException when no class constant, extra, or
	 *   list element matches.
	 * @return mixed canonical constant name (class form), or the matching
	 *   extra / list element (other forms).
	 */
	public static function ensureEnum($value, $enums, mixed ...$extras): mixed
	{
		// Normalize extras: accept either variadic args or a single array.
		if (count($extras) === 1 && is_array($extras[0])) {
			$extras = $extras[0];
		}
		$className = null;
		$ref = null;
		if (is_object($enums)) {
			$className = $enums::class;
			$ref = TComponentReflection::getReflectionClassByType($className);
		} elseif (is_string($enums)) {
			$ref = TComponentReflection::getReflectionClassByType($enums);
			if ($ref !== null) {
				$className = $enums;
			}
		}

		if ($className !== null) {
			// Enum instance pass-through: unwrap to the case's canonical name.
			if ($value instanceof \UnitEnum && $value instanceof $className) {
				return $value->name;
			}
			if (is_string($value)) {
				// Fast path — exact case match.
				if ($ref->hasConstant($value)) {
					return $value;
				}
				// Slow path — case-insensitive enumeration; returns the
				// canonical name so any-casing input normalizes.
				foreach ($ref->getConstants() as $name => $_) {
					if (strcasecmp($name, $value) === 0) {
						return $name;
					}
				}
			}
			// Extras — unified matcher; canonical name returned on match.
			if ($extras !== []) {
				[$ok, $result] = self::_matchEnumExtra($value, $extras, true);
				if ($ok) {
					return $result;
				}
			}
			$labels = implode(' | ', array_keys($ref->getConstants()));
			if ($extras !== []) {
				$labels .= ($labels !== '' ? ' | ' : '') . self::_formatEnumExtrasLabel($extras);
			}
			$errVal = is_scalar($value) || $value === null ? $value : get_debug_type($value);
			throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid', $errVal, $labels);
		}

		// Array / variadic form
		if (!is_array($enums)) {
			$enums = func_get_args();
			array_shift($enums);
		}
		if (in_array($value, $enums, true)) {
			return $value;
		}
		throw new TInvalidDataValueException(
			'propertyvalue_enumvalue_invalid',
			$value,
			implode(' | ', $enums)
		);
	}

	/**
	 * Resolves a constant name (case-insensitive) on a class to its constant
	 * VALUE.  Companion to {@see ensureEnum()} which returns the canonical
	 * name; this method performs the name → value translation step.  For
	 * `const Alpha = 'a'`, any casing of `'Alpha'` resolves to `'a'`.
	 *
	 * A class constant whose value is a `BackedEnum` case is unwrapped to
	 * that case's `->value`; a non-backed `UnitEnum` case is returned as
	 * the case object (no backing value exists); other constant values are
	 * returned as-is.  An enum instance passed as `$value` is unwrapped the
	 * same way.
	 *
	 * `$extras` is an optional array consulted after the class lookup misses;
	 * it shares the unified semantics described in {@see _matchEnumExtra()} and
	 * accepts the same mixed shape as {@see ensureEnum()}'s extras:
	 *
	 * - **String key** (`'Auto' => 'auto'`) — case-insensitive alias map; the
	 *   key matches a string `$value` and the mapped value is returned.
	 * - **Int-keyed string** (`'Auto'`) — case-insensitive name; the string
	 *   is its own value and is returned on match.
	 * - **Int-keyed non-string** (`null`, `false`, …) — strict-equality
	 *   sentinel; the value is returned on match.
	 *
	 * Unlike {@see ensureEnum()}'s extras (which accept either variadic or
	 * array shapes), this parameter is always a single array.
	 *
	 * ```php
	 * // Class constant — case-insensitive name → declared VALUE.
	 * TPropertyValue::ensureEnumValue('alpha', MyEnum::class);    // 'a'  (for const Alpha = 'a')
	 * TPropertyValue::ensureEnumValue(MyEnum::Beta, MyEnum::class); // 'b' (instance → value)
	 *
	 * // String-keyed extras — alias map.
	 * TPropertyValue::ensureEnumValue('auto', MyEnum::class, ['Auto' => 0]); // 0
	 *
	 * // Int-keyed extras — strict-equality sentinel list.
	 * TPropertyValue::ensureEnumValue(null, MyEnum::class, [null, false]);  // null
	 * ```
	 *
	 * @param mixed $value constant name to look up (case-insensitive), an
	 *   enum instance of `$enums` to unwrap, or a sentinel covered by an
	 *   int-keyed extras entry.
	 * @param object|string $enums class name or instance whose class
	 *   provides the constant table.
	 * @param array $extras mixed alias map / sentinel list of additional
	 *   virtual constants (shared shape with {@see ensureEnum()}).
	 * @throws TInvalidDataValueException when no class constant nor extras
	 *   key matches.
	 * @return mixed BackedEnum backing value, non-backed UnitEnum case, raw
	 *   constant value, or the matching extras value.
	 * @since 4.4.0
	 */
	public static function ensureEnumValue(mixed $value, string|object $enums, array $extras = []): mixed
	{
		$className = is_object($enums) ? $enums::class : $enums;
		// Enum instance pass-through.
		if ($value instanceof \UnitEnum && $value instanceof $className) {
			return $value instanceof \BackedEnum ? $value->value : $value;
		}
		$ref = TComponentReflection::getReflectionClassByType($className);
		if ($ref !== null && is_string($value)) {
			// Fast path — exact case match via ReflectionClass.
			if ($ref->hasConstant($value)) {
				$c = $ref->getConstant($value);
				return $c instanceof \BackedEnum ? $c->value : $c;
			}
			// Slow path — case-insensitive enumeration.
			foreach ($ref->getConstants() as $name => $constValue) {
				if (strcasecmp($name, $value) === 0) {
					return $constValue instanceof \BackedEnum ? $constValue->value : $constValue;
				}
			}
		}
		// Extras — unified matcher; mapped value returned on match.
		if ($extras !== []) {
			[$ok, $result] = self::_matchEnumExtra($value, $extras, false);
			if ($ok) {
				return $result;
			}
		}
		$labels = $ref !== null ? implode(' | ', array_keys($ref->getConstants())) : '';
		if ($extras !== []) {
			$labels .= ($labels !== '' ? ' | ' : '') . self::_formatEnumExtrasLabel($extras);
		}
		$errVal = is_scalar($value) || $value === null ? $value : get_debug_type($value);
		throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid', $errVal, $labels);
	}

	/**
	 * Walks the unified `$extras` array on behalf of {@see ensureEnum()} and
	 * {@see ensureEnumValue()}.  Both methods accept the same extras shape and
	 * the same matching rules; only the return value differs:
	 *
	 * - **String key** (`'Alias' => 'mapped'`) — case-insensitive match on the
	 *   KEY against a string `$value`.  Returns the key when `$returnKey` is
	 *   true (canonical name for `ensureEnum`); returns the mapped value
	 *   otherwise (for `ensureEnumValue`).
	 * - **Int-keyed string** (`'Auto'`) — the string is both name and value;
	 *   case-insensitive match returns the string itself for both methods.
	 * - **Int-keyed non-string** (`null`, `false`, `0`, `-1`, …) — strict
	 *   equality sentinel; the matched value is returned by both methods.
	 *
	 * @since 4.4.0
	 * @param mixed $value
	 * @param array $extras
	 * @param bool $returnKey
	 * @return array{0: bool, 1: mixed} `[matched, result]` — `matched` is
	 *   false when nothing in `$extras` satisfies `$value`.
	 */
	private static function _matchEnumExtra(mixed $value, array $extras, bool $returnKey): array
	{
		foreach ($extras as $key => $extraValue) {
			if (is_string($key)) {
				if (is_string($value) && strcasecmp($key, $value) === 0) {
					return [true, $returnKey ? $key : $extraValue];
				}
			} elseif (is_string($extraValue)) {
				if (is_string($value) && strcasecmp($extraValue, $value) === 0) {
					return [true, $extraValue];
				}
			} elseif ($extraValue === $value) {
				return [true, $extraValue];
			}
		}
		return [false, null];
	}

	/**
	 * Formats `$extras` as a `|`-separated label list for error messages.
	 * Mirrors {@see _matchEnumExtra()}'s entry semantics: string-keyed
	 * entries surface as their key, int-keyed strings as the string itself,
	 * and int-keyed non-strings as their `var_export` form.
	 *
	 * @since 4.4.0
	 * @param array $extras
	 */
	private static function _formatEnumExtrasLabel(array $extras): string
	{
		$labels = [];
		foreach ($extras as $key => $extraValue) {
			if (is_string($key)) {
				$labels[] = $key;
			} elseif (is_string($extraValue)) {
				$labels[] = $extraValue;
			} else {
				$labels[] = var_export($extraValue, true);
			}
		}
		return implode(' | ', $labels);
	}

	// =========================================================================
	// ensureArray() + Helpers
	// =========================================================================

	/**
	 * Converts a value to an array, parsing strings as PHP-style array literals.
	 *
	 * Non-string values are cast via PHP's `(array)` coercion.  String values
	 * are parsed as PHP array literals.  Two flag bits control the behavior:
	 *
	 * - {@see ARRAY_STRICT_GRAMMAR} — restricts the parser to the PHP-literal
	 *   grammar (`[...]` short syntax or `array(...)` keyword form, no bare
	 *   `(...)` array, no bare-word strings, no legacy octal, no auto-wrap
	 *   of unbracketed input).
	 * - {@see ARRAY_STRICT_ERRORS} — converts the silent single-element
	 *   fallback into a thrown {@see TInvalidDataValueException}.  Composable
	 *   with the grammar flag.
	 *
	 * With `$flags === 0` (the default) the loose grammar applies: a trimmed
	 * string that begins with `(` or `[` is parsed in place; anything else is
	 * re-parsed as if wrapped in `(...)`, so bare element lists like
	 * `red, green, blue` resolve to `['red', 'green', 'blue']`.  Loose grammar
	 * accepts PHP-style integers (decimal, hex `0xFF`, binary `0b101`, modern
	 * octal `0o17`, with optional underscore separators; PHP 7's leading-zero
	 * octal form is dropped, so `017` reads as decimal 17), floats (`1.5`,
	 * `.5`, `1e10`, `1_000.5_5`), single- or double-quoted strings with the
	 * common backslash escapes, the keywords `true`/`false`/`null`
	 * (case-insensitive), unquoted bare-word strings, and nested arrays in
	 * `(...)`, `[...]`, or `array(...)` form freely mixed.  Each element
	 * supports an optional `key => ` prefix (int or string literal); a
	 * trailing comma is accepted.  The bare-word rule supports template-
	 * attribute conventions like
	 * `<com:TControl colors="red, green, blue"/>`.
	 *
	 * The parser is regex-driven — no `eval()` — and the empty string always
	 * returns the empty array.
	 *
	 * ```php
	 * // Loose grammar (default) — bare element lists auto-wrap.
	 * TPropertyValue::ensureArray('red, green, blue');   // ['red', 'green', 'blue']
	 * TPropertyValue::ensureArray('[1, 2, 3]');          // [1, 2, 3]
	 * TPropertyValue::ensureArray('(a, b => 2)');        // ['a', 'b' => 2]
	 * TPropertyValue::ensureArray('1_000.5');            // [1000.5]
	 * TPropertyValue::ensureArray('');                   // []
	 *
	 * // Native array — pass-through via (array) cast.
	 * TPropertyValue::ensureArray(['x' => 1]);           // ['x' => 1]
	 *
	 * // Strict grammar — bracketed literals only, no auto-wrap.
	 * $strict = TPropertyValue::ARRAY_STRICT_GRAMMAR;
	 * TPropertyValue::ensureArray('[1, 2]', $strict);    // [1, 2]
	 * TPropertyValue::ensureArray('1, 2', $strict);      // ['1, 2'] (silent fallback)
	 *
	 * // Strict + errors — silent fallback becomes an exception.
	 * TPropertyValue::ensureArray('1, 2', $strict | TPropertyValue::ARRAY_STRICT_ERRORS);
	 * // throws TInvalidDataValueException
	 * ```
	 *
	 * @param mixed $value the value to be converted.
	 * @param int $flags zero or more of {@see ARRAY_STRICT_GRAMMAR},
	 *   {@see ARRAY_STRICT_ERRORS} combined with `|`.  Defaults to `0`
	 *   (loose grammar, silent fallback).
	 * @throws TInvalidDataValueException when {@see ARRAY_STRICT_ERRORS} is
	 *   set and the input does not parse.
	 * @return array
	 */
	public static function ensureArray($value, int $flags = 0): array
	{
		if (!is_string($value)) {
			return (array) $value;
		}
		$value = trim($value);
		$len = strlen($value);
		if ($len === 0) {
			return [];
		}
		$strict = ($flags & static::ARRAY_STRICT_GRAMMAR) !== 0;
		$allowBareParen = $strict && ($flags & static::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN) !== 0;
		$parsed = self::_parseArrayLiteral($value, $strict, $allowBareParen);
		if ($parsed === null && !$strict) {
			$parsed = self::_parseArrayLiteral('(' . $value . ')', false, false);
		}
		if ($parsed !== null) {
			return $parsed;
		}
		if (($flags & static::ARRAY_STRICT_ERRORS) !== 0) {
			throw new TInvalidDataValueException('propertyvalue_invalid_array_literal', $value);
		}
		return [$value];
	}

	/**
	 * Normalizes a value into an array whose elements are coerced to the
	 * specified type, with optional per-element transforms and filters.
	 *
	 * Input flows through {@see ensureArray()} (CSV string, `[a,b,c]`
	 * literal, and native array all resolve to the same flat list), then
	 * each element is coerced to `$type`:
	 *
	 * - Built-in `TYPE_*` constants (`'string'`, `'int'`, `'float'`, `'bool'`,
	 *   `'array'`, `'object'`) delegate to the matching `ensure*` helper.
	 * - `'mixed'` and `'null'` pass through unchanged.
	 * - Any other value is treated as a class name and coerced via
	 *   {@see _coerceToClass()} (BackedEnum, IEnumerable, ICoercible, …).
	 *
	 * The flag pipeline runs in fixed order:
	 *
	 * 1. {@see FILTER_NULL} — discards `null` inputs before coercion.
	 *    A post-coercion check is unnecessary because typed coercion turns
	 *    `null` into `''` / `0` / `false`.
	 * 2. {@see FILTER_FALSE} — discards `false` both before and after
	 *    coercion.  Pre-coercion catches literal `false` before
	 *    `TYPE_INT` turns it into `0`; post-coercion catches a coerced
	 *    `'false'` / `0` from `TYPE_BOOL`.
	 * 3. {@see FILTER_BLANK} — discards `''` strings after the trim
	 *    below.  Pair with {@see AOT_TRIM} to drop whitespace-only
	 *    entries.  Non-string elements bypass this filter.
	 *
	 * {@see FILTER_EMPTY} is the union of the three filter flags
	 * above, matching PHP `empty()` for `null`, `false`, and `''`.
	 *
	 * Coercion and string transforms run between the filter passes:
	 *
	 * 4. *(coercion to `$type`)*
	 * 5. {@see AOT_TRIM}      — trims string elements.
	 * 6. {@see AOT_LOWERCASE} — `strtolower` on string elements.
	 *
	 * Key handling:
	 *
	 * - String (associative) keys are preserved verbatim.
	 * - Numeric keys repack to a contiguous `0..N-1` sequence with no
	 *   filter-induced gaps.
	 * - Mixed inputs keep associative entries in place and renumber
	 *   the rest.
	 *
	 * ```php
	 * // Default flags: trim + filter empty composite.
	 * TPropertyValue::ensureArrayOfType('Reader, , User', TPropertyValue::TYPE_STRING);
	 * // ['Reader', 'User']
	 *
	 * // String + trim + lowercase + filter blank.
	 * TPropertyValue::ensureArrayOfType(
	 *     ['  Admin ', '', '  EDITOR '],
	 *     TPropertyValue::TYPE_STRING,
	 *     TPropertyValue::AOT_TRIM | TPropertyValue::AOT_LOWERCASE | TPropertyValue::FILTER_BLANK,
	 * );  // ['admin', 'editor']
	 *
	 * // Int coercion — literal `false` dropped before becoming `0`.
	 * TPropertyValue::ensureArrayOfType([1, false, 2], TPropertyValue::TYPE_INT, TPropertyValue::FILTER_FALSE);
	 * // [1, 2]
	 *
	 * // String keys preserved, numeric repacked.
	 * TPropertyValue::ensureArrayOfType(
	 *     [0 => 'a', 'name' => 'Alice', 5 => '', 8 => 'b'],
	 *     TPropertyValue::TYPE_STRING,
	 * );  // [0 => 'a', 'name' => 'Alice', 1 => 'b']
	 *
	 * // Raw pass-through.
	 * TPropertyValue::ensureArrayOfType(
	 *     ['  x ', null, ''],
	 *     TPropertyValue::TYPE_STRING,
	 *     TPropertyValue::FILTER_NONE,
	 * );  // ['  x ', '', '']  (null became '' via ensureString, nothing dropped)
	 * ```
	 *
	 * @param mixed $value the value to normalize.
	 * @param string $type one of the `TYPE_*` constants or a class name; each
	 *   element of the resulting array is coerced to this type.
	 * @param int $flags zero or more of {@see AOT_TRIM},
	 *   {@see AOT_LOWERCASE}, {@see FILTER_NULL},
	 *   {@see FILTER_FALSE}, {@see FILTER_BLANK},
	 *   {@see FILTER_EMPTY} combined with `|`.  Defaults to
	 *   `AOT_TRIM | FILTER_EMPTY` (trim strings; drop nulls,
	 *   falses, and post-trim blanks).  Pass {@see FILTER_NONE} for raw
	 *   pass-through.
	 * @return array the normalized array.
	 * @since 4.4.0
	 */
	public static function ensureArrayOfType(
		mixed $value,
		string $type,
		int $flags = self::AOT_TRIM | self::FILTER_EMPTY,
	): array {
		$trim = ($flags & static::AOT_TRIM) !== 0;
		$lowercase = ($flags & static::AOT_LOWERCASE) !== 0;
		$filterNull = ($flags & static::FILTER_NULL) !== 0;
		$filterFalse = ($flags & static::FILTER_FALSE) !== 0;
		$filterBlank = ($flags & static::FILTER_BLANK) !== 0;

		$out = [];
		foreach (static::ensureArray($value) as $key => $item) {
			// 1. Pre-coercion short-circuits.  Nulls are dropped before
			// typed coercion turns them into '' / 0 / false.  Literal
			// false is also dropped here so it doesn't survive TYPE_INT
			// (false → 0) or TYPE_STRING (false → '') as a stray zero
			// or empty.
			if ($filterNull && $item === null) {
				continue;
			}
			if ($filterFalse && $item === false) {
				continue;
			}

			// 2. Coerce to target type.
			$item = match ($type) {
				static::TYPE_STRING => static::ensureString($item),
				static::TYPE_INT => static::ensureInteger($item),
				static::TYPE_FLOAT => static::ensureFloat($item),
				static::TYPE_BOOL => static::ensureBoolean($item),
				static::TYPE_ARRAY, static::TYPE_ITERABLE => static::ensureArray($item),
				static::TYPE_OBJECT => static::ensureObject($item),
				static::TYPE_MIXED, static::TYPE_NULL => $item,
				default => self::_coerceToClass($item, $type),
			};

			// 3. Filter false AGAIN after coercion — catches the common
			// TYPE_BOOL case where a string `'false'` or numeric `0`
			// coerces into bool false (the pre-coercion pass only sees
			// literal `false`).
			if ($filterFalse && $item === false) {
				continue;
			}

			// 4. String-only pipeline: trim, lowercase, then blank filter.
			// All three are no-ops for non-string items, so they live inside
			// the same is_string branch — saves a comparison per non-string.
			if (is_string($item)) {
				if ($trim) {
					$item = trim($item);
				}
				if ($lowercase) {
					$item = strtolower($item);
				}
				// Trim-aware blank check catches '' AND whitespace-only,
				// regardless of whether AOT_TRIM mutated the element first.
				if ($filterBlank && trim($item) === '') {
					continue;
				}
			}

			if (!is_int($key)) {
				$out[$key] = $item;
			} else {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * Recursive PCRE grammar that has matched a single PHP-style array
	 * literal: `(...)` or `[...]` at every depth, comma-separated elements
	 * with optional `key => ` prefixes and trailing comma, and the scalar
	 * subset (int, float, quoted string, `true`/`false`, `null`, and the
	 * bare-word fallback for unquoted strings).  Named subpatterns have
	 * lived in a `(?(DEFINE) ... )` block and participated only via
	 * `(?&name)` references.  {@see _parseArrayLiteral()} has used it as a
	 * one-shot validator before the extraction pass.
	 *
	 * @since 4.4.0
	 */
	private const ARRAY_LITERAL_PATTERN = <<<'REGEX'
		/\A
		(?(DEFINE)
			(?<ws>      \s* )
			(?<sqstr>   ' (?: \\. | [^'\\] )* ' )
			(?<dqstr>   " (?: \\. | [^"\\] )* " )
			(?<str>     (?&sqstr) | (?&dqstr) )
			(?<digits>  \d (?: _? \d )* )
			(?<float>   [+-]? (?:
							(?&digits) \. (?&digits)? (?: [eE] [+-]? (?&digits) )?
						  | \. (?&digits)             (?: [eE] [+-]? (?&digits) )?
						  | (?&digits) [eE] [+-]? (?&digits)
						) )
			(?<int>     [+-]? (?:
							0[xX] [0-9a-fA-F] (?: _? [0-9a-fA-F] )*
						  | 0[bB] [01]        (?: _? [01]        )*
						  | 0[oO] [0-7]       (?: _? [0-7]       )*
						  | (?&digits)
						) )
			(?<bool>    (?i: true | false ) \b )
			(?<null>    (?i: null ) \b )
			(?<bare>    [^,()[\]=\s] [^,()[\]=]* )
			(?<scalar>  (?&float) | (?&int) | (?&str) | (?&bool) | (?&null) | (?&bare) )
			(?<key>     (?&int) | (?&str) | (?&bare) )
			(?<value>   (?&scalar) | (?&arr) )
			(?<elem>    (?: (?&key) (?&ws) => (?&ws) )? (?&value) )
			(?<elist>   (?&elem) (?: (?&ws) , (?&ws) (?&elem) )* (?: (?&ws) , )? )
			(?<arr>     \( (?&ws) (?: (?&elist) (?&ws) )? \)
					  | \[ (?&ws) (?: (?&elist) (?&ws) )? \]
					  | (?i:array) (?&ws) \( (?&ws) (?: (?&elist) (?&ws) )? \) )
		)
		(?&ws) (?&arr) (?&ws)
		\z/sx
		REGEX;

	/**
	 * Strict variant of {@see ARRAY_LITERAL_PATTERN} that has matched only
	 * what PHP itself has accepted as an array literal: `[...]` short syntax
	 * or `array(...)` keyword form at every depth, and no bare-word strings,
	 * no legacy `0[0-7]+` octal, no bare `(...)` array form.  Selected by
	 * {@see _parseArrayLiteral()} when the caller has set
	 * {@see ARRAY_STRICT_GRAMMAR}.
	 *
	 * @since 4.4.0
	 */
	private const ARRAY_LITERAL_PATTERN_STRICT = <<<'REGEX'
		/\A
		(?(DEFINE)
			(?<ws>      \s* )
			(?<sqstr>   ' (?: \\. | [^'\\] )* ' )
			(?<dqstr>   " (?: \\. | [^"\\] )* " )
			(?<str>     (?&sqstr) | (?&dqstr) )
			(?<digits>  \d (?: _? \d )* )
			(?<float>   [+-]? (?:
							(?&digits) \. (?&digits)? (?: [eE] [+-]? (?&digits) )?
						  | \. (?&digits)             (?: [eE] [+-]? (?&digits) )?
						  | (?&digits) [eE] [+-]? (?&digits)
						) )
			(?<int>     [+-]? (?:
							0[xX] [0-9a-fA-F] (?: _? [0-9a-fA-F] )*
						  | 0[bB] [01]        (?: _? [01]        )*
						  | 0[oO] [0-7]       (?: _? [0-7]       )*
						  | 0 (?! [0-9_] )
						  | [1-9] (?: _? \d )*
						) )
			(?<bool>    (?i: true | false ) \b )
			(?<null>    (?i: null ) \b )
			(?<scalar>  (?&float) | (?&int) | (?&str) | (?&bool) | (?&null) )
			(?<key>     (?&int) | (?&str) )
			(?<value>   (?&scalar) | (?&arr) )
			(?<elem>    (?: (?&key) (?&ws) => (?&ws) )? (?&value) )
			(?<elist>   (?&elem) (?: (?&ws) , (?&ws) (?&elem) )* (?: (?&ws) , )? )
			(?<arr>     \[ (?&ws) (?: (?&elist) (?&ws) )? \]
					  | (?i:array) (?&ws) \( (?&ws) (?: (?&elist) (?&ws) )? \) )
		)
		(?&ws) (?&arr) (?&ws)
		\z/sx
		REGEX;

	/**
	 * Extension of {@see ARRAY_LITERAL_PATTERN_STRICT} that has additionally
	 * accepted the Prado bare-paren `(...)` array form alongside `[...]` and
	 * `array(...)`.  The scalar subset has remained strict — no bare-word
	 * strings, no legacy octal.  Selected by {@see _parseArrayLiteral()} when
	 * the caller has set both {@see ARRAY_STRICT_GRAMMAR} and
	 * {@see ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN}.
	 *
	 * @since 4.4.0
	 */
	private const ARRAY_LITERAL_PATTERN_STRICT_WITH_PAREN = <<<'REGEX'
		/\A
		(?(DEFINE)
			(?<ws>      \s* )
			(?<sqstr>   ' (?: \\. | [^'\\] )* ' )
			(?<dqstr>   " (?: \\. | [^"\\] )* " )
			(?<str>     (?&sqstr) | (?&dqstr) )
			(?<digits>  \d (?: _? \d )* )
			(?<float>   [+-]? (?:
							(?&digits) \. (?&digits)? (?: [eE] [+-]? (?&digits) )?
						  | \. (?&digits)             (?: [eE] [+-]? (?&digits) )?
						  | (?&digits) [eE] [+-]? (?&digits)
						) )
			(?<int>     [+-]? (?:
							0[xX] [0-9a-fA-F] (?: _? [0-9a-fA-F] )*
						  | 0[bB] [01]        (?: _? [01]        )*
						  | 0[oO] [0-7]       (?: _? [0-7]       )*
						  | 0 (?! [0-9_] )
						  | [1-9] (?: _? \d )*
						) )
			(?<bool>    (?i: true | false ) \b )
			(?<null>    (?i: null ) \b )
			(?<scalar>  (?&float) | (?&int) | (?&str) | (?&bool) | (?&null) )
			(?<key>     (?&int) | (?&str) )
			(?<value>   (?&scalar) | (?&arr) )
			(?<elem>    (?: (?&key) (?&ws) => (?&ws) )? (?&value) )
			(?<elist>   (?&elem) (?: (?&ws) , (?&ws) (?&elem) )* (?: (?&ws) , )? )
			(?<arr>     \( (?&ws) (?: (?&elist) (?&ws) )? \)
					  | \[ (?&ws) (?: (?&elist) (?&ws) )? \]
					  | (?i:array) (?&ws) \( (?&ws) (?: (?&elist) (?&ws) )? \) )
		)
		(?&ws) (?&arr) (?&ws)
		\z/sx
		REGEX;

	/**
	 * Anchored PCRE pattern that has matched a single PHP-style integer
	 * literal at the current position: decimal (`123`, `-7`, `1_000`), hex
	 * (`0xFF`, `0xFF_FF`), binary (`0b101`, `0b1010_0011`), and modern octal
	 * (`0o17`, `0O17`).  PHP 7's leading-zero octal form (`017` reading as
	 * 15, not 17) has been intentionally dropped — the silent meaning-shift
	 * has been a long-standing footgun and PHP 8.1 has introduced the
	 * explicit `0o` prefix specifically to disambiguate.  An input like
	 * `017` has therefore been read as decimal 17 here.  PHP's "underscores
	 * between digits only" rule has been enforced by the pattern shape, so
	 * `0x_FF`, `1_`, and `1__0` have all been rejected.
	 *
	 * @since 4.4.0
	 */
	private const INT_LITERAL_PATTERN = '/\G[+-]?(?:0[xX][0-9a-fA-F](?:_?[0-9a-fA-F])*|0[bB][01](?:_?[01])*|0[oO][0-7](?:_?[0-7])*|\d(?:_?\d)*)/A';

	/**
	 * Anchored PCRE pattern that has matched a single PHP-style float literal
	 * at the current position: `1.5`, `.5`, `1.`, `1e3`, `-1.5E-3`, with
	 * optional underscored digit separators in any of the integer,
	 * fractional, and exponent parts.  Float has been attempted before int
	 * so that `1.5` and `1e3` have resolved to PHP `float`.
	 *
	 * @since 4.4.0
	 */
	private const FLOAT_LITERAL_PATTERN = '/\G[+-]?(?:\d(?:_?\d)*\.(?:\d(?:_?\d)*)?(?:[eE][+-]?\d(?:_?\d)*)?|\.\d(?:_?\d)*(?:[eE][+-]?\d(?:_?\d)*)?|\d(?:_?\d)*[eE][+-]?\d(?:_?\d)*)/A';

	/**
	 * Has parsed a validated PHP-style array literal into the corresponding
	 * PHP array, returning `null` on parse failure so {@see ensureArray()}
	 * has fallen back to the single-element wrapping.  Both `(...)` and
	 * `[...]` delimiters have been accepted at every depth and freely mixed.
	 * {@see ARRAY_LITERAL_PATTERN} has performed the one-shot validation;
	 * once it has matched, the extraction pass has been written without
	 * error handling.
	 *
	 * @param string $s the trimmed input.  Under loose grammar it may begin
	 *   with `(`, `[`, `array(`, or — when wrapped by {@see ensureArray()} —
	 *   any bare element list.  Under strict grammar it must begin with `[`
	 *   or `array(` (or `(` when `$allowBareParen` is `true`).
	 * @param bool $strict when `true`, select {@see ARRAY_LITERAL_PATTERN_STRICT}
	 *   (or {@see ARRAY_LITERAL_PATTERN_STRICT_WITH_PAREN} when `$allowBareParen`
	 *   is also `true`) and disable the bare-word fallback inside the parser helpers.
	 * @param bool $allowBareParen when `true` and `$strict` is also `true`, select
	 *   {@see ARRAY_LITERAL_PATTERN_STRICT_WITH_PAREN} so the Prado `(...)` form
	 *   is accepted alongside `[...]` and `array(...)`.  Has no effect when
	 *   `$strict` is `false` because the loose grammar already accepts `(...)`.
	 * @return ?array the parsed array, or `null` on syntax error.
	 * @since 4.4.0
	 */
	private static function _parseArrayLiteral(string $s, bool $strict = false, bool $allowBareParen = false): ?array
	{
		if ($strict) {
			$pattern = $allowBareParen
				? self::ARRAY_LITERAL_PATTERN_STRICT_WITH_PAREN
				: self::ARRAY_LITERAL_PATTERN_STRICT;
		} else {
			$pattern = self::ARRAY_LITERAL_PATTERN;
		}
		if (!preg_match($pattern, $s)) {
			return null;
		}
		$pos = 0;
		self::_skipWs($s, $pos);
		// Both grammars have accepted the `array(...)` keyword form at the
		// top level; the helper has advanced `$pos` past the keyword when
		// present so {@see _consumeArray()} has found `(` directly.
		self::_skipArrayKeyword($s, $pos);
		return self::_consumeArray($s, $pos, $strict);
	}

	/**
	 * Has advanced `$pos` past the optional `array` keyword (case-insensitive)
	 * and any whitespace between it and the following `(`, so callers have
	 * been able to treat `array(1, 2)` and `(1, 2)` uniformly from there on.
	 * When `$pos` has not pointed at `array` followed by `(`, the cursor has
	 * been left unchanged.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @since 4.4.0
	 */
	private static function _skipArrayKeyword(string $s, int &$pos): void
	{
		$len = strlen($s);
		if ($pos + 5 > $len || strncasecmp(substr($s, $pos, 5), 'array', 5) !== 0) {
			return;
		}
		$look = $pos + 5;
		while ($look < $len && ($s[$look] === ' ' || $s[$look] === "\t"
			|| $s[$look] === "\n" || $s[$look] === "\r"
			|| $s[$look] === "\f" || $s[$look] === "\v")
		) {
			$look++;
		}
		if ($look < $len && $s[$look] === '(') {
			$pos = $look;
		}
	}

	/**
	 * Has advanced `$pos` past any ASCII whitespace.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor.
	 * @since 4.4.0
	 */
	private static function _skipWs(string $s, int &$pos): void
	{
		$len = strlen($s);
		while ($pos < $len && ($s[$pos] === ' ' || $s[$pos] === "\t"
			|| $s[$pos] === "\n" || $s[$pos] === "\r"
			|| $s[$pos] === "\f" || $s[$pos] === "\v")
		) {
			$pos++;
		}
	}

	/**
	 * Has consumed an array literal at `$pos` (which has pointed at `(` or
	 * `[`) and returned the parsed array.  Mixed delimiters at deeper nesting
	 * have been handled by recursive calls.  Auto-numbered keys have followed
	 * PHP's "next integer greater than the largest int key seen so far" rule.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @param bool $strict propagated to nested {@see _consumeValue()} /
	 *   {@see _consumeKey()} calls so they have skipped the bare-word
	 *   fallback under strict grammar.
	 * @return array the parsed array.
	 * @since 4.4.0
	 */
	private static function _consumeArray(string $s, int &$pos, bool $strict = false): array
	{
		$len = strlen($s);
		$close = $s[$pos] === '(' ? ')' : ']';
		$pos++;
		$result = [];
		$nextAutoKey = 0;
		self::_skipWs($s, $pos);
		// The validator guarantees a matching close bracket exists; the
		// `$pos < $len` guard has only protected against a validator-parser
		// drift turning into an infinite loop on PHP 8.x's out-of-bounds
		// string access returning the empty string.
		while ($pos < $len && $s[$pos] !== $close) {
			$saved = $pos;
			$key = self::_consumeKey($s, $pos, $strict);
			self::_skipWs($s, $pos);
			if ($key !== null && isset($s[$pos + 1]) && $s[$pos] === '=' && $s[$pos + 1] === '>') {
				$pos += 2;
				self::_skipWs($s, $pos);
				$result[$key] = self::_consumeValue($s, $pos, $strict);
				if (is_int($key) && $key >= $nextAutoKey) {
					$nextAutoKey = $key + 1;
				}
			} else {
				$pos = $saved;
				$result[$nextAutoKey++] = self::_consumeValue($s, $pos, $strict);
			}
			self::_skipWs($s, $pos);
			if ($pos < $len && $s[$pos] === ',') {
				$pos++;
				self::_skipWs($s, $pos);
			}
		}
		if ($pos < $len) {
			$pos++;
		}
		return $result;
	}

	/**
	 * Has consumed a value (scalar or nested array) at `$pos`.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @param bool $strict propagated to {@see _consumeArray()} and
	 *   {@see _consumeScalar()} so the bare-word fallback has been disabled
	 *   under strict grammar.
	 * @return mixed the parsed value.
	 * @since 4.4.0
	 */
	private static function _consumeValue(string $s, int &$pos, bool $strict = false): mixed
	{
		$c = $s[$pos];
		if ($c === '(' || $c === '[') {
			return self::_consumeArray($s, $pos, $strict);
		}
		// The `array(...)` keyword form has been recognized at every depth in
		// both grammars; the helper has advanced `$pos` past `array` and the
		// following whitespace so the array body has been consumed normally.
		if (($c === 'a' || $c === 'A')
			&& $pos + 5 <= strlen($s)
			&& strncasecmp(substr($s, $pos, 5), 'array', 5) === 0
		) {
			$saved = $pos;
			self::_skipArrayKeyword($s, $pos);
			if ($pos !== $saved) {
				return self::_consumeArray($s, $pos, $strict);
			}
		}
		return self::_consumeScalar($s, $pos, $strict);
	}

	/**
	 * Has tentatively consumed a key (quoted string, integer literal, or
	 * bare-word string) at `$pos`.  An int key has committed only when
	 * directly followed by `=>` (after optional whitespace) so the bare-word
	 * fallback can still claim the same span when the element has turned out
	 * not to be a key⇒value pair.  Returns `null` without advancing `$pos`
	 * when no key form has matched at all.
	 *
	 * The caller has decided whether to commit (when `=>` has followed) or
	 * restore `$pos` and reparse the span as a value.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @param bool $strict when `true`, the bare-word key fallback has been
	 *   skipped so non-int / non-string spans have returned `null` and the
	 *   caller has reparsed the span as a value.
	 * @return null|int|string the key, or `null` if no key has been present.
	 * @since 4.4.0
	 */
	private static function _consumeKey(string $s, int &$pos, bool $strict = false): null|int|string
	{
		if ($s[$pos] === '"' || $s[$pos] === "'") {
			$str = self::_tryConsumeString($s, $pos);
			if ($str !== null) {
				return $str;
			}
		}
		if (preg_match(self::INT_LITERAL_PATTERN, $s, $m, 0, $pos)) {
			$end = $pos + strlen($m[0]);
			$body = ($m[0][0] === '+' || $m[0][0] === '-') ? substr($m[0], 1) : $m[0];
			$isPrefixed = strlen($body) >= 2 && $body[0] === '0'
				&& ($body[1] === 'x' || $body[1] === 'X'
					|| $body[1] === 'b' || $body[1] === 'B'
					|| $body[1] === 'o' || $body[1] === 'O');
			$isFloatPrefix = !$isPrefixed && isset($s[$end])
				&& ($s[$end] === '.' || $s[$end] === 'e' || $s[$end] === 'E');
			if (!$isFloatPrefix) {
				$look = $end;
				while (isset($s[$look]) && ($s[$look] === ' ' || $s[$look] === "\t"
					|| $s[$look] === "\n" || $s[$look] === "\r"
					|| $s[$look] === "\f" || $s[$look] === "\v")
				) {
					$look++;
				}
				if (isset($s[$look]) && $s[$look] === '=' && isset($s[$look + 1])
					&& $s[$look + 1] === '>'
				) {
					$pos = $end;
					return self::_intFromLiteral($m[0]);
				}
			}
		}
		if ($strict) {
			return null;
		}
		$bare = self::_consumeBareWord($s, $pos);
		return $bare !== '' ? $bare : null;
	}

	/**
	 * Has consumed a scalar at `$pos` in priority order: quoted string, float,
	 * int, reserved keyword (`null`/`true`/`false`), and — under loose grammar
	 * only — bare-word string.  Each numeric or keyword candidate has had to
	 * reach an element-end (top-level `,`, `=>`, `)`, `]`, or end of input)
	 * to commit; otherwise the span has fallen through to the bare-word
	 * fallback so that mistyped or suffixed tokens (`1abc`, `truely`, `0xZZ`)
	 * have become strings rather than being silently truncated.  Under strict
	 * grammar the bare-word fallback has been disabled — the strict validator
	 * has already rejected any input that would have reached it.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @param bool $strict whether to skip the bare-word fallback.
	 * @return null|bool|float|int|string the parsed scalar.
	 * @since 4.4.0
	 */
	private static function _consumeScalar(string $s, int &$pos, bool $strict = false): null|bool|float|int|string
	{
		if ($s[$pos] === '"' || $s[$pos] === "'") {
			$str = self::_tryConsumeString($s, $pos);
			if ($str !== null) {
				return $str;
			}
		}
		if (preg_match(self::FLOAT_LITERAL_PATTERN, $s, $m, 0, $pos)) {
			$end = $pos + strlen($m[0]);
			if (self::_atElementEnd($s, $end)) {
				$pos = $end;
				return self::_floatFromLiteral($m[0]);
			}
		}
		if (preg_match(self::INT_LITERAL_PATTERN, $s, $m, 0, $pos)) {
			$end = $pos + strlen($m[0]);
			if (self::_atElementEnd($s, $end)) {
				$pos = $end;
				return self::_intFromLiteral($m[0]);
			}
		}
		if (preg_match('/\Gnull\b/iA', $s, $m, 0, $pos) && self::_atElementEnd($s, $pos + 4)) {
			$pos += 4;
			return null;
		}
		if (preg_match('/\Gtrue\b/iA', $s, $m, 0, $pos) && self::_atElementEnd($s, $pos + 4)) {
			$pos += 4;
			return true;
		}
		if (preg_match('/\Gfalse\b/iA', $s, $m, 0, $pos) && self::_atElementEnd($s, $pos + 5)) {
			$pos += 5;
			return false;
		}
		if ($strict) {
			return null;
		}
		return self::_consumeBareWord($s, $pos);
	}

	/**
	 * Has converted a validated PHP-style integer-literal string to its PHP
	 * `int` value, handling sign, underscored digit separators, and three
	 * base prefixes: `0x`/`0X` (hex), `0b`/`0B` (binary), and `0o`/`0O`
	 * (modern octal, PHP 8.1+).  PHP 7's leading-zero octal form has been
	 * intentionally dropped — `017` resolves to decimal 17, not octal 15,
	 * matching PHP 8.1's direction of forcing the explicit `0o` prefix for
	 * octal intent and avoiding the long-standing leading-zero footgun.
	 *
	 * @param string $raw the validated literal — the regex has already
	 *   guaranteed a well-formed form.
	 * @return int the integer value.
	 * @since 4.4.0
	 */
	private static function _intFromLiteral(string $raw): int
	{
		$sign = 1;
		if ($raw[0] === '+') {
			$raw = substr($raw, 1);
		} elseif ($raw[0] === '-') {
			$sign = -1;
			$raw = substr($raw, 1);
		}
		$raw = str_replace('_', '', $raw);
		if (strlen($raw) >= 2 && $raw[0] === '0') {
			$p = $raw[1];
			if ($p === 'x' || $p === 'X') {
				return $sign * intval(substr($raw, 2), 16);
			}
			if ($p === 'b' || $p === 'B') {
				return $sign * intval(substr($raw, 2), 2);
			}
			if ($p === 'o' || $p === 'O') {
				return $sign * intval(substr($raw, 2), 8);
			}
		}
		return $sign * (int) $raw;
	}

	/**
	 * Has converted a validated PHP-style float-literal string to its PHP
	 * `float` value.  Digit-separator underscores have been stripped before
	 * casting so literals like `1_000.5_5` or `1.5e1_0` have resolved
	 * correctly — PHP's `(float)` cast has not honored underscores in
	 * numeric strings.
	 *
	 * @param string $raw the validated literal.
	 * @return float the float value.
	 * @since 4.4.0
	 */
	private static function _floatFromLiteral(string $raw): float
	{
		return (float) str_replace('_', '', $raw);
	}

	/**
	 * Has tentatively consumed a single- or double-quoted string at `$pos`,
	 * returning `null` and restoring `$pos` to its entry value when the input
	 * has not provided a matching close quote.  An unterminated literal has
	 * therefore yielded the bare-word fallback in the caller — the original
	 * quote character has remained part of the resulting string.
	 *
	 * Escape sequences supported by each quote style:
	 *
	 * - single-quoted: `\\` → `\`, `\'` → `'`; any other `\X` has been
	 *   preserved verbatim, matching PHP's `'...'` literal behavior.
	 * - double-quoted: `\n`, `\r`, `\t`, `\v`, `\f`, `\e`, `\0`, `\\`, `\"`,
	 *   and `\$` have resolved to the corresponding byte; any other `\X`
	 *   has been preserved verbatim.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @return ?string the decoded string contents (without surrounding
	 *   quotes), or `null` when the string has been unterminated.
	 * @since 4.4.0
	 */
	private static function _tryConsumeString(string $s, int &$pos): ?string
	{
		static $dqEscapes = [
			'n' => "\n", 'r' => "\r", 't' => "\t",
			'v' => "\v", 'f' => "\f", 'e' => "\x1b",
			'0' => "\0", '\\' => '\\', '"' => '"', '$' => '$',
		];
		$saved = $pos;
		$quote = $s[$pos];
		$pos++;
		$len = strlen($s);
		$result = '';
		while ($pos < $len && $s[$pos] !== $quote) {
			if ($s[$pos] === '\\' && $pos + 1 < $len) {
				$next = $s[$pos + 1];
				if ($quote === "'") {
					if ($next === '\\' || $next === "'") {
						$result .= $next;
						$pos += 2;
						continue;
					}
				} elseif (isset($dqEscapes[$next])) {
					$result .= $dqEscapes[$next];
					$pos += 2;
					continue;
				}
			}
			$result .= $s[$pos];
			$pos++;
		}
		if ($pos >= $len) {
			$pos = $saved;
			return null;
		}
		$pos++;
		return $result;
	}

	/**
	 * Has consumed a bare-word string at `$pos` — every character up to (but
	 * not including) the next top-level delimiter (`,`, `=>`, `)`, `]`, or
	 * end of input), with the trailing whitespace trimmed.  When `$pos` has
	 * already sat at a delimiter, no characters have been consumed and the
	 * empty string has been returned so the caller can decide whether the
	 * absence of a token has been an error.
	 *
	 * @param string $s the source string.
	 * @param int &$pos the position cursor (in/out).
	 * @return string the trimmed bare-word, possibly empty.
	 * @since 4.4.0
	 */
	private static function _consumeBareWord(string $s, int &$pos): string
	{
		$len = strlen($s);
		if ($pos >= $len) {
			return '';
		}
		$c = $s[$pos];
		if ($c === ',' || $c === ')' || $c === ']'
			|| ($c === '=' && isset($s[$pos + 1]) && $s[$pos + 1] === '>')
		) {
			return '';
		}
		$start = $pos;
		$pos++;
		while ($pos < $len) {
			$c = $s[$pos];
			if ($c === ',' || $c === ')' || $c === ']') {
				break;
			}
			if ($c === '=' && isset($s[$pos + 1]) && $s[$pos + 1] === '>') {
				break;
			}
			$pos++;
		}
		return rtrim(substr($s, $start, $pos - $start));
	}

	/**
	 * Has reported whether the next non-whitespace character at `$pos` ends
	 * the current element — a top-level `,`, `=>`, `)`, `]`, or end of input.
	 * Used by {@see _consumeScalar()} to decide when a number or reserved
	 * keyword has committed cleanly versus fallen through to bare-word.
	 *
	 * @param string $s the source string.
	 * @param int $pos the position to inspect (not advanced).
	 * @return bool whether `$pos` has been at an element boundary.
	 * @since 4.4.0
	 */
	private static function _atElementEnd(string $s, int $pos): bool
	{
		$len = strlen($s);
		while ($pos < $len && ($s[$pos] === ' ' || $s[$pos] === "\t"
			|| $s[$pos] === "\n" || $s[$pos] === "\r"
			|| $s[$pos] === "\f" || $s[$pos] === "\v")
		) {
			$pos++;
		}
		if ($pos >= $len) {
			return true;
		}
		$c = $s[$pos];
		if ($c === ',' || $c === ')' || $c === ']') {
			return true;
		}
		return $c === '=' && isset($s[$pos + 1]) && $s[$pos + 1] === '>';
	}

	// =========================================================================
	// applyProperty() + coerceForSetter() + coerceToType() + Helpers
	// =========================================================================

	/**
	 * Sets a named property on an object, coercing the value toward the setter's declared
	 * parameter type before assignment. The value may be of any type — strings from XML
	 * config, booleans from PHP config arrays, objects from template expressions, etc.
	 * The final assignment always goes through the object's normal `__set()` chain so that
	 * events, behaviors, and `js`-prefix wrapping are preserved.
	 *
	 * This is the recommended entry point for framework internals (configuration loaders,
	 * template parsers, behavior injectors) that assign a property whose source type may
	 * not match the setter's declared type.
	 *
	 * @param object $object the target object.
	 * @param string $property the property name without the `set` prefix, e.g. `'Enabled'`.
	 * @param mixed $value the value to set; coerced to the setter's declared type.
	 * @since 4.4.0
	 */
	public static function applyProperty(object $object, string $property, mixed $value): void
	{
		$setter = 'set' . $property;
		if (method_exists($object, $setter)
			|| ($object instanceof TComponent && ($object->hasMethod($setter) || $object->getBehaviorsEnabled()))
		) {
			static::coerceForSetter($object, $setter, $value);
		}
		// Goes through __set() — preserves event/behavior/JS-prefix logic
		$object->$property = $value;
	}

	/**
	 * Coerces a value in-place to match a type hint declared on an object's setter.
	 *
	 * `$value` is taken by reference so that no copy is made at the call boundary
	 * when the caller holds a large value. The value is only coerced when a concrete
	 * type hint exists on the first parameter of `$method`; it is left unchanged
	 * when no hint is found.
	 *
	 * Reflection and behavior-method lookup are delegated to
	 * {@see TComponentReflection::getReflectionMethodByType()}, which maintains a
	 * shared framework-wide cache and automatically searches active behaviors on
	 * {@see TComponent} instances when the class itself does not declare the setter.
	 *
	 * @param object|string $classOrObject the class name, or an object instance (preferred
	 *   when the object may carry active behaviors that expose the setter).
	 * @param string $method the setter method name, e.g. `'setEnabled'`.
	 * @param mixed &$value the value to coerce in-place.
	 * @since 4.4.0
	 */
	public static function coerceForSetter(string|object $classOrObject, string $method, mixed &$value): void
	{
		$ref = TComponentReflection::getReflectionMethodByType($classOrObject, $method);
		$type = $ref !== null ? ($ref->getParameters()[0] ?? null)?->getType() : null;
		$value = static::coerceToType($value, $type);
	}

	/**
	 * Coerces a value to the specified reflection type using the existing `ensure*`
	 * helpers for scalar types and special handling for backed enums and union types.
	 *
	 * | Type hint             | Behavior                                           |
	 * |-----------------------|----------------------------------------------------|
	 * | `bool`                | {@see ensureBoolean} — 'true'/'false' aware        |
	 * | `int`                 | {@see ensureInteger}                               |
	 * | `float`               | {@see ensureFloat}                                 |
	 * | `string`              | {@see ensureString} — bool→'true'/'false' aware    |
	 * | `array` / `iterable`  | {@see ensureArray} — `(a,b,c)`/`[a,b,c]` supported |
	 * | `object`              | {@see ensureObject}                                |
	 * | `mixed` / `null`      | pass-through / `null`                              |
	 * | Backed enum class     | `tryFrom()` exact backing value, then case-insensitive case-name scan |
	 * | {@see IEnumerable}    | case-insensitive `valueOfConstant()` name→value; unknown → pass-through |
	 * | {@see ICoercible}     | `coerceFromValue()` factory; `null` return → pass-through |
	 * | Union type            | heuristic chain — see {@see _coerceUnionType}      |
	 * | Nullable (`?T`)       | empty string / `null` input → `null`               |
	 *
	 * @param mixed $value the value to coerce.
	 * @param null|\ReflectionType $type the reflection type to coerce toward, or `null`
	 *   to return `$value` unchanged.
	 * @return mixed the coerced value.
	 * @since 4.4.0
	 */
	public static function coerceToType(mixed $value, ?\ReflectionType $type): mixed
	{
		if ($type === null) {
			return $value;
		}
		if ($type instanceof \ReflectionNamedType) {
			// Actual null, or empty string when the type permits null
			if ($value === null || ($type->allowsNull() && $value === '')) {
				return null;
			}
			return match ($type->getName()) {
				static::TYPE_BOOL => static::ensureBoolean($value),
				static::TYPE_INT => static::ensureInteger($value),
				static::TYPE_FLOAT => static::ensureFloat($value),
				static::TYPE_STRING => static::ensureString($value),
				static::TYPE_ARRAY, static::TYPE_ITERABLE => static::ensureArray($value),
				static::TYPE_OBJECT => static::ensureObject($value),
				static::TYPE_MIXED, static::TYPE_NULL => $value,
				default => self::_coerceToClass($value, $type->getName()),
			};
		}
		if ($type instanceof \ReflectionUnionType) {
			return self::_coerceUnionType($value, $type);
		}
		// ReflectionIntersectionType and any future reflection types: pass through
		return $value;
	}

	/**
	 * Coerces a value toward a non-builtin class type that Prado recognizes
	 * as a coercible or enumerable domain.  All other class types are
	 * returned unchanged.
	 *
	 * Resolution order parallels {@see _coerceUnionType()}:
	 *
	 * 1. **ICoercible** — when `$className` implements {@see \Prado\ICoercible},
	 *    an existing instance of the class passes through unchanged; otherwise
	 *    `coerceFromValue($value)` runs and a non-`null` return wins.  A `null`
	 *    return continues to the enum paths below.
	 * 2. **BackedEnum + int** — `tryFrom($value)` resolves an int input to
	 *    its backing value's case.
	 * 3. **String + enum** — {@see _tryMatchEnum()} performs a case-insensitive
	 *    name lookup (and, for BackedEnum, value-based `tryFrom` first).
	 *
	 * On any miss, `$value` is returned unchanged so the TypeError surfaces
	 * at the setter boundary.
	 *
	 * @param mixed $value the value to coerce.
	 * @param string $className the target class or interface name.
	 * @return mixed the coerced value, or `$value` unchanged on miss.
	 * @since 4.4.0
	 */
	private static function _coerceToClass(mixed $value, string $className): mixed
	{
		if (is_a($className, ICoercible::class, true)) {
			if ($value instanceof $className) {
				return $value;
			}
			$coerced = $className::coerceFromValue($value);
			if ($coerced !== null) {
				return $coerced;
			}
		}
		if (is_a($className, \BackedEnum::class, true) && is_int($value)) {
			try {
				return $className::tryFrom($value) ?? $value;
			} catch (\TypeError) {
				return $value;
			}
		}
		if (is_string($value)) {
			$match = self::_tryMatchEnum($className, $value);
			if ($match !== null) {
				return $match;
			}
		}
		return $value;
	}

	/**
	 * Has validated a string against an enumerable class by *constant name*
	 * (case-insensitively), returning the matched form on a hit and `null`
	 * on a miss.  The enum has acted purely as a name-validator — name→value
	 * translation has been left to the class itself (or to a separate
	 * coercion pass).  Returned forms:
	 *
	 * - `UnitEnum` / `BackedEnum` — the matched case object; for
	 *   `BackedEnum` a `tryFrom($value)` backing-value lookup has run first
	 *   so a raw backing value also resolves to its case.
	 * - {@see IEnumerable} — the canonical constant name with original
	 *   casing preserved, so `'red'` has resolved to `'Red'` against a
	 *   `const Red = '#FF0000'` declaration.  The constant's *value* has
	 *   *not* been returned here — the typical property type pattern
	 *   `TWebColor|string` accepts the validated name string and the
	 *   class itself has translated `'Red'` → `'#FF0000'` when needed.
	 *
	 * Name lookup has been case-insensitive — `'red'`, `'Red'`, and `'RED'`
	 * have all resolved to the constant named `Red`.  When `$className` has
	 * not been an enumerable class, the function has returned `null` without
	 * doing anything.
	 *
	 * The helper has been the shared resolver behind {@see _coerceToClass()}
	 * for string inputs and behind the enumerable-validation step of
	 * {@see _coerceUnionType()}.
	 *
	 * @param string $className the candidate enumerable class name.
	 * @param string $value the candidate constant name (or backing value
	 *   for BackedEnum).
	 * @return mixed the matched case (UnitEnum / BackedEnum) or canonical
	 *   constant name (IEnumerable), or `null` on miss / non-enum class.
	 * @since 4.4.0
	 */
	private static function _tryMatchEnum(string $className, string $value): mixed
	{
		if (is_a($className, \UnitEnum::class, true)) {
			if (is_a($className, \BackedEnum::class, true)) {
				try {
					$case = $className::tryFrom($value);
					if ($case !== null) {
						return $case;
					}
				} catch (\TypeError) {
				}
			}
			foreach ($className::cases() as $case) {
				if (strcasecmp($case->name, $value) === 0) {
					return $case;
				}
			}
			return null;
		}
		if (is_a($className, IEnumerable::class, true)) {
			$ref = TComponentReflection::getReflectionClassByType($className);
			if ($ref === null) {
				return null;
			}
			foreach ($ref->getConstants() as $name => $_) {
				if (strcasecmp($name, $value) === 0) {
					return $name;
				}
			}
		}
		return null;
	}

	/**
	 * Coerces a value toward one of the types in a PHP union type hint.
	 *
	 * When the union is unambiguous (one non-null member) the single type is used
	 * directly. For genuinely ambiguous unions a priority-ordered heuristic chain
	 * selects the most appropriate type:
	 *
	 * 1. `null` / empty string → `null` (when `null` is in the union)
	 * 2. **ICoercible** — non-builtin union members implementing
	 *    {@see \Prado\ICoercible} are tried in declaration order; an existing
	 *    instance passes through, otherwise `coerceFromValue()` runs and the
	 *    first non-`null` result wins.
	 * 3. Enumerable validation — for string `$value`, case-insensitive
	 *    constant-name lookup via {@see _tryMatchEnum()} against each
	 *    enum-like (UnitEnum / IEnumerable) union member; the first match
	 *    wins.  Return shape per {@see _tryMatchEnum()}'s contract.
	 * 4. `array` / typed object value whose type appears in the union → use it
	 *    (pre-empts step 5 to prevent `(string)$array = "Array"` and
	 *    `(string)$object` TypeError when the value has a native match)
	 * 5. `string` in union → pass through / `ensureString` if not already a string
	 *    (scalar non-string values such as `int` and `bool` are coerced to their
	 *    string representation here when `string` is present in the union)
	 * 6. Non-string typed value whose PHP type directly matches a union member → use it
	 *    (only reached when `string` is NOT in the union); then PHP-compatible
	 *    widening: `bool` → `int`/`float`, `int` → `float`
	 * 7. `(a,b,c)` / `[a,b,c]` / `array(a,b,c)` notation + `array`/`iterable` in union → {@see ensureArray}
	 * 8. `'true'`/`'false'` literal + `bool` in union → {@see ensureBoolean}
	 * 9. Numeric value + `int`/`float` in union → float when `.` or `e`/`E` (scientific notation)
	 *    is present, int otherwise; matching PHP non-strict behavior
	 * 10. Non-builtin class types — value-based lookup via {@see _coerceToClass}
	 *     catches inputs that match an enum's *backing value* (e.g. `100` for an
	 *     int-backed enum) where step 3's *name* lookup did not apply
	 * 11. Fallback: first non-`null` type sorted by {@see TYPE_COERCE_ORDER}
	 *
	 * @param mixed $value the value to coerce.
	 * @param \ReflectionUnionType $type the union type.
	 * @return mixed the coerced value.
	 * @since 4.4.0
	 */
	private static function _coerceUnionType(mixed $value, \ReflectionUnionType $type): mixed
	{
		/** @var \ReflectionNamedType[] $named */
		$named = array_values(array_filter(
			$type->getTypes(),
			fn ($t) => $t instanceof \ReflectionNamedType
		));
		$names = array_map(fn (\ReflectionNamedType $t) => $t->getName(), $named);
		/** @var array<string,\ReflectionNamedType> $typeMap */
		$typeMap = array_combine($names, $named);

		// 1. Null handling
		$allowsNull = in_array(static::TYPE_NULL, $names, true);
		if ($value === null || ($allowsNull && $value === '')) {
			return null;
		}

		// Optimization: single non-null type — delegate directly
		$nonNull = array_values(array_filter($named, fn (\ReflectionNamedType $t) => $t->getName() !== static::TYPE_NULL));
		if (count($nonNull) === 1) {
			return static::coerceToType($value, $nonNull[0]);
		}

		// 2. ICoercible.  Each non-builtin {@see ICoercible} member, in
		// declaration order, claims an identity match or the first non-`null`
		// `coerceFromValue()` return.
		foreach ($nonNull as $t) {
			if ($t->isBuiltin()) {
				continue;
			}
			$n = $t->getName();
			if (is_a($n, ICoercible::class, true)) {
				if ($value instanceof $n) {
					return $value;
				}
				$coerced = $n::coerceFromValue($value);
				if ($coerced !== null) {
					return $coerced;
				}
			}
		}

		// 3. Enum validation.  Case-insensitive constant-name lookup against
		// any enum-like (UnitEnum / IEnumerable) union member via
		// {@see _tryMatchEnum()}, which returns `null` for non-enum classes;
		// the first match wins.
		if (is_string($value)) {
			foreach ($nonNull as $t) {
				if ($t->isBuiltin()) {
					continue;
				}
				$match = self::_tryMatchEnum($t->getName(), $value);
				if ($match !== null) {
					return $match;
				}
			}
		}

		// 4. Non-stringable native short-circuit.  Arrays and typed-object
		// instances claim a native union match before step 5 —
		// `(string)$arr` would be the useless `"Array"` and `(string)$obj`
		// TypeErrors without `__toString()`.
		if (!is_string($value)) {
			if (is_array($value)) {
				// Prefer array over iterable when both are present.
				if (isset($typeMap[static::TYPE_ARRAY])) {
					return static::coerceToType($value, $typeMap[static::TYPE_ARRAY]);
				}
				if (isset($typeMap[static::TYPE_ITERABLE])) {
					return static::coerceToType($value, $typeMap[static::TYPE_ITERABLE]);
				}
			} elseif (is_object($value)) {
				foreach ($nonNull as $t) {
					$n = $t->getName();
					if (!$t->isBuiltin() && $value instanceof $n) {
						return static::coerceToType($value, $t);
					}
				}
			}
		}

		// 5. String member.  Strings pass through; non-string scalars
		// (bool, int, float) are coerced via {@see ensureString()}.
		// Step 4 has already claimed any array or typed object with a
		// native union match.
		if (in_array(static::TYPE_STRING, $names, true)) {
			return is_string($value) ? $value : static::ensureString($value);
		}

		// 6. Native-type short-circuit: only reached when string is NOT in the union.
		// First try an exact match (Pass A), then apply PHP non-strict widening
		// coercions: bool → int/float (true=1, false=0) and int → float (Pass B).
		if (!is_string($value)) {
			// Pass A — exact native-type match
			foreach ($nonNull as $t) {
				$n = $t->getName();
				if (
					(is_bool($value) && $n === static::TYPE_BOOL) ||
					(is_int($value) && $n === static::TYPE_INT) ||
					(is_float($value) && $n === static::TYPE_FLOAT) ||
					(is_object($value) && !$t->isBuiltin() && $value instanceof $n)
				) {
					return static::coerceToType($value, $t);
				}
			}
			// Pass B — PHP non-strict widening when the native type is absent from the union
			if (is_bool($value)) {
				// bool widens to int first, then float (PHP non-strict: true=1, false=0)
				foreach ([static::TYPE_INT, static::TYPE_FLOAT] as $target) {
					if (isset($typeMap[$target])) {
						return static::coerceToType($value, $typeMap[$target]);
					}
				}
			} elseif (is_int($value)) {
				// int widens to float (lossless promotion)
				if (isset($typeMap[static::TYPE_FLOAT])) {
					return static::coerceToType($value, $typeMap[static::TYPE_FLOAT]);
				}
			}
			// No match — convert to string and continue with shape heuristics
		}

		$strValue = is_string($value) ? $value : static::ensureString($value);
		$hasArray = in_array(static::TYPE_ARRAY, $names, true) || in_array(static::TYPE_ITERABLE, $names, true);
		$hasBool = in_array(static::TYPE_BOOL, $names, true);
		$hasInt = in_array(static::TYPE_INT, $names, true);
		$hasFloat = in_array(static::TYPE_FLOAT, $names, true);

		// 7. Array notation — unambiguous regardless of other types present.
		// Three delimiter forms are accepted: the Prado `(...)` convention,
		// the PHP 8 short `[...]` form, and the PHP `array(...)` keyword form.
		if ($hasArray) {
			$trimmed = trim($strValue);
			$tlen = strlen($trimmed);
			if ($tlen >= 2
				&& (($trimmed[0] === '(' && $trimmed[$tlen - 1] === ')')
					|| ($trimmed[0] === '[' && $trimmed[$tlen - 1] === ']')
					|| ($trimmed[$tlen - 1] === ')' && stripos($trimmed, 'array') === 0))
			) {
				return static::ensureArray($strValue);
			}
		}

		// 8. Boolean literals — 'true'/'false' only, not generic truthy strings
		if ($hasBool && in_array(strtolower($strValue), [static::BOOL_TRUE, static::BOOL_FALSE], true)) {
			return static::ensureBoolean($strValue);
		}

		// 9. Numeric shape
		if (is_numeric($strValue)) {
			if ($hasInt && $hasFloat) {
				// Treat as float when: (a) a decimal point or scientific-notation exponent is
				// present ('1e5', '2.5e-1'), or (b) the value exceeds PHP_INT range — matching
				// PHP's own non-strict coercion where out-of-range integer strings promote to
				// float.  The lossless round-trip `(string)(int)$s === ltrim($s, '+')` detects
				// overflow without platform-specific constants.
				if (str_contains($strValue, '.') || stripos($strValue, 'e') !== false) {
					return (float) $strValue;
				}
				$intVal = (int) $strValue;
				return ((string) $intVal === ltrim($strValue, '+')) ? $intVal : (float) $strValue;
			}
			if ($hasInt) {
				return (int) $strValue;
			}
			if ($hasFloat) {
				return (float) $strValue;
			}
		}

		// 10. Non-builtin class value-based lookup.  Tries the original $value
		// first so a PHP int reaches an int-backed enum's tryFrom() before
		// stringification; retries with $strValue only when the original
		// attempt produced no change.  `isBuiltin()` excludes the `null`
		// member of the union (which it reports as builtin).
		foreach ($named as $t) {
			if (!$t->isBuiltin()) {
				$n = $t->getName();
				$coerced = self::_coerceToClass($value, $n);
				if ($coerced !== $value) {
					return $coerced;
				}
				if ($strValue !== $value) {
					$coerced = self::_coerceToClass($strValue, $n);
					if ($coerced !== $strValue) {
						return $coerced;
					}
				}
			}
		}

		// 11. Fallback.  Sorts non-null members by {@see TYPE_COERCE_ORDER}
		// (non-builtin class names sort after every builtin, preserving
		// reflection order among themselves) and coerces $strValue toward
		// the winner.  $nonNull has ≥ 2 members here — single-non-null
		// short-circuited above.
		$fallback = $nonNull;
		usort($fallback, static function (\ReflectionNamedType $a, \ReflectionNamedType $b): int {
			$max = count(self::TYPE_COERCE_ORDER);
			$ai = array_search($a->getName(), self::TYPE_COERCE_ORDER, true);
			$bi = array_search($b->getName(), self::TYPE_COERCE_ORDER, true);
			return ($ai === false ? $max : $ai) <=> ($bi === false ? $max : $bi);
		});
		return static::coerceToType($strValue, $fallback[0]);
	}
}
