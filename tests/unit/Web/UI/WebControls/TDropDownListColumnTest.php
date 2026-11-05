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

	public function testConstruct()
	{
		$column = new TDropDownListColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
		$this->assertInstanceOf(TDropDownList::class, PradoUnit::getProp($column, '_listControl'));
	}

	public function testDefaults()
	{
		$column = new TDropDownListColumn();
		$this->assertSame('', $column->getDataTextField());
		$this->assertSame('', $column->getDataTextFormatString());
		$this->assertSame('', $column->getDataValueField());
		$this->assertFalse($column->getReadOnly());
		$this->assertNull($column->getListDataSource());
		$this->assertSame('', $column->getListValueField());
		$this->assertSame('', $column->getListTextField());
		$this->assertSame('', $column->getListTextFormatString());
	}

	public function testStringProperties()
	{
		$column = new TDropDownListColumn();
		$properties = [
			'DataTextField' => 'colorName',
			'DataTextFormatString' => 'Color: %s',
			'DataValueField' => 'color',
			'ListValueField' => 'code',
			'ListTextField' => 'name',
			'ListTextFormatString' => '[%s]',
		];
		foreach ($properties as $name => $value) {
			$column->{'set' . $name}($value);
			$this->assertSame($value, $column->{'get' . $name}(), $name);
		}
		foreach ($properties as $name => $value) {
			$column->{'set' . $name}('');
			$this->assertSame('', $column->{'get' . $name}(), $name);
		}
	}

	public function testReadOnly()
	{
		$column = new TDropDownListColumn();
		$column->setReadOnly('true');
		$this->assertTrue($column->getReadOnly());
		$column->setReadOnly(false);
		$this->assertFalse($column->getReadOnly());
	}

	public function testListDataSource()
	{
		$column = new TDropDownListColumn();
		$column->setListDataSource(['a' => 'apple']);
		$this->assertInstanceOf(\Traversable::class, $column->getListDataSource());
		$this->assertSame(['a' => 'apple'], iterator_to_array($column->getListDataSource()));
		$column->setListDataSource(null);
		$this->assertNull($column->getListDataSource());
	}

	public function testCallableFormatStrings()
	{
		$column = new TDropDownListColumn();
		$text = fn ($value) => strtoupper($value);
		$list = fn ($value) => "<$value>";
		$column->setDataTextFormatString($text);
		$column->setListTextFormatString($list);
		$this->assertSame($text, $column->getDataTextFormatString());
		$this->assertSame($list, $column->getListTextFormatString());
		$state = $column->saveState();
		$this->assertArrayNotHasKey('DataTextFormatString', $state);
		$this->assertArrayNotHasKey('ListTextFormatString', $state);
	}

	public function testInitializeCellNormalItems()
	{
		$column = new TDropDownListColumn();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, [], $itemType);
			$this->assertSame(0, $cell->getControls()->getCount(), $itemType);
			$this->assertFalse($cell->hasEventHandler('OnDataBinding'), $itemType);
		}

		$column->setDataTextField('name');
		$this->assertTrue($this->createCell($column, [])->hasEventHandler('OnDataBinding'));

		$column = new TDropDownListColumn();
		$column->setDataValueField('code');
		$cell = $this->createCell($column, [], TListItemType::SelectedItem);
		$this->assertTrue($cell->hasEventHandler('OnDataBinding'));
		$this->assertSame(0, $cell->getControls()->getCount());
	}

	public function testInitializeCellHeaderFooterAndOthers()
	{
		$column = new TDropDownListColumn();
		$column->setHeaderText('Color');
		$column->setFooterText('End');
		$column->setDataTextField('name');

		$cell = $this->createCell($column, [], TListItemType::Header);
		$this->assertSame('Color', $cell->getText());
		$this->assertFalse($cell->hasEventHandler('OnDataBinding'));

		$cell = $this->createCell($column, [], TListItemType::Footer);
		$this->assertSame('End', $cell->getText());

		foreach ([TListItemType::Separator, TListItemType::Pager] as $itemType) {
			$cell = $this->createCell($column, [], $itemType);
			$this->assertSame('', $cell->getText());
			$this->assertSame(0, $cell->getControls()->getCount());
			$this->assertFalse($cell->hasEventHandler('OnDataBinding'));
		}
	}

	public function testInitializeCellEditItem()
	{
		$column = $this->createEditColumn();
		$cell = $this->createCell($column, [], TListItemType::EditItem);
		$this->assertSame(1, $cell->getControls()->getCount());
		$list = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TDropDownList::class, $list);
		$this->assertSame($list, $cell->getRegisteredObject('DropDownList'));
		$this->assertNotSame(PradoUnit::getProp($column, '_listControl'), $list);
		$this->assertTrue($list->hasEventHandler('OnDataBinding'));
		$this->assertFalse($cell->hasEventHandler('OnDataBinding'));
		$this->assertSame(['red', 'green', 'blue'], $this->itemTexts($list));
		$this->assertSame('g', $list->getItems()->itemAt(1)->getValue());
		$this->assertTrue(PradoUnit::getProp($column, '_dataBound'));
	}

	public function testInitializeCellEditItemWithoutDataSource()
	{
		$column = new TDropDownListColumn();
		$cell = $this->createCell($column, [], TListItemType::EditItem);
		$list = $cell->getRegisteredObject('DropDownList');
		$this->assertInstanceOf(TDropDownList::class, $list);
		$this->assertSame(0, $list->getItems()->getCount());
		$this->assertFalse(PradoUnit::getProp($column, '_dataBound'));
	}

	public function testInitializeCellBindsListOnce()
	{
		$column = $this->createEditColumn();
		$this->createCell($column, [], TListItemType::Item);
		$this->assertTrue(PradoUnit::getProp($column, '_dataBound'));
		$column->setListTextField('code');
		$list = $this->createCell($column, [], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['red', 'green', 'blue'], $this->itemTexts($list));
	}

	public function testInitializeCellListTextFormatString()
	{
		$column = $this->createEditColumn();
		$column->setListTextFormatString('Color %s');
		$list = $this->createCell($column, [], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['Color red', 'Color green', 'Color blue'], $this->itemTexts($list));

		$column = $this->createEditColumn();
		$column->setListTextFormatString('#strtoupper({0})');
		$list = $this->createCell($column, [], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['RED', 'GREEN', 'BLUE'], $this->itemTexts($list));
	}

	public function testInitializeCellListTextFormatCallable()
	{
		$column = $this->createEditColumn();
		$column->setListTextFormatString(fn ($value) => ucfirst($value) . '!');
		$list = $this->createCell($column, [], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['Red!', 'Green!', 'Blue!'], $this->itemTexts($list));
		$this->assertSame(['r', 'g', 'b'], [
			$list->getItems()->itemAt(0)->getValue(),
			$list->getItems()->itemAt(1)->getValue(),
			$list->getItems()->itemAt(2)->getValue(),
		]);
	}

	public function testInitializeCellReadOnlyEditItem()
	{
		$column = $this->createEditColumn();
		$column->setReadOnly(true);
		$cell = $this->createCell($column, [], TListItemType::EditItem);
		$this->assertSame(0, $cell->getControls()->getCount());
		$this->assertNull($cell->getRegisteredObject('DropDownList'));
		$this->assertTrue($cell->hasEventHandler('OnDataBinding'));
	}

	public function testDataBindEditItemSelectsValue()
	{
		$column = $this->createEditColumn();
		$cell = $this->bindCell($column, ['color' => 'b'], TListItemType::EditItem);
		$list = $cell->getRegisteredObject('DropDownList');
		$this->assertSame('b', $list->getSelectedValue());
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame('', $cell->getText());
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

	public function testDataBindEditItemUnknownValueClearsSelection()
	{
		$column = $this->createEditColumn();
		$cell = $this->bindCell($column, ['color' => 'x'], TListItemType::EditItem);
		$list = $cell->getRegisteredObject('DropDownList');
		foreach ($list->getItems() as $item) {
			$this->assertFalse($item->getSelected());
		}
	}

	public function testDataBindReadOnlyEditItemSetsText()
	{
		$column = $this->createEditColumn();
		$column->setReadOnly(true);
		$column->setDataTextField('name');
		$column->setDataTextFormatString('<%s>');
		$cell = $this->bindCell($column, ['color' => 'g', 'name' => 'green'], TListItemType::EditItem);
		$this->assertSame('<green>', $cell->getText());
	}

	public function testDataBindNormalItemText()
	{
		$column = new TDropDownListColumn();
		$column->setDataValueField('color');
		$this->assertSame('g', $this->bindCell($column, ['color' => 'g', 'name' => 'green'])->getText());

		$column->setDataTextField('name');
		$this->assertSame('green', $this->bindCell($column, ['color' => 'g', 'name' => 'green'])->getText());

		$column = new TDropDownListColumn();
		$column->setDataTextField('name');
		$cell = $this->bindCell($column, ['name' => 'blue'], TListItemType::AlternatingItem);
		$this->assertSame('blue', $cell->getText());
	}

	public function testDataBindNormalItemFormats()
	{
		$column = new TDropDownListColumn();
		$column->setDataValueField('color');
		$column->setDataTextField('name');

		$column->setDataTextFormatString('Color: %s');
		$this->assertSame('Color: red', $this->bindCell($column, ['color' => 'r', 'name' => 'red'])->getText());

		$column->setDataTextFormatString('#strrev({0})');
		$this->assertSame('der', $this->bindCell($column, ['color' => 'r', 'name' => 'red'])->getText());

		$column->setDataTextFormatString(fn ($value) => strtoupper($value));
		$this->assertSame('RED', $this->bindCell($column, ['color' => 'r', 'name' => 'red'])->getText());
	}

	public function testDataBindValueFieldIsNotFormatted()
	{
		$column = new TDropDownListColumn();
		$column->setDataValueField('color');
		$column->setDataTextFormatString('Color: %s');
		$this->assertSame('g', $this->bindCell($column, ['color' => 'g'])->getText());
	}

	public function testDataBindColumnDirect()
	{
		$column = new TDropDownListColumn();
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['color' => 'r']);
		$cell = new TTableCell();
		$cell->setText('before');
		$item->getCells()->add($cell);
		$column->dataBindColumn($cell, null);
		$this->assertSame('', $cell->getText());

		$column->setDataValueField('color');
		$column->dataBindColumn($cell, null);
		$this->assertSame('r', $cell->getText());
	}

	public function testDataBindMissingFieldThrows()
	{
		$column = new TDropDownListColumn();
		$column->setDataValueField('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindCell($column, ['color' => 'r']);
	}

	public function testAddParsedObject()
	{
		$column = new TDropDownListColumn();
		$selected = new TListItem('Red', 'r', true);
		$column->addParsedObject($selected);
		$column->addParsedObject(new TListItem('Green', 'g'));
		$column->addParsedObject('ignored text');
		$this->assertFalse($selected->getSelected());

		$cell = $this->bindCell($column, ['color' => 'g'], TListItemType::EditItem);
		$list = $cell->getRegisteredObject('DropDownList');
		$this->assertSame(['Red', 'Green'], $this->itemTexts($list));
		$this->assertSame(-1, $list->getSelectedIndex(), 'no DataValueField or DataTextField selects the empty value');
	}

	public function testAddParsedObjectBindsWithDataValueField()
	{
		$column = new TDropDownListColumn();
		$column->addParsedObject(new TListItem('Red', 'r'));
		$column->addParsedObject(new TListItem('Green', 'g'));
		$column->setDataValueField('color');
		$cell = $this->bindCell($column, ['color' => 'g'], TListItemType::EditItem);
		$this->assertSame('g', $cell->getRegisteredObject('DropDownList')->getSelectedValue());
	}

	public function testSaveAndLoadState()
	{
		$column = new TDropDownListColumn();
		$column->setDataValueField('color');
		$column->addParsedObject(new TListItem('Red', 'r'));
		$column->addParsedObject(new TListItem('Green', 'g'));
		$state = $column->saveState();
		$this->assertSame('color', $state['DataValueField']);
		$this->assertArrayHasKey('Items', $state);

		$restored = new TDropDownListColumn();
		$restored->loadState($state);
		$this->assertSame('color', $restored->getDataValueField());
		$this->assertTrue(PradoUnit::getProp($restored, '_stateLoaded'));
		$restored->addParsedObject(new TListItem('Blue', 'b'));
		$list = $this->bindCell($restored, ['color' => 'g'], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['Red', 'Green'], $this->itemTexts($list));
		$this->assertSame('g', $list->getSelectedValue());
	}

	public function testLoadStateAfterDataBoundKeepsItems()
	{
		$column = $this->createEditColumn();
		$this->createCell($column, [], TListItemType::Item);
		$column->loadState(['Items' => null]);
		$list = $this->createCell($column, [], TListItemType::EditItem)->getRegisteredObject('DropDownList');
		$this->assertSame(['red', 'green', 'blue'], $this->itemTexts($list));
	}
}
