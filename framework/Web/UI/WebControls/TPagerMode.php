<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPagerMode enum.
 * TPagerMode defines the enumerable type for the possible modes that a {@link TPager} control can take.
 *
 * The following enumerable values are defined:
 * - NextPrev: pager buttons are displayed as next and previous pages
 * - Numeric: pager buttons are displayed as numeric page numbers
 * - DropDownList: a dropdown list is used to select pages
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TPagerMode: string
{
	case NextPrev = 'NextPrev';
	case Numeric = 'Numeric';
	case DropDownList = 'DropDownList';
}
