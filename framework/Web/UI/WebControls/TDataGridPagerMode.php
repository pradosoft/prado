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
 * TDataGridPagerMode class.
 * TDataGridPagerMode defines the enumerable type for the possible modes that a datagrid pager can take.
 *
 * The following enumerable values are defined:
 * - NextPrev: pager buttons are displayed as next and previous pages
 * - Numeric: pager buttons are displayed as numeric page numbers
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDataGridPagerMode extends TEnumerable
{
	const NextPrev='NextPrev';
	const Numeric='Numeric';
}