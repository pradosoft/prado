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
 * TDbNullConversionMode enum
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
enum TDbNullConversionMode: string
{
	/**
	 * No conversion is performed for null and empty values.
	 */
	case Preserved = 'Preserved';
	/**
	 * NULL is converted to empty string
	 */
	case NullToEmptyString = 'NullToEmptyString';
	/**
	 * Empty string is converted to NULL
	 */
	case EmptyStringToNull = 'EmptyStringToNull';
}
