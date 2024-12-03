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
 * TDataGridPagerButtonType class.
 * TDataGridPagerButtonType defines the enumerable type for the possible types of datagrid pager buttons.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form submit buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TDataGridPagerButtonType extends \Prado\TEnumerable
{
	public const LinkButton = 'LinkButton';
	public const PushButton = 'PushButton';
}
