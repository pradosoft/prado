<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActiveTableCell;
use Prado\Web\UI\ActiveControls\TActiveTableCellEventParameter;
use Prado\Web\UI\ActiveControls\TActiveTableRow;
use Prado\Web\UI\ActiveControls\TActiveTableRowEventParameter;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TTable;
use Prado\Web\UI\WebControls\TTableCell;

/**
 * TActiveTableCell and TActiveTableRow raise their selection events in a callback,
 * with the cell index and the row index, and render in a callback.
 */
class TActiveTableCellTest extends TestCase
{
	/**
	 * Returns a callback page holding a table Grid of two active rows, R0 and R1, each
	 * of two active cells, C00 to C11.
	 */
	private function createTable(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$table = new TTable();
		for ($r = 0; $r < 2; $r++) {
			$row = new TActiveTableRow();
			$row->setID('R' . $r);
			$table->getRows()->add($row);
			for ($c = 0; $c < 2; $c++) {
				$cell = new TActiveTableCell();
				$cell->setID('C' . $r . $c);
				$row->getCells()->add($cell);
			}
		}
		$page->addControl($table, 'Grid', $stage);
		foreach ($table->getRows() as $row) {
			PradoUnit::invoke($row, 'setControlStage', $stage);
			foreach ($row->getCells() as $cell) {
				PradoUnit::invoke($cell, 'setControlStage', $stage);
			}
		}
		return [$page, $table];
	}

	private function writer(): THtmlWriter
	{
		return new THtmlWriter(new TTextWriter());
	}

	public function testClientSide()
	{
		foreach ([new TActiveTableCell(), new TActiveTableRow()] as $control) {
			$this->assertInstanceOf(TCallbackClientSide::class, $control->getClientSide());
			$this->assertSame($control->getActiveControl()->getClientSide(), $control->getClientSide());
		}
	}

	public function testEventParametersCarryTheIndex()
	{
		$cell = new TActiveTableCellEventParameter(null, 'p', 3);
		$this->assertSame(3, $cell->getSelectedCellIndex());
		$this->assertSame('p', $cell->getCallbackParameter());
		$row = new TActiveTableRowEventParameter(null, 'q', 2);
		$this->assertSame(2, $row->getSelectedRowIndex());
		$this->assertSame('q', $row->getCallbackParameter());
	}

	public function testCellAndRowIndexes()
	{
		[, $table] = $this->createTable();
		$rows = $table->getRows();
		$this->assertSame(1, $rows->itemAt(1)->getRowIndex());
		$this->assertSame(0, $rows->itemAt(0)->getRowIndex());
		$this->assertSame(1, $rows->itemAt(1)->getCells()->itemAt(1)->getCellIndex());
		$this->assertSame($rows->itemAt(1), $rows->itemAt(1)->getCells()->itemAt(0)->getRow());
		$this->assertSame($table, $rows->itemAt(0)->getTable());
	}

	public function testCellCallbackRaisesCellSelectedAndBubblesToTheRow()
	{
		[, $table] = $this->createTable();
		$row = $table->getRows()->itemAt(1);
		$cell = $row->getCells()->itemAt(1);
		$events = [];
		$cell->attachEventHandler('OnCellSelected', function ($sender, $param) use (&$events) {
			$events[] = ['cell', $param->getSelectedCellIndex(), $param->getCallbackParameter()];
		});
		$row->attachEventHandler('OnRowSelected', function ($sender, $param) use (&$events) {
			$events[] = ['row', $param->getSelectedRowIndex(), $param->getCallbackParameter()];
		});
		$cell->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame([['cell', 1, 'p'], ['row', 1, 'p']], $events);
	}

	public function testRowIgnoresABubbledEventOfAnotherKind()
	{
		[, $table] = $this->createTable();
		$row = $table->getRows()->itemAt(0);
		$this->assertFalse($row->bubbleEvent($row, new TCallbackEventParameter(null, 'p')));
	}

	public function testFirstIndexesAndTheCachedRowAndTable()
	{
		[, $table] = $this->createTable();
		$row = $table->getRows()->itemAt(0);
		$cell = $row->getCells()->itemAt(0);
		$this->assertSame(0, $cell->getCellIndex());
		$this->assertSame($row, $cell->getRow());
		$this->assertSame($row, $cell->getRow());
		$this->assertSame($table, $row->getTable());
		$this->assertSame($table, $row->getTable());
	}

	public function testDetachedCellAndRowThrow()
	{
		try {
			(new TActiveTableCell())->getRow();
			$this->fail('A cell without a parent has no row.');
		} catch (TConfigurationException $e) {
			$this->assertStringContainsString('must be enclosed within a TTableRow control', $e->getMessage());
		}
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('must be enclosed within a TTable control');
		(new TActiveTableRow())->getTable();
	}

	public function testRenderBeforePreRenderWithoutActiveChildrenDefers()
	{
		[$page, $table] = $this->createTable();
		$row = $table->getRows()->itemAt(0);
		$cell = $row->getCells()->itemAt(0);
		$cell->getControls()->add('text');
		$cell->render($this->writer());
		$this->assertSame(['C00' => $cell], $page->pageAdapter->rendered);

		$plain = new TActiveTableRow();
		$plain->setID('Plain');
		$table->getRows()->add($plain);
		$plain->getCells()->add(new TTableCell());
		$plain->render($this->writer());
		$this->assertSame($plain, $page->pageAdapter->rendered['Plain']);
	}

