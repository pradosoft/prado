<?php

/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * TDbNullConversionMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbNullConversionMode extends \Prado\TEnumerable
{
	/**
	 * No conversion is performed for null and empty values.
	 */
	public const Preserved = 'Preserved';
	/**
	 * NULL is converted to empty string
	 */
	public const NullToEmptyString = 'NullToEmptyString';
	/**
	 * Empty string is converted to NULL
	 */
	public const EmptyStringToNull = 'EmptyStringToNull';
}
