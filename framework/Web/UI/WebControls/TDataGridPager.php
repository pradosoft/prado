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
 * TDataGridPager class.
 *
 * TDataGridPager represents a datagrid pager.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataGridPager extends TPanel implements \Prado\Web\UI\INamingContainer
{
	private $_dataGrid;

	/**
	 * Constructor.
	 * @param TDataGrid $dataGrid datagrid object
	 */
	public function __construct($dataGrid)
	{
		$this->_dataGrid = $dataGrid;
		parent::__construct();
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param \Prado\Web\UI\TControl $sender the sender of the event
	 * @param \Prado\TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof \Prado\Web\UI\TCommandEventParameter) {
			$this->raiseBubbleEvent($this, new TDataGridCommandEventParameter($this, $sender, $param));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return TDataGrid the datagrid owning this pager
	 */
	public function getDataGrid()
	{
		return $this->_dataGrid;
	}

	/**
	 * @return string item type.
	 */
	public function getItemType()
	{
		return TListItemType::Pager;
	}
}
