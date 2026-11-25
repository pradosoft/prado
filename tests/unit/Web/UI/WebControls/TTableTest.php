<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TTable;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableColumn;
use Prado\Web\UI\WebControls\TTableColumnGroup;
use Prado\Web\UI\WebControls\TTableColumnGroupCollection;
use Prado\Web\UI\WebControls\TTableRow;
use Prado\Web\UI\WebControls\TTableFooterRow;
use Prado\Web\UI\WebControls\TTableHeaderRow;
use Prado\Web\UI\WebControls\TTableRowSection;
use Prado\Web\UI\WebControls\TTableCaptionAlign;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TTableStyle;
use Prado\Web\UI\WebControls\TTableGridLines;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Exceptions\TConfigurationException;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;

class TTableTest extends TestCase
{
	use TWebControlRenderTrait;

	private function newRow(string $text): TTableRow
	{
		$row = new TTableRow();
		$cell = new TTableCell();
		$cell->setText($text);
		$row->getCells()->add($cell);
		return $row;
	}

	public function testColumnGroupsDefaultEmpty()
	{
		$table = new TTable();
		$this->assertInstanceOf(TTableColumnGroupCollection::class, $table->getColumnGroups());
		$this->assertSame(0, $table->getColumnGroups()->getCount());
	}

	public function testAddParsedObjectRoutesRowsAndColumnGroups()
	{
		$table = new TTable();
		$row = new TTableRow();
		$group = new TTableColumnGroup();

		$table->addParsedObject($row);
		$table->addParsedObject($group);
		$table->addParsedObject('ignored text');

		$this->assertSame(1, $table->getRows()->getCount());
		$this->assertSame($row, $table->getRows()->itemAt(0));
		$this->assertSame(1, $table->getColumnGroups()->getCount());
		$this->assertSame($group, $table->getColumnGroups()->itemAt(0));
	}

	public function testNoColgroupRenderedByDefault()
	{
		$table = new TTable();
		$table->getRows()->add($this->newRow('data'));
		$output = $this->render($table);
		$this->assertStringNotContainsString('<colgroup', $output);
	}

	public function testColumnGroupRenderedBeforeRows()
	{
		$table = new TTable();

		$group = new TTableColumnGroup();
		$col = new TTableColumn();
		$col->setWidth('8em');
		$group->getColumns()->add($col);
		$table->getColumnGroups()->add($group);

		$table->getRows()->add($this->newRow('data'));

		$output = $this->render($table);
		$this->assertStringContainsString('<colgroup>', $output);
		$this->assertStringContainsString('<col ', $output);
		$this->assertLessThan(strpos($output, '<tr'), strpos($output, '<colgroup'));
	}

	public function testCaptionEncodedWhenEncode()
	{
		$table = new TTable();
		$table->setCaption('<b>Q1 & Q2</b>');
		$this->assertFalse($table->getEncode());
		$this->assertStringContainsString('<caption><b>Q1 & Q2</b></caption>', $this->render($table));

		$table->setEncode(true);
		$this->assertStringContainsString('<caption>&lt;b&gt;Q1 & Q2&lt;/b&gt;</caption>', $this->render($table));
	}

	public function testColumnGroupRenderedAfterCaption()
	{
		$table = new TTable();
		$table->setCaption('Quarterly results');
		$table->getColumnGroups()->add(new TTableColumnGroup());
		$table->getRows()->add($this->newRow('data'));

		$output = $this->render($table);
		$captionPos = strpos($output, '<caption>');
		$colgroupPos = strpos($output, '<colgroup');
		$this->assertNotFalse($captionPos);
		$this->assertNotFalse($colgroupPos);
		$this->assertLessThan($colgroupPos, $captionPos);
	}

	public function testMultipleColumnGroupsRenderedInOrder()
	{
		$table = new TTable();

		$first = new TTableColumnGroup();
		$first->setCssClass('firstGroup');
		$table->getColumnGroups()->add($first);

		$second = new TTableColumnGroup();
		$second->setSpan(2);
		$second->setCssClass('secondGroup');
		$table->getColumnGroups()->add($second);

		$output = $this->render($table);
		$this->assertSame(2, substr_count($output, '<colgroup'));
		$this->assertLessThan(strpos($output, 'secondGroup'), strpos($output, 'firstGroup'));
		$this->assertStringContainsString('span="2"', $output);
	}

	// ================================================================================
	// Style properties
	// ================================================================================

	public function testStylePropertiesWithoutStyleReturnDefaults()
	{
		$table = new TTable();
		$this->assertSame(-1, $table->getCellSpacing());
		$this->assertSame(-1, $table->getCellPadding());
		$this->assertSame(THorizontalAlign::NotSet, $table->getHorizontalAlign());
		$this->assertSame(TTableGridLines::None, $table->getGridLines());
		$this->assertSame('', $table->getBackImageUrl());
		$this->assertFalse($table->getHasStyle());
	}

	public function testStylePropertiesDelegateToTheTableStyle()
	{
		$table = new TTable();
		$table->setCellSpacing(2);
		$table->setCellPadding(3);
		$table->setHorizontalAlign(THorizontalAlign::Right);
		$table->setGridLines(TTableGridLines::Horizontal);
		$table->setBackImageUrl('bg.png');
		$this->assertInstanceOf(TTableStyle::class, $table->getStyle());
		$this->assertSame(2, $table->getCellSpacing());
		$this->assertSame(3, $table->getCellPadding());
		$this->assertSame(THorizontalAlign::Right, $table->getHorizontalAlign());
		$this->assertSame(TTableGridLines::Horizontal, $table->getGridLines());
		$this->assertSame('bg.png', $table->getBackImageUrl());
		$this->assertSame(2, $table->getStyle()->getCellSpacing());
	}

