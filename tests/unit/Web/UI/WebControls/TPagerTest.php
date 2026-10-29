<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TPager;
use Prado\Web\UI\WebControls\TPagerButtonType;
use Prado\Web\UI\WebControls\TPagerMode;

/**
 * Client script manager that skips the postback scripts of the pager buttons.
 */
class TPagerTestClientScript extends TClientScriptManager
{
	public function registerPostBackControl($class, $options)
	{
	}
}

class TPagerTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * Creates a pager of a three-page checkbox list on a page and builds it.
	 * @param int $pageIndex the current page index
	 * @param ?callable $configure configures the pager before it builds
	 */
	private function createPager(int $pageIndex = 0, ?callable $configure = null): TPager
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TPagerTestClientScript($page));
		$list = new TCheckBoxList();
		$list->setID('List');
		$list->setAllowPaging(true);
		$list->setPageSize(1);
		$list->setCurrentPageIndex($pageIndex);
		$page->getControls()->add($list);
		$list->setDataSource([1, 2, 3]);
		$list->dataBind();
		$pager = new TPager();
		$pager->setControlToPaginate('List');
		$page->getControls()->add($pager);
		if ($configure) {
			$configure($pager);
		}
		$pager->onPreRender(null);
		return $pager;
	}

	public function testRendersNamedNavLandmark()
	{
		$output = $this->render($this->createPager());
		$this->assertStringStartsWith('<nav aria-label="Pagination">', $output);
		$this->assertStringEndsWith('</nav>', $output);
	}

	public function testAuthorAccessibleNameReplacesDefault()
	{
		$output = $this->render($this->createPager(0, fn ($pager) => $pager->setAttribute('aria-labelledby', 'heading')));
		$this->assertStringStartsWith('<nav aria-labelledby="heading">', $output);
	}

	public function testNextPrevButtonNames()
	{
		$output = $this->render($this->createPager(1, fn ($pager) => $pager->setButtonType(TPagerButtonType::PushButton)));
		foreach (['First page', 'Previous page', 'Next page', 'Last page'] as $name) {
			$this->assertStringContainsString('aria-label="' . $name . '"', $output);
		}
	}

	public function testNumericPagerMarksCurrentPage()
	{
		$output = $this->render($this->createPager(1, fn ($pager) => $pager->setMode(TPagerMode::Numeric)));
		$this->assertStringContainsString('<span aria-current="page" class="">2</span>', $output);
		$this->assertMatchesRegularExpression('#<a [^>]*aria-label="Page 1"[^>]*>1</a>#', $output);
	}

	public function testNumericPagerNamesPageSetButtons()
	{
		$output = $this->render($this->createPager(1, function ($pager) {
			$pager->setMode(TPagerMode::Numeric);
			$pager->setPageButtonCount(1);
		}));
		$this->assertStringContainsString('aria-label="Previous pages"', $output);
		$this->assertStringContainsString('aria-label="Next pages"', $output);
	}

	public function testDropDownListPagerIsNamed()
	{
		$output = $this->render($this->createPager(0, fn ($pager) => $pager->setMode(TPagerMode::DropDownList)));
		$this->assertMatchesRegularExpression('#<select [^>]*aria-label="Page"#', $output);
	}

	public function testListLayout()
	{
		$pager = new TPager();
		$this->assertFalse($pager->getListLayout());
		$output = $this->render($this->createPager(0, fn ($pager) => $pager->setListLayout('true')));
		$this->assertMatchesRegularExpression('#^<nav aria-label="Pagination"><ul><li><span class="">&lt;&lt;</span></li><li><span class="">&lt;</span></li><li><a [^>]*>&gt;</a></li><li><a [^>]*>&gt;&gt;</a></li></ul></nav>$#', $output);
	}

	public function testDefaultPageTexts()
	{
		$pager = new TPager();
		$this->assertSame('<<', $pager->getFirstPageText());
		$this->assertSame('<', $pager->getPrevPageText());
		$this->assertSame('>', $pager->getNextPageText());
		$this->assertSame('>>', $pager->getLastPageText());
	}

	public function testEncodeDefaultPagerText()
	{
		$pager = new TPager();
		foreach (['<' => '&lt;', '>' => '&gt;', '<<' => '&lt;&lt;', '>>' => '&gt;&gt;', '<b>' => '<b>', '3' => '3', '' => ''] as $text => $expected) {
			$this->assertSame($expected, PradoUnit::invoke($pager, 'encodeDefaultPagerText', (string) $text));
		}
	}

	public function testCreatePagerButtonEncodesDefaultTextOfLinksAndLabels()
	{
		$pager = new TPager();
		$cases = [
			[TPagerButtonType::LinkButton, true, TLinkButton::class, '&lt;&lt;'],
			[TPagerButtonType::LinkButton, false, TLabel::class, '&lt;&lt;'],
			[TPagerButtonType::PushButton, true, TButton::class, '<<'],
			[TPagerButtonType::PushButton, false, TButton::class, '<<'],
			[TPagerButtonType::ImageButton, true, TImageButton::class, '<<'],
		];
		foreach ($cases as [$type, $enabled, $class, $text]) {
			$button = PradoUnit::invoke($pager, 'createPagerButton', $type, $enabled, '<<', TPager::CMD_PAGE_FIRST, '');
			$this->assertInstanceOf($class, $button);
			$this->assertSame($text, $button->getText(), "$type " . (int) $enabled);
		}

		$button = PradoUnit::invoke($pager, 'createPagerButton', TPagerButtonType::LinkButton, true, '<b>First</b>', TPager::CMD_PAGE_FIRST, '');
		$this->assertSame('<b>First</b>', $button->getText());
	}
}
