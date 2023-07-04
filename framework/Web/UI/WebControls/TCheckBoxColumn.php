<?php
/**
 * TCheckBoxColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TCheckBoxColumn class
 *
 * TCheckBoxColumn represents a checkbox column that is bound to a field in a data source.
 * The checked state of the checkboxes are determiend by the bound data at
 * {@see setDataField DataField}. If {@see setReadOnly ReadOnly} is false,
 * TCheckBoxColumn will display an enabled checkbox provided the cells are
 * in edit mode. Otherwise, the checkboxes will be disabled to prevent from editting.
 *
 * The checkbox control in the TCheckBoxColumn can be accessed by one of
 * the following two methods:
 * ```php
 * $datagridItem->CheckBoxColumnID->CheckBox
 * $datagridItem->CheckBoxColumnID->Controls[0]
 * ```
 * The second method is possible because the checkbox control created within the
 * datagrid cell is the first child.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TCheckBoxColumn extends TDataGridColumn
{
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
	 * It creates a checkbox inside the cell.
	 * If the column is read-only or if the item is not in edit mode,
	 * the checkbox will be set disabled.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$checkBox = new TCheckBox();
			if ($this->getReadOnly() || $itemType !== TListItemType::EditItem) {
				$checkBox->setEnabled(false);
			}
			$cell->setHorizontalAlign('Center');
			$cell->getControls()->add($checkBox);
			$cell->registerObject('CheckBox', $checkBox);
			if ($this->getDataField() !== '') {
				$checkBox->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
			}
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
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
		if (($field = $this->getDataField()) !== '') {
			$value = TPropertyValue::ensureBoolean($this->getDataFieldValue($data, $field));
		} else {
			$value = TPropertyValue::ensureBoolean($data);
		}
		if ($sender instanceof TCheckBox) {
			$sender->setChecked($value);
		}
	}
}
