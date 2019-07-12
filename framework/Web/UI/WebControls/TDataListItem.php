<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TDataListItem class
 *
 * A TDataListItem control represents an item in the {@link TDataList} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getData Data} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataListItem extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\INamingContainer, IItemDataRenderer
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

	private $_tagName = 'span';

	/**
	 * Returns the tag name used for this control.
	 * @return string tag name of the control to be rendered
	 */
	protected function getTagName()
	{
		return $this->_tagName;
	}

	/**
	 * @param string $value tag name of the control to be rendered
	 */
	public function setTagName($value)
	{
		$this->_tagName = $value;
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
	 * @param TListItemType $value item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TListItemType');
	}

	/**
	 * @return int zero-based index of the item in the item collection of datalist
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param int $value zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex = TPropertyValue::ensureInteger($value);
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
			$this->raiseBubbleEvent($this, new TDataListCommandEventParameter($this, $sender, $param));
			return true;
		} else {
			return false;
		}
	}
}
