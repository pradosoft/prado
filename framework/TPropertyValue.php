<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TPropertyValue class
 *
 * TPropertyValue is a utility class that provides static methods
 * to convert component property values to specific types.
 *
 * TPropertyValue is commonly used in component setter methods to ensure
 * the new property value is of specific type.
 * For example, a boolean-typed property setter method would be as follows,
 * <code>
 * function setPropertyName($value) {
 *     $value=TPropertyValue::ensureBoolean($value);
 *     // $value is now of boolean type
 * }
 * </code>
 *
 * Properties can be of the following types with specific type conversion rules:
 * - string: a boolean value will be converted to 'true' or 'false'.
 * - boolean: string 'true' (case-insensitive) will be converted to true,
 *            string 'false' (case-insensitive) will be converted to false.
 * - integer
 * - float
 * - array: string starting with '(' and ending with ')' will be considered as
 *          as an array expression and will be evaluated. Otherwise, an array
 *          with the value to be ensured is returned.
 * - object
 * - enum: enumerable type, represented by an array of strings.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TPropertyValue
{
	/**
	 * Converts a value to boolean type.
	 * Note, string 'true' (case-insensitive) will be converted to true,
	 * string 'false' (case-insensitive) will be converted to false.
	 * If a string represents a non-zero number, it will be treated as true.
	 * @param mixed $value the value to be converted.
	 * @return bool
	 */
	public static function ensureBoolean($value): bool
	{
		if (is_string($value)) {
			return strcasecmp($value, 'true') == 0 || (is_numeric($value) && $value != 0);
		} else {
			return (bool) $value;
		}
	}

	/**
	 * Converts a value to string type.
	 * Note, a boolean value will be converted to 'true' if it is true
	 * and 'false' if it is false.
	 * @param mixed $value the value to be converted.
	 * @return string
	 */
	public static function ensureString($value): string
	{
		if (TJavaScript::isJsLiteral($value)) {
			return $value;
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
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
	 * Converts a value to array type. If the value is a string and it is
	 * in the form (a,b,c) then an array consisting of each of the elements
	 * will be returned. If the value is a string and it is not in this form
	 * then an array consisting of just the string will be returned. If the value
	 * is not a string then
	 * @param mixed $value the value to be converted.
	 * @return array
	 */
	public static function ensureArray($value): array
	{
		if (is_string($value)) {
			$value = trim($value);
			$len = strlen($value);
			if ($len >= 2 && $value[0] == '(' && $value[$len - 1] == ')') {
				return eval('return array' . $value . ';');
			} else {
				return $len > 0 ? [$value] : [];
			}
		} else {
			return (array) $value;
		}
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
	 * This method checks if the value is of the specified enumerable type.
	 * A value is a valid enumerable value if it is equal to the name of a constant
	 * in the specified enumerable type (class).
	 * For more details about enumerable, see {@link TEnumerable}.
	 *
	 * For backward compatibility, this method also supports sanity
	 * check of a string value to see if it is among the given list of strings.
	 * @param mixed $value the value to be converted.
	 * @param mixed $enums class name of the enumerable type, or array of valid enumeration values. If this is not an array,
	 * the method considers its parameters are of variable length, and the second till the last parameters are enumeration values.
	 * @throws TInvalidDataValueException if the original value is not in the string array.
	 * @return string the valid enumeration value
	 */
	public static function ensureEnum($value, $enums): string
	{
		static $types = [];
		if (func_num_args() === 2 && is_string($enums)) {
			if (!isset($types[$enums])) {
				$types[$enums] = new \ReflectionClass($enums);
			}
			if ($types[$enums]->hasConstant($value)) {
				return $value;
			} else {
				throw new TInvalidDataValueException(
					'propertyvalue_enumvalue_invalid',
					$value,
					implode(' | ', $types[$enums]->getConstants())
				);
			}
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
	public static function ensureNullIfEmpty($value)
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
	 * they are assumed be be bound [0...255], inclusive.
	 * @param array|float|int|string $value String Web Color name or Hex Color (eg. '#336699'),
	 *   array of [$r, $g, $b] or ['red' => $red, 'green' => $green, 'blue' = $blue], or
	 *   int color (0x00RRGGBB [$blue is null]), or int red [0..255] when $blue is not null.
	 * @param bool|float|int $green When $blue !== null, $green is an int color, otherwise its
	 *   the flag to allow converting Web Color names to their web colors. Default true,
	 *	 for allow web colors to translate into their # hex color.
	 * @param null|float|int $blue The blue color. Default null for (A) or (B)
	 * @return string The valid # hex color.
	 */
	public static function ensureHexColor($value, $green = true, $blue = null)
	{
		if (is_array($value)) {
			$blue = array_key_exists('blue', $value) ? $value['blue'] : (array_key_exists(2, $value) ? $value[2] : null);
			$green = array_key_exists('green', $value) ? $value['green'] : (array_key_exists(1, $value) ? $value[1] : true);
			$value = array_key_exists('red', $value) ? $value['red'] : (array_key_exists(0, $value) ? $value[0] : null);
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
			static $colors;
			if (!$colors) {
				$reflect = new \ReflectionClass(\Prado\Web\UI\TWebColors::class);
				$colors = $reflect->getConstants();
				$colors = array_change_key_case($colors);
			}
			if (array_key_exists($lvalue = strtolower($value), $colors)) {
				$value = $colors[$lvalue];
				$len = strlen($value);
			}
		}
		if ($len == 0 || $value[0] !== '#' || ($len !== 4 && $len !== 7) || !preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $value)) {
			throw new TInvalidDataValueException('propertyvalue_invalid_hex_color', $value);
		}
		if ($len === 4) {
			$value = preg_replace('/^#(.)(.)(.)$/', '#${1}${1}${2}${2}${3}${3}', $value);
		}
		return strtoupper($value);
	}
}
