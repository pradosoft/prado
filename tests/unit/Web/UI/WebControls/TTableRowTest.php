<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TTable;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableCellCollection;
use Prado\Web\UI\WebControls\TTableFooterRow;
use Prado\Web\UI\WebControls\TTableHeaderRow;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Web\UI\WebControls\TTableRow;
use Prado\Web\UI\WebControls\TTableRowCollection;
use Prado\Web\UI\WebControls\TTableRowSection;
use Prado\Web\UI\WebControls\TVerticalAlign;

class TTableRowTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * Creates a cell with text.
	 * @param string $text the cell text
	 * @return TTableCell the cell
	 */
	private function newCell(string $text): TTableCell
	{
		$cell = new TTableCell();
		$cell->setText($text);
		return $cell;
	}

	public function testCellsIsTheCellCollection()
	{
		$row = new TTableRow();
		$this->assertInstanceOf(TTableCellCollection::class, $row->getCells());
		$this->assertSame($row->getControls(), $row->getCells());
	}

	public function testAddParsedObjectAddsOnlyCells()
	{
		$row = new TTableRow();
		$cell = new TTableCell();
		$row->addParsedObject($cell);
		$row->addParsedObject('text between cells');
		$this->assertSame(1, $row->getCells()->getCount());
		$this->assertSame($cell, $row->getCells()->itemAt(0));
	}

	public function testCellCollectionRejectsNonCells()
	{
		try {
			(new TTableRow())->getCells()->add(new TTableRow());
			$this->fail('Expected TInvalidDataTypeException');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('tablecellcollection_tablecell_required', $e->getErrorCode());
		}
	}

	public function testRowCollectionRejectsNonRows()
	{
		$table = new TTable();
		$this->assertInstanceOf(TTableRowCollection::class, $table->getRows());
		try {
			$table->getRows()->add(new TTableCell());
			$this->fail('Expected TInvalidDataTypeException');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('tablerowcollection_tablerow_required', $e->getErrorCode());
		}
	}

	public function testStylePropertiesWithoutStyleReturnDefaults()
	{
		$row = new TTableRow();
		$this->assertSame(THorizontalAlign::NotSet, $row->getHorizontalAlign());
		$this->assertSame(TVerticalAlign::NotSet, $row->getVerticalAlign());
		$this->assertFalse($row->getHasStyle());
	}

	public function testStylePropertiesDelegateToTheItemStyle()
	{
		$row = new TTableRow();
		$row->setHorizontalAlign(THorizontalAlign::Left);
		$row->setVerticalAlign(TVerticalAlign::Top);
		$this->assertInstanceOf(TTableItemStyle::class, $row->getStyle());
		$this->assertSame(THorizontalAlign::Left, $row->getHorizontalAlign());
		$this->assertSame(TVerticalAlign::Top, $row->getVerticalAlign());
		$this->assertSame('<tr style="text-align:left;vertical-align:top;"></tr>', $this->render($row));
	}

	public function testTableSection()
	{
		$row = new TTableRow();
		$this->assertSame(TTableRowSection::Body, $row->getTableSection());
		$row->setTableSection(TTableRowSection::Footer);
		$this->assertSame(TTableRowSection::Footer, $row->getTableSection());
		$this->expectException(TInvalidDataValueException::class);
		$row->setTableSection('Middle');
	}

	public function testRenderEmptyRow()
	{
		$this->assertSame('<tr></tr>', $this->render(new TTableRow()));
	}

	public function testRenderCellsOnTheirOwnLines()
	{
		$row = new TTableRow();
		$row->getCells()->add($this->newCell('a'));
		$row->getCells()->add($this->newCell('b'));
		$this->assertSame("<tr>\n<td>a</td>\n<td>b</td>\n</tr>", $this->render($row));
	}

	public function testHeaderRowSectionIsReadOnly()
	{
		$row = new TTableHeaderRow();
		$this->assertSame(TTableRowSection::Header, $row->getTableSection());
		try {
			$row->setTableSection(TTableRowSection::Body);
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('tableheaderrow_tablesection_readonly', $e->getErrorCode());
		}
	}

	public function testFooterRowSectionIsReadOnly()
	{
		$row = new TTableFooterRow();
		$this->assertSame(TTableRowSection::Footer, $row->getTableSection());
		try {
			$row->setTableSection(TTableRowSection::Body);
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('tablefooterrow_tablesection_readonly', $e->getErrorCode());
		}
	}

	public function testRenderOneCell()
	{
		$row = new TTableRow();
		$row->getCells()->add($this->newCell('a'));
		$this->assertSame("<tr>\n<td>a</td>\n</tr>", $this->render($row));
	}
}
