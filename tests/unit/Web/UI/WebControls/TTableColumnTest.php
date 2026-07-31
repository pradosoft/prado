<?php

use Prado\Web\UI\WebControls\TTableColumn;
use Prado\Web\UI\WebControls\TTableColumnGroup;
use PHPUnit\Framework\TestCase;

class TTableColumnTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersColTag()
	{
		$col = new TTableColumn();
		$output = $this->render($col);
		$this->assertStringContainsString('<col', $output);
		$this->assertStringNotContainsString('</col>', $output);
	}

	public function testSpanDefaultOne()
	{
		$col = new TTableColumn();
		$this->assertSame(1, $col->getSpan());
	}

	public function testSetSpan()
	{
		$col = new TTableColumn();
		$col->setSpan(3);
		$this->assertSame(3, $col->getSpan());
		$col->setSpan('2');
		$this->assertSame(2, $col->getSpan());
	}

	public function testSetSpanBelowOneThrows()
	{
		$col = new TTableColumn();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$col->setSpan(0);
	}

	public function testSpanAttributeRenderedWhenGreaterThanOne()
	{
		$col = new TTableColumn();
		$col->setSpan(2);
		$output = $this->render($col);
		$this->assertStringContainsString('span="2"', $output);
	}

	public function testSpanAttributeNotRenderedWhenOne()
	{
		$col = new TTableColumn();
		$output = $this->render($col);
		$this->assertStringNotContainsString('span=', $output);
	}

	public function testCssClassRendered()
	{
		$col = new TTableColumn();
		$col->setCssClass('numeric');
		$output = $this->render($col);
		$this->assertStringContainsString('class="numeric"', $output);
	}

	public function testWidthRenderedAsStyle()
	{
		$col = new TTableColumn();
		$col->setWidth('8em');
		$output = $this->render($col);
		$this->assertStringContainsString('width:8em', $output);
	}

	public function testStyleDeclarationsRendered()
	{
		$col = new TTableColumn();
		$col->setStyle('background-color:#eef; border-right:1px solid #ccc');
		$output = $this->render($col);
		$this->assertStringContainsString('background-color:#eef', $output);
		$this->assertStringContainsString('border-right:1px solid #ccc', $output);
	}

	public function testDefaultsRenderNoAttributes()
	{
		$col = new TTableColumn();
		$output = $this->render($col);
		$this->assertStringNotContainsString('class=', $output);
		$this->assertStringNotContainsString('style=', $output);
	}

	public function testIsNotTTableColumnGroup()
	{
		$col = new TTableColumn();
		$this->assertNotInstanceOf(TTableColumnGroup::class, $col);
	}
}
