<?php

use Prado\Web\UI\WebControls\TTable;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableColumn;
use Prado\Web\UI\WebControls\TTableColumnGroup;
use Prado\Web\UI\WebControls\TTableColumnGroupCollection;
use Prado\Web\UI\WebControls\TTableRow;
use PHPUnit\Framework\TestCase;

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
}
