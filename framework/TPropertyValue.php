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
 * @package Prado
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
 * @package Prado
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
	public static function ensureBoolean($value)
	{
		if (is_string($value)) {
			return strcasecmp($value, 'true') == 0 || (is_numeric($value) && $value != 0);
		} else {
			return (boolean) $value;
		}
	}

	/**
	 * Converts a value to string type.
	 * Note, a boolean value will be converted to 'true' if it is true
	 * and 'false' if it is false.
	 * @param mixed $value the value to be converted.
	 * @return string
	 */
	public static function ensureString($value)
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
	public static function ensureInteger($value)
	{
		return (integer) $value;
	}

	/**
	 * Converts a value to float type.
	 * @param mixed $value the value to be converted.
	 * @return float
	 */
	public static function ensureFloat($value)
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
	public static function ensureArray($value)
	{
		if (is_string($value)) {
			$value = trim($value);
			$len = strlen($value);
			if ($len >= 2 && $value[0] == '(' && $value[$len - 1] == ')') {
				eval('$array=array' . $value . ';');
				return $array;
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
	public static function ensureObject($value)
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
	public static function ensureEnum($value, $enums)
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
}
