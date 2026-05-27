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
	 * Step-9 fallback order for {@see _coerceUnionType}: non-null union members are tried in this
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
	 * @param mixed $value the value to be converted.
	 * @return bool
	 */
	public static function ensureBoolean($value): bool
	{
		if (is_string($value)) {
			return strcasecmp($value, self::BOOL_TRUE) === 0 || (is_numeric($value) && $value != 0);
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
	 * @param mixed $value the value to be converted.
	 * @return string|\Stringable
	 */
	public static function ensureString($value): string|\Stringable
	{
		if (TJavaScript::isJsLiteral($value)) {
			return $value;
		}
		if (is_bool($value)) {
			return $value ? self::BOOL_TRUE : self::BOOL_FALSE;
		} else {
			return (string) $value;
		}
	}

	/**
	 * Converts a value to integer type.
	 * @param mixed $value the value to be converted.
	 * @return int
	 */
	public static function ensureInteger($value): int
	{
		return (int) $value;
	}

	/**
	 * Converts a value to float type.
	 * @param mixed $value the value to be converted.
	 * @return float
	 */
	public static function ensureFloat($value): float
	{
		return (float) $value;
	}

	/**
	 * Converts a value to object type.
	 * @param mixed $value the value to be converted.
	 * @return object
	 */
	public static function ensureObject($value): object
	{
		return (object) $value;
	}

	/**
	 * Converts a value to enum type.
	 *
	 * When `$enums` is a class name, three styles are supported:
	 *
	 * - **PHP native `\BackedEnum`** *(4.4.0+)*: accepts an existing instance of the class,
	 *   an exact backing value (via `tryFrom()`), or a case name (case-insensitive scan over
	 *   `cases()`).  Returns the matching `\BackedEnum` instance; throws
	 *   {@see TInvalidDataValueException} if none match.
	 * - **{@see IEnumerable}** (including {@see TEnumerable}): accepts a constant name
	 *   (case-insensitive); returns the canonical constant *value* (not the input string).
	 * - **Any other class**: accepts any constant name present on the class; returns the value.
	 *
	 * When `$enums` is an array (or extra variadic arguments), the value is checked for strict
	 * membership in the list; the matching element is returned unchanged.
	 * @param mixed $value the value to be converted.
	 * @param mixed $enums class name of the enumerable type, or array of valid enumeration values. If this is not an array,
	 * the method considers its parameters are of variable length, and the second till the last parameters are enumeration values.
	 * @throws TInvalidDataValueException if the value does not match any valid enumeration entry.
	 * @return \BackedEnum|string the valid enumeration value or instance.
	 */
	public static function ensureEnum($value, $enums): string|\BackedEnum
	{
		if (func_num_args() === 2 && is_string($enums)) {
			if (is_a($enums, \BackedEnum::class, true)) {
				if ($value instanceof $enums) {
					return $value;
				}
				// Guard backing-type before tryFrom: float (and other non-scalar) would cause
				// a native TypeError from tryFrom rather than a clean TInvalidDataValueException.
				if (is_string($value) || is_int($value)) {
					$case = $enums::tryFrom($value);
					if ($case !== null) {
						return $case;
					}
				}
				if (is_string($value)) {
					foreach ($enums::cases() as $case) {
						if (strcasecmp($case->name, $value) === 0) {
							return $case;
						}
					}
				}
				$labels = implode(' | ', array_map(fn (\BackedEnum $c) => $c->name . '=' . $c->value, $enums::cases()));
				$errVal = is_scalar($value) || $value === null ? $value : get_debug_type($value);
				throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid', $errVal, $labels);
			} elseif (is_a($enums, IEnumerable::class, true)) {
				if (is_string($value)) {
					$resolved = $enums::valueOfConstant($value, false);
					if ($resolved !== null) {
						return $resolved;
					}
				}
				$constants = is_a($enums, TEnumerable::class, true)
					? $enums::getReflectionClass()->getConstants()
					: (TComponentReflection::getReflectionClassByType($enums)?->getConstants() ?? []);
			} else {
				$ref = TComponentReflection::getReflectionClassByType($enums);
				if ($ref?->hasConstant($value)) {
					return $value;
				}
				$constants = $ref?->getConstants() ?? [];
			}
			$errVal = is_scalar($value) || $value === null ? $value : get_debug_type($value);
			throw new TInvalidDataValueException(
				'propertyvalue_enumvalue_invalid',
				$errVal,
				implode(' | ', $constants)
			);
		} elseif (!is_array($enums)) {
			$enums = func_get_args();
			array_shift($enums);
		}
		if (in_array($value, $enums, true)) {
			return $value;
		} else {
			throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid', $value, implode(' | ', $enums));
		}
	}

	/**
	 * Converts the value to 'null' if the given value is empty
	 * @param mixed $value value to be converted
	 * @return mixed input or NULL if input is empty
	 */
	public static function ensureNullIfEmpty($value): mixed
	{
		return empty($value) ? null : $value;
	}

	/**
	 * Converts the value to a web "#RRGGBB" hex color.
	 * The value[s] could be as an A) Web Color or # Hex Color string, or B) as a color
	 * encoded integer, eg 0x00RRGGBB, C) a triple ($value [red], $green, $blue), or D)
	 * an array of red, green, and blue, and index 0, 1, 2 or 'red', 'green', 'blue'.
	 * In instance (A), $green is treated as a boolean flag for whether to convert
	 * any web colors to their # hex color.  When red, green, or blue colors are specified
	 * they are assumed to be bound [0...255], inclusive.
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
			$blue = array_key_exists(self::COLOR_BLUE, $value) ? $value[self::COLOR_BLUE] : (array_key_exists(2, $value) ? $value[2] : null);
			$green = array_key_exists(self::COLOR_GREEN, $value) ? $value[self::COLOR_GREEN] : (array_key_exists(1, $value) ? $value[1] : true);
			$value = array_key_exists(self::COLOR_RED, $value) ? $value[self::COLOR_RED] : (array_key_exists(0, $value) ? $value[0] : null);
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
		$value = self::ensureString($value);
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
	// ensureArray() + Helpers
	// =========================================================================

	/**
	 * Has converted a value to array type.
	 *
	 * Non-string values have been cast via PHP's `(array)` coercion.  String
	 * values have been parsed as PHP-style array literals.  The behavior has
	 * been controlled by two flag bits:
	 *
	 * - {@see ARRAY_STRICT_GRAMMAR} — has restricted the parser to the
	 *   PHP-literal grammar (`[...]` short syntax or `array(...)` keyword
	 *   form, no bare `(...)` array, no bare-word strings, no legacy octal,
	 *   no auto-wrap of unbracketed input).
	 * - {@see ARRAY_STRICT_ERRORS} — has converted the silent
	 *   single-element fallback into a thrown
	 *   {@see TInvalidDataValueException}.  Composable with the grammar flag.
	 *
	 * With `$flags === 0` (the default) the loose grammar has applied:
	 * a trimmed string that has begun with `(` or `[` has been parsed in
	 * place; anything else has been re-parsed as if wrapped in `(...)`, so
	 * bare element lists like `red, green, blue` have resolved to
	 * `['red', 'green', 'blue']`.  Loose grammar has accepted PHP-style
	 * integers (decimal, hex `0xFF`, binary `0b101`, modern octal `0o17`,
	 * with optional underscored separators; PHP 7's leading-zero octal form
	 * has been dropped so `017` has read as decimal 17), floats
	 * (`1.5`, `.5`, `1e10`, `1_000.5_5`), single- or double-quoted strings
	 * with the common backslash escapes, the keywords `true`/`false`/`null`
	 * (case-insensitive), unquoted bare-word strings, and nested arrays in
	 * `(...)`, `[...]`, or `array(...)` form freely mixed.  Each element has
	 * supported an optional `key => ` prefix (int or string literal); a
	 * trailing comma has been accepted.  The bare-word rule has supported
	 * template-attribute conventions like
	 * `<com:TControl colors="red, green, blue"/>`.
	 *
	 * The parser has been regex-driven — no `eval()` — and the empty string
	 * has always returned the empty array.
	 *
	 * @param mixed $value the value to be converted.
	 * @param int $flags zero or more of {@see ARRAY_STRICT_GRAMMAR},
	 *   {@see ARRAY_STRICT_ERRORS} combined with `|`.  Defaults to `0`
	 *   (loose grammar, silent fallback).
	 * @throws TInvalidDataValueException when {@see ARRAY_STRICT_ERRORS} has
	 *   been set and the input has not parsed.
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
		$strict = ($flags & self::ARRAY_STRICT_GRAMMAR) !== 0;
		$allowBareParen = $strict && ($flags & self::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN) !== 0;
		$parsed = self::_parseArrayLiteral($value, $strict, $allowBareParen);
		if ($parsed === null && !$strict) {
			$parsed = self::_parseArrayLiteral('(' . $value . ')', false, false);
		}
		if ($parsed !== null) {
			return $parsed;
		}
		if (($flags & self::ARRAY_STRICT_ERRORS) !== 0) {
			throw new TInvalidDataValueException('propertyvalue_invalid_array_literal', $value);
		}
		return [$value];
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
	// applyProperty() + Helpers
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
			self::coerceForSetter($object, $setter, $value);
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
		$value = self::coerceToType($value, $type);
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
				self::TYPE_BOOL => self::ensureBoolean($value),
				self::TYPE_INT => self::ensureInteger($value),
				self::TYPE_FLOAT => self::ensureFloat($value),
				self::TYPE_STRING => self::ensureString($value),
				self::TYPE_ARRAY, self::TYPE_ITERABLE => self::ensureArray($value),
				self::TYPE_OBJECT => self::ensureObject($value),
				self::TYPE_MIXED, self::TYPE_NULL => $value,
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
	 * Coerces a value toward a non-builtin class type that Prado recognises as an
	 * enumerable domain.  All other class types are returned unchanged.
	 *
	 * - **PHP 8.1 backed enums** (`BackedEnum`): first tries `$className::tryFrom($value)`
	 *   for an exact backing-value match (e.g. `'red'` → `Color::Red`).  On a miss, a
	 *   case-insensitive name scan runs over `$className::cases()`, so the PHP case name
	 *   may be supplied in any casing (`'Red'`, `'RED'`, `'rEd'` all resolve to
	 *   `Color::Red`).  If neither lookup succeeds the original `$value` is returned so
	 *   the TypeError surfaces at the setter boundary.
	 *
	 * - **{@see IEnumerable} implementors** (including {@see TEnumerable} subclasses):
	 *   resolves a constant *name* to its *value* via `valueOfConstant($value, false)`
	 *   (case-insensitive).  For the conventional case where name equals value
	 *   (`const Left = 'Left'`), any casing of the name (`'left'`, `'LEFT'`) normalizes
	 *   to the canonical value `'Left'`.  When name differs from value (`const Alpha = 'a'`),
	 *   any casing of the name resolves to the value `'a'`.  If no constant name matches,
	 *   `$value` is returned unchanged so the TypeError surfaces at the setter boundary.
	 *
	 * @param mixed $value the value to coerce.
	 * @param string $className the target class or interface name.
	 * @return mixed the coerced value, or `$value` unchanged if coercion is not possible.
	 * @since 4.4.0
	 */
	private static function _coerceToClass(mixed $value, string $className): mixed
	{
		if (is_a($className, \BackedEnum::class, true) && (is_string($value) || is_int($value))) {
			$case = $className::tryFrom($value);
			if ($case !== null) {
				return $case;
			}
			if (is_string($value)) {
				foreach ($className::cases() as $case) {
					if (strcasecmp($case->name, $value) === 0) {
						return $case;
					}
				}
			}
			return $value;
		}
		if (is_a($className, IEnumerable::class, true) && is_string($value)) {
			return $className::valueOfConstant($value, false) ?? $value;
		}
		return $value;
	}

	/**
	 * Coerces a value toward one of the types in a PHP union type hint.
	 *
	 * When the union is unambiguous (one non-null member) the single type is used
	 * directly. For genuinely ambiguous unions a priority-ordered heuristic chain
	 * selects the most appropriate type:
	 *
	 * 1. `null` / empty string → `null` (when `null` is in the union)
	 * 2. `array` / typed object value whose type appears in the union → use it
	 *    (pre-empts step 3 to prevent `(string)$array = "Array"` and
	 *    `(string)$object` TypeError when the value has a native match)
	 * 3. `string` in union → pass through / `ensureString` if not already a string
	 *    (scalar non-string values such as `int` and `bool` are coerced to their
	 *    string representation here when `string` is present in the union)
	 * 4. Non-string typed value whose PHP type directly matches a union member → use it
	 *    (only reached when `string` is NOT in the union); then PHP-compatible
	 *    widening: `bool` → `int`/`float`, `int` → `float`
	 * 5. `(a,b,c)` / `[a,b,c]` / `array(a,b,c)` notation + `array`/`iterable` in union → {@see ensureArray}
	 * 6. `'true'`/`'false'` literal + `bool` in union → {@see ensureBoolean}
	 * 7. Numeric value + `int`/`float` in union → float when `.` or `e`/`E` (scientific notation)
	 *    is present, int otherwise; matching PHP non-strict behavior
	 * 8. Non-builtin class types (backed enums, {@see IEnumerable} implementors) → {@see _coerceToClass}
	 * 9. Fallback: first non-`null` type sorted by {@see TYPE_COERCE_ORDER}
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
		$allowsNull = in_array(self::TYPE_NULL, $names, true);
		if ($value === null || ($allowsNull && $value === '')) {
			return null;
		}

		// Optimization: single non-null type — delegate directly
		$nonNull = array_values(array_filter($named, fn (\ReflectionNamedType $t) => $t->getName() !== self::TYPE_NULL));
		if (count($nonNull) === 1) {
			return self::coerceToType($value, $nonNull[0]);
		}

		// 2. Pre-string short-circuit for non-stringable native values.
		// Arrays become the useless string "Array" via (string)$arr, and objects
		// without __toString() throw a TypeError via (string)$obj, so these two
		// forms are claimed before the string-member check in step 3.
		if (!is_string($value)) {
			if (is_array($value)) {
				// Prefer array over iterable when both are present.
				if (isset($typeMap[self::TYPE_ARRAY])) {
					return self::coerceToType($value, $typeMap[self::TYPE_ARRAY]);
				}
				if (isset($typeMap[self::TYPE_ITERABLE])) {
					return self::coerceToType($value, $typeMap[self::TYPE_ITERABLE]);
				}
			} elseif (is_object($value)) {
				foreach ($nonNull as $t) {
					$n = $t->getName();
					if (!$t->isBuiltin() && $value instanceof $n) {
						return self::coerceToType($value, $t);
					}
				}
			}
		}

		// 3. string is a valid union member — value is already valid as a string;
		// non-string scalar values (bool, int, float) are coerced to their string
		// representation here. Arrays and typed objects have already been claimed
		// by step 2 when a native-type match existed, so only ungrabbed values
		// reach ensureString().
		if (in_array(self::TYPE_STRING, $names, true)) {
			return is_string($value) ? $value : self::ensureString($value);
		}

		// 4. Native-type short-circuit: only reached when string is NOT in the union.
		// First try an exact match (Pass A), then apply PHP non-strict widening
		// coercions: bool → int/float (true=1, false=0) and int → float (Pass B).
		if (!is_string($value)) {
			// Pass A — exact native-type match
			foreach ($nonNull as $t) {
				$n = $t->getName();
				if (
					(is_bool($value) && $n === self::TYPE_BOOL) ||
					(is_int($value) && $n === self::TYPE_INT) ||
					(is_float($value) && $n === self::TYPE_FLOAT) ||
					(is_object($value) && !$t->isBuiltin() && $value instanceof $n)
				) {
					return self::coerceToType($value, $t);
				}
			}
			// Pass B — PHP non-strict widening when the native type is absent from the union
			if (is_bool($value)) {
				// bool widens to int first, then float (PHP non-strict: true=1, false=0)
				foreach ([self::TYPE_INT, self::TYPE_FLOAT] as $target) {
					if (isset($typeMap[$target])) {
						return self::coerceToType($value, $typeMap[$target]);
					}
				}
			} elseif (is_int($value)) {
				// int widens to float (lossless promotion)
				if (isset($typeMap[self::TYPE_FLOAT])) {
					return self::coerceToType($value, $typeMap[self::TYPE_FLOAT]);
				}
			}
			// No match — convert to string and continue with shape heuristics
		}

		$strValue = is_string($value) ? $value : self::ensureString($value);
		$hasArray = in_array(self::TYPE_ARRAY, $names, true) || in_array(self::TYPE_ITERABLE, $names, true);
		$hasBool = in_array(self::TYPE_BOOL, $names, true);
		$hasInt = in_array(self::TYPE_INT, $names, true);
		$hasFloat = in_array(self::TYPE_FLOAT, $names, true);

		// 5. Array notation — unambiguous regardless of other types present.
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
				return self::ensureArray($strValue);
			}
		}

		// 6. Boolean literals — 'true'/'false' only, not generic truthy strings
		if ($hasBool && in_array(strtolower($strValue), [self::BOOL_TRUE, self::BOOL_FALSE], true)) {
			return self::ensureBoolean($strValue);
		}

		// 7. Numeric shape
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

		// 8. Non-builtin class types (backed enums, value objects).
		// `isBuiltin()` returns `true` for `null` so the test has already
		// excluded the `null` member of the union.
		// Try the original $value first so that a PHP int reaches an int-backed enum's
		// tryFrom() before being stringified.  Only fall back to $strValue when the
		// original attempt produced no change (e.g. string-backed enums from non-string input).
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

		// 9. Fallback: sort by TYPE_COERCE_ORDER and coerce to the highest-priority type.
		// Non-builtin class names not in the list sort after all builtin types,
		// preserving their original reflection order relative to each other.
		// $nonNull always has ≥ 2 members here (single-non-null is handled above).
		$fallback = $nonNull;
		usort($fallback, static function (\ReflectionNamedType $a, \ReflectionNamedType $b): int {
			$max = count(self::TYPE_COERCE_ORDER);
			$ai = array_search($a->getName(), self::TYPE_COERCE_ORDER, true);
			$bi = array_search($b->getName(), self::TYPE_COERCE_ORDER, true);
			return ($ai === false ? $max : $ai) <=> ($bi === false ? $max : $bi);
		});
		return self::coerceToType($strValue, $fallback[0]);
	}
}
