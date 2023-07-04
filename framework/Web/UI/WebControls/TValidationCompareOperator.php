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
 * TValidationCompareOperator class.
 * TValidationCompareOperator defines the enumerable type for the comparison operations
 * that {@see \Prado\Web\UI\WebControls\TCompareValidator} can perform validation with.
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
class TValidationCompareOperator extends \Prado\TEnumerable
{
	public const Equal = 'Equal';
	public const NotEqual = 'NotEqual';
	public const GreaterThan = 'GreaterThan';
	public const GreaterThanEqual = 'GreaterThanEqual';
	public const LessThan = 'LessThan';
	public const LessThanEqual = 'LessThanEqual';
}
