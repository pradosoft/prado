<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPagerMode class.
 * TPagerMode defines the enumerable type for the possible modes that a {@link TPager} control can take.
 *
 * The following enumerable values are defined:
 * - NextPrev: pager buttons are displayed as next and previous pages
 * - Numeric: pager buttons are displayed as numeric page numbers
 * - DropDownList: a dropdown list is used to select pages
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TPagerMode extends \Prado\TEnumerable
{
	const NextPrev = 'NextPrev';
	const Numeric = 'Numeric';
	const DropDownList = 'DropDownList';
}
