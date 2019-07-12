<?php
/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data
 */

namespace Prado\Data;

/**
 * TDbNullConversionMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Data
 * @since 3.0
 */
class TDbNullConversionMode extends \Prado\TEnumerable
{
	/**
	 * No conversion is performed for null and empty values.
	 */
	const Preserved = 'Preserved';
	/**
	 * NULL is converted to empty string
	 */
	const NullToEmptyString = 'NullToEmptyString';
	/**
	 * Empty string is converted to NULL
	 */
	const EmptyStringToNull = 'EmptyStringToNull';
}
