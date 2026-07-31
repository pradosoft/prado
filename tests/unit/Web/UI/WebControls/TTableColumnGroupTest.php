<?php

use Prado\Web\UI\WebControls\TTableColumn;
use Prado\Web\UI\WebControls\TTableColumnCollection;
use Prado\Web\UI\WebControls\TTableColumnGroup;
use Prado\Web\UI\WebControls\TTableColumnGroupCollection;
use PHPUnit\Framework\TestCase;

class TTableColumnGroupTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersColgroupTag()
	{
		$group = new TTableColumnGroup();
		$output = $this->render($group);
		$this->assertStringContainsString('<colgroup', $output);
		$this->assertStringContainsString('</colgroup>', $output);
	}

	public function testExtendsTTableColumn()
	{
		$group = new TTableColumnGroup();
		$this->assertInstanceOf(TTableColumn::class, $group);
	}

	public function testColumnsDefaultEmpty()
	{
		$group = new TTableColumnGroup();
		$this->assertInstanceOf(TTableColumnCollection::class, $group->getColumns());
		$this->assertSame(0, $group->getColumns()->getCount());
	}

	public function testSpanRenderedWithoutColumns()
	{
		$group = new TTableColumnGroup();
		$group->setSpan(3);
		$output = $this->render($group);
		$this->assertStringContainsString('span="3"', $output);
	}

	public function testSpanNotRenderedWithColumns()
	{
		// The HTML specification forbids span on a colgroup with col children
		$group = new TTableColumnGroup();
		$group->setSpan(3);
		$group->getColumns()->add(new TTableColumn());
		$output = $this->render($group);
		$this->assertStringNotContainsString('span=', $output);
		$this->assertStringContainsString('<col', $output);
	}

	public function testColumnChildrenRenderedInOrder()
	{
		$group = new TTableColumnGroup();

		$first = new TTableColumn();
		$first->setCssClass('first');
		$group->getColumns()->add($first);

		$second = new TTableColumn();
		$second->setCssClass('second');
		$group->getColumns()->add($second);

		$output = $this->render($group);
		$this->assertSame(2, substr_count($output, '<col '));
		$this->assertLessThan(strpos($output, 'second'), strpos($output, 'first'));
	}

	public function testStylingRenderedWithColumns()
	{
		$group = new TTableColumnGroup();
		$group->setCssClass('grp');
		$group->setWidth('50%');
		$group->getColumns()->add(new TTableColumn());
		$output = $this->render($group);
		$this->assertStringContainsString('class="grp"', $output);
		$this->assertStringContainsString('width:50%', $output);
	}

	public function testAddParsedObjectAddsColumns()
	{
		$group = new TTableColumnGroup();
		$col = new TTableColumn();
		$group->addParsedObject($col);
		$group->addParsedObject('ignored text');
		$this->assertSame(1, $group->getColumns()->getCount());
		$this->assertSame($col, $group->getColumns()->itemAt(0));
	}

	public function testAddParsedObjectIgnoresNestedGroup()
	{
		$group = new TTableColumnGroup();
		$group->addParsedObject(new TTableColumnGroup());
		$this->assertSame(0, $group->getColumns()->getCount());
	}

	public function testColumnCollectionRejectsGroup()
	{
		$group = new TTableColumnGroup();
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		$group->getColumns()->add(new TTableColumnGroup());
	}

	public function testColumnCollectionRejectsNonColumn()
	{
		$collection = new TTableColumnCollection();
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		$collection->add('not a column');
	}

	public function testGroupCollectionAcceptsGroup()
	{
		$collection = new TTableColumnGroupCollection();
		$collection->add(new TTableColumnGroup());
		$this->assertSame(1, $collection->getCount());
	}

	public function testGroupCollectionRejectsBareColumn()
	{
		$collection = new TTableColumnGroupCollection();
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		$collection->add(new TTableColumn());
	}
}
