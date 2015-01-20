<?php
/**
 * TCompareValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TValidationCompareOperator class.
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
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TValidationCompareOperator extends TEnumerable
{
	const Equal='Equal';
	const NotEqual='NotEqual';
	const GreaterThan='GreaterThan';
	const GreaterThanEqual='GreaterThanEqual';
	const LessThan='LessThan';
	const LessThanEqual='LessThanEqual';
}