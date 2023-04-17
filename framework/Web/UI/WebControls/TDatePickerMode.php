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
 * TDatePickerMode enum.
 * TDatePickerMode defines the enumerable type for the possible UI mode
 * that a {@link TDatePicker} control can take.
 *
 * The following enumerable values are defined:
 * - Basic: Only shows a text input, focusing on the input shows the date picker
 * - Clickable: Only shows a text input, clicking on the input shows the date picker (since 3.2)
 * - Button: Shows a button next to the text input, clicking on the button shows the date, button text can be by the
 * - ImageButton: Shows an image next to the text input, clicking on the image shows the date picker,
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TDatePickerMode: string
{
	case Basic = 'Basic';
	case Clickable = 'Clickable';
	case Button = 'Button';
	case ImageButton = 'ImageButton';
}
