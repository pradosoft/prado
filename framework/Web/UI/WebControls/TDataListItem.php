<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TDataListItem class
 *
 * A TDataListItem control represents an item in the {@link TDataList} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getDataItem DataItem} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItem extends TWebControl implements INamingContainer, IItemDataRenderer
{
	/**
	 * index of the data item in the Items collection of DataList
	 */
	private $_itemIndex;
	/**
	 * type of the TDataListItem
	 * @var TListItemType
	 */
	private $_itemType;
	/**
	 * value of the data associated with this item
	 * @var mixed
	 */
	private $_data;

	private $_tagName='span';

	/**
	 * Returns the tag name used for this control.
	 * @return string tag name of the control to be rendered
	 */
	protected function getTagName()
	{
		return $this->_tagName;
	}

	/**
	 * @param string tag name of the control to be rendered
	 */
	public function setTagName($value)
	{
		$this->_tagName=$value;
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableItemStyle} to be used by a datalist item.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return TListItemType item type
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'TListItemType');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of datalist
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param integer zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex=TPropertyValue::ensureInteger($value);
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
	 * @param mixed data to be associated with the item
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}

	/**
	 * This property is deprecated since v3.1.0.
	 * @return mixed data associated with the item
	 * @deprecated deprecated since v3.1.0. Use {@link getData} instead.
	 */
	public function getDataItem()
	{
		return $this->getData();
	}

	/**
	 * This property is deprecated since v3.1.0.
	 * @param mixed data to be associated with the item
	 * @deprecated deprecated since version 3.1.0. Use {@link setData} instead.
	 */
	public function setDataItem($value)
	{
		return $this->setData($value);
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
			$this->raiseBubbleEvent($this,new TDataListCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}
}