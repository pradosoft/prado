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
 * TDbColumnCaseMode enum
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
enum TDbColumnCaseMode: string
{
	/**
	 * Column name cases are kept as is from the database
	 */
	case Preserved = 'Preserved';
	/**
	 * Column names are converted to lower case
	 */
	case LowerCase = 'LowerCase';
	/**
	 * Column names are converted to upper case
	 */
	case UpperCase = 'UpperCase';
}
