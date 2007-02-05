<?php
/**
 * TDataGridItemRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

Prado::using('System.Web.UI.WebControls.TDataGrid');

/**
 * TDataGridItemRenderer class
 *
 * TDataGridItemRenderer can be used as a convenient base class to
 * define an item renderer class for {@link TDataGrid}.
 *
 * Because TDataGridItemRenderer extends from {@link TTemplateControl}, derived child classes
 * can have templates to define their presentational layout.
 *
 * TDataGridItemRenderer implements {@link IItemDataRenderer} interface,
 * which enables the following properties that are related with data-bound controls:
 * - {@link getItemIndex ItemIndex}: zero-based index of the datagrid item containing this control.
 * - {@link getItemType ItemType}: type of the datagrid item containing this control, such as TListItemType::AlternatingItem
 * - {@link getData Data}: the data row associated with the datagrid item that this control resides in

 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.1.0
 */
class TDataGridItemRenderer extends TTemplateControl implements IItemDataRenderer
{
	/**
	 * index of the data item in the Items collection of datalist
	 */
	private $_itemIndex;
	/**
	 * type of the TDataGridItem
	 * @var TListItemType
	 */
	private $_itemType;
	/**
	 * data associated with this item
	 * @var mixed
	 */
	private $_data;

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
	 * Returns a value indicating the zero-based index of the item in the corresponding data control's item collection.
	 * If the item is not in the collection (e.g. it is a header item), it returns -1.
	 * @return integer zero-based index of the item.
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
	 * @return mixed the data row associated with the datagrid item
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed the data row to be associated with the datagrid item
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}
}

?>