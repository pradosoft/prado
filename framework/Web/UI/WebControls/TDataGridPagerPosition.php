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
 * TDataGridPagerPosition enum.
 * TDataGridPagerPosition defines the enumerable type for the possible positions that a datagrid pager can be located at.
 *
 * The following enumerable values are defined:
 * - Bottom: pager appears only at the bottom of the data grid.
 * - Top: pager appears only at the top of the data grid.
 * - TopAndBottom: pager appears on both top and bottom of the data grid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TDataGridPagerPosition: string
{
	case Bottom = 'Bottom';
	case Top = 'Top';
	case TopAndBottom = 'TopAndBottom';
}
