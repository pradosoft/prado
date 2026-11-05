<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTextBox;

class TBoundColumnTestFormatter
{
	public function format($value)
	{
		return '<' . $value . '>';
	}
}

class TBoundColumnTest extends TestCase
{
	private function createCell($column, $itemType, $data = null)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$item->setData($data);
		$cell = $itemType === TListItemType::Header ? new TTableHeaderCell() : new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	private function bindCell($column, $itemType, $data)
	{
		$cell = $this->createCell($column, $itemType, $data);
		$cell->dataBind();
		return $cell;
	}

	public function testStringProperties()
	{
		$column = new TBoundColumn();
		foreach (['DataField', 'ItemRenderer', 'EditItemRenderer', 'DataFormatString'] as $property) {
			$getter = 'get' . $property;
			$setter = 'set' . $property;
			$this->assertSame('', $column->$getter(), $property);
			$column->$setter('value-' . $property);
			$this->assertSame('value-' . $property, $column->$getter(), $property);
			$this->assertSame('value-' . $property, $column->saveState()[$property]);
			$column->$setter('');
			$this->assertSame('', $column->$getter(), $property);
			$this->assertArrayNotHasKey($property, $column->saveState());
		}
	}

	public function testCallableDataFormatString()
	{
		$column = new TBoundColumn();
		$column->setDataFormatString('%s!');
		$formatter = fn ($value) => strtoupper($value);
		$column->setDataFormatString($formatter);
		$this->assertSame($formatter, $column->getDataFormatString());
		$this->assertArrayNotHasKey('DataFormatString', $column->saveState());
		$column->setDataFormatString('%s?');
		$this->assertSame('%s?', $column->getDataFormatString());
	}

	public function testReadOnly()
	{
		$column = new TBoundColumn();
		$this->assertFalse($column->getReadOnly());
		$column->setReadOnly('true');
		$this->assertTrue($column->getReadOnly());
		$this->assertTrue($column->saveState()['ReadOnly']);
		$column->setReadOnly(false);
		$this->assertFalse($column->getReadOnly());
		$this->assertArrayNotHasKey('ReadOnly', $column->saveState());
	}

	public function testItemCellsBindDataField()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType, ['name' => 'Alice']);
			$this->assertSame('', $cell->getText(), $itemType);
			$cell->dataBind();
			$this->assertSame('Alice', $cell->getText(), $itemType);
			$this->assertFalse($cell->getHasControls(), $itemType);
		}
	}

	public function testDataBindWithoutDataField()
	{
		$column = new TBoundColumn();
		$this->assertSame('42', $this->bindCell($column, TListItemType::Item, 42)->getText());
	}

	public function testDataBindColumnDirectly()
	{
		$column = new TBoundColumn();
		$column->setDataField('id');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['id' => 5]);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->dataBindColumn($cell, null);
		$this->assertSame('5', $cell->getText());
	}

	public function testDataBindMissingDataThrows()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindCell($column, TListItemType::Item, 'scalar');
	}

	public function testDataFormatString()
	{
		$column = new TBoundColumn();
		$column->setDataField('price');

		$column->setDataFormatString('$%.2f');
		$this->assertSame('$3.50', $this->bindCell($column, TListItemType::Item, ['price' => 3.5])->getText());

		$column->setDataFormatString('#{0} * 2');
		$this->assertSame(7.0, $this->bindCell($column, TListItemType::Item, ['price' => 3.5])->getText());

		$column->setDataFormatString(fn ($value) => 'Price ' . $value);
		$this->assertSame('Price 3.5', $this->bindCell($column, TListItemType::Item, ['price' => 3.5])->getText());

		$column->setDataFormatString([new TBoundColumnTestFormatter(), 'format']);
		$this->assertSame('<3.5>', $this->bindCell($column, TListItemType::Item, ['price' => 3.5])->getText());
	}

	public function testDataFormatStringWithoutDataField()
	{
		$column = new TBoundColumn();
		$column->setDataFormatString('Row %s');
		$this->assertSame('Row 9', $this->bindCell($column, TListItemType::Item, 9)->getText());
	}

	public function testInvalidExpressionFormat()
	{
		$column = new TBoundColumn();
		$column->setDataFormatString('#{0} +');
		try {
			$this->bindCell($column, TListItemType::Item, 1);
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('datagridcolumn_expression_invalid', $e->getErrorCode());
		}
	}

	public function testValueIsNotEncoded()
	{
		$column = new TBoundColumn();
		$column->setDataField('html');
		$this->assertSame('<b>x</b>', $this->bindCell($column, TListItemType::Item, ['html' => '<b>x</b>'])->getText());
	}

	public function testItemRenderer()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setDataFormatString('[%s]');
		$column->setItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Item, ['name' => 'Bob']);
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('', $label->getText());
		$cell->dataBind();
		$this->assertSame('[Bob]', $label->getText());
		$this->assertSame('', $cell->getText());
	}

	public function testEditItemCreatesTextBox()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$cell = $this->createCell($column, TListItemType::EditItem, ['name' => 'Carol']);
		$textBox = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TTextBox::class, $textBox);
		$this->assertSame($textBox, $cell->TextBox);
		$cell->dataBind();
		$this->assertSame('Carol', $textBox->getText());
		$this->assertSame('', $cell->getText());
	}

	public function testEditItemRenderer()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setEditItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::EditItem, ['name' => 'Dan']);
		$control = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $control);
		$this->assertSame($control, $cell->EditControl);
		$cell->dataBind();
		$this->assertSame('Dan', $control->getText());
	}

	public function testReadOnlyEditItem()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setReadOnly(true);
		$column->setEditItemRenderer(TTextBox::class);
		$cell = $this->bindCell($column, TListItemType::EditItem, ['name' => 'Eve']);
		$this->assertFalse($cell->getHasControls());
		$this->assertSame('Eve', $cell->getText());
	}

	public function testReadOnlyEditItemWithItemRenderer()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setReadOnly(true);
		$column->setItemRenderer(TLabel::class);
		$cell = $this->bindCell($column, TListItemType::EditItem, ['name' => 'Fay']);
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('Fay', $label->getText());
	}

	public function testHeaderAndFooterUseParent()
	{
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setHeaderText('Name');
		$column->setFooterText('End');
		$this->assertSame('Name', $this->createCell($column, TListItemType::Header)->getText());
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
		$this->assertSame('', $this->createCell($column, TListItemType::Separator)->getText());
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->setEditItemIndex(2);
		$column = new TBoundColumn();
		$column->setDataField('name');
		$column->setHeaderText('Name');
		$column->setDataFormatString(fn ($value) => ucfirst($value));
		$grid->getColumns()->add($column);
		$grid->setDataSource([['name' => 'ann'], ['name' => 'ben'], ['name' => 'cid']]);
		$grid->dataBind();

		$items = $grid->getItems();
		$this->assertSame('Name', $grid->getHeader()->getCells()->itemAt(0)->getText());
		$this->assertSame('Ann', $items->itemAt(0)->getCells()->itemAt(0)->getText());
		$this->assertSame('Ben', $items->itemAt(1)->getCells()->itemAt(0)->getText());
		$this->assertSame(TListItemType::AlternatingItem, $items->itemAt(1)->getItemType());
		$editCell = $items->itemAt(2)->getCells()->itemAt(0);
		$this->assertSame('Cid', $editCell->TextBox->getText());
	}
}
