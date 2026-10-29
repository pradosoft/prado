<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TTableHeaderCell;

class TTableCellTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRenderContentsEncodesTextWhenEncode()
	{
		$cell = new TTableCell();
		$this->assertFalse($cell->getEncode());
		$cell->setText('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderContents($cell));

		$cell->setEncode(true);
		$this->assertTrue($cell->getEncode());
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($cell));
	}

	public function testHeaderCellInheritsEncode()
	{
		$cell = new TTableHeaderCell();
		$cell->setText('<i>');
		$cell->setEncode(true);
		$this->assertSame('&lt;i&gt;', $this->renderContents($cell));
	}

	public function testRenderContentsWithoutTextOrControls()
	{
		$this->assertSame('&nbsp;', $this->renderContents(new TTableCell()));
	}

	public function testHeadersDefault()
	{
		$cell = new TTableCell();
		$this->assertSame('', $cell->getHeaders());
		$this->assertStringNotContainsString('headers', $this->render($cell));
	}

	public function testHeadersResolveControlIdsToClientIds()
	{
		$page = new TPage();
		$header = new TTableHeaderCell();
		$header->setID('q1');
		$page->getControls()->add($header);
		$cell = new TTableCell();
		$page->getControls()->add($cell);
		$cell->setHeaders(" q1   sales_row\n");
		$this->assertSame('q1 sales_row', $cell->getHeaders());
		$this->assertStringContainsString('headers="' . $header->getClientID() . ' sales_row"', $this->render($cell));
	}

	public function testHeaderCellRendersHeaders()
	{
		$cell = new TTableHeaderCell();
		$cell->setHeaders('region');
		$this->assertStringContainsString('headers="region"', $this->render($cell));
	}
}
