<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTableRow;
use Prado\Web\UI\WebControls\TTemplateColumn;

class TTemplateColumnTestTemplate implements ITemplate
{
	public function __construct(private string $_text)
	{
	}

	public function instantiateIn($parent)
	{
		$label = new TLabel();
		$label->setText($this->_text);
		$parent->getControls()->add($label);
	}

	public function getIncludedFiles()
	{
		return [];
	}
}

class TTemplateColumnTestRenderer extends TControl implements IItemDataRenderer
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

class TTemplateColumnTestItemRow extends TTableRow implements IItemDataRenderer, INamingContainer
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

class TTemplateColumnTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TTemplateColumn $column
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

	public function testConstruct()
	{
		$column = new TTemplateColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
	}

	public function testRendererProperties()
	{
		$column = new TTemplateColumn();
		foreach (['ItemRenderer', 'EditItemRenderer'] as $property) {
			$getter = 'get' . $property;
			$setter = 'set' . $property;
			$this->assertSame('', $column->$getter(), $property);
			$column->$setter(TLabel::class);
			$this->assertSame(TLabel::class, $column->$getter(), $property);
			$this->assertSame(TLabel::class, $column->saveState()[$property], $property);
			$column->$setter('');
			$this->assertSame('', $column->$getter(), $property);
			$this->assertArrayNotHasKey($property, $column->saveState(), $property);
		}
	}

	public function testTemplateProperties()
	{
		$column = new TTemplateColumn();
		foreach (['ItemTemplate', 'EditItemTemplate', 'HeaderTemplate', 'FooterTemplate'] as $property) {
			$getter = 'get' . $property;
			$setter = 'set' . $property;
			$this->assertNull($column->$getter(), $property);
			$template = new TTemplateColumnTestTemplate($property);
			$column->$setter($template);
			$this->assertSame($template, $column->$getter(), $property);
			$column->$setter(null);
			$this->assertNull($column->$getter(), $property);
		}
	}

	public function testTemplatePropertiesRequireTemplate()
	{
		$column = new TTemplateColumn();
		foreach (['ItemTemplate', 'EditItemTemplate', 'HeaderTemplate', 'FooterTemplate'] as $property) {
			$setter = 'set' . $property;
			try {
				$column->$setter('not a template');
				$this->fail('Expected TInvalidDataTypeException for ' . $property);
			} catch (TInvalidDataTypeException $e) {
				$this->assertSame('templatecolumn_template_required', $e->getErrorCode(), $property);
				$this->assertStringContainsString($property, $e->getMessage(), $property);
			}
		}
	}

	public function testEmptyItemCellsGetNonBreakingSpace()
	{
		$column = new TTemplateColumn();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame('&nbsp;', $cell->getText(), $itemType);
			$this->assertFalse($cell->getHasControls(), $itemType);
		}
	}

	public function testEmptyEditItemCellStaysEmpty()
	{
		$cell = $this->createCell(new TTemplateColumn(), TListItemType::EditItem);
		$this->assertSame('', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testItemTemplate()
	{
		$column = new TTemplateColumn();
		$column->setItemTemplate(new TTemplateColumnTestTemplate('item'));
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$label = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(TLabel::class, $label, $itemType);
			$this->assertSame('item', $label->getText(), $itemType);
		}
	}

	public function testEditItemTemplate()
	{
		$column = new TTemplateColumn();
		$column->setItemTemplate(new TTemplateColumnTestTemplate('item'));
		$column->setItemRenderer(TPanel::class);
		$column->setEditItemTemplate(new TTemplateColumnTestTemplate('edit'));
		$this->assertSame('edit', $this->createCell($column, TListItemType::EditItem)->getControls()->itemAt(0)->getText());
		$this->assertInstanceOf(TPanel::class, $this->createCell($column, TListItemType::Item)->getControls()->itemAt(0));
	}

	public function testItemRendererTakesPrecedenceOverTemplate()
	{
		$column = new TTemplateColumn();
		$column->setItemTemplate(new TTemplateColumnTestTemplate('item'));
		$column->setItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Item, 'bound');
		$this->assertSame(1, $cell->getControls()->getCount());
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('', $label->getText());
		$cell->dataBind();
		$this->assertSame('bound', $label->getText());
	}

	public function testEditItemRenderer()
	{
		$column = new TTemplateColumn();
		$column->setItemRenderer(TPanel::class);
		$column->setEditItemTemplate(new TTemplateColumnTestTemplate('edit'));
		$column->setEditItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::EditItem, 'edit-data');
		$this->assertSame(1, $cell->getControls()->getCount());
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$cell->dataBind();
		$this->assertSame('edit-data', $label->getText());
	}

	public function testEditItemFallsBackToItemRenderer()
	{
		$column = new TTemplateColumn();
		$column->setItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::EditItem, 'fallback');
		$cell->dataBind();
		$this->assertSame('fallback', $cell->getControls()->itemAt(0)->getText());
	}

	public function testNonDataRendererIsNotBound()
	{
		$column = new TTemplateColumn();
		$column->setItemRenderer(TPanel::class);
		$cell = $this->createCell($column, TListItemType::Item, 'data');
		$panel = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TPanel::class, $panel);
		$this->assertFalse($panel->hasEventHandler('OnDataBinding'));
		$cell->dataBind();
		$this->assertSame('', $cell->getText());
	}

	public function testItemDataRendererReceivesItemIndexAndType()
	{
		$column = new TTemplateColumn();
		$column->setItemRenderer(TTemplateColumnTestRenderer::class);
		$row = new TTemplateColumnTestItemRow();
		$row->setData(['id' => 3]);
		$cell = new TTableCell();
		$row->getCells()->add($cell);
		$column->initializeCell($cell, 0, TListItemType::SelectedItem);
		$renderer = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TTemplateColumnTestRenderer::class, $renderer);
		$this->assertSame(7, $renderer->getItemIndex());
		$this->assertSame(TListItemType::SelectedItem, $renderer->getItemType());
		$cell->dataBind();
		$this->assertSame(['id' => 3], $renderer->getData());
	}

	public function testItemDataRendererWithoutItemRendererParent()
	{
		$column = new TTemplateColumn();
		$column->setItemRenderer(TTemplateColumnTestRenderer::class);
		$cell = $this->createCell($column, TListItemType::Item, 'data');
		$renderer = $cell->getControls()->itemAt(0);
		$this->assertNull($renderer->getItemIndex());
		$this->assertNull($renderer->getItemType());
		$cell->dataBind();
		$this->assertSame('data', $renderer->getData());
	}

	public function testDataBindColumn()
	{
		$column = new TTemplateColumn();
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['name' => 'x']);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$renderer = new TTemplateColumnTestRenderer();
		$cell->getControls()->add($renderer);
		$column->dataBindColumn($renderer, null);
		$this->assertSame(['name' => 'x'], $renderer->getData());
	}

	public function testHeaderDefault()
	{
		$column = new TTemplateColumn();
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Name', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	public function testHeaderTemplate()
	{
		$column = new TTemplateColumn();
		$column->setHeaderText('Name');
		$column->setHeaderTemplate(new TTemplateColumnTestTemplate('head'));
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('', $cell->getText());
		$this->assertSame('head', $cell->getControls()->itemAt(0)->getText());
	}

	public function testHeaderRendererTakesPrecedenceOverTemplate()
	{
		$column = new TTemplateColumn();
		$column->setHeaderText('Name');
		$column->setHeaderTemplate(new TTemplateColumnTestTemplate('head'));
		$column->setHeaderRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame(1, $cell->getControls()->getCount());
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('Name', $label->getText());
	}

	public function testFooterDefault()
	{
		$column = new TTemplateColumn();
		$column->setFooterText('End');
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
		$this->assertSame('&nbsp;', $this->createCell(new TTemplateColumn(), TListItemType::Footer)->getText());
	}

	public function testFooterTemplate()
	{
		$column = new TTemplateColumn();
		$column->setFooterText('End');
		$column->setFooterTemplate(new TTemplateColumnTestTemplate('foot'));
		$cell = $this->createCell($column, TListItemType::Footer);
		$this->assertSame('', $cell->getText());
		$this->assertSame('foot', $cell->getControls()->itemAt(0)->getText());
	}

	public function testFooterRendererTakesPrecedenceOverTemplate()
	{
		$column = new TTemplateColumn();
		$column->setFooterText('End');
		$column->setFooterTemplate(new TTemplateColumnTestTemplate('foot'));
		$column->setFooterRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Footer);
		$this->assertSame(1, $cell->getControls()->getCount());
		$this->assertSame('End', $cell->getControls()->itemAt(0)->getText());
	}

	public function testOtherItemTypesAreIgnored()
	{
		$column = new TTemplateColumn();
		$column->setItemTemplate(new TTemplateColumnTestTemplate('item'));
		$column->setHeaderText('Name');
		foreach ([TListItemType::Separator, TListItemType::Pager] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame('', $cell->getText(), $itemType);
			$this->assertFalse($cell->getHasControls(), $itemType);
		}
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->setEditItemIndex(1);
		$column = new TTemplateColumn();
		$column->setHeaderTemplate(new TTemplateColumnTestTemplate('head'));
		$column->setItemRenderer(TLabel::class);
		$column->setEditItemTemplate(new TTemplateColumnTestTemplate('editing'));
		$grid->getColumns()->add($column);
		$grid->setDataSource(['a', 'b', 'c']);
		$grid->dataBind();

		$items = $grid->getItems();
		$this->assertSame('head', $grid->getHeader()->getCells()->itemAt(0)->getControls()->itemAt(0)->getText());
		$this->assertSame('a', $items->itemAt(0)->getCells()->itemAt(0)->getControls()->itemAt(0)->getText());
		$this->assertSame('editing', $items->itemAt(1)->getCells()->itemAt(0)->getControls()->itemAt(0)->getText());
		$this->assertSame('c', $items->itemAt(2)->getCells()->itemAt(0)->getControls()->itemAt(0)->getText());
	}
}
