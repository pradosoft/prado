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
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TDataGridItem class
 *
 * A TDataGridItem control represents an item in the {@link TDataGrid} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getData Data} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property. Property {@link getDataSourceIndex DataSourceIndex}
 * gives the index of the item from the bound data source.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataGridItem extends TTableRow implements \Prado\Web\UI\INamingContainer
{
	/**
	 * @var int index of the data item in the Items collection of datagrid
	 */
	private $_itemIndex = '';
	/**
	 * @var int index of the item from the bound data source
	 */
	private $_dataSourceIndex = 0;
	/**
	 * type of the TDataGridItem
	 * @var string
	 */
	private $_itemType = '';
	/**
	 * value of the data item
	 * @var mixed
	 */
	private $_data;

	/**
	 * Constructor.
	 * @param int $itemIndex zero-based index of the item in the item collection of datagrid
	 * @param int $dataSourceIndex
	 * @param TListItemType $itemType item type
	 */
	public function __construct($itemIndex, $dataSourceIndex, $itemType)
	{
		$this->_itemIndex = $itemIndex;
		$this->_dataSourceIndex = $dataSourceIndex;
		$this->setItemType($itemType);
		if ($itemType === TListItemType::Header) {
			$this->setTableSection(TTableRowSection::Header);
		} elseif ($itemType === TListItemType::Footer) {
			$this->setTableSection(TTableRowSection::Footer);
		}
	}

	/**
	 * @return TListItemType item type.
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType $value item type
	 */
	public function setItemType($value)
	{
		$this->_itemType = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TListItemType');
	}

	/**
	 * @return int zero-based index of the item in the item collection of datagrid
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * @return int the index of the datagrid item from the bound data source
	 */
	public function getDataSourceIndex()
	{
		return $this->_dataSourceIndex;
	}

	/**
	 * @return mixed data associated with the item
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed $value data to be associated with the item
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->_data = $value;
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl $sender the sender of the event
	 * @param TEventParameter $param event parameter
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
}
