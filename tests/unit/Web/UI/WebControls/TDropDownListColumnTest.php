<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TDropDownListColumn;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;

class TDropDownListColumnTest extends TestCase
{
	/**
	 * Returns a list data source of colors keyed by code.
	 * @return array the list data source
	 */
	private function colors()
	{
		return [
			['code' => 'r', 'name' => 'red'],
			['code' => 'g', 'name' => 'green'],
			['code' => 'b', 'name' => 'blue'],
		];
	}

	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TDropDownListColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return TTableCell the initialized cell
	 */
	private function createCell(TDropDownListColumn $column, $data, $itemType = TListItemType::Item)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$item->setData($data);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	/**
	 * Creates, initializes and data binds a cell.
	 * @param TDropDownListColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return TTableCell the bound cell
	 */
	private function bindCell(TDropDownListColumn $column, $data, $itemType = TListItemType::Item)
	{
		$cell = $this->createCell($column, $data, $itemType);
		$cell->getParent()->dataBind();
		return $cell;
	}

	/**
	 * Creates an editable column bound to the color list.
	 * @return TDropDownListColumn the column
	 */
	private function createEditColumn()
	{
		$column = new TDropDownListColumn();
		$column->setListDataSource($this->colors());
		$column->setListValueField('code');
		$column->setListTextField('name');
		$column->setDataValueField('color');
		return $column;
	}

	/**
	 * Returns the texts of the list items.
	 * @param TDropDownList $list
	 * @return string[] the item texts
	 */
	private function itemTexts(TDropDownList $list)
	{
		$texts = [];
		foreach ($list->getItems() as $item) {
			$texts[] = $item->getText();
		}
		return $texts;
	}

	public function testDataBindEditItemsSelectIndependently()
	{
		$column = $this->createEditColumn();
		$first = $this->bindCell($column, ['color' => 'r'], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$second = $this->bindCell($column, ['color' => 'b'], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame('r', $first->getSelectedValue());
		$this->assertSame('b', $second->getSelectedValue());
		$this->assertSame(-1, PradoUnit::getProp($column, '_listControl')->getSelectedIndex());
	}

		public function testDataBindEditItemSelectsByTextField()
	{
		$column = new TDropDownListColumn();
		$column->setListDataSource(['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']);
		$column->setDataTextField('name');
		$column->setDataTextFormatString('ignored %s');
		$cell = $this->bindCell($column, ['name' => 'green'], TListItemType::EditItem);
		$this->assertSame('green', $cell->getRegisteredObject('DropDownList')->getSelectedValue());
	}

}
