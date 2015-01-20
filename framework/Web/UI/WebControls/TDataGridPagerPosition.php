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
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TDataGridPagerPosition class.
 * TDataGridPagerPosition defines the enumerable type for the possible positions that a datagrid pager can be located at.
 *
 * The following enumerable values are defined:
 * - Bottom: pager appears only at the bottom of the data grid.
 * - Top: pager appears only at the top of the data grid.
 * - TopAndBottom: pager appears on both top and bottom of the data grid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDataGridPagerPosition extends TEnumerable
{
	const Bottom='Bottom';
	const Top='Top';
	const TopAndBottom='TopAndBottom';
}