<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumnCollection;
use Prado\Web\UI\WebControls\TDataGridCommandEventParameter;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TDataGridItemCollection;
use Prado\Web\UI\WebControls\TDataGridItemEventParameter;
use Prado\Web\UI\WebControls\TDataGridPageChangedEventParameter;
use Prado\Web\UI\WebControls\TDataGridPager;
use Prado\Web\UI\WebControls\TDataGridPagerButtonType;
use Prado\Web\UI\WebControls\TDataGridPagerEventParameter;
use Prado\Web\UI\WebControls\TDataGridPagerMode;
use Prado\Web\UI\WebControls\TDataGridPagerPosition;
use Prado\Web\UI\WebControls\TDataGridPagerStyle;
use Prado\Web\UI\WebControls\TDataGridSortCommandEventParameter;
use Prado\Web\UI\WebControls\TDataGridSortDirection;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTableCaptionAlign;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Web\UI\WebControls\TTableStyle;

/**
 * Client script manager that records postback control registrations.
 */
class TDataGridTestClientScript extends TClientScriptManager
{
	private array $_postBackControls = [];

	public function getPostBackControls(): array
	{
		return $this->_postBackControls;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->_postBackControls[] = [$class, $options];
	}
}

/**
 * Template that instantiates a literal holding a fixed text.
 */
class TDataGridTestTemplate implements ITemplate
{
	private int $_instantiated = 0;

	public function __construct(private string $_text = 'No data')
	{
	}

	public function getInstantiated(): int
	{
		return $this->_instantiated;
	}

	public function instantiateIn($parent)
	{
		$this->_instantiated++;
		$literal = new TLiteral();
		$literal->setText($this->_text);
		$parent->getControls()->add($literal);
	}

	public function getIncludedFiles()
	{
		return [];
	}
}

/**
 * Data grid that generates auto columns of the fully qualified bound column class.
 */
class TDataGridTestGrid extends TDataGrid
{
	protected function getAutoGenerateColumnName()
	{
		return TBoundColumn::class;
	}
}

/**
 * Data grid item reporting an item type that the grid does not style.
 */
class TDataGridTestCustomItem extends TDataGridItem
{
	public function getItemType()
	{
		return 'Custom';
	}
}

class TDataGridTest extends TestCase
{
	use TWebControlRenderTrait;

	// ================================================================================
	// Helpers
	// ================================================================================

	private function rows(int $count): array
	{
		$rows = [];
		for ($i = 0; $i < $count; $i++) {
			$rows[] = ['id' => 'k' . $i, 'name' => 'n' . $i, 'city' => 'c' . $i];
		}
		return $rows;
	}

	private function createColumn(string $field, string $id = ''): TBoundColumn
	{
		$column = new TBoundColumn();
		if ($id !== '') {
			$column->setID($id);
		}
		$column->setDataField($field);
		$column->setHeaderText(ucfirst($field));
		$column->setFooterText('F-' . $field);
		return $column;
	}

	private function createGrid(array $fields = ['name', 'city']): TDataGrid
	{
		$grid = new TDataGrid();
		$grid->setID('Grid');
		$grid->setAutoGenerateColumns(false);
		foreach ($fields as $field) {
			$grid->getColumns()->add($this->createColumn($field));
		}
		return $grid;
	}

	private function bind(TDataGrid $grid, $data): TDataGrid
	{
		$grid->setDataSource($data);
		$grid->dataBind();
		return $grid;
	}

