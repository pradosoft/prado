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
 * TDataGridItemEventParameter class
 *
 * TDataGridItemEventParameter encapsulates the parameter data for
 * {@see TDataGrid::onItemCreated OnItemCreated} event of {@see TDataGrid} controls.
 * The {@see getItem Item} property indicates the datagrid item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataGridItemEventParameter extends \Prado\TEventParameter
{
	/**
	 * The TDataGridItem control responsible for the event.
	 * @var TDataGridItem
	 */
	private $_item;

	/**
	 * Constructor.
	 * @param TDataGridItem $item datagrid item related with the corresponding event
	 */
	public function __construct(TDataGridItem $item)
	{
		$this->_item = $item;
		parent::__construct();
	}

	/**
	 * @return TDataGridItem datagrid item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}