	public function testGridLinesRenderBorderOne()
	{
		$table = new TTable();
		$table->setGridLines(TTableGridLines::Both);
		$this->assertStringStartsWith('<table rules="all" border="1">', $this->render($table));
	}

	public function testGridLinesRenderTheBorderWidth()
	{
		$table = new TTable();
		$table->setGridLines(TTableGridLines::Vertical);
		$table->setBorderWidth('3px');
		$this->assertStringStartsWith('<table rules="cols" border="3" style="border-width:3px;">', $this->render($table));
	}

	public function testStyleWithoutGridLinesRendersNoBorder()
	{
		$table = new TTable();
		$table->setCellPadding(0);
		$this->assertStringStartsWith('<table cellpadding="0">', $this->render($table));
	}

	// ================================================================================
	// Table sections
	// ================================================================================

	/**
	 * Creates a row in a table section.
	 * @param string $section the TTableRowSection
	 * @param string $text the cell text
	 * @return TTableRow the row
	 */
	private function sectionRow(string $section, string $text): TTableRow
	{
		$row = $this->newRow($text);
		$row->setTableSection($section);
		return $row;
	}

	/**
	 * Renders a table of rows with whitespace removed between tags.
	 * @param TTableRow[] $rows the rows
	 * @return string the rendered markup
	 */
	private function renderRows(array $rows): string
	{
		$table = new TTable();
		foreach ($rows as $row) {
			$table->getRows()->add($row);
		}
		return preg_replace('/>\s+</', '><', $this->render($table));
	}

	public function testBodyRowsRenderWithoutSections()
	{
		$this->assertSame(
			'<table><tr><td>a</td></tr><tr><td>b</td></tr></table>',
			$this->renderRows([$this->newRow('a'), $this->newRow('b')])
		);
	}

	public function testHeaderBodyAndFooterSections()
	{
		$this->assertSame(
			'<table><thead><tr><td>h1</td></tr><tr><td>h2</td></tr></thead><tbody><tr><td>b</td></tr></tbody><tfoot><tr><td>f</td></tr></tfoot></table>',
			$this->renderRows([
				$this->sectionRow(TTableRowSection::Header, 'h1'),
				$this->sectionRow(TTableRowSection::Header, 'h2'),
				$this->newRow('b'),
				$this->sectionRow(TTableRowSection::Footer, 'f'),
			])
		);
	}

	public function testHeaderAndFooterRowClasses()
	{
		$header = new TTableHeaderRow();
		$footer = new TTableFooterRow();
		$this->assertSame(
			'<table><thead><tr></tr></thead><tfoot><tr></tr></tfoot></table>',
			$this->renderRows([$header, $footer])
		);
	}

	public function testHeaderOnlySection()
	{
		$this->assertSame(
			'<table><thead><tr><td>h</td></tr></thead></table>',
			$this->renderRows([$this->sectionRow(TTableRowSection::Header, 'h')])
		);
	}

	public function testBodyThenFooterSections()
	{
		$this->assertSame(
			'<table><tbody><tr><td>b</td></tr></tbody><tfoot><tr><td>f</td></tr></tfoot></table>',
			$this->renderRows([$this->newRow('b'), $this->sectionRow(TTableRowSection::Footer, 'f')])
		);
	}

	public function testFooterOnlySection()
	{
		$this->assertSame(
			'<table><tfoot><tr><td>f</td></tr></tfoot></table>',
			$this->renderRows([$this->sectionRow(TTableRowSection::Footer, 'f')])
		);
	}

	public static function outOfOrderSections(): array
	{
		return [
			'header after body' => [TTableRowSection::Body, TTableRowSection::Header],
			'header after footer' => [TTableRowSection::Footer, TTableRowSection::Header],
			'body after footer' => [TTableRowSection::Footer, TTableRowSection::Body],
		];
	}

	/**
	 * @dataProvider outOfOrderSections
	 */
	public function testOutOfOrderSectionsThrow(string $first, string $second)
	{
		try {
			$this->renderRows([$this->sectionRow($first, 'a'), $this->sectionRow($second, 'b')]);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('table_tablesection_outoforder', $e->getErrorCode());
		}
	}

	// ================================================================================
	// Caption alignment
	// ================================================================================

	public static function captionAlignProvider(): array
	{
		return [
			'NotSet' => [TTableCaptionAlign::NotSet, '<caption>Sales</caption>'],
			'Top' => [TTableCaptionAlign::Top, '<caption style="caption-side:top;">Sales</caption>'],
			'Bottom' => [TTableCaptionAlign::Bottom, '<caption style="caption-side:bottom;">Sales</caption>'],
			'Left' => [TTableCaptionAlign::Left, '<caption style="caption-side:top;text-align:left;">Sales</caption>'],
			'Right' => [TTableCaptionAlign::Right, '<caption style="caption-side:top;text-align:right;">Sales</caption>'],
		];
	}

	/**
	 * @dataProvider captionAlignProvider
	 */
	public function testCaptionAlign(string $align, string $expected)
	{
		$table = new TTable();
		$table->setCaption('Sales');
		$table->setCaptionAlign($align);
		$this->assertSame($align, $table->getCaptionAlign());
		$this->assertSame('<table>' . $expected . '</table>', $this->render($table));
	}

	public function testCaptionAlignWithoutCaptionRendersNoCaption()
	{
		$table = new TTable();
		$table->setCaptionAlign(TTableCaptionAlign::Left);
		$this->assertSame('<table></table>', $this->render($table));
	}

	public function testCaptionAlignRejectsInvalidValues()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TTable())->setCaptionAlign('Center');
	}
}
