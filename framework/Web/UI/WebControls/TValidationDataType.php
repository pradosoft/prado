<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

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
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TValidationDataType extends TEnumerable
{
	const Integer='Integer';
	const Float='Float';
	const Date='Date';
	const String='String';
}