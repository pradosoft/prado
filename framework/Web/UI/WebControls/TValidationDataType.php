<?php

/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Util\TSimpleDateFormatter;

/**
 * TValidationDataType class.
 * TValidationDataType defines the enumerable type for the possible data types that
 * a comparison validator can validate upon.
 *
 * The following enumerable values are defined:
 * - Integer
 * - Float
 * - Date
 * - String
 *
 * {@see convert()} converts a value to a data type with the rules of the client
 * validators, and returns null for a value that does not convert.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TValidationDataType extends \Prado\TEnumerable
{
	public const Integer = 'Integer';
	public const Float = 'Float';
	public const Date = 'Date';
	public const String = 'String';

	/**
	 * Converts a value to a data type with the rules of the client validators.
	 * | Data type | Accepted value | Result |
	 * |---|---|---|
	 * | `Integer` | optional sign and digits | int |
	 * | `Float` | optional sign, digits, and a `.` fraction, without an exponent | float |
	 * | `Date` | an int or float timestamp, as from {@see TDatePicker} | int timestamp |
	 * | `Date` with a date format | a date that {@see TSimpleDateFormatter} parses | int timestamp |
	 * | `Date` without a date format | a date that `strtotime()` parses, before 1970 included | int timestamp |
	 * | `String` or another type | any value | string |
	 * Surrounding whitespace is allowed for `Integer` and `Float`.
	 * @param string $dataType the data type.
	 * @param float|int|string $value the value to convert.
	 * @param string $dateFormat the date format for `Date`; empty for `strtotime()`.
	 * @return null|float|int|string the converted value, or null when the value does not convert.
	 * @since 4.4.0
	 */
	public static function convert(string $dataType, int|float|string $value, string $dateFormat = ''): int|float|string|null
	{
		switch ($dataType) {
			case self::Integer:
				return preg_match('/^\s*[-+]?\d+\s*$/', (string) $value) ? (int) trim((string) $value) : null;
			case self::Float:
				return preg_match('/^\s*[-+]?(\d+(\.\d+)?|\.\d+)\s*$/', (string) $value) ? (float) trim((string) $value) : null;
			case self::Date:
				if (!is_string($value)) {
					return (int) $value;
				}
				if ($dateFormat !== '') {
					return (new TSimpleDateFormatter($dateFormat))->parse($value, false);
				}
				return ($time = strtotime($value)) === false ? null : $time;
		}
		return (string) $value;
	}
}
