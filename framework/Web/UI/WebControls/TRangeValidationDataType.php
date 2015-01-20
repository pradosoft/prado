<?php
/**
 * TRangeValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TRangeValidationDataType class.
 * TRangeValidationDataType defines the enumerable type for the possible data types that
 * a range validator can validate upon.
 *
 * The following enumerable values are defined:
 * - Integer
 * - Float
 * - Date
 * - String
 * - StringLength
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TRangeValidationDataType extends TValidationDataType
{
	const StringLength='StringLength';
}