<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTableHeaderScope;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TVerticalAlign;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Exceptions\TInvalidDataValueException;

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

	public function testStylePropertiesWithoutStyleReturnDefaults()
	{
		$cell = new TTableCell();
		$this->assertSame(THorizontalAlign::NotSet, $cell->getHorizontalAlign());
		$this->assertSame(TVerticalAlign::NotSet, $cell->getVerticalAlign());
		$this->assertTrue($cell->getWrap());
		$this->assertFalse($cell->getHasStyle());
	}

	public function testStylePropertiesDelegateToTheItemStyle()
	{
		$cell = new TTableCell();
		$cell->setHorizontalAlign(THorizontalAlign::Center);
		$cell->setVerticalAlign(TVerticalAlign::Bottom);
		$cell->setWrap(false);
		$this->assertInstanceOf(TTableItemStyle::class, $cell->getStyle());
		$this->assertSame(THorizontalAlign::Center, $cell->getHorizontalAlign());
		$this->assertSame(TVerticalAlign::Bottom, $cell->getVerticalAlign());
		$this->assertFalse($cell->getWrap());
		$this->assertSame('<td style="white-space:nowrap;text-align:center;vertical-align:bottom;">&nbsp;</td>', $this->render($cell));
	}

	public function testSpansRenderWhenPositive()
	{
		$cell = new TTableCell();
		$this->assertSame(0, $cell->getColumnSpan());
		$this->assertSame(0, $cell->getRowSpan());
		$cell->setColumnSpan('2');
		$cell->setRowSpan(3);
		$this->assertSame(2, $cell->getColumnSpan());
		$this->assertSame(3, $cell->getRowSpan());
		$this->assertSame('<td colspan="2" rowspan="3">&nbsp;</td>', $this->render($cell));

		$cell->setColumnSpan(0);
		$cell->setRowSpan(-1);
		$this->assertSame('<td>&nbsp;</td>', $this->render($cell));
	}

	public function testDataIsTheText()
	{
		$cell = new TTableCell();
		$cell->setData('value');
		$this->assertSame('value', $cell->getText());
		$this->assertSame('value', $cell->getData());
	}

	public function testRenderContentsRendersChildControlsWithoutText()
	{
		$cell = new TTableCell();
		$literal = new TLiteral();
		$literal->setText('child');
		$cell->getControls()->add($literal);
		$this->assertSame('child', $this->renderContents($cell));

		$cell->setText('text');
		$this->assertSame('text', $this->renderContents($cell));
	}

	public function testHeaderCellRendersTh()
	{
		$this->assertSame('<th>&nbsp;</th>', $this->render(new TTableHeaderCell()));
	}

	public function testHeaderCellScope()
	{
		$cell = new TTableHeaderCell();
		$this->assertSame(TTableHeaderScope::NotSet, $cell->getScope());
		$cell->setScope(TTableHeaderScope::Row);
		$this->assertSame('<th scope="row">&nbsp;</th>', $this->render($cell));
		$cell->setScope(TTableHeaderScope::Column);
		$this->assertSame('<th scope="col">&nbsp;</th>', $this->render($cell));
		$cell->setScope(TTableHeaderScope::NotSet);
		$this->assertSame('<th>&nbsp;</th>', $this->render($cell));
	}

	public function testHeaderCellScopeRejectsInvalidValues()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TTableHeaderCell())->setScope('Diagonal');
	}

	public function testHeaderCellAbbreviatedAndCategoryText()
	{
		$cell = new TTableHeaderCell();
		$this->assertSame('', $cell->getAbbreviatedText());
		$this->assertSame('', $cell->getCategoryText());
		$cell->setAbbreviatedText('Qty');
		$cell->setCategoryText('sales');
		$this->assertSame('Qty', $cell->getAbbreviatedText());
		$this->assertSame('sales', $cell->getCategoryText());
		$this->assertSame('<th abbr="Qty" axis="sales">&nbsp;</th>', $this->render($cell));
	}

	public function testAttributeCombinations()
	{
		foreach ([0, 2] as $colspan) {
			foreach ([0, 3] as $rowspan) {
				foreach (['', 'h1'] as $headers) {
					$cell = new TTableCell();
					$cell->setColumnSpan($colspan);
					$cell->setRowSpan($rowspan);
					$cell->setHeaders($headers);
					$expected = '<td'
						. ($colspan ? ' colspan="2"' : '')
						. ($rowspan ? ' rowspan="3"' : '')
						. ($headers ? ' headers="h1"' : '')
						. '>&nbsp;</td>';
					$this->assertSame($expected, $this->render($cell), "colspan $colspan, rowspan $rowspan, headers '$headers'");
				}
			}
		}
	}

	public function testHeadersResolveASingleControlId()
	{
		$page = new TPage();
		$header = new TTableHeaderCell();
		$header->setID('q1');
		$page->getControls()->add($header);
		$cell = new TTableCell();
		$page->getControls()->add($cell);
		$cell->setHeaders('q1');
		$this->assertStringContainsString('headers="' . $header->getClientID() . '"', $this->render($cell));
	}

	public function testHeaderCellAttributeCombinations()
	{
		$scopes = [TTableHeaderScope::NotSet => '', TTableHeaderScope::Row => ' scope="row"', TTableHeaderScope::Column => ' scope="col"'];
		foreach ($scopes as $scope => $scopeAttribute) {
			foreach (['', 'Qty'] as $abbr) {
				foreach (['', 'sales'] as $axis) {
					$cell = new TTableHeaderCell();
					$cell->setScope($scope);
					$cell->setAbbreviatedText($abbr);
					$cell->setCategoryText($axis);
					$expected = '<th' . $scopeAttribute
						. ($abbr ? ' abbr="Qty"' : '')
						. ($axis ? ' axis="sales"' : '')
						. '>&nbsp;</th>';
					$this->assertSame($expected, $this->render($cell), "scope $scope, abbr '$abbr', axis '$axis'");
				}
			}
		}
	}
}
