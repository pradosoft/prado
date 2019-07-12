<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDatePickerPositionMode class.
 * TDatePickerPositionMode defines the positions available for the calendar popup, relative to the corresponding input.
 *
 * The following enumerable values are defined:
 * - Top: the date picker is placed above the input field
 * - Bottom: the date picker is placed below the input field
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.4
 */
class TDatePickerPositionMode extends \Prado\TEnumerable
{
	const Top = 'Top';
	const Bottom = 'Bottom';
}
