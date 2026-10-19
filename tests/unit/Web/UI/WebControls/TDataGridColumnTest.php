<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\TComponent;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TImage;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Web\UI\WebControls\TTableRow;

class TDataGridColumnTestColumn extends TDataGridColumn
{
}

class TDataGridColumnTestData extends TComponent
{
	public function getName()
	{
		return 'component-name';
	}
}

class TDataGridColumnTestItemRenderer extends TControl implements IItemDataRenderer
{
	private $_data;
	private $_itemIndex;
	private $_itemType;

	public function getData()
	{
		return $this->_data;
	}

	public function setData($value)
	{
		$this->_data = $value;
	}

	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	public function setItemIndex($value)
	{
		$this->_itemIndex = $value;
	}

	public function getItemType()
	{
		return $this->_itemType;
	}

	public function setItemType($value)
	{
		$this->_itemType = $value;
	}
}

class TDataGridColumnTestItemRow extends TTableRow implements IItemDataRenderer, INamingContainer
{
	private $_data;

	public function getData()
	{
		return $this->_data;
	}

	public function setData($value)
	{
		$this->_data = $value;
	}

	public function getItemIndex()
	{
		return 7;
	}

	public function setItemIndex($value)
	{
	}

	public function getItemType()
	{
		return TListItemType::AlternatingItem;
	}

	public function setItemType($value)
	{
	}
}

class TDataGridColumnTest extends TestCase
{
	private function createCell($column, $itemType, $index = 0)
	{
		$item = new TDataGridItem($index, $index, $itemType);
		$cell = $itemType === TListItemType::Header ? new TTableHeaderCell() : new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	public function testID()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertSame('', $column->getID());
		$column->setID('Column_1');
		$this->assertSame('Column_1', $column->getID());
		$column->setID('_col');
		$this->assertSame('_col', $column->getID());
	}

	public function testInvalidID()
	{
		$column = new TDataGridColumnTestColumn();
		foreach (['1col', 'bad-id', 'with space', ''] as $id) {
			try {
				$column->setID($id);
				$this->fail("Expected TInvalidDataValueException for '$id'");
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('datagridcolumn_id_invalid', $e->getErrorCode());
			}
		}
		$this->assertSame('', $column->getID());
	}

