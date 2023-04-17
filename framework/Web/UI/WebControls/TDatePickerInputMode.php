<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDatePickerInputMode enum.
 * TDatePickerInputMode defines the enumerable type for the possible datepicker input methods.
 *
 * The following enumerable values are defined:
 * - TextBox: text boxes are used to input date values
 * - DropDownList: dropdown lists are used to pick up date values
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TDatePickerInputMode: string
{
	case TextBox = 'TextBox';
	case DropDownList = 'DropDownList';
}
