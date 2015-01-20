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
 * TDataGridPagerButtonType class.
 * TDataGridPagerButtonType defines the enumerable type for the possible types of datagrid pager buttons.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form submit buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDataGridPagerButtonType extends TEnumerable
{
	const LinkButton='LinkButton';
	const PushButton='PushButton';
}