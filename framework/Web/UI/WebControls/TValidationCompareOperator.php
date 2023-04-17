<?php
/**
 * TCompareValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TValidationCompareOperator enum.
 * TValidationCompareOperator defines the enumerable type for the comparison operations
 * that {@link TCompareValidator} can perform validation with.
 *
 * The following enumerable values are defined:
 * - Equal
 * - NotEqual
 * - GreaterThan
 * - GreaterThanEqual
 * - LessThan
 * - LessThanEqual
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TValidationCompareOperator: string
{
	case Equal = 'Equal';
	case NotEqual = 'NotEqual';
	case GreaterThan = 'GreaterThan';
	case GreaterThanEqual = 'GreaterThanEqual';
	case LessThan = 'LessThan';
	case LessThanEqual = 'LessThanEqual';
}
