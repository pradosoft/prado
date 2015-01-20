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
 * TDataGridPager class.
 *
 * TDataGridPager represents a datagrid pager.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridPager extends TPanel implements INamingContainer
{
	private $_dataGrid;

	/**
	 * Constructor.
	 * @param TDataGrid datagrid object
	 */
	public function __construct($dataGrid)
	{
		$this->_dataGrid=$dataGrid;
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$this->raiseBubbleEvent($this,new TDataGridCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
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