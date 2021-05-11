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
 * TDbColumnCaseMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Data
 * @since 3.0
 */
class TDbColumnCaseMode extends \Prado\TEnumerable
{
	/**
	 * Column name cases are kept as is from the database
	 */
	public const Preserved = 'Preserved';
	/**
	 * Column names are converted to lower case
	 */
	public const LowerCase = 'LowerCase';
	/**
	 * Column names are converted to upper case
	 */
	public const UpperCase = 'UpperCase';
}
