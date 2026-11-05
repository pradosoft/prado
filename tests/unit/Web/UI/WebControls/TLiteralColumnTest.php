<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TLiteralColumn;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;

class TLiteralColumnTest extends TestCase
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
		$column = new TLiteralColumn();
		foreach (['DataField', 'DataFormatString', 'Text'] as $property) {
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
		$column = new TLiteralColumn();
		$formatter = fn ($value) => $value;
		$column->setDataFormatString($formatter);
		$this->assertSame($formatter, $column->getDataFormatString());
		$this->assertArrayNotHasKey('DataFormatString', $column->saveState());
	}

	public function testEncode()
	{
		$column = new TLiteralColumn();
		$this->assertFalse($column->getEncode());
		$column->setEncode('true');
		$this->assertTrue($column->getEncode());
		$this->assertTrue($column->saveState()['Encode']);
		$column->setEncode(false);
		$this->assertFalse($column->getEncode());
		$this->assertArrayNotHasKey('Encode', $column->saveState());
	}

	public function testStaticText()
	{
		$column = new TLiteralColumn();
		$column->setText('<i>static</i>');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::EditItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType, ['ignored' => 1]);
			$this->assertSame('<i>static</i>', $cell->getText(), $itemType);
			$cell->dataBind();
			$this->assertSame('<i>static</i>', $cell->getText(), $itemType);
		}
	}

	public function testStaticTextEncoded()
	{
		$column = new TLiteralColumn();
		$column->setText('<i title="t">a & b</i>');
		$column->setEncode(true);
		$this->assertSame('&lt;i title=&quot;t&quot;&gt;a & b&lt;/i&gt;', $this->createCell($column, TListItemType::Item)->getText());
	}

	public function testDataField()
	{
		$column = new TLiteralColumn();
		$column->setDataField('name');
		$column->setText('unused');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::EditItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType, ['name' => '<b>Ann</b>']);
			$this->assertSame('', $cell->getText(), $itemType);
			$cell->dataBind();
			$this->assertSame('<b>Ann</b>', $cell->getText(), $itemType);
		}
	}

	public function testDataFieldEncoded()
	{
		$column = new TLiteralColumn();
		$column->setDataField('name');
		$column->setEncode(true);
		$this->assertSame('&lt;b&gt;Ann&lt;/b&gt;', $this->bindCell($column, TListItemType::Item, ['name' => '<b>Ann</b>'])->getText());
	}

	public function testDataFormatString()
	{
		$column = new TLiteralColumn();
		$column->setDataField('qty');

		$column->setDataFormatString('%03d');
		$this->assertSame('007', $this->bindCell($column, TListItemType::Item, ['qty' => 7])->getText());

		$column->setDataFormatString('#{0} + 1');
		$this->assertSame(8, $this->bindCell($column, TListItemType::Item, ['qty' => 7])->getText());

		$column->setDataFormatString(fn ($value) => "<$value>");
		$this->assertSame('<7>', $this->bindCell($column, TListItemType::Item, ['qty' => 7])->getText());

		$column->setEncode(true);
		$this->assertSame('&lt;7&gt;', $this->bindCell($column, TListItemType::Item, ['qty' => 7])->getText());
	}

	public function testDataBindColumnWithoutDataField()
	{
		$column = new TLiteralColumn();
		$column->setDataFormatString('Row %s');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData('<3>');
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->dataBindColumn($cell, null);
		$this->assertSame('Row <3>', $cell->getText());

		$column->setEncode(true);
		$column->dataBindColumn($cell, null);
		$this->assertSame('Row &lt;3&gt;', $cell->getText());
	}

	public function testDataBindColumnIgnoresNonCellSender()
	{
		$column = new TLiteralColumn();
		$column->setDataField('name');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['name' => 'Ann']);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$label = new TLabel();
		$cell->getControls()->add($label);
		$column->dataBindColumn($label, null);
		$this->assertSame('', $label->getText());
		$this->assertSame('', $cell->getText());
	}

	public function testDataBindInvalidDataThrows()
	{
		$column = new TLiteralColumn();
		$column->setDataField('name');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindCell($column, TListItemType::Item, 'scalar');
	}

	public function testHeaderAndFooterUseParent()
	{
		$column = new TLiteralColumn();
		$column->setText('body');
		$column->setHeaderText('Head');
		$this->assertSame('Head', $this->createCell($column, TListItemType::Header)->getText());
		$this->assertSame('&nbsp;', $this->createCell($column, TListItemType::Footer)->getText());
		$this->assertSame('', $this->createCell($column, TListItemType::Separator)->getText());
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$column = new TLiteralColumn();
		$column->setDataField('name');
		$column->setEncode(true);
		$grid->getColumns()->add($column);
		$grid->setDataSource([['name' => 'a&b</p>'], ['name' => 'c']]);
		$grid->dataBind();
		$items = $grid->getItems();
		$this->assertSame('a&b&lt;/p&gt;', $items->itemAt(0)->getCells()->itemAt(0)->getText());
		$this->assertSame('c', $items->itemAt(1)->getCells()->itemAt(0)->getText());
	}
}
