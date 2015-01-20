<?php
/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data
 */

/**
 * TDbNullConversionMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Data
 * @since 3.0
 */
class TDbNullConversionMode extends TEnumerable
{
	/**
	 * No conversion is performed for null and empty values.
	 */
	const Preserved='Preserved';
	/**
	 * NULL is converted to empty string
	 */
	const NullToEmptyString='NullToEmptyString';
	/**
	 * Empty string is converted to NULL
	 */
	const EmptyStringToNull='EmptyStringToNull';
}