	private function attachToPage(TDataGrid $grid): TPage
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TDataGridTestClientScript($page));
		$page->getControls()->add($grid);
		return $page;
	}

	private function itemTypes(TDataGrid $grid): array
	{
		$types = [];
		foreach ($grid->getItems() as $item) {
			$types[] = $item->getItemType();
		}
		return $types;
	}

	private function cellTexts(TDataGridItem $item): array
	{
		$texts = [];
		foreach ($item->getCells() as $cell) {
			$texts[] = $cell->getText();
		}
		return $texts;
	}

	/**
	 * Summarizes pager controls: "L:text" for labels, "B:text|cmd|param" for
	 * link buttons and "P:text|cmd|param|enabled" for push buttons.
	 * @param TDataGridPager $pager the pager to summarize
	 */
	private function pagerSummary(TDataGridPager $pager): array
	{
		$summary = [];
		foreach ($pager->getControls() as $control) {
			if ($control instanceof TLinkButton) {
				$summary[] = 'B:' . $control->getText() . '|' . $control->getCommandName() . '|' . $control->getCommandParameter();
			} elseif ($control instanceof TButton) {
				$summary[] = 'P:' . $control->getText() . '|' . $control->getCommandName() . '|' . $control->getCommandParameter() . '|' . ($control->getEnabled() ? '1' : '0');
			} elseif ($control instanceof TLabel) {
				$summary[] = 'L:' . $control->getText();
			}
		}
		return $summary;
	}

	private function recordEvents(TDataGrid $grid, array $events): \ArrayObject
	{
		$log = new \ArrayObject();
		foreach ($events as $event) {
			$grid->attachEventHandler($event, function ($sender, $param) use ($log, $event) {
				$log[] = [$event, $sender, $param];
			});
		}
		return $log;
	}

	private function command(TDataGrid $grid, string $name, $parameter = ''): void
	{
		$cell = $grid->getItems()->itemAt(0)->getCells()->itemAt(0);
		$button = new TLinkButton();
		$cell->getControls()->add($button);
		$button->onCommand(new TCommandEventParameter($name, $parameter));
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testProtectedDefaults()
	{
		$grid = new TDataGrid();
		$this->assertSame('table', PradoUnit::invoke($grid, 'getTagName'));
		$this->assertSame('TBoundColumn', PradoUnit::invoke($grid, 'getAutoGenerateColumnName'));
		$this->assertInstanceOf(TTableStyle::class, PradoUnit::invoke($grid, 'createStyle'));
		$this->assertInstanceOf(TTableStyle::class, $grid->getStyle());
	}

	public function testCommandConstants()
	{
		$this->assertSame('Select', TDataGrid::CMD_SELECT);
		$this->assertSame('Edit', TDataGrid::CMD_EDIT);
		$this->assertSame('Update', TDataGrid::CMD_UPDATE);
		$this->assertSame('Delete', TDataGrid::CMD_DELETE);
		$this->assertSame('Cancel', TDataGrid::CMD_CANCEL);
		$this->assertSame('Sort', TDataGrid::CMD_SORT);
		$this->assertSame('Page', TDataGrid::CMD_PAGE);
		$this->assertSame('Next', TDataGrid::CMD_PAGE_NEXT);
		$this->assertSame('Previous', TDataGrid::CMD_PAGE_PREV);
		$this->assertSame('First', TDataGrid::CMD_PAGE_FIRST);
		$this->assertSame('Last', TDataGrid::CMD_PAGE_LAST);
	}

	public function testCollectionsAreCreatedOnce()
	{
		$grid = new TDataGrid();
		$columns = $grid->getColumns();
		$this->assertInstanceOf(TDataGridColumnCollection::class, $columns);
		$this->assertSame($columns, $grid->getColumns());
		$autoColumns = $grid->getAutoColumns();
		$this->assertInstanceOf(TDataGridColumnCollection::class, $autoColumns);
		$this->assertSame($autoColumns, $grid->getAutoColumns());
		$this->assertNotSame($columns, $autoColumns);

		$this->assertSame(0, $grid->getItemCount());
		$items = $grid->getItems();
		$this->assertInstanceOf(TDataGridItemCollection::class, $items);
		$this->assertSame($items, $grid->getItems());
		$this->assertSame(0, $grid->getItemCount());
	}

	public function testAddParsedObject()
	{
		$grid = new TDataGrid();
		$column = new TBoundColumn();
		$grid->addParsedObject($column);
		$this->assertSame(1, $grid->getColumns()->getCount());
		$this->assertSame($column, $grid->getColumns()->itemAt(0));
		$this->assertSame($grid, $column->getOwner());
		$this->assertFalse($grid->getHasControls());

		$panel = new TPanel();
		$grid->addParsedObject($panel);
		$this->assertSame(1, $grid->getColumns()->getCount());
		$this->assertSame($panel, $grid->getControls()->itemAt(0));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testBackImageUrl()
	{
		$grid = new TDataGrid();
		$this->assertSame('', $grid->getBackImageUrl());
		$grid->setBackImageUrl('bg.png');
		$this->assertSame('bg.png', $grid->getBackImageUrl());
		$this->assertSame('bg.png', $grid->getStyle()->getBackImageUrl());
	}

	public function testItemStyles()
	{
		$grid = new TDataGrid();
		$styles = [
			'ItemStyle' => TTableItemStyle::class,
			'AlternatingItemStyle' => TTableItemStyle::class,
			'SelectedItemStyle' => TTableItemStyle::class,
			'EditItemStyle' => TTableItemStyle::class,
			'HeaderStyle' => TTableItemStyle::class,
			'FooterStyle' => TTableItemStyle::class,
			'PagerStyle' => TDataGridPagerStyle::class,
			'TableHeadStyle' => TStyle::class,
			'TableBodyStyle' => TStyle::class,
			'TableFootStyle' => TStyle::class,
		];
		foreach ($styles as $property => $class) {
			$this->assertNull($grid->getViewState($property), $property);
			$getter = 'get' . $property;
			$style = $grid->$getter();
			$this->assertInstanceOf($class, $style, $property);
			$this->assertSame($style, $grid->$getter(), $property);
			$this->assertSame($style, $grid->getViewState($property), $property);
		}
	}

	public function testCaption()
	{
		$grid = new TDataGrid();
		$this->assertSame('', $grid->getCaption());
		$grid->setCaption('Report');
		$this->assertSame('Report', $grid->getCaption());
		$grid->setCaption('');
		$this->assertSame('', $grid->getCaption());
	}

	public function testCaptionAlign()
	{
		$grid = new TDataGrid();
		$this->assertSame(TTableCaptionAlign::NotSet, $grid->getCaptionAlign());
		$grid->setCaptionAlign('top');
		$this->assertSame(TTableCaptionAlign::Top, $grid->getCaptionAlign());
		$grid->setCaptionAlign(TTableCaptionAlign::Right);
		$this->assertSame(TTableCaptionAlign::Right, $grid->getCaptionAlign());
	}

	public function testCaptionAlignInvalid()
	{
		$grid = new TDataGrid();
		$this->expectException(TInvalidDataValueException::class);
		$grid->setCaptionAlign('Middle');
	}

	public function testBooleanProperties()
	{
		$defaults = [
			'AllowSorting' => false,
			'AutoGenerateColumns' => true,
			'ShowHeader' => true,
			'ShowFooter' => false,
		];
		foreach ($defaults as $property => $default) {
			$grid = new TDataGrid();
			$getter = 'get' . $property;
			$setter = 'set' . $property;
			$this->assertSame($default, $grid->$getter(), $property);
			$grid->$setter($default ? 'false' : 'true');
			$this->assertSame(!$default, $grid->$getter(), $property);
			$grid->$setter($default);
			$this->assertSame($default, $grid->$getter(), $property);
		}
	}

	public function testHeaderFooterAndPagersDefaultToNull()
	{
		$grid = new TDataGrid();
		$this->assertNull($grid->getHeader());
		$this->assertNull($grid->getFooter());
		$this->assertNull($grid->getTopPager());
		$this->assertNull($grid->getBottomPager());
		$this->assertNull($grid->getSelectedItem());
		$this->assertNull($grid->getEditItem());
	}

	public function testEmptyTemplate()
	{
		$grid = new TDataGrid();
		$this->assertNull($grid->getEmptyTemplate());
		$template = new TDataGridTestTemplate();
		$grid->setEmptyTemplate($template);
		$this->assertSame($template, $grid->getEmptyTemplate());
		$grid->setEmptyTemplate(null);
		$this->assertNull($grid->getEmptyTemplate());
	}

	public function testEmptyTemplateInvalid()
	{
		$grid = new TDataGrid();
		try {
			$grid->setEmptyTemplate('template');
			$this->fail('Expected TInvalidDataTypeException');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('datagrid_template_required', $e->getErrorCode());
		}
	}

	// ================================================================================
	// Selected and edit items
	// ================================================================================

	public function testSelectedItemIndexWithoutItems()
	{
		$grid = new TDataGrid();
		$this->assertSame(-1, $grid->getSelectedItemIndex());
		$grid->setSelectedItemIndex('2');
		$this->assertSame(2, $grid->getSelectedItemIndex());
		$this->assertNull($grid->getSelectedItem());
		$grid->setSelectedItemIndex(-5);
		$this->assertSame(-1, $grid->getSelectedItemIndex());
	}

	public function testSelectedItemIndexUpdatesItemTypes()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(4));
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::Item, TListItemType::AlternatingItem], $this->itemTypes($grid));

		$grid->setSelectedItemIndex(1);
		$this->assertSame(TListItemType::SelectedItem, $grid->getItems()->itemAt(1)->getItemType());
		$this->assertSame($grid->getItems()->itemAt(1), $grid->getSelectedItem());

		$grid->setSelectedItemIndex(1);
		$this->assertSame(TListItemType::SelectedItem, $grid->getItems()->itemAt(1)->getItemType());

		$grid->setSelectedItemIndex(2);
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::AlternatingItem], $this->itemTypes($grid));

		$grid->setSelectedItemIndex(-1);
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::Item, TListItemType::AlternatingItem], $this->itemTypes($grid));
		$this->assertNull($grid->getSelectedItem());

		$grid->setSelectedItemIndex(10);
		$this->assertSame(10, $grid->getSelectedItemIndex());
		$this->assertNull($grid->getSelectedItem());
	}

	public function testSelectedItemIndexKeepsEditItem()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(3));
		$grid->setEditItemIndex(1);
		$grid->setSelectedItemIndex(1);
		$this->assertSame(TListItemType::EditItem, $grid->getItems()->itemAt(1)->getItemType());
		$grid->setSelectedItemIndex(0);
		$this->assertSame([TListItemType::SelectedItem, TListItemType::EditItem, TListItemType::Item], $this->itemTypes($grid));
	}

	public function testEditItemIndexWithoutItems()
	{
		$grid = new TDataGrid();
		$this->assertSame(-1, $grid->getEditItemIndex());
		$grid->setEditItemIndex('3');
		$this->assertSame(3, $grid->getEditItemIndex());
		$this->assertNull($grid->getEditItem());
		$grid->setEditItemIndex(-2);
		$this->assertSame(-1, $grid->getEditItemIndex());
	}

	public function testEditItemIndexUpdatesItemTypes()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(3));
		$grid->setEditItemIndex(2);
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::EditItem], $this->itemTypes($grid));
		$this->assertSame($grid->getItems()->itemAt(2), $grid->getEditItem());

		$grid->setEditItemIndex(2);
		$this->assertSame(TListItemType::EditItem, $grid->getItems()->itemAt(2)->getItemType());

		$grid->setEditItemIndex(1);
		$this->assertSame([TListItemType::Item, TListItemType::EditItem, TListItemType::Item], $this->itemTypes($grid));

		$grid->setEditItemIndex(-1);
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::Item], $this->itemTypes($grid));
		$this->assertNull($grid->getEditItem());
	}

	// ================================================================================
	// Data binding
	// ================================================================================

	public function testDataBindCreatesItems()
	{
		$grid = $this->createGrid();
		$log = $this->recordEvents($grid, ['OnItemCreated', 'OnItemDataBound']);
		$this->bind($grid, $this->rows(3));

		$this->assertSame(3, $grid->getItemCount());
		$this->assertSame(3, $grid->getViewState('ItemCount'));
		$this->assertSame(0, $grid->getViewState('DataSourceIndex'));
		$this->assertNull($grid->getTopPager());
		$this->assertNull($grid->getBottomPager());

		$header = $grid->getHeader();
		$this->assertSame(TListItemType::Header, $header->getItemType());
		$this->assertSame(-1, $header->getItemIndex());
		$this->assertSame(-1, $header->getDataSourceIndex());
		$this->assertInstanceOf(TTableHeaderCell::class, $header->getCells()->itemAt(0));
		$this->assertSame(['Name', 'City'], $this->cellTexts($header));

		$footer = $grid->getFooter();
		$this->assertSame(TListItemType::Footer, $footer->getItemType());
		$this->assertSame(['F-name', 'F-city'], $this->cellTexts($footer));

		foreach ($grid->getItems() as $index => $item) {
			$this->assertSame($index, $item->getItemIndex());
			$this->assertSame($index, $item->getDataSourceIndex());
			$this->assertSame($this->rows(3)[$index], $item->getData());
			$this->assertInstanceOf(TTableCell::class, $item->getCells()->itemAt(0));
			$this->assertSame(['n' . $index, 'c' . $index], $this->cellTexts($item));
		}

		$this->assertSame([$header, $grid->getItems()->itemAt(0), $grid->getItems()->itemAt(1), $grid->getItems()->itemAt(2), $footer], $grid->getControls()->toArray());
		$this->assertSame([0, 1, 2], $grid->getDataKeys()->toArray());

		$created = array_values(array_filter((array) $log, fn ($e) => $e[0] === 'OnItemCreated'));
		$bound = array_values(array_filter((array) $log, fn ($e) => $e[0] === 'OnItemDataBound'));
		$this->assertCount(5, $created);
		$this->assertCount(5, $bound);
		$this->assertSame($grid, $created[0][1]);
		$this->assertInstanceOf(TDataGridItemEventParameter::class, $created[0][2]);
		$this->assertSame($header, $created[0][2]->getItem());
		$this->assertSame($footer, $bound[4][2]->getItem());
	}

	public function testDataBindSelectedAndEditIndex()
	{
		$grid = $this->createGrid();
		$grid->setSelectedItemIndex(1);
		$grid->setEditItemIndex(2);
		$this->bind($grid, $this->rows(4));
		$this->assertSame([TListItemType::Item, TListItemType::SelectedItem, TListItemType::EditItem, TListItemType::AlternatingItem], $this->itemTypes($grid));
	}

	public function testDataBindDataKeyField()
	{
		$grid = $this->createGrid();
		$grid->setDataKeyField('id');
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['k0', 'k1', 'k2'], $grid->getDataKeys()->toArray());

		$grid->setDataKeyField('');
		$this->bind($grid, ['a' => ['name' => 'x', 'city' => 'y']]);
		$this->assertSame(['a'], $grid->getDataKeys()->toArray());
	}

	public function testColumnIdRegistersCell()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->getColumns()->add($this->createColumn('name', 'NameColumn'));
		$this->bind($grid, $this->rows(1));
		$item = $grid->getItems()->itemAt(0);
		$this->assertSame($item->getCells()->itemAt(0), $item->getRegisteredObject('NameColumn'));
		$this->assertSame($grid->getHeader()->getCells()->itemAt(0), $grid->getHeader()->getRegisteredObject('NameColumn'));
	}

	public function testDataBindWithoutColumns()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$this->bind($grid, $this->rows(2));
		$this->assertSame(0, $grid->getItemCount());
		$this->assertNull($grid->getHeader());
		$this->assertNull($grid->getFooter());
		$this->assertFalse($grid->getHasControls());
		$this->assertSame([], $grid->getDataKeys()->toArray());
	}

	public function testDataBindEmptyData()
	{
		$grid = $this->bind($this->createGrid(), []);
		$this->assertSame(0, $grid->getItemCount());
		$this->assertNull($grid->getHeader());
		$this->assertNull($grid->getFooter());
		$this->assertSame(0, $grid->getViewState('ItemCount'));
		$this->assertFalse($grid->getHasControls());
	}

	public function testDataBindEmptyTemplate()
	{
		$grid = $this->createGrid();
		$template = new TDataGridTestTemplate('Nothing here');
		$grid->setEmptyTemplate($template);
		$this->bind($grid, []);
		$this->assertSame(1, $template->getInstantiated());
		$this->assertTrue(PradoUnit::getProp($grid, '_useEmptyTemplate'));
		$this->assertSame(1, $grid->getControls()->getCount());
		$this->assertSame('Nothing here', $grid->getControls()->itemAt(0)->getText());

		$this->bind($grid, $this->rows(1));
		$this->assertFalse(PradoUnit::getProp($grid, '_useEmptyTemplate'));
		$this->assertSame(1, $template->getInstantiated());
	}

	public function testRebindReplacesItems()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(3));
		$this->bind($grid, $this->rows(1));
		$this->assertSame(1, $grid->getItemCount());
		$this->assertSame(3, $grid->getControls()->getCount());
	}

	public function testReset()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$this->bind($grid, $this->rows(2));
		$this->assertNotNull($grid->getTopPager());
		$grid->reset();
		$this->assertFalse($grid->getHasControls());
		$this->assertSame(0, $grid->getItemCount());
		$this->assertNull($grid->getHeader());
		$this->assertNull($grid->getFooter());
		$this->assertNull($grid->getTopPager());
		$this->assertNull($grid->getBottomPager());
		$this->assertFalse(PradoUnit::getProp($grid, '_useEmptyTemplate'));
	}

	// ================================================================================
	// Auto-generated columns
	// ================================================================================

	public function testAutoGenerateColumns()
	{
		$grid = new TDataGrid();
		$grid->getColumns()->add($this->createColumn('id'));
		$this->bind($grid, [['name' => 'a', 'city' => 'b', 'id' => 1], ['name' => 'c', 'city' => 'd', 'id' => 2]]);

		$autoColumns = $grid->getAutoColumns();
		$this->assertSame(3, $autoColumns->getCount());
		$this->assertSame(1, $grid->getColumns()->getCount());
		foreach (['name', 'city', 'id'] as $i => $key) {
			$column = $autoColumns->itemAt($i);
			$this->assertInstanceOf(TBoundColumn::class, $column);
			$this->assertSame($key, $column->getHeaderText());
			$this->assertSame($key, $column->getDataField());
			$this->assertSame($key, $column->getSortExpression());
			$this->assertSame($grid, $column->getOwner());
		}
		$this->assertSame(['Id', 'name', 'city', 'id'], $this->cellTexts($grid->getHeader()));
		$this->assertSame(['1', 'a', 'b', '1'], $this->cellTexts($grid->getItems()->itemAt(0)));
	}

	public function testAutoGenerateColumnsIntegerKeys()
	{
		$grid = $this->bind(new TDataGrid(), [['x', 'y']]);
		$autoColumns = $grid->getAutoColumns();
		$this->assertSame(2, $autoColumns->getCount());
		foreach ([0, 1] as $index) {
			$column = $autoColumns->itemAt($index);
			$this->assertSame(TListItemType::Item, $column->getHeaderText());
			$this->assertSame($index, $column->getDataField());
			$this->assertSame(TListItemType::Item, $column->getSortExpression());
		}
	}

	public function testAutoGenerateColumnsEmptyData()
	{
		$grid = new TDataGrid();
		$grid->getAutoColumns()->add(new TBoundColumn());
		$this->bind($grid, []);
		$this->assertSame(0, $grid->getAutoColumns()->getCount());
		$this->assertSame(0, $grid->getItemCount());
	}

	public function testCreateAutoColumnsWithoutDataSource()
	{
		$grid = new TDataGrid();
		$this->assertNull(PradoUnit::invoke($grid, 'createAutoColumns', null));
	}

	// ================================================================================
	// Paging
	// ================================================================================

	public function testPagingCreatesPagers()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(2);
		$grid->setCurrentPageIndex(1);
		$log = $this->recordEvents($grid, ['OnPagerCreated']);
		$this->bind($grid, $this->rows(5));

		$this->assertSame(3, $grid->getPageCount());
		$this->assertSame(2, $grid->getItemCount());
		$this->assertSame(2, $grid->getViewState('DataSourceIndex'));
		$this->assertSame(['n2', 'c2'], $this->cellTexts($grid->getItems()->itemAt(0)));
		$this->assertSame(0, $grid->getItems()->itemAt(0)->getItemIndex());
		$this->assertSame(2, $grid->getItems()->itemAt(0)->getDataSourceIndex());
		$this->assertSame(3, $grid->getItems()->itemAt(1)->getDataSourceIndex());

		$top = $grid->getTopPager();
		$bottom = $grid->getBottomPager();
		$this->assertInstanceOf(TDataGridPager::class, $top);
		$this->assertInstanceOf(TDataGridPager::class, $bottom);
		$this->assertNotSame($top, $bottom);
		$this->assertSame($grid, $top->getDataGrid());
		$this->assertSame($top, $grid->getControls()->itemAt(0));
		$this->assertSame($bottom, $grid->getControls()->itemAt($grid->getControls()->getCount() - 1));

		$this->assertCount(2, $log);
		$this->assertInstanceOf(TDataGridPagerEventParameter::class, $log[0][2]);
		$this->assertSame($top, $log[0][2]->getPager());
		$this->assertSame($bottom, $log[1][2]->getPager());
	}

	public function testPagingClampsCurrentPageIndex()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(2);
		$grid->setCurrentPageIndex(9);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(1, $grid->getCurrentPageIndex());
		$this->assertSame(1, $grid->getItemCount());
		$this->assertSame(['n2', 'c2'], $this->cellTexts($grid->getItems()->itemAt(0)));
	}

	public function testCustomPaging()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setAllowCustomPaging(true);
		$grid->setPageSize(2);
		$grid->setVirtualItemCount(7);
		$grid->setCurrentPageIndex(2);
		$this->bind($grid, $this->rows(2));
		$this->assertSame(4, $grid->getPageCount());
		$this->assertSame(2, $grid->getCurrentPageIndex());
		$this->assertSame(2, $grid->getItemCount());
		$this->assertSame(0, $grid->getItems()->itemAt(0)->getDataSourceIndex());
		$this->assertNotNull($grid->getTopPager());
	}

	public function testPagingWithoutPagedDataSource()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		PradoUnit::invoke($grid, 'performDataBinding', new \ArrayIterator($this->rows(2)));
		$this->assertSame(2, $grid->getItemCount());
		$this->assertNull($grid->getTopPager());
		$this->assertNull($grid->getBottomPager());
	}

	public function testNextPrevPagerFirstPage()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['L:&lt;&lt;', 'L:&lt;', 'B:&gt;|Page|Next', 'B:&gt;&gt;|Page|Last'], $this->pagerSummary($grid->getTopPager()));
		$this->assertSame($this->pagerSummary($grid->getTopPager()), $this->pagerSummary($grid->getBottomPager()));
	}

	public function testNextPrevPagerMiddlePage()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->setCurrentPageIndex(1);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['B:&lt;&lt;|Page|First', 'B:&lt;|Page|Previous', 'B:&gt;|Page|Next', 'B:&gt;&gt;|Page|Last'], $this->pagerSummary($grid->getTopPager()));
		$this->assertContains("\n", $grid->getTopPager()->getControls()->toArray());
	}

	public function testNextPrevPagerLastPage()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->setCurrentPageIndex(2);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['B:&lt;&lt;|Page|First', 'B:&lt;|Page|Previous', 'L:&gt;', 'L:&gt;&gt;'], $this->pagerSummary($grid->getTopPager()));
	}

	public function testNextPrevPagerWithoutFirstAndLastText()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$style = $grid->getPagerStyle();
		$style->setFirstPageText('');
		$style->setLastPageText('');
		$style->setNextPageText('Next');
		$style->setPrevPageText('Prev');
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['L:Prev', 'B:Next|Page|Next'], $this->pagerSummary($grid->getTopPager()));

		$grid->setCurrentPageIndex(2);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['B:Prev|Page|Previous', 'L:Next'], $this->pagerSummary($grid->getTopPager()));
	}

	public function testNextPrevPagerSinglePage()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$this->bind($grid, $this->rows(2));
		$this->assertSame(['L:&lt;&lt;', 'L:&lt;', 'L:&gt;', 'L:&gt;&gt;'], $this->pagerSummary($grid->getTopPager()));
	}

	public function testNextPrevPagerPushButtons()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->getPagerStyle()->setButtonType(TDataGridPagerButtonType::PushButton);
		$this->bind($grid, $this->rows(2));
		$this->assertSame(['P:<<|||0', 'P:<|||0', 'P:>|Page|Next|1', 'P:>>|Page|Last|1'], $this->pagerSummary($grid->getTopPager()));
		foreach ($grid->getTopPager()->getControls() as $control) {
			if ($control instanceof TButton) {
				$this->assertFalse($control->getCausesValidation());
			}
		}
	}

	public function testCreatePagerButton()
	{
		$grid = new TDataGrid();
		$pager = new TDataGridPager($grid);

		$label = PradoUnit::invoke($grid, 'createPagerButton', $pager, TDataGridPagerButtonType::LinkButton, false, 'Text', 'Page', '1');
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('Text', $label->getText());

		$link = PradoUnit::invoke($grid, 'createPagerButton', $pager, TDataGridPagerButtonType::LinkButton, true, 'Go', 'Page', '2');
		$this->assertInstanceOf(TLinkButton::class, $link);
		$this->assertSame('Go', $link->getText());
		$this->assertSame('Page', $link->getCommandName());
		$this->assertSame('2', $link->getCommandParameter());
		$this->assertFalse($link->getCausesValidation());

		$push = PradoUnit::invoke($grid, 'createPagerButton', $pager, TDataGridPagerButtonType::PushButton, true, 'Push', 'Page', '3');
		$this->assertInstanceOf(TButton::class, $push);
		$this->assertTrue($push->getEnabled());
		$this->assertSame('3', $push->getCommandParameter());

		$disabled = PradoUnit::invoke($grid, 'createPagerButton', $pager, TDataGridPagerButtonType::PushButton, false, 'Off', '', '');
		$this->assertFalse($disabled->getEnabled());
		$this->assertSame('Off', $disabled->getText());
	}

	private function numericPager(int $pageCount, int $currentPageIndex, int $buttonCount = 3, ?callable $configure = null): array
	{
		$grid = new TDataGrid();
		$grid->setViewState('PageCount', $pageCount);
		$grid->setCurrentPageIndex($currentPageIndex);
		$style = $grid->getPagerStyle();
		$style->setMode(TDataGridPagerMode::Numeric);
		$style->setPageButtonCount($buttonCount);
		if ($configure) {
			$configure($style);
		}
		$pager = new TDataGridPager($grid);
		PradoUnit::invoke($grid, 'buildPager', $pager);
		return $this->pagerSummary($pager);
	}

	public function testNumericPagerFirstWindow()
	{
		$this->assertSame(['L:1', 'B:2|Page|2', 'B:3|Page|3', 'B:&gt;|Page|4', 'B:&gt;&gt;|Page|Last'], $this->numericPager(10, 0));
	}

	public function testNumericPagerMiddleWindow()
	{
		$this->assertSame(
			['B:&lt;&lt;|Page|First', 'B:&lt;|Page|3', 'B:4|Page|4', 'L:5', 'B:6|Page|6', 'B:&gt;|Page|7', 'B:&gt;&gt;|Page|Last'],
			$this->numericPager(10, 4)
		);
	}

	public function testNumericPagerLastWindowIsFilled()
	{
		$this->assertSame(['B:&lt;&lt;|Page|First', 'B:&lt;|Page|7', 'B:8|Page|8', 'B:9|Page|9', 'L:10'], $this->numericPager(10, 9));
	}

	public function testNumericPagerFewerPagesThanButtons()
	{
		$this->assertSame(['B:1|Page|1', 'L:2'], $this->numericPager(2, 1, 10));
	}

	public function testNumericPagerStartIndexClampedToFirstPage()
	{
		$this->assertSame(['B:1|Page|1', 'B:2|Page|2', 'B:3|Page|3'], $this->numericPager(3, 5, 10));
	}

	public function testPagerEncodesOnlyDefaultLinkTexts()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->getPagerStyle()->setFirstPageText('<b>First</b>');
		$grid->getPagerStyle()->setNextPageText('Next &raquo;');
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['L:<b>First</b>', 'L:&lt;', 'B:Next &raquo;|Page|Next', 'B:&gt;&gt;|Page|Last'], $this->pagerSummary($grid->getTopPager()));
	}

	public function testEncodeDefaultPagerText()
	{
		$grid = new TDataGrid();
		foreach (['<' => '&lt;', '>' => '&gt;', '<<' => '&lt;&lt;', '>>' => '&gt;&gt;', '<b>' => '<b>', '3' => '3', '' => ''] as $text => $expected) {
			$this->assertSame($expected, PradoUnit::invoke($grid, 'encodeDefaultPagerText', (string) $text));
		}
	}

		public function testNumericPagerWithoutFirstAndLastText()
	{
		$summary = $this->numericPager(10, 4, 3, function ($style) {
			$style->setFirstPageText('');
			$style->setLastPageText('');
		});
		$this->assertSame(['B:&lt;|Page|3', 'B:4|Page|4', 'L:5', 'B:6|Page|6', 'B:&gt;|Page|7'], $summary);
	}

	public function testNumericPagerThroughDataBind()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->getPagerStyle()->setMode(TDataGridPagerMode::Numeric);
		$this->bind($grid, $this->rows(3));
		$this->assertSame(['L:1', 'B:2|Page|2', 'B:3|Page|3'], $this->pagerSummary($grid->getTopPager()));
	}

	// ================================================================================
	// Command bubbling
	// ================================================================================

	public function testBubbleEventIgnoresOtherParameters()
	{
		$grid = new TDataGrid();
		$log = $this->recordEvents($grid, ['OnItemCommand']);
		$this->assertFalse($grid->bubbleEvent($grid, new TCommandEventParameter('Edit', '')));
		$this->assertFalse($grid->bubbleEvent($grid, new TEventParameter()));
		$this->assertCount(0, $log);
	}

	public function testBubbleEventUnknownCommandRaisesItemCommandOnly()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(1));
		$item = $grid->getItems()->itemAt(0);
		$log = $this->recordEvents($grid, ['OnItemCommand', 'OnEditCommand']);
		$param = new TDataGridCommandEventParameter($item, $item, new TCommandEventParameter('Custom', 'p'));
		$this->assertFalse($grid->bubbleEvent($item, $param));
		$this->assertCount(1, $log);
		$this->assertSame(['OnItemCommand', $grid, $param], $log[0]);
	}

	public function testSelectCommandFromPagerSelectsNothing()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(2);
		$this->bind($grid, $this->rows(5));
		$log = $this->recordEvents($grid, ['OnItemCommand', 'OnSelectedIndexChanged']);
		$button = new TLinkButton();
		$grid->getTopPager()->getControls()->add($button);
		$button->onCommand(new TCommandEventParameter('Select', ''));
		$this->assertSame(-1, $grid->getSelectedItemIndex());
		$this->assertSame(['OnItemCommand'], array_column($log->getArrayCopy(), 0));
	}

		public function testItemCommandsRaiseEvents()
	{
		$commands = [
			'edit' => 'OnEditCommand',
			'Delete' => 'OnDeleteCommand',
			'UPDATE' => 'OnUpdateCommand',
			'Cancel' => 'OnCancelCommand',
		];
		foreach ($commands as $command => $event) {
			$grid = $this->bind($this->createGrid(), $this->rows(2));
			$log = $this->recordEvents($grid, ['OnItemCommand', $event]);
			$this->command($grid, $command, 'arg');
			$this->assertCount(2, $log, $command);
			$this->assertSame('OnItemCommand', $log[0][0]);
			$this->assertSame($event, $log[1][0]);
			$param = $log[1][2];
			$this->assertInstanceOf(TDataGridCommandEventParameter::class, $param);
			$this->assertSame($log[0][2], $param);
			$this->assertSame($command, $param->getCommandName());
			$this->assertSame('arg', $param->getCommandParameter());
			$this->assertSame($grid->getItems()->itemAt(0), $param->getItem());
			$this->assertInstanceOf(TLinkButton::class, $param->getCommandSource());
		}
	}

	public function testSelectCommand()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(2));
		$log = $this->recordEvents($grid, ['OnItemCommand', 'OnSelectedIndexChanged']);
		$item = $grid->getItems()->itemAt(1);
		$button = new TLinkButton();
		$item->getCells()->itemAt(1)->getControls()->add($button);
		$button->onCommand(new TCommandEventParameter('select', ''));
		$this->assertSame(1, $grid->getSelectedItemIndex());
		$this->assertSame(TListItemType::SelectedItem, $item->getItemType());
		$this->assertSame(['OnItemCommand', 'OnSelectedIndexChanged'], [$log[0][0], $log[1][0]]);
		$this->assertSame($item, $log[1][2]->getItem());
	}

	public function testSortCommand()
	{
		$grid = $this->createGrid();
		$grid->setAllowSorting(true);
		$grid->getColumns()->itemAt(0)->setSortExpression('name');
		$this->bind($grid, $this->rows(2));
		$log = $this->recordEvents($grid, ['OnItemCommand', 'OnSortCommand']);

		$sortButton = $grid->getHeader()->getCells()->itemAt(0)->getControls()->itemAt(0);
		$this->assertInstanceOf(TLinkButton::class, $sortButton);
		$this->assertFalse($grid->getHeader()->getCells()->itemAt(1)->getHasControls());
		$sortButton->onCommand(new TCommandEventParameter($sortButton->getCommandName(), $sortButton->getCommandParameter()));

		$this->assertCount(2, $log);
		$param = $log[1][2];
		$this->assertInstanceOf(TDataGridSortCommandEventParameter::class, $param);
		$this->assertSame('name', $param->getSortExpression());
		$this->assertSame($grid->getHeader(), $param->getCommandSource());
	}

	private function sortableGrid(): TDataGrid
	{
		$grid = $this->createGrid();
		$grid->setAllowSorting(true);
		$grid->getColumns()->itemAt(0)->setSortExpression('name');
		$grid->getColumns()->itemAt(1)->setSortExpression('city');
		return $this->bind($grid, $this->rows(2));
	}

	/**
	 * Clicks the sort button of a column. A handler applying the requested sort
	 * sets the sort state of the grid when `$apply` is true.
	 */
	private function sort(TDataGrid $grid, int $column, bool $apply = true): TDataGridSortCommandEventParameter
	{
		$log = new \ArrayObject();
		$grid->attachEventHandler('OnSortCommand', function ($sender, $param) use ($log, $apply) {
			$log[] = $param;
			if ($apply) {
				$sender->setSortExpression($param->getSortExpression());
				$sender->setSortDirection($param->getSortDirection());
			}
		});
		$button = $grid->getHeader()->getCells()->itemAt($column)->getControls()->itemAt(0);
		$button->onCommand(new TCommandEventParameter($button->getCommandName(), $button->getCommandParameter()));
		$grid->getEventHandlers('OnSortCommand')->clear();
		return $log[0];
	}

	public function testSortStateDefaults()
	{
		$grid = new TDataGrid();
		$this->assertSame('', $grid->getSortExpression());
		$this->assertSame(TDataGridSortDirection::Ascending, $grid->getSortDirection());
		$grid->setSortExpression('name');
		$grid->setSortDirection('descending');
		$this->assertSame('name', $grid->getSortExpression());
		$this->assertSame(TDataGridSortDirection::Descending, $grid->getSortDirection());
	}

	public function testSortDirectionInvalid()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TDataGrid())->setSortDirection('Sideways');
	}

	public function testSortCommandLeavesSortState()
	{
		$grid = $this->sortableGrid();
		$param = $this->sort($grid, 0, false);
		$this->assertSame(TDataGridSortDirection::Ascending, $param->getSortDirection());
		$this->assertSame('', $grid->getSortExpression());
		$this->assertSame(TDataGridSortDirection::Ascending, $this->sort($grid, 0, false)->getSortDirection());
	}

	public function testSortCommandReversesCurrentExpression()
	{
		$grid = $this->sortableGrid();
		$this->assertSame(TDataGridSortDirection::Ascending, $this->sort($grid, 0)->getSortDirection());
		$this->assertSame(TDataGridSortDirection::Descending, $this->sort($grid, 0)->getSortDirection());
		$this->assertSame(TDataGridSortDirection::Ascending, $this->sort($grid, 0)->getSortDirection());
		$this->assertSame('name', $grid->getSortExpression());
		$this->assertSame(TDataGridSortDirection::Ascending, $grid->getSortDirection());
	}

	public function testSortCommandNewExpressionSortsAscending()
	{
		$grid = $this->sortableGrid();
		$grid->setSortExpression('name');
		$grid->setSortDirection(TDataGridSortDirection::Descending);
		$param = $this->sort($grid, 1);
		$this->assertSame('city', $param->getSortExpression());
		$this->assertSame(TDataGridSortDirection::Ascending, $param->getSortDirection());
		$this->assertSame('city', $grid->getSortExpression());
	}

	public function testSortCommandFromDescendingSortsAscending()
	{
		$grid = $this->sortableGrid();
		$grid->setSortExpression('name');
		$grid->setSortDirection(TDataGridSortDirection::Descending);
		$this->assertSame(TDataGridSortDirection::Ascending, $this->sort($grid, 0)->getSortDirection());
	}

	public function testSortCommandEventParameterDirection()
	{
		$command = new TDataGridCommandEventParameter(null, null, new TCommandEventParameter('sort', 'name'));
		$this->assertSame(TDataGridSortDirection::Ascending, (new TDataGridSortCommandEventParameter(null, $command))->getSortDirection());
		$this->assertSame(TDataGridSortDirection::Descending, (new TDataGridSortCommandEventParameter(null, $command, 'Descending'))->getSortDirection());
		$this->expectException(TInvalidDataValueException::class);
		new TDataGridSortCommandEventParameter(null, $command, 'Up');
	}

	public function testPageCommands()
	{
		$cases = [
			'Next' => 2,
			'previous' => 0,
			'FIRST' => 0,
			'Last' => 4,
			'4' => 3,
		];
		foreach ($cases as $parameter => $expected) {
			$grid = $this->createGrid();
			$grid->setAllowPaging(true);
			$grid->setPageSize(1);
			$grid->setCurrentPageIndex(1);
			$this->bind($grid, $this->rows(5));
			$log = $this->recordEvents($grid, ['OnItemCommand', 'OnPageIndexChanged']);
			$this->command($grid, 'Page', (string) $parameter);
			$this->assertCount(2, $log, (string) $parameter);
			$param = $log[1][2];
			$this->assertInstanceOf(TDataGridPageChangedEventParameter::class, $param);
			$this->assertSame($expected, $param->getNewPageIndex(), (string) $parameter);
			$this->assertSame($grid->getItems()->itemAt(0), $param->getCommandSource());
			$this->assertSame(1, $grid->getCurrentPageIndex());
		}
	}

	public function testPagerButtonBubblesPageCommand()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$this->bind($grid, $this->rows(3));
		$log = $this->recordEvents($grid, ['OnItemCommand', 'OnPageIndexChanged']);
		$next = null;
		foreach ($grid->getBottomPager()->getControls() as $control) {
			if ($control instanceof TLinkButton && $control->getCommandParameter() === TDataGrid::CMD_PAGE_NEXT) {
				$next = $control;
			}
		}
		$next->onCommand(new TCommandEventParameter($next->getCommandName(), $next->getCommandParameter()));
		$this->assertCount(2, $log);
		$this->assertSame($grid->getBottomPager(), $log[0][2]->getItem());
		$this->assertSame($next, $log[0][2]->getCommandSource());
		$this->assertSame(1, $log[1][2]->getNewPageIndex());
		$this->assertSame($grid->getBottomPager(), $log[1][2]->getCommandSource());
	}

	public function testEventRaisersWithoutHandlers()
	{
		$grid = new TDataGrid();
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$param = new TDataGridCommandEventParameter($item, $item, new TCommandEventParameter('x', 'y'));
		$log = $this->recordEvents($grid, ['OnCancelCommand', 'OnDeleteCommand', 'OnEditCommand', 'OnItemCommand', 'OnUpdateCommand']);
		$grid->onCancelCommand($param);
		$grid->onDeleteCommand($param);
		$grid->onEditCommand($param);
		$grid->onItemCommand($param);
		$grid->onUpdateCommand($param);
		$this->assertSame(['OnCancelCommand', 'OnDeleteCommand', 'OnEditCommand', 'OnItemCommand', 'OnUpdateCommand'], array_column((array) $log, 0));

		$log = $this->recordEvents($grid, ['OnSortCommand', 'OnItemCreated', 'OnPagerCreated', 'OnItemDataBound', 'OnPageIndexChanged']);
		$grid->onSortCommand(new TDataGridSortCommandEventParameter($item, $param));
		$grid->onItemCreated(new TDataGridItemEventParameter($item));
		$grid->onPagerCreated(new TDataGridPagerEventParameter(new TDataGridPager($grid)));
		$grid->onItemDataBound(new TDataGridItemEventParameter($item));
		$grid->onPageIndexChanged(new TDataGridPageChangedEventParameter($item, 3));
		$this->assertSame(['OnSortCommand', 'OnItemCreated', 'OnPagerCreated', 'OnItemDataBound', 'OnPageIndexChanged'], array_column((array) $log, 0));
	}

	// ================================================================================
	// State
	// ================================================================================

	private function transferState(TDataGrid $source, TDataGrid $target): void
	{
		$source->saveState();
		$viewState = unserialize(serialize(PradoUnit::getProp($source, '_viewState')));
		PradoUnit::setProp($target, '_viewState', $viewState);
		$target->loadState();
	}

	public function testSaveStateWithoutCollections()
	{
		$grid = new TDataGrid();
		$grid->setViewState('ItemCount', 3);
		$grid->setViewState('AutoColumns', ['x']);
		$grid->setViewState('Columns', ['y']);
		$grid->saveState();
		$this->assertNull($grid->getViewState('ItemCount'));
		$this->assertNull($grid->getViewState('AutoColumns'));
		$this->assertNull($grid->getViewState('Columns'));
	}

	public function testSaveStateStoresColumnsAndItemCount()
	{
		$grid = $this->createGrid();
		$grid->getColumns()->itemAt(0)->setSortExpression('name');
		$this->bind($grid, $this->rows(2));
		$grid->saveState();
		$this->assertSame(2, $grid->getViewState('ItemCount'));
		$this->assertSame([], $grid->getViewState('AutoColumns', []));
		$columns = $grid->getViewState('Columns');
		$this->assertCount(2, $columns);
		$this->assertSame('name', $columns[0]['SortExpression']);
		$this->assertSame('city', $columns[1]['DataField']);
	}

	public function testSaveStateDisabledViewState()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(2));
		$grid->clearViewState('ItemCount');
		$grid->setEnableViewState(false);
		$grid->saveState();
		$this->assertNull($grid->getViewState('ItemCount'));
		$this->assertNull($grid->getViewState('Columns'));
	}

	public function testLoadStateRestoresGrid()
	{
		$source = $this->createGrid();
		$source->getColumns()->itemAt(0)->setSortExpression('name');
		$source->setSelectedItemIndex(1);
		$source->setEditItemIndex(2);
		$this->bind($source, $this->rows(4));

		$target = $this->createGrid([]);
		$target->getColumns()->add(new TBoundColumn());
		$target->getColumns()->add(new TBoundColumn());
		$log = $this->recordEvents($target, ['OnItemCreated', 'OnItemDataBound']);
		$this->transferState($source, $target);

		$this->assertSame('name', $target->getColumns()->itemAt(0)->getSortExpression());
		$this->assertSame('city', $target->getColumns()->itemAt(1)->getDataField());
		$this->assertSame(4, $target->getItemCount());
		$this->assertSame([TListItemType::Item, TListItemType::SelectedItem, TListItemType::EditItem, TListItemType::AlternatingItem], $this->itemTypes($target));
		$this->assertSame(TListItemType::Header, $target->getHeader()->getItemType());
		$this->assertSame(TListItemType::Footer, $target->getFooter()->getItemType());
		$this->assertSame(['Name', 'City'], $this->cellTexts($target->getHeader()));
		$this->assertSame(3, $target->getItems()->itemAt(3)->getDataSourceIndex());
		$this->assertNull($target->getItems()->itemAt(0)->getData());
		$this->assertNull($target->getTopPager());
		$this->assertNull($target->getAutoColumns()->getCount() ? 'auto' : null);
		$this->assertSame(6, count(array_filter((array) $log, fn ($e) => $e[0] === 'OnItemCreated')));
		$this->assertSame(0, count(array_filter((array) $log, fn ($e) => $e[0] === 'OnItemDataBound')));
	}

	public function testLoadStateColumnCountMismatchKeepsColumns()
	{
		$source = $this->createGrid();
		$this->bind($source, $this->rows(1));
		$target = $this->createGrid(['id']);
		$this->transferState($source, $target);
		$this->assertSame('id', $target->getColumns()->itemAt(0)->getDataField());
		$this->assertSame(1, $target->getItemCount());
		$this->assertSame(1, $target->getItems()->itemAt(0)->getCells()->getCount());
	}

	public function testLoadStateRestoresAutoColumns()
	{
		$source = new TDataGrid();
		$this->bind($source, [['name' => 'a', 'city' => 'b']]);
		$target = new TDataGrid();
		$this->transferState($source, $target);
		$autoColumns = PradoUnit::getProp($target, '_autoColumns');
		$this->assertInstanceOf(TDataGridColumnCollection::class, $autoColumns);
		$this->assertSame(2, $autoColumns->getCount());
		$this->assertInstanceOf(TBoundColumn::class, $autoColumns->itemAt(0));
		$this->assertSame('name', $autoColumns->itemAt(0)->getDataField());
		$this->assertSame('city', $autoColumns->itemAt(1)->getHeaderText());
		$this->assertSame(['name', 'city'], $this->cellTexts($target->getHeader()));
		$this->assertSame(1, $target->getItemCount());
	}

	public function testLoadStateRestoresAutoColumnsOfOverriddenClass()
	{
		$source = new TDataGridTestGrid();
		$this->bind($source, [['name' => 'a']]);
		$target = new TDataGridTestGrid();
		$this->transferState($source, $target);
		$this->assertSame('name', $target->getAutoColumns()->itemAt(0)->getDataField());
	}

	public function testLoadStateWithoutAutoColumnsClearsThem()
	{
		$target = $this->createGrid();
		$target->getAutoColumns()->add(new TBoundColumn());
		$target->loadState();
		$this->assertNull(PradoUnit::getProp($target, '_autoColumns'));
		$this->assertSame(0, $target->getItemCount());
		$this->assertNull($target->getHeader());
	}

	public function testLoadStateRestoresPagers()
	{
		$source = $this->createGrid();
		$source->setAllowPaging(true);
		$source->setPageSize(2);
		$source->setCurrentPageIndex(1);
		$this->bind($source, $this->rows(5));

		$target = $this->createGrid();
		$this->transferState($source, $target);
		$this->assertSame(2, $target->getItemCount());
		$this->assertSame(2, $target->getItems()->itemAt(0)->getDataSourceIndex());
		$this->assertInstanceOf(TDataGridPager::class, $target->getTopPager());
		$this->assertInstanceOf(TDataGridPager::class, $target->getBottomPager());
		$this->assertSame($this->pagerSummary($source->getTopPager()), $this->pagerSummary($target->getTopPager()));
	}

	public function testLoadStateRestoresEmptyTemplate()
	{
		$source = $this->createGrid();
		$this->bind($source, []);
		$target = $this->createGrid();
		$template = new TDataGridTestTemplate();
		$target->setEmptyTemplate($template);
		$this->transferState($source, $target);
		$this->assertSame(1, $template->getInstantiated());
		$this->assertTrue(PradoUnit::getProp($target, '_useEmptyTemplate'));
		$this->assertSame(0, $target->getItemCount());
		$this->assertNull($target->getHeader());
	}

	public function testLoadStateSkippedWhenDataBound()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(2));
		$grid->setViewState('ItemCount', 5);
		$grid->loadState();
		$this->assertSame(2, $grid->getItemCount());
	}

	public function testLoadStateSkippedWhenViewStateDisabled()
	{
		$grid = $this->createGrid();
		$grid->setViewState('ItemCount', 2);
		$grid->setEnableViewState(false);
		$grid->loadState();
		$this->assertSame(0, $grid->getItemCount());
		$this->assertNull($grid->getHeader());
	}

	public function testStateRecursiveRoundTrip()
	{
		$source = $this->createGrid();
		$this->bind($source, $this->rows(2));
		$state = PradoUnit::invoke($source, 'saveStateRecursive');
		$state = unserialize(serialize($state));

		$target = $this->createGrid();
		PradoUnit::reflectionMethod($target, 'loadStateRecursive')->invokeArgs($target, [&$state]);
		$this->assertSame(2, $target->getItemCount());
		$this->assertSame([TListItemType::Item, TListItemType::AlternatingItem], $this->itemTypes($target));
	}

	// ================================================================================
	// Cell grouping
	// ================================================================================

	public function testGroupCellsWithoutColumns()
	{
		$grid = new TDataGrid();
		PradoUnit::invoke($grid, 'groupCells');
		$this->assertSame(0, $grid->getItemCount());
	}

	public function testGroupCells()
	{
		$grid = $this->createGrid(['name', 'city']);
		$grid->getColumns()->itemAt(0)->setEnableCellGrouping(true);
		$this->bind($grid, [
			['name' => 'a', 'city' => 'x'],
			['name' => 'a', 'city' => 'x'],
			['name' => 'a', 'city' => 'x'],
			['name' => 'b', 'city' => 'x'],
			['name' => '', 'city' => 'x'],
			['name' => 'b', 'city' => 'x'],
			['name' => 'b', 'city' => 'x'],
		]);
		PradoUnit::invoke($grid, 'groupCells');
		$cells = [];
		foreach ($grid->getItems() as $item) {
			$cell = $item->getCells()->itemAt(0);
			$cells[] = [$cell->getRowSpan(), $cell->getVisible(false)];
			$this->assertSame(0, $item->getCells()->itemAt(1)->getRowSpan());
			$this->assertTrue($item->getCells()->itemAt(1)->getVisible(false));
		}
		$this->assertSame([[3, true], [0, false], [0, false], [0, true], [0, true], [2, true], [0, false]], $cells);
	}

	public function testGroupCellsSkipsHiddenCellsAndEditItems()
	{
		$grid = $this->createGrid(['name']);
		$grid->getColumns()->itemAt(0)->setEnableCellGrouping(true);
		$grid->setEditItemIndex(2);
		$this->bind($grid, [['name' => 'a'], ['name' => 'a'], ['name' => 'a'], ['name' => 'a'], ['name' => 'a']]);
		$grid->getItems()->itemAt(1)->getCells()->itemAt(0)->setVisible(false);
		PradoUnit::invoke($grid, 'groupCells');
		$rowSpans = [];
		$visible = [];
		foreach ($grid->getItems() as $item) {
			$rowSpans[] = $item->getCells()->itemAt(0)->getRowSpan();
			$visible[] = $item->getCells()->itemAt(0)->getVisible(false);
		}
		$this->assertSame([3, 0, 0, 0, 0], $rowSpans);
		$this->assertSame([true, false, true, false, false], $visible);
	}

	public function testGroupCellsUsesDataRendererText()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$column = $this->createColumn('name');
		$column->setItemRenderer(TLabel::class);
		$column->setEnableCellGrouping(true);
		$grid->getColumns()->add($column);
		$this->bind($grid, [['name' => 'a'], ['name' => 'a']]);
		$this->assertSame('', $grid->getItems()->itemAt(0)->getCells()->itemAt(0)->getText());
		PradoUnit::invoke($grid, 'groupCells');
		$this->assertSame(2, $grid->getItems()->itemAt(0)->getCells()->itemAt(0)->getRowSpan());
		$this->assertFalse($grid->getItems()->itemAt(1)->getCells()->itemAt(0)->getVisible(false));
	}

	public function testGetCellText()
	{
		$grid = new TDataGrid();
		$cell = new TTableCell();
		$this->assertSame('', PradoUnit::invoke($grid, 'getCellText', $cell));
		$cell->setText('text');
		$this->assertSame('text', PradoUnit::invoke($grid, 'getCellText', $cell));

		$cell = new TTableCell();
		$cell->getControls()->add(new TPanel());
		$this->assertSame('', PradoUnit::invoke($grid, 'getCellText', $cell));
		$label = new TLabel();
		$label->setText('label');
		$cell->getControls()->add($label);
		$this->assertSame('label', PradoUnit::invoke($grid, 'getCellText', $cell));
	}

	// ================================================================================
	// Item styles
	// ================================================================================

	public function testApplyItemStylesWithoutStyles()
	{
		$grid = $this->bind($this->createGrid(), $this->rows(2));
		PradoUnit::invoke($grid, 'applyItemStyles');
		foreach ($grid->getItems() as $item) {
			$this->assertFalse($item->getHasStyle());
		}
		$this->assertTrue($grid->getHeader()->getVisible(false));
		$this->assertFalse($grid->getFooter()->getVisible(false));
	}

	public function testApplyItemStylesInheritance()
	{
		$grid = $this->createGrid();
		$grid->getItemStyle()->setForeColor('red');
		$grid->getAlternatingItemStyle()->setBackColor('blue');
		$grid->getSelectedItemStyle()->setBorderColor('green');
		$grid->getSelectedItemStyle()->setCssClass('selected');
		$grid->getEditItemStyle()->setCssClass('edit');
		$grid->getHeaderStyle()->setCssClass('header');
		$grid->getFooterStyle()->setCssClass('footer');
		$grid->setShowFooter(true);
		$grid->setSelectedItemIndex(2);
		$grid->setEditItemIndex(3);
		$this->bind($grid, $this->rows(5));
		PradoUnit::invoke($grid, 'applyItemStyles');

		$items = $grid->getItems();
		$this->assertSame('header', $grid->getHeader()->getStyle()->getCssClass());
		$this->assertSame('footer', $grid->getFooter()->getStyle()->getCssClass());
		$this->assertTrue($grid->getFooter()->getVisible(false));

		$this->assertSame('red', $items->itemAt(0)->getStyle()->getForeColor());
		$this->assertSame('', $items->itemAt(0)->getStyle()->getBackColor());

		$this->assertSame('blue', $items->itemAt(1)->getStyle()->getBackColor());
		$this->assertSame('red', $items->itemAt(1)->getStyle()->getForeColor());

		// item 2 has an even item index and merges ItemStyle
		$selected = $items->itemAt(2)->getStyle();
		$this->assertSame('selected', $selected->getCssClass());
		$this->assertSame('red', $selected->getForeColor());
		$this->assertSame('', $selected->getBackColor());

		// item 3 has an odd item index and merges AlternatingItemStyle
		$edit = $items->itemAt(3)->getStyle();
		$this->assertSame('edit', $edit->getCssClass());
		$this->assertSame('green', $edit->getBorderColor());
		$this->assertSame('blue', $edit->getBackColor());
		$this->assertSame('red', $edit->getForeColor());
	}

	public function testApplyItemStylesSelectedOddEditEvenControlIndex()
	{
		$grid = $this->createGrid();
		$grid->getItemStyle()->setForeColor('red');
		$grid->getSelectedItemStyle()->setCssClass('selected');
		$grid->setSelectedItemIndex(1);
		$grid->setEditItemIndex(0);
		$this->bind($grid, $this->rows(2));
		PradoUnit::invoke($grid, 'applyItemStyles');
		$items = $grid->getItems();
		// AlternatingItemStyle falls back to ItemStyle; EditItemStyle falls back to SelectedItemStyle
		$this->assertSame('selected', $items->itemAt(0)->getStyle()->getCssClass());
		$this->assertSame('red', $items->itemAt(0)->getStyle()->getForeColor());
		$this->assertSame('selected', $items->itemAt(1)->getStyle()->getCssClass());
		$this->assertSame('red', $items->itemAt(1)->getStyle()->getForeColor());
	}

	public function testApplyItemStylesWithPagerUsesItemIndex()
	{
		foreach ([TDataGridPagerPosition::Bottom, TDataGridPagerPosition::Top] as $position) {
			foreach ([0 => 'item', 1 => 'alt'] as $index => $class) {
				$grid = $this->createGrid();
				$grid->setAllowPaging(true);
				$grid->setPageSize(4);
				$grid->getPagerStyle()->setPosition($position);
				$grid->getItemStyle()->setCssClass('item');
				$grid->getAlternatingItemStyle()->setCssClass('alt');
				$grid->getSelectedItemStyle()->setForeColor('red');
				$grid->getEditItemStyle()->setBackColor('blue');
				$grid->setSelectedItemIndex($index);
				$grid->setEditItemIndex(1 - $index);
				$this->bind($grid, $this->rows(6));
				PradoUnit::invoke($grid, 'applyItemStyles');
				$items = $grid->getItems();
				$this->assertSame('red', $items->itemAt($index)->getStyle()->getForeColor());
				$this->assertSame($class, $items->itemAt($index)->getStyle()->getCssClass(), "$position selected $index");
				$this->assertSame('blue', $items->itemAt(1 - $index)->getStyle()->getBackColor());
				$this->assertSame($index ? 'item' : 'alt', $items->itemAt(1 - $index)->getStyle()->getCssClass(), "$position edit " . (1 - $index));
			}
		}
	}

		public function testApplyItemStylesWithoutItemStyle()
	{
		$grid = $this->createGrid();
		$grid->getEditItemStyle()->setCssClass('edit');
		$grid->setSelectedItemIndex(0);
		$grid->setEditItemIndex(1);
		$this->bind($grid, $this->rows(3));
		PradoUnit::invoke($grid, 'applyItemStyles');
		$items = $grid->getItems();
		$this->assertSame('', $items->itemAt(0)->getStyle()->getCssClass());
		$this->assertSame('edit', $items->itemAt(1)->getStyle()->getCssClass());
		$this->assertSame('', $items->itemAt(2)->getStyle()->getCssClass());
	}

	public function testApplyItemStylesHidesHeader()
	{
		$grid = $this->createGrid();
		$grid->setShowHeader(false);
		$this->bind($grid, $this->rows(1));
		PradoUnit::invoke($grid, 'applyItemStyles');
		$this->assertFalse($grid->getHeader()->getVisible(false));
	}

	public function testApplyItemStylesColumnStyles()
	{
		$grid = $this->createGrid(['name', 'city', 'id']);
		$name = $grid->getColumns()->itemAt(0);
		$name->getHeaderStyle()->setCssClass('h');
		$name->getFooterStyle()->setCssClass('f');
		$name->getItemStyle()->setCssClass('i');
		$grid->getColumns()->itemAt(1)->setVisible(false);
		$grid->setShowFooter(true);
		$this->bind($grid, $this->rows(2));
		PradoUnit::invoke($grid, 'applyItemStyles');

		$this->assertSame('h', $grid->getHeader()->getCells()->itemAt(0)->getStyle()->getCssClass());
		$this->assertSame('f', $grid->getFooter()->getCells()->itemAt(0)->getStyle()->getCssClass());
		foreach ($grid->getItems() as $item) {
			$this->assertSame('i', $item->getCells()->itemAt(0)->getStyle()->getCssClass());
			$this->assertFalse($item->getCells()->itemAt(1)->getVisible(false));
			$this->assertFalse($item->getCells()->itemAt(2)->getHasStyle());
		}
		$this->assertFalse($grid->getHeader()->getCells()->itemAt(1)->getVisible(false));
	}

	public function testApplyItemStylesPagerPosition()
	{
		$positions = [
			TDataGridPagerPosition::Bottom => [false, true],
			TDataGridPagerPosition::Top => [true, false],
			TDataGridPagerPosition::TopAndBottom => [true, true],
		];
		foreach ($positions as $position => $expected) {
			$grid = $this->createGrid();
			$grid->setAllowPaging(true);
			$grid->getPagerStyle()->setPosition($position);
			$grid->getPagerStyle()->setCssClass('pager');
			$this->bind($grid, $this->rows(2));
			PradoUnit::invoke($grid, 'applyItemStyles');
			$this->assertSame($expected, [$grid->getTopPager()->getVisible(false), $grid->getBottomPager()->getVisible(false)], $position);
			$this->assertSame('pager', $grid->getTopPager()->getStyle()->getCssClass());
		}
	}

	public function testApplyItemStylesPagerHidden()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->getPagerStyle()->setPosition(TDataGridPagerPosition::TopAndBottom);
		$grid->getPagerStyle()->setVisible(false);
		$this->bind($grid, $this->rows(2));
		PradoUnit::invoke($grid, 'applyItemStyles');
		$this->assertFalse($grid->getTopPager()->getVisible(false));
		$this->assertFalse($grid->getBottomPager()->getVisible(false));
	}

	public function testApplyItemStylesPagerWithoutPagerStyle()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$this->bind($grid, $this->rows(2));
		$grid->clearViewState('PagerStyle');
		PradoUnit::invoke($grid, 'applyItemStyles');
		$this->assertTrue($grid->getTopPager()->getVisible(false));
		$this->assertTrue($grid->getBottomPager()->getVisible(false));
	}

	public function testApplyItemStylesSeparatorAndOtherControls()
	{
		$grid = $this->bind(new TDataGrid(), [['name' => 'a']]);
		$separator = new TDataGridItem(-1, -1, TListItemType::Separator);
		$grid->getControls()->add($separator);
		$grid->getControls()->add(new TPanel());
		$separatorStyle = new TTableItemStyle();
		$separatorStyle->setCssClass('sep');
		$grid->setViewState('SeparatorStyle', $separatorStyle);
		PradoUnit::invoke($grid, 'applyItemStyles');
		$this->assertSame('sep', $separator->getStyle()->getCssClass());

		$other = new TDataGridItem(-1, -1, TListItemType::Separator);
		$custom = new TDataGridTestCustomItem(-1, -1, TListItemType::Item);
		$grid->getControls()->add($other);
		$grid->getControls()->add($custom);
		$grid->clearViewState('SeparatorStyle');
		PradoUnit::invoke($grid, 'applyItemStyles');
		$this->assertFalse($other->getHasStyle());
		$this->assertFalse($custom->getHasStyle());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderWithoutControls()
	{
		$this->assertSame('', $this->render(new TDataGrid()));
	}

	public function testRenderWithControlsButNoItems()
	{
		$grid = new TDataGrid();
		$grid->getControls()->add(new TPanel());
		$this->assertSame('', $this->render($grid));
	}

	public function testRenderTable()
	{
		$grid = $this->createGrid();
		$grid->setCssClass('grid');
		$this->bind($grid, $this->rows(2));
		$this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertStringStartsWith('<table id="Grid" class="grid"', $output);
		$this->assertStringEndsWith('</table>', $output);
		$this->assertStringNotContainsString('<caption', $output);
		$this->assertMatchesRegularExpression('#<thead>\s*<tr>\s*<th[^>]*>Name</th>\s*<th[^>]*>City</th>\s*</tr></thead>#', $output);
		$this->assertMatchesRegularExpression('#<tbody>\s*<tr>\s*<td>n0</td>\s*<td>c0</td>\s*</tr><tr>\s*<td>n1</td>\s*<td>c1</td>\s*</tr></tbody>#', $output);
		$this->assertStringNotContainsString('<tfoot', $output);
	}

	public static function ariaSortProvider(): array
	{
		return [
			'unsorted' => ['', TDataGridSortDirection::Ascending, [null, null]],
			'ascending' => ['name', TDataGridSortDirection::Ascending, ['ascending', null]],
			'descending' => ['city', TDataGridSortDirection::Descending, [null, 'descending']],
			'no column' => ['id', TDataGridSortDirection::Ascending, [null, null]],
		];
	}

	/**
	 * @dataProvider ariaSortProvider
	 */
	public function testRenderAriaSort(string $expression, string $direction, array $expected)
	{
		$grid = $this->sortableGrid();
		$grid->setSortExpression($expression);
		$grid->setSortDirection($direction);
		$this->attachToPage($grid);
		$output = $this->render($grid);
		preg_match_all('#<th(?=[\s>])([^>]*)>#', $output, $matches);
		$this->assertCount(2, $matches[1]);
		foreach ($expected as $i => $sort) {
			if ($sort === null) {
				$this->assertStringNotContainsString('aria-sort', $matches[1][$i]);
			} else {
				$this->assertStringContainsString('aria-sort="' . $sort . '"', $matches[1][$i]);
			}
		}
	}

	public function testRenderAriaSortFollowsSortChange()
	{
		$grid = $this->sortableGrid();
		$this->attachToPage($grid);
		$grid->setSortExpression('name');
		$this->assertMatchesRegularExpression('#<th aria-sort="ascending"[^>]*>.*?Name#s', $this->render($grid));
		$grid->setSortExpression('city');
		$output = $this->render($grid);
		$this->assertSame(1, substr_count($output, 'aria-sort'));
		$this->assertMatchesRegularExpression('#<th aria-sort="ascending"[^>]*>.*?City#s', $output);
	}

	public function testRenderAriaSortRequiresAllowSorting()
	{
		$grid = $this->createGrid();
		$grid->getColumns()->itemAt(0)->setSortExpression('name');
		$grid->setSortExpression('name');
		$this->bind($grid, $this->rows(1));
		$this->attachToPage($grid);
		$this->assertStringNotContainsString('aria-sort', $this->render($grid));
	}

	public function testRenderAriaSortOnAutoColumn()
	{
		$grid = new TDataGrid();
		$grid->setID('Grid');
		$grid->setAllowSorting(true);
		$grid->setSortExpression('city');
		$grid->setSortDirection(TDataGridSortDirection::Descending);
		$this->bind($grid, $this->rows(1));
		$this->attachToPage($grid);
		preg_match_all('#<th(?=[\s>])([^>]*)>#', $this->render($grid), $matches);
		$this->assertSame([false, false, true], array_map(fn ($attributes) => str_contains($attributes, 'aria-sort="descending"'), $matches[1]));
	}

	public function testRenderFooterCaptionAndSectionStyles()
	{
		$grid = $this->createGrid();
		$grid->setShowFooter(true);
		$grid->setCaption('Cities');
		$grid->setCaptionAlign(TTableCaptionAlign::Bottom);
		$grid->getTableHeadStyle()->setCssClass('thead');
		$grid->getTableBodyStyle()->setCssClass('tbody');
		$grid->getTableFootStyle()->setCssClass('tfoot');
		$this->bind($grid, $this->rows(1));
		$this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertStringContainsString('<caption style="caption-side:bottom;">Cities</caption>', $output);
		$this->assertStringContainsString('<thead class="thead">', $output);
		$this->assertStringContainsString('<tbody class="tbody">', $output);
		$this->assertMatchesRegularExpression('#<tfoot class="tfoot">\s*<tr>\s*<td>F-name</td>\s*<td>F-city</td>\s*</tr></tfoot>#', $output);
	}

	public function testRenderCaptionWithoutAlign()
	{
		$grid = new TDataGrid();
		$grid->setCaption('Plain');
		$this->assertStringContainsString('<caption>Plain</caption>', $this->renderBeginTag($grid));
	}

	public function testRenderHiddenHeader()
	{
		$grid = $this->createGrid();
		$grid->setShowHeader(false);
		$this->bind($grid, $this->rows(1));
		$this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertStringNotContainsString('<thead', $output);
		$this->assertStringContainsString('<tbody>', $output);
	}

	public function testRenderPagers()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$grid->setPageSize(1);
		$grid->getPagerStyle()->setPosition(TDataGridPagerPosition::TopAndBottom);
		$this->bind($grid, $this->rows(2));
		$page = $this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertSame(2, substr_count($output, '<div'));
		$this->assertLessThan(strpos($output, '<table'), strpos($output, '<div'));
		$this->assertGreaterThan(strrpos($output, '</table>'), strrpos($output, '<div'));
		$this->assertSame(2, substr_count($output, '<span>&lt;</span>'));
		$this->assertSame(4, count($page->getClientScript()->getPostBackControls()));
	}

	public function testRenderBottomPagerOnly()
	{
		$grid = $this->createGrid();
		$grid->setAllowPaging(true);
		$this->bind($grid, $this->rows(2));
		$this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertStringStartsWith("\n<table", $output);
		$this->assertSame(1, substr_count($output, '<div'));
		$this->assertGreaterThan(strrpos($output, '</table>'), strpos($output, '<div'));
	}

	public function testRenderEmptyTemplate()
	{
		$grid = $this->createGrid();
		$grid->setEmptyTemplate(new TDataGridTestTemplate('Empty!'));
		$grid->setCssClass('grid');
		$grid->setToolTip('tip');
		$this->bind($grid, []);
		$this->attachToPage($grid);
		$this->assertSame('<span id="Grid" title="tip" class="grid">Empty!</span>', $this->render($grid));
	}

	public function testRenderEmptyTemplateWithoutStyle()
	{
		$grid = $this->createGrid();
		$grid->setEmptyTemplate(new TDataGridTestTemplate('Empty!'));
		$this->bind($grid, []);
		$this->attachToPage($grid);
		$this->assertSame('<span id="Grid">Empty!</span>', $this->render($grid));
	}

	public function testRenderAppliesCellGrouping()
	{
		$grid = $this->createGrid(['city']);
		$grid->getColumns()->itemAt(0)->setEnableCellGrouping(true);
		$this->bind($grid, [['city' => 'x'], ['city' => 'x']]);
		$this->attachToPage($grid);
		$output = $this->render($grid);
		$this->assertStringContainsString('<td rowspan="2">x</td>', $output);
		$this->assertSame(1, substr_count($output, '>x</td>'));
	}
}
