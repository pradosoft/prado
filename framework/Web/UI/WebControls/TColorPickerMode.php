<?php
/**
 * TColorPicker class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TColorPickerMode class.
 * TColorPickerMode defines the enumerable type for the possible UI mode
 * that a {@link TColorPicker} control can take.
 *
 * The following enumerable values are defined:
 * # Simple - Grid with 12 simple colors.
 * # Basic - Grid with the most common 70 colors. This is the default mode.
 * # Full - Full-featured color picker.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TColorPickerMode extends TEnumerable
{
	const Simple='Simple';
	const Basic='Basic';
	const Full='Full';
}