	public function testOwner()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertNull($column->getOwner());
		$grid = new TDataGrid();
		$column->setOwner($grid);
		$this->assertSame($grid, $column->getOwner());
	}

	public function testOwnerRequiresDataGrid()
	{
		$column = new TDataGridColumnTestColumn();
		$this->expectException(\TypeError::class);
		$column->setOwner(new TPanel());
	}

	public function testStringProperties()
	{
		$column = new TDataGridColumnTestColumn();
		foreach (['HeaderText', 'FooterText', 'HeaderImageUrl', 'HeaderRenderer', 'FooterRenderer', 'SortExpression'] as $property) {
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

	public function testStringPropertiesThroughMagicAccess()
	{
		$column = new TDataGridColumnTestColumn();
		$column->HeaderText = 'Name';
		$column->FooterText = 'Total';
		$this->assertSame('Name', $column->HeaderText);
		$this->assertSame('Total', $column->FooterText);
	}

	public function testStyles()
	{
		foreach (['HeaderStyle', 'FooterStyle', 'ItemStyle'] as $property) {
			$column = new TDataGridColumnTestColumn();
			$getter = 'get' . $property;
			$this->assertNull($column->$getter(false), $property);
			$this->assertArrayNotHasKey($property, $column->saveState());
			$style = $column->$getter();
			$this->assertInstanceOf(TTableItemStyle::class, $style);
			$this->assertSame($style, $column->$getter());
			$this->assertSame($style, $column->$getter(false));
			$this->assertSame($style, $column->saveState()[$property]);
		}
	}

	public function testEnableCellGrouping()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertFalse($column->getEnableCellGrouping());
		$column->setEnableCellGrouping('true');
		$this->assertTrue($column->getEnableCellGrouping());
		$this->assertTrue($column->saveState()['EnableCellGrouping']);
		$column->setEnableCellGrouping(false);
		$this->assertFalse($column->getEnableCellGrouping());
		$this->assertArrayNotHasKey('EnableCellGrouping', $column->saveState());
	}

	public function testVisible()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertTrue($column->getVisible());
		$this->assertTrue($column->getVisible(false));
		$column->setVisible('false');
		$this->assertFalse($column->getVisible());
		$this->assertFalse($column->saveState()['Visible']);
		$column->setVisible(true);
		$this->assertTrue($column->getVisible());
		$this->assertArrayNotHasKey('Visible', $column->saveState());
	}

	public function testSaveAndLoadState()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertSame([], $column->saveState());
		$column->setHeaderText('Header');
		$column->setSortExpression('name');
		$column->setVisible(false);
		$column->getItemStyle()->setCssClass('item');
		$state = $column->saveState();

		$restored = new TDataGridColumnTestColumn();
		$restored->loadState($state);
		$this->assertSame('Header', $restored->getHeaderText());
		$this->assertSame('name', $restored->getSortExpression());
		$this->assertFalse($restored->getVisible());
		$this->assertSame('item', $restored->getItemStyle(false)->getCssClass());
		$this->assertSame($state, $restored->saveState());

		$serialized = unserialize(serialize($state));
		$restored->loadState($serialized);
		$this->assertSame('item', $restored->getItemStyle(false)->getCssClass());
	}

	public function testViewState()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertSame('default', PradoUnit::invoke($column, 'getViewState', 'Key', 'default'));
		$this->assertNull(PradoUnit::invoke($column, 'getViewState', 'Key'));
		PradoUnit::invoke($column, 'setViewState', 'Key', 'value', 'default');
		$this->assertSame('value', PradoUnit::invoke($column, 'getViewState', 'Key', 'default'));
		PradoUnit::invoke($column, 'setViewState', 'Key', 'default', 'default');
		$this->assertSame('fallback', PradoUnit::invoke($column, 'getViewState', 'Key', 'fallback'));
		$this->assertArrayNotHasKey('Key', $column->saveState());
	}

	public function testInitializeDoesNothing()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('x');
		$state = $column->saveState();
		$column->initialize();
		$this->assertSame($state, $column->saveState());
	}

	public function testGetDataFieldValue()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertSame('a', PradoUnit::invoke($column, 'getDataFieldValue', ['name' => 'a'], 'name'));
		$this->assertSame('deep', PradoUnit::invoke($column, 'getDataFieldValue', ['outer' => ['inner' => 'deep']], 'outer.inner'));
		$this->assertSame('m', PradoUnit::invoke($column, 'getDataFieldValue', new TMap(['name' => 'm']), 'name'));
		$this->assertSame('component-name', PradoUnit::invoke($column, 'getDataFieldValue', new TDataGridColumnTestData(), 'Name'));
		$object = new \stdClass();
		$object->title = 'std';
		$this->assertSame('std', PradoUnit::invoke($column, 'getDataFieldValue', $object, 'title'));
	}

	public function testGetDataFieldValueInvalidData()
	{
		$column = new TDataGridColumnTestColumn();
		try {
			PradoUnit::invoke($column, 'getDataFieldValue', 'scalar', 'name');
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('datafieldaccessor_data_invalid', $e->getErrorCode());
		}
	}

	public function testGetDataFieldValueMissingField()
	{
		$column = new TDataGridColumnTestColumn();
		try {
			PradoUnit::invoke($column, 'getDataFieldValue', new TDataGridColumnTestData(), 'Missing');
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('datafieldaccessor_datafield_invalid', $e->getErrorCode());
		}
	}

	public function testGetAllowSorting()
	{
		$column = new TDataGridColumnTestColumn();
		$this->assertFalse($column->getAllowSorting());
		$column->setSortExpression('name');
		$this->assertTrue($column->getAllowSorting());

		$grid = new TDataGrid();
		$column->setOwner($grid);
		$this->assertFalse($column->getAllowSorting());
		$grid->setAllowSorting(true);
		$this->assertTrue($column->getAllowSorting());
		$column->setSortExpression('');
		$this->assertFalse($column->getAllowSorting());
	}

	public function testHeaderCellEmpty()
	{
		$cell = $this->createCell(new TDataGridColumnTestColumn(), TListItemType::Header);
		$this->assertSame('&nbsp;', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testHeaderCellText()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Name', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testHeaderCellImage()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderImageUrl('head.png');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('', $cell->getText());
		$this->assertCount(1, $cell->getControls());
		$image = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TImage::class, $image);
		$this->assertSame('head.png', $image->getImageUrl());
		$this->assertSame('', $image->getAlternateText());

		$column->setHeaderText('Alt');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Alt', $cell->getControls()->itemAt(0)->getAlternateText());
	}

	public function testHeaderCellSortLinkButton()
	{
		$grid = new TDataGrid();
		$grid->setAllowSorting(true);
		$column = new TDataGridColumnTestColumn();
		$column->setOwner($grid);
		$column->setSortExpression('name');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('', $cell->getText());
		$button = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLinkButton::class, $button);
		$this->assertSame('Name', $button->getText());
		$this->assertSame(TDataGrid::CMD_SORT, $button->getCommandName());
		$this->assertSame('name', $button->getCommandParameter());
		$this->assertFalse($button->getCausesValidation());
	}

	public function testHeaderCellSortImageButton()
	{
		$grid = new TDataGrid();
		$grid->setAllowSorting(true);
		$column = new TDataGridColumnTestColumn();
		$column->setOwner($grid);
		$column->setSortExpression('price');
		$column->setHeaderImageUrl('sort.png');
		$cell = $this->createCell($column, TListItemType::Header);
		$button = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TImageButton::class, $button);
		$this->assertSame('sort.png', $button->getImageUrl());
		$this->assertSame(TDataGrid::CMD_SORT, $button->getCommandName());
		$this->assertSame('price', $button->getCommandParameter());
		$this->assertFalse($button->getCausesValidation());
		$this->assertSame('', $button->getAlternateText());

		$column->setHeaderText('Price');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Price', $cell->getControls()->itemAt(0)->getAlternateText());
	}

	public function testHeaderCellSortWithoutTextOrImage()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setSortExpression('name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('&nbsp;', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testHeaderCellNoSortWhenGridDisallowsSorting()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setOwner(new TDataGrid());
		$column->setSortExpression('name');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Name', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testHeaderRenderer()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('Name');
		$column->setSortExpression('name');
		$column->setHeaderRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('', $cell->getText());
		$this->assertCount(1, $cell->getControls());
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('Name', $label->getText());
	}

	public function testHeaderRendererWithoutDataRenderer()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('Name');
		$column->setHeaderRenderer(TPanel::class);
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertInstanceOf(TPanel::class, $cell->getControls()->itemAt(0));
		$this->assertSame('', $cell->getText());
	}

	public function testFooterCell()
	{
		$column = new TDataGridColumnTestColumn();
		$cell = $this->createCell($column, TListItemType::Footer);
		$this->assertSame('&nbsp;', $cell->getText());

		$column->setFooterText('Total');
		$cell = $this->createCell($column, TListItemType::Footer);
		$this->assertSame('Total', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testFooterRenderer()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setFooterText('Total');
		$column->setFooterRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Footer);
		$this->assertSame('', $cell->getText());
		$this->assertSame('Total', $cell->getControls()->itemAt(0)->getText());
	}

	public function testItemCellsAreUntouched()
	{
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('Header');
		$column->setFooterText('Footer');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem, TListItemType::Separator, TListItemType::Pager] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame('', $cell->getText(), $itemType);
			$this->assertFalse($cell->getHasControls(), $itemType);
		}
	}

	public function testRendererControlReceivesItemInformation()
	{
		$column = new TDataGridColumnTestColumn();
		$row = new TDataGridColumnTestItemRow();
		$cell = new TTableCell();
		$row->getCells()->add($cell);
		$control = PradoUnit::invoke($column, 'initializeCellRendererControl', $cell, TDataGridColumnTestItemRenderer::class, 'payload');
		$this->assertInstanceOf(TDataGridColumnTestItemRenderer::class, $control);
		$this->assertSame($cell, $control->getParent());
		$this->assertSame(7, $control->getItemIndex());
		$this->assertSame(TListItemType::AlternatingItem, $control->getItemType());
		$this->assertSame('payload', $control->getData());
	}

	public function testRendererControlWithoutItemParent()
	{
		$column = new TDataGridColumnTestColumn();
		$item = new TDataGridItem(3, 3, TListItemType::Item);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$control = PradoUnit::invoke($column, 'initializeCellRendererControl', $cell, TDataGridColumnTestItemRenderer::class);
		$this->assertNull($control->getItemIndex());
		$this->assertNull($control->getItemType());
		$this->assertNull($control->getData());
	}

	public function testHeaderAndFooterInDataGrid()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$column = new TDataGridColumnTestColumn();
		$column->setHeaderText('Header');
		$column->setFooterText('Footer');
		$grid->getColumns()->add($column);
		$this->assertSame($grid, $column->getOwner());
		$grid->setDataSource([['a' => 1], ['a' => 2]]);
		$grid->dataBind();
		$this->assertSame('Header', $grid->getHeader()->getCells()->itemAt(0)->getText());
		$this->assertSame('Footer', $grid->getFooter()->getCells()->itemAt(0)->getText());
		$this->assertSame(2, $grid->getItems()->getCount());
	}
}
