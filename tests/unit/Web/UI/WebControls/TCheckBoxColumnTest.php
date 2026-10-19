<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TComponent;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TCheckBoxColumn;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;

class TCheckBoxColumnTestData extends TComponent
{
	public function getActive()
	{
		return 'yes';
	}
}

class TCheckBoxColumnTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TCheckBoxColumn $column
	 * @param string $itemType
	 * @param mixed $data
	 * @return TTableCell the initialized cell
	 */
	private function createCell($column, $itemType, $data = null)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$item->setData($data);
		$cell = $itemType === TListItemType::Header ? new TTableHeaderCell() : new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	/**
	 * Creates, initializes and data binds a cell, returning its check box.
	 * @param TCheckBoxColumn $column
	 * @param string $itemType
	 * @param mixed $data
	 * @return TCheckBox the check box of the cell
	 */
	private function bindCheckBox($column, $itemType, $data)
	{
		$cell = $this->createCell($column, $itemType, $data);
		$cell->dataBind();
		return $cell->getRegisteredObject('CheckBox');
	}

	public function testConstruct()
	{
		$column = new TCheckBoxColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
	}

	public function testDataField()
	{
		$column = new TCheckBoxColumn();
		$this->assertSame('', $column->getDataField());
		$column->setDataField('active');
		$this->assertSame('active', $column->getDataField());
		$this->assertSame('active', $column->saveState()['DataField']);
		$column->setDataField('');
		$this->assertSame('', $column->getDataField());
		$this->assertArrayNotHasKey('DataField', $column->saveState());
	}

	public function testReadOnly()
	{
		$column = new TCheckBoxColumn();
		$this->assertFalse($column->getReadOnly());
		$column->setReadOnly('true');
		$this->assertTrue($column->getReadOnly());
		$this->assertTrue($column->saveState()['ReadOnly']);
		$column->setReadOnly(false);
		$this->assertFalse($column->getReadOnly());
		$this->assertArrayNotHasKey('ReadOnly', $column->saveState());
	}

	public function testItemCellsHaveDisabledCheckBox()
	{
		$column = new TCheckBoxColumn();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$checkBox = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(TCheckBox::class, $checkBox, $itemType);
			$this->assertSame($checkBox, $cell->getRegisteredObject('CheckBox'), $itemType);
			$this->assertSame($checkBox, $cell->CheckBox, $itemType);
			$this->assertFalse($checkBox->getEnabled(false), $itemType);
			$this->assertSame('Center', $cell->getHorizontalAlign(), $itemType);
			$this->assertSame(1, $cell->getControls()->getCount(), $itemType);
		}
	}

	public function testEditItemCheckBoxIsEnabled()
	{
		$column = new TCheckBoxColumn();
		$cell = $this->createCell($column, TListItemType::EditItem);
		$checkBox = $cell->getRegisteredObject('CheckBox');
		$this->assertInstanceOf(TCheckBox::class, $checkBox);
		$this->assertTrue($checkBox->getEnabled(false));
		$this->assertSame('Center', $cell->getHorizontalAlign());
	}

	public function testReadOnlyEditItemCheckBoxIsDisabled()
	{
		$column = new TCheckBoxColumn();
		$column->setReadOnly(true);
		$checkBox = $this->createCell($column, TListItemType::EditItem)->getRegisteredObject('CheckBox');
		$this->assertFalse($checkBox->getEnabled(false));
	}

	public function testNoDataFieldDoesNotBind()
	{
		$column = new TCheckBoxColumn();
		$checkBox = $this->bindCheckBox($column, TListItemType::Item, true);
		$this->assertFalse($checkBox->getChecked());
	}

	public function testDataFieldBindsCheckedState()
	{
		$column = new TCheckBoxColumn();
		$column->setDataField('active');
		$this->assertTrue($this->bindCheckBox($column, TListItemType::Item, ['active' => true])->getChecked());
		$this->assertTrue($this->bindCheckBox($column, TListItemType::AlternatingItem, ['active' => 'true'])->getChecked());
		$this->assertTrue($this->bindCheckBox($column, TListItemType::EditItem, ['active' => 1])->getChecked());
		$this->assertFalse($this->bindCheckBox($column, TListItemType::SelectedItem, ['active' => false])->getChecked());
		$this->assertFalse($this->bindCheckBox($column, TListItemType::Item, ['active' => 'false'])->getChecked());
		$this->assertFalse($this->bindCheckBox($column, TListItemType::Item, ['active' => 0])->getChecked());
	}

	public function testDataFieldFromComponent()
	{
		$column = new TCheckBoxColumn();
		$column->setDataField('Active');
		$this->assertFalse($this->bindCheckBox($column, TListItemType::Item, new TCheckBoxColumnTestData())->getChecked());
	}

	public function testDataFieldMissingThrows()
	{
		$column = new TCheckBoxColumn();
		$column->setDataField('active');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindCheckBox($column, TListItemType::Item, 'scalar');
	}

	public function testDataBindColumnWithoutDataField()
	{
		$column = new TCheckBoxColumn();
		$cell = $this->createCell($column, TListItemType::Item, 'true');
		$checkBox = $cell->getRegisteredObject('CheckBox');
		$column->dataBindColumn($checkBox, null);
		$this->assertTrue($checkBox->getChecked());

		$cell = $this->createCell($column, TListItemType::Item, 0);
		$checkBox = $cell->getRegisteredObject('CheckBox');
		$checkBox->setChecked(true);
		$column->dataBindColumn($checkBox, null);
		$this->assertFalse($checkBox->getChecked());
	}

	public function testDataBindColumnIgnoresNonCheckBoxSender()
	{
		$column = new TCheckBoxColumn();
		$column->setDataField('active');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['active' => true]);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$label = new TLabel();
		$cell->getControls()->add($label);
		$column->dataBindColumn($label, null);
		$this->assertSame('', $label->getText());
	}

	public function testHeaderAndFooterUseParent()
	{
		$column = new TCheckBoxColumn();
		$column->setDataField('active');
		$column->setHeaderText('Active');
		$column->setFooterText('End');
		$header = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Active', $header->getText());
		$this->assertFalse($header->getHasControls());
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
		$separator = $this->createCell($column, TListItemType::Separator);
		$this->assertSame('', $separator->getText());
		$this->assertFalse($separator->getHasControls());
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->setEditItemIndex(1);
		$column = new TCheckBoxColumn();
		$column->setDataField('active');
		$column->setHeaderText('Active');
		$grid->getColumns()->add($column);
		$grid->setDataSource([['active' => true], ['active' => false], ['active' => 'TRUE']]);
		$grid->dataBind();

		$items = $grid->getItems();
		$this->assertSame('Active', $grid->getHeader()->getCells()->itemAt(0)->getText());
		$first = $items->itemAt(0)->getCells()->itemAt(0)->CheckBox;
		$this->assertTrue($first->getChecked());
		$this->assertFalse($first->getEnabled(false));
		$edit = $items->itemAt(1)->getCells()->itemAt(0)->CheckBox;
		$this->assertFalse($edit->getChecked());
		$this->assertTrue($edit->getEnabled(false));
		$this->assertTrue($items->itemAt(2)->getCells()->itemAt(0)->CheckBox->getChecked());
	}
}
