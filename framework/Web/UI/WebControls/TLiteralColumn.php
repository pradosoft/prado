<?php

/**
 * TLiteralColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TLiteralColumn class
 *
 * TLiteralColumn represents a static text column that is bound to a field in a data source.
 * The cells in the column will be displayed with static texts using the data indexed by
 * {@see setDataField DataField}. You can customize the display by
 * setting {@see setDataFormatString DataFormatString}.
 *
 * If {@see setDataField DataField} is not specified, the cells will be filled
 * with {@see setText Text}.
 *
 * If {@see setEncode Encode} is true, the static texts will be HTML-encoded.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.5
 */
class TLiteralColumn extends TDataGridColumn
{
	use TEncodeTrait;

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
	 * @return callable|string the format string or callable used to control how the bound data will be displayed.
	 */
	public function getDataFormatString()
	{
		return $this->getDataFormat('DataFormatString');
	}

	/**
	 * @param callable|string $value the format string or callable used to control how the bound data will be displayed.
	 */
	public function setDataFormatString($value)
	{
		$this->setDataFormat('DataFormatString', $value);
	}

	/**
	 * @return string static text to be displayed in the column. Defaults to empty.
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value static text to be displayed in the column.
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}


	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::EditItem || $itemType === TListItemType::SelectedItem) {
			if ($this->getDataField() !== '') {
				$cell->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
			} else {
				$text = $this->getText();
				$text = $this->encodeText($text);
				$cell->setText($text);
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
		$formatString = $this->getDataFormatString();
		if (($field = $this->getDataField()) !== '') {
			$value = $this->formatDataValue($formatString, $this->getDataFieldValue($data, $field));
		} else {
			$value = $this->formatDataValue($formatString, $data);
		}
		if ($sender instanceof TTableCell) {
			$value = $this->encodeText($value);
			$sender->setText($value);
		}
	}
}
