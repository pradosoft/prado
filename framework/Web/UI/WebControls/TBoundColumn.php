<?php
/**
 * TBoundColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TBoundColumn class
 *
 * TBoundColumn represents a column that is bound to a field in a data source.
 * The cells in the column will be displayed using the data indexed by
 * {@link setDataField DataField}. You can customize the display by
 * setting {@link setDataFormatString DataFormatString}.
 *
 * If {@link setReadOnly ReadOnly} is false, TBoundColumn will display cells in edit mode
 * with textboxes. Otherwise, a static text is displayed.
 *
 * When a datagrid row is in edit mode, the textbox control in the TBoundColumn
 * can be accessed by one of the following two methods:
 * <code>
 * $datagridItem->BoundColumnID->TextBox
 * $datagridItem->BoundColumnID->Controls[0]
 * </code>
 * The second method is possible because the textbox control created within the
 * datagrid cell is the first child.
 *
 * Since v3.1.0, TBoundColumn has introduced two new properties {@link setItemRenderer ItemRenderer}
 * and {@link setEditItemRenderer EditItemRenderer} which can be used to specify
 * the layout of the datagrid cells in browsing and editing mode.
 * A renderer refers to a control class that is to be instantiated as a control.
 * For more details, see {@link TRepeater} and {@link TDataList}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TBoundColumn extends TDataGridColumn
{
	/**
	 * @return string the class name for the item cell renderer. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getItemRenderer()
	{
		return $this->getViewState('ItemRenderer', '');
	}

	/**
	 * Sets the item cell renderer class.
	 *
	 * If not empty, the class will be used to instantiate as a child control in the item cells of the column.
	 *
	 * If the class implements {@link \Prado\IDataRenderer}, the <b>Data</b> property
	 * will be set as the data associated with the datagrid cell during databinding.
	 * The data can be either the whole data row or a field of the row if
	 * {@link getDataField DataField} is not empty. If {@link getDataFormatString DataFormatString}
	 * is not empty, the data will be formatted first before passing to the renderer.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @since 3.1.0
	 */
	public function setItemRenderer($value)
	{
		$this->setViewState('ItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for the edit item cell renderer. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getEditItemRenderer()
	{
		return $this->getViewState('EditItemRenderer', '');
	}

	/**
	 * Sets the edit item cell renderer class.
	 *
	 * If not empty, the class will be used to instantiate as a child control in the item cell that is in edit mode.
	 *
	 * If the class implements {@link \Prado\IDataRenderer}, the <b>Data</b> property
	 * will be set as the data associated with the datagrid cell during databinding.
	 * The data can be either the whole data row or a field of the row if
	 * {@link getDataField DataField} is not empty. If {@link getDataFormatString DataFormatString}
	 * is not empty, the data will be formatted first before passing to the renderer.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @since 3.1.0
	 */
	public function setEditItemRenderer($value)
	{
		$this->setViewState('EditItemRenderer', $value, '');
	}

	/**
	 * @return string the field name from the data source to bind to the column
	 */
	public function getDataField()
	{
		return $this->getViewState('DataField', '');
	}

	/**
	 * @param string $value the field name from the data source to bind to the column
	 */
	public function setDataField($value)
	{
		$this->setViewState('DataField', $value, '');
	}

	/**
	 * @return string the formatting string used to control how the bound data will be displayed.
	 */
	public function getDataFormatString()
	{
		return $this->getViewState('DataFormatString', '');
	}

	/**
	 * @param string $value the formatting string used to control how the bound data will be displayed.
	 */
	public function setDataFormatString($value)
	{
		$this->setViewState('DataFormatString', $value, '');
	}

	/**
	 * @return bool whether the items in the column can be edited. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly', false);
	}

	/**
	 * @param bool $value whether the items in the column can be edited
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a textbox for item in edit mode and the column is not read-only.
	 * Otherwise it displays a static text.
	 * The caption of the button and the static text are retrieved
	 * from the datasource.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		$item = $cell->getParent();
		switch ($itemType) {
			case TListItemType::Item:
			case TListItemType::AlternatingItem:
			case TListItemType::SelectedItem:
				if (($classPath = $this->getItemRenderer()) !== '') {
					$control = $this->initializeCellRendererControl($cell, $classPath);
				} else {
					$control = $cell;
				}
				$control->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
				break;
			case TListItemType::EditItem:
				if (!$this->getReadOnly()) {
					if (($classPath = $this->getEditItemRenderer()) !== '') {
						$control = $this->initializeCellRendererControl($cell, $classPath);
						$cell->registerObject('EditControl', $control);
					} else {
						$control = new TTextBox;
						$cell->getControls()->add($control);
						$cell->registerObject('TextBox', $control);
					}
				} else {
					if (($classPath = $this->getItemRenderer()) !== '') {
						$control = $this->initializeCellRendererControl($cell, $classPath);
					} else {
						$control = $cell;
					}
				}
				$control->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
				break;
			default:
				parent::initializeCell($cell, $columnIndex, $itemType);
				break;
		}
	}

	/**
	 * Databinds a cell in the column.
	 * This method is invoked when datagrid performs databinding.
	 * It populates the content of the cell with the relevant data from data source.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function dataBindColumn($sender, $param)
	{
		$item = $sender->getNamingContainer();
		$data = $item->getData();
		$formatString = $this->getDataFormatString();
		if (($field = $this->getDataField()) !== '') {
			$value = $this->formatDataValue($formatString, $this->getDataFieldValue($data, $field));
		} else {
			$value = $this->formatDataValue($formatString, $data);
		}
		$sender->setData($value);
	}
}
