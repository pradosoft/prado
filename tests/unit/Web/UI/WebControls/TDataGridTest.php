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

	// ================================================================================
	// Properties
	// ================================================================================

	// ================================================================================
	// Selected and edit items
	// ================================================================================

	// ================================================================================
	// Data binding
	// ================================================================================

	// ================================================================================
	// Auto-generated columns
	// ================================================================================

	// ================================================================================
	// Paging
	// ================================================================================

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

	// ================================================================================
	// Command bubbling
	// ================================================================================

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

	// ================================================================================
	// Cell grouping
	// ================================================================================

	// ================================================================================
	// Item styles
	// ================================================================================

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

	// ================================================================================
	// Rendering
	// ================================================================================

}
