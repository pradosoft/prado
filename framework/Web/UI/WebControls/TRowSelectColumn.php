<?php

/**
 * TRowSelectColumn class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TRowSelectColumn class
 *
 * TRowSelectColumn is a {@see TDataGrid} column of row checkboxes for selecting
 * rows, with a select-all checkbox in the header cell.
 * ```html
 * <com:TDataGrid ID="Grid" DataKeyField="id" AutoGenerateColumns="false">
 *     <com:TRowSelectColumn ID="Select" DataTextField="name" />
 *     <com:TBoundColumn DataField="name" HeaderText="Name" />
 * </com:TDataGrid>
 * ```
 * After a postback, {@see getSelectedIndices()} returns the item indices of the
 * checked rows and {@see getSelectedKeys()} returns their data keys.
 *
 * | Cell | Content |
 * |---|---|
 * | header | select-all checkbox named {@see setSelectAllText SelectAllText}, when {@see setShowSelectAll ShowSelectAll} is true |
 * | item | row checkbox named "Select {value}" from {@see setDataTextField DataTextField}, else "Select row N" |
 *
 * The client script checks or clears every row checkbox when the select-all
 * checkbox changes, shows the select-all checkbox checked when every row is
 * checked and mixed when some are, and on a shift-click sets the rows between the
 * previously clicked row and the clicked row to the clicked row's state. Without
 * script, the row checkboxes work alone.
 *
 * The checkbox of an item is available as:
 * ```php
 * $datagridItem->SelectColumnID->CheckBox
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRowSelectColumn extends TDataGridColumn
{
	/**
	 * @var int the index of the column cells within the datagrid items, -1 before the cells are initialized.
	 */
	private $_columnIndex = -1;

	/**
	 * @return string the data field whose value names each row checkbox. Defaults to ''.
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField', '');
	}

	/**
	 * @param string $value the data field whose value names each row checkbox.
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField', $value, '');
	}

	/**
	 * @return string the accessible name of the select-all checkbox. Defaults to '', which names it "Select all rows".
	 */
	public function getSelectAllText()
	{
		return $this->getViewState('SelectAllText', '');
	}

	/**
	 * @param string $value the accessible name of the select-all checkbox.
	 */
	public function setSelectAllText($value)
	{
		$this->setViewState('SelectAllText', $value, '');
	}

	/**
	 * @return bool whether the header cell holds the select-all checkbox. Defaults to true.
	 */
	public function getShowSelectAll()
	{
		return $this->getViewState('ShowSelectAll', true);
	}

	/**
	 * @param bool $value whether the header cell holds the select-all checkbox.
	 *   When false, the header cell shows the {@see setHeaderText HeaderText}.
	 */
	public function setShowSelectAll($value)
	{
		$this->setViewState('ShowSelectAll', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Initializes a cell with a row checkbox, or a header cell with the select-all checkbox.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		$this->_columnIndex = $columnIndex;
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$checkBox = new TCheckBox();
			$cell->getControls()->add($checkBox);
			$cell->registerObject('CheckBox', $checkBox);
			$checkBox->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
		} elseif ($itemType === TListItemType::Header && $this->getShowSelectAll()) {
			$checkBox = new TCheckBox();
			$checkBox->getInputAttributes()->add('aria-label', $this->getSelectAllName());
			$cell->getControls()->add($checkBox);
			$cell->registerObject('CheckBox', $checkBox);
			$checkBox->attachEventHandler('OnPreRender', [$this, 'registerClientScript']);
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
		}
	}

	/**
	 * @return string the accessible name of the select-all checkbox.
	 */
	protected function getSelectAllName()
	{
		return ($text = $this->getSelectAllText()) !== '' ? $text : Prado::localize('Select all rows');
	}

	/**
	 * Names a row checkbox when the datagrid performs databinding.
	 * @param TCheckBox $sender the row checkbox.
	 * @param \Prado\TEventParameter $param the event parameter.
	 */
	public function dataBindColumn($sender, $param)
	{
		/** @var TDataGridItem $item */
		$item = $sender->getNamingContainer();
		if (($field = $this->getDataTextField()) !== '') {
			$name = Prado::localize('Select {0}', [(string) $this->getDataFieldValue($item->getData(), $field)]);
		} else {
			$name = Prado::localize('Select row {0}', [$item->getItemIndex() + 1]);
		}
		$sender->getInputAttributes()->add('aria-label', $name);
	}

	/**
	 * Registers the client script of the select-all checkbox.
	 * @param TCheckBox $sender the select-all checkbox.
	 * @param \Prado\TEventParameter $param the event parameter.
	 */
	public function registerClientScript($sender, $param)
	{
		$ids = [];
		foreach ($this->getRowCheckBoxes() as $checkBox) {
			$ids[] = $checkBox->getClientID();
		}
		$cs = $sender->getPage()->getClientScript();
		$cs->registerPradoScript('rowselect');
		$cs->registerPostBackControl('Prado.WebUI.TRowSelectColumn', ['ID' => $sender->getClientID(), 'CheckBoxIDs' => $ids]);
	}

	/**
	 * @return array<int, TCheckBox> the row checkboxes, indexed by item index.
	 */
	public function getRowCheckBoxes()
	{
		$checkBoxes = [];
		if ($this->_columnIndex < 0 || ($owner = $this->getOwner()) === null) {
			return $checkBoxes;
		}
		foreach ($owner->getItems() as $item) {
			$cells = $item->getCells();
			if ($cells->getCount() > $this->_columnIndex && ($checkBox = $cells->itemAt($this->_columnIndex)->getRegisteredObject('CheckBox')) instanceof TCheckBox) {
				$checkBoxes[$item->getItemIndex()] = $checkBox;
			}
		}
		return $checkBoxes;
	}

	/**
	 * @return int[] the item indices of the checked rows.
	 */
	public function getSelectedIndices()
	{
		$indices = [];
		foreach ($this->getRowCheckBoxes() as $index => $checkBox) {
			if ($checkBox->getChecked()) {
				$indices[] = $index;
			}
		}
		return $indices;
	}

	/**
	 * @return array the data keys of the checked rows, from the datagrid {@see TDataGrid::getDataKeys DataKeys}.
	 */
	public function getSelectedKeys()
	{
		$keys = [];
		$dataKeys = $this->getOwner()?->getDataKeys();
		foreach ($this->getSelectedIndices() as $index) {
			if ($dataKeys !== null && $index < $dataKeys->getCount()) {
				$keys[] = $dataKeys->itemAt($index);
			}
		}
		return $keys;
	}
}
