<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TDatePickerInputMode class.
 * TDatePickerInputMode defines the enumerable type for the possible datepicker input methods.
 *
 * The following enumerable values are defined:
 * - TextBox: text boxes are used to input date values
 * - DropDownList: dropdown lists are used to pick up date values
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDatePickerInputMode extends TEnumerable
{
	const TextBox='TextBox';
	const DropDownList='DropDownList';
}