	public function testCellOutsideARowThrows()
	{
		$page = new TActiveTestPage();
		$cell = $page->addControl(new TActiveTableCell(), 'Cell');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('must be enclosed within a TTableRow control');
		$cell->getCellIndex();
	}

	public function testCellNotInTheCellsOfItsRowThrows()
	{
		[, $table] = $this->createTable();
		$inner = new TActiveTableCell();
		$table->getRows()->itemAt(0)->getCells()->itemAt(0)->getControls()->add($inner);
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('is not a member of the TTableCellCollection');
		$inner->getCellIndex();
	}

	public function testRowOutsideATableThrows()
	{
		$page = new TActiveTestPage();
		$row = $page->addControl(new TActiveTableRow(), 'Row');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('must be enclosed within a TTable control');
		$row->getRowIndex();
	}

	public function testRowNotInTheRowsOfItsTableThrows()
	{
		[, $table] = $this->createTable();
		$inner = new TActiveTableRow();
		$table->getRows()->itemAt(0)->getCells()->itemAt(0)->getControls()->add($inner);
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('is not a member of the TTableRowCollection');
		$inner->getRowIndex();
	}

	public function testRenderRegistersTheCallbackControlOnlyWithASelectionHandler()
	{
		[$page, $table] = $this->createTable(true, TControl::CS_PRERENDERED);
		$row = $table->getRows()->itemAt(0);
		$cell = $row->getCells()->itemAt(0);
		$writer = $this->writer();
		$cell->render($writer);
		$row->render($writer);
		$this->assertSame([], $page->cs->callbackControls);

		$cell->attachEventHandler('OnCellSelected', fn () => null);
		$row->attachEventHandler('OnRowSelected', fn () => null);
		$writer = $this->writer();
		$cell->render($writer);
		$this->assertStringContainsString('<td id="C00">&nbsp;</td>', $writer->flush());
		$row->render($writer);
		$this->assertStringContainsString('<tr id="R0">', $writer->flush());
		$this->assertSame(['Prado.WebUI.TActiveTableCell', 'Prado.WebUI.TActiveTableRow', 'Prado.WebUI.TActiveTableCell'], array_column($page->cs->callbackControls, 0));
		$this->assertSame('C00', $page->cs->callbackControls[0][1]['ID']);
		$this->assertSame('C00', $page->cs->callbackControls[0][1]['EventTarget']);
	}

	public function testRenderAfterPreRenderReplacesTheClientContentInACallback()
	{
		[$page, $table] = $this->createTable(true, TControl::CS_PRERENDERED);
		$row = $table->getRows()->itemAt(0);
		$cell = $row->getCells()->itemAt(0);
		$cell->render($this->writer());
		$row->render($this->writer());
		$functions = $page->getClientFunctions();
		$this->assertSame('C00', $functions[0]['Prado.Element.replace'][0]);
		$this->assertSame('R0', end($functions)['Prado.Element.replace'][0]);

		[$page, $table] = $this->createTable(false, TControl::CS_PRERENDERED);
		$table->getRows()->itemAt(0)->render($this->writer());
		$table->getRows()->itemAt(0)->getCells()->itemAt(0)->render($this->writer());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRowRenderedAfterPreRenderInACallbackReplacesOnlyItself()
	{
		[$page, $table] = $this->createTable(true, TControl::CS_PRERENDERED);
		$table->getRows()->itemAt(0)->render($this->writer());
		$functions = $page->getClientFunctions();
		$this->assertCount(1, $functions);
		$this->assertSame('R0', $functions[0]['Prado.Element.replace'][0]);
	}

	public function testRenderBeforePreRenderOutsideACallbackRendersAtOnce()
	{
		[$page, $table] = $this->createTable(false);
		$row = $table->getRows()->itemAt(0);
		$writer = $this->writer();
		$row->render($writer);
		$this->assertStringContainsString('<tr id="R0">', $writer->flush());
		$row->getCells()->itemAt(0)->render($writer);
		$this->assertStringContainsString('<td id="C00">', $writer->flush());
		$this->assertSame([], $page->pageAdapter->rendered);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRenderBeforePreRenderDefersAndDisablesTheActiveChildUpdates()
	{
		[$page, $table] = $this->createTable();
		$row = $table->getRows()->itemAt(0);
		$cell = $row->getCells()->itemAt(0);
		$label = new TActiveLabel();
		$cell->getControls()->add($label);
		$cell->render($this->writer());
		$this->assertSame(['C00' => $cell], $page->pageAdapter->rendered);
		$this->assertFalse($label->getActiveControl()->getEnableUpdate());

		$row->render($this->writer());
		$this->assertSame($row, $page->pageAdapter->rendered['R0']);
		$this->assertFalse($cell->getActiveControl()->getEnableUpdate());
		$this->assertFalse($row->getCells()->itemAt(1)->getActiveControl()->getEnableUpdate());

		$empty = new TActiveTableRow();
		$table->getRows()->add($empty);
		$empty->render($this->writer());
		$empty = new TActiveTableCell();
		$row->getCells()->add($empty);
		$empty->render($this->writer());
		$this->assertCount(4, $page->pageAdapter->rendered);
	}
}
