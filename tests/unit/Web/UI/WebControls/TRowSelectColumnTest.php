<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TRowSelectColumn;

/**
 * Client script manager that records the registered scripts and postback controls.
 */
class TRowSelectColumnTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];
	public array $postBackControls = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->postBackControls[] = [$class, $options];
	}
}

class TRowSelectColumnTest extends TestCase
{
	use TWebControlRenderTrait;

	private function rows(int $count): array
	{
		$rows = [];
		for ($i = 0; $i < $count; $i++) {
			$rows[] = ['id' => 'k' . $i, 'name' => 'n' . $i];
		}
		return $rows;
	}

	private function createGrid(?TRowSelectColumn $column = null): TDataGrid
	{
		$grid = new TDataGrid();
		$grid->setID('Grid');
		$grid->setAutoGenerateColumns(false);
		$grid->setDataKeyField('id');
		$grid->getColumns()->add($column ?? new TRowSelectColumn());
		$name = new TBoundColumn();
		$name->setDataField('name');
		$name->setHeaderText('Name');
		$grid->getColumns()->add($name);
		return $grid;
	}

	private function bind(TDataGrid $grid, int $count): TPage
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TRowSelectColumnTestClientScript($page));
		$page->getControls()->add($grid);
		$grid->setDataSource($this->rows($count));
		$grid->dataBind();
		return $page;
	}

	private function selectAllCheckBox(TDataGrid $grid): ?TCheckBox
	{
		return $grid->getHeader()->getCells()->itemAt(0)->getRegisteredObject('CheckBox');
	}

	public function testExtendsTDataGridColumn()
	{
		$this->assertInstanceOf(TDataGridColumn::class, new TRowSelectColumn());
	}

	public function testDefaults()
	{
		$column = new TRowSelectColumn();
		$this->assertSame('', $column->getDataTextField());
		$this->assertSame('', $column->getSelectAllText());
		$this->assertTrue($column->getShowSelectAll());
		$this->assertSame([], $column->getRowCheckBoxes());
		$this->assertSame([], $column->getSelectedIndices());
		$this->assertSame([], $column->getSelectedKeys());
	}

	public function testSetters()
	{
		$column = new TRowSelectColumn();
		$column->setDataTextField('name');
		$column->setSelectAllText('All cities');
		$column->setShowSelectAll('false');
		$this->assertSame('name', $column->getDataTextField());
		$this->assertSame('All cities', $column->getSelectAllText());
		$this->assertFalse($column->getShowSelectAll());
	}

	public function testHeaderHoldsNamedSelectAllCheckBox()
	{
		$grid = $this->createGrid();
		$this->bind($grid, 2);
		$checkBox = $this->selectAllCheckBox($grid);
		$this->assertInstanceOf(TCheckBox::class, $checkBox);
		$this->assertSame('Select all rows', $checkBox->getInputAttributes()->itemAt('aria-label'));
	}

	public function testSelectAllText()
	{
		$column = new TRowSelectColumn();
		$column->setSelectAllText('Select every city');
		$grid = $this->createGrid($column);
		$this->bind($grid, 1);
		$this->assertSame('Select every city', $this->selectAllCheckBox($grid)->getInputAttributes()->itemAt('aria-label'));
	}

	public function testShowSelectAllFalseShowsHeaderText()
	{
		$column = new TRowSelectColumn();
		$column->setShowSelectAll(false);
		$column->setHeaderText('Pick');
		$grid = $this->createGrid($column);
		$this->bind($grid, 1);
		$this->assertNull($this->selectAllCheckBox($grid));
		$this->assertSame('Pick', $grid->getHeader()->getCells()->itemAt(0)->getText());
	}

	public function testRowCheckBoxesNamedByRowNumber()
	{
		$column = new TRowSelectColumn();
		$grid = $this->createGrid($column);
		$this->bind($grid, 2);
		$checkBoxes = $column->getRowCheckBoxes();
		$this->assertSame([0, 1], array_keys($checkBoxes));
		$this->assertSame('Select row 1', $checkBoxes[0]->getInputAttributes()->itemAt('aria-label'));
		$this->assertSame('Select row 2', $checkBoxes[1]->getInputAttributes()->itemAt('aria-label'));
	}

	public function testRowCheckBoxesNamedByDataTextField()
	{
		$column = new TRowSelectColumn();
		$column->setDataTextField('name');
		$grid = $this->createGrid($column);
		$this->bind($grid, 2);
		$checkBoxes = $column->getRowCheckBoxes();
		$this->assertSame('Select n0', $checkBoxes[0]->getInputAttributes()->itemAt('aria-label'));
		$this->assertSame('Select n1', $checkBoxes[1]->getInputAttributes()->itemAt('aria-label'));
	}

	public function testItemCheckBoxIsRegisteredOnTheCell()
	{
		$column = new TRowSelectColumn();
		$grid = $this->createGrid($column);
		$this->bind($grid, 1);
		$cell = $grid->getItems()->itemAt(0)->getCells()->itemAt(0);
		$this->assertSame($column->getRowCheckBoxes()[0], $cell->getRegisteredObject('CheckBox'));
	}

	public function testSelectedIndicesAndKeys()
	{
		$column = new TRowSelectColumn();
		$grid = $this->createGrid($column);
		$this->bind($grid, 3);
		$checkBoxes = $column->getRowCheckBoxes();
		$checkBoxes[0]->setChecked(true);
		$checkBoxes[2]->setChecked(true);
		$this->assertSame([0, 2], $column->getSelectedIndices());
		$this->assertSame(['k0', 'k2'], $column->getSelectedKeys());
	}

	public function testSelectedKeysWithoutDataKeyFieldAreDataSourceKeys()
	{
		$column = new TRowSelectColumn();
		$grid = $this->createGrid($column);
		$grid->setDataKeyField('');
		$page = new TPage();
		$page->getControls()->add($grid);
		$grid->setDataSource(['a' => ['name' => 'x'], 'b' => ['name' => 'y']]);
		$grid->dataBind();
		$column->getRowCheckBoxes()[1]->setChecked(true);
		$this->assertSame([1], $column->getSelectedIndices());
		$this->assertSame(['b'], $column->getSelectedKeys());
	}

	public function testRegisterClientScript()
	{
		$column = new TRowSelectColumn();
		$grid = $this->createGrid($column);
		$page = $this->bind($grid, 2);
		$selectAll = $this->selectAllCheckBox($grid);
		$selectAll->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertSame(['rowselect'], $cs->pradoScripts);
		$ids = [];
		foreach ($column->getRowCheckBoxes() as $checkBox) {
			$ids[] = $checkBox->getClientID();
		}
		$this->assertCount(2, $ids);
		$this->assertSame([['Prado.WebUI.TRowSelectColumn', ['ID' => $selectAll->getClientID(), 'CheckBoxIDs' => $ids]]], $cs->postBackControls);
	}

	public function testRenderedCheckBoxesCarryNames()
	{
		$grid = $this->createGrid();
		$this->bind($grid, 1);
		$output = $this->render($grid);
		$this->assertMatchesRegularExpression('#<th><input [^>]*type="checkbox"[^>]*aria-label="Select all rows"#', $output);
		$this->assertMatchesRegularExpression('#<td><input [^>]*type="checkbox"[^>]*aria-label="Select row 1"#', $output);
	}
}
