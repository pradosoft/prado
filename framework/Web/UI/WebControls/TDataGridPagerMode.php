<?php
/**
 * TDataGrid related class files.
 * This file contains the definition of the following classes:
 * TDataGrid, TDataGridItem, TDataGridItemCollection, TDataGridColumnCollection,
 * TDataGridPagerStyle, TDataGridItemEventParameter,
 * TDataGridCommandEventParameter, TDataGridSortCommandEventParameter,
 * TDataGridPageChangedEventParameter
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataGridPagerMode enum.
 * TDataGridPagerMode defines the enumerable type for the possible modes that a datagrid pager can take.
 *
 * The following enumerable values are defined:
 * - NextPrev: pager buttons are displayed as next and previous pages
 * - Numeric: pager buttons are displayed as numeric page numbers
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TDataGridPagerMode: string
{
	case NextPrev = 'NextPrev';
	case Numeric = 'Numeric';
}
