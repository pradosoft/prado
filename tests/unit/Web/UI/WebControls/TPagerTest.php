<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TEventParameter;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TPagerPageChangedEventParameter;
use Prado\Web\UI\WebControls\TRepeater;
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

	// ------------------------------------------------------------------
	// Coverage (#1497)
	// ------------------------------------------------------------------

	/**
	 * Describes the built pager controls: "\n" for a separator string, otherwise
	 * "Class(text,command,parameter)" with a "-" suffix for a disabled button.
	 */
	private function describe(TPager $pager): array
	{
		$out = [];
		foreach ($pager->getControls() as $control) {
			if (is_string($control)) {
				$out[] = $control;
				continue;
			}
			$class = substr(strrchr($control::class, '\\'), 1);
			if ($control instanceof TLabel) {
				$out[] = "$class(" . $control->getText() . ')';
			} elseif ($control instanceof TDropDownList) {
				$out[] = "$class(" . $control->getItems()->getCount() . ',' . $control->getSelectedIndex() . ')';
			} else {
				$out[] = "$class(" . $control->getText() . ',' . $control->getCommandName() . ',' . $control->getCommandParameter() . ')' . ($control->getEnabled() ? '' : '-');
			}
		}
		return $out;
	}

	public function testPropertyDefaultsAndSetters()
	{
		$pager = new TPager();
		$this->assertSame('', $pager->getButtonCssClass());
		$this->assertSame(TPagerMode::NextPrev, $pager->getMode());
		$this->assertSame(TPagerButtonType::LinkButton, $pager->getButtonType());
		$this->assertSame(10, $pager->getPageButtonCount());
		$this->assertSame(0, $pager->getCurrentPageIndex());
		$this->assertSame(0, $pager->getPageCount());
		$this->assertSame('', $pager->getControlToPaginate());
		foreach (['First', 'Last', 'Next', 'Prev', 'Numeric'] as $name) {
			$this->assertSame('', $pager->{'get' . $name . 'PageImageUrl'}());
			$pager->{'set' . $name . 'PageImageUrl'}("$name.png");
			$this->assertSame("$name.png", $pager->{'get' . $name . 'PageImageUrl'}());
		}
		foreach (['First' => 'F', 'Last' => 'L', 'Next' => 'N', 'Prev' => 'P'] as $name => $text) {
			$pager->{'set' . $name . 'PageText'}($text);
			$this->assertSame($text, $pager->{'get' . $name . 'PageText'}());
		}
		$pager->setButtonCssClass(7);
		$this->assertSame('7', $pager->getButtonCssClass());
		$pager->setMode('numeric');
		$this->assertSame(TPagerMode::Numeric, $pager->getMode());
		$pager->setButtonType('imagebutton');
		$this->assertSame(TPagerButtonType::ImageButton, $pager->getButtonType());
		$pager->setPageButtonCount('3');
		$this->assertSame(3, $pager->getPageButtonCount());
	}

	public function testPageButtonCountMustBePositive()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TPager())->setPageButtonCount(0);
	}

	public function testCurrentPageIndexAndPageCountMustNotBeNegative()
	{
		$pager = new TPager();
		foreach (['setCurrentPageIndex', 'setPageCount'] as $setter) {
			try {
				PradoUnit::invoke($pager, $setter, -1);
				$this->fail("$setter accepted -1");
			} catch (TInvalidDataValueException $e) {
				$this->addToAssertionCount(1);
			}
		}
	}

	public function testFirstAndLastPage()
	{
		$this->assertTrue($this->createPager(0)->getIsFirstPage());
		$this->assertFalse($this->createPager(0)->getIsLastPage());
		$pager = $this->createPager(2);
		$this->assertFalse($pager->getIsFirstPage());
		$this->assertTrue($pager->getIsLastPage());
		$this->assertSame(3, $pager->getPageCount());
		$this->assertSame(2, $pager->getCurrentPageIndex());
	}

	public function testNextPrevPagerOnTheFirstMiddleAndLastPage()
	{
		$this->assertSame(['TLabel(&lt;&lt;)', "\n", 'TLabel(&lt;)', "\n", 'TLinkButton(&gt;,Next,)', "\n", 'TLinkButton(&gt;&gt;,Last,)'], $this->describe($this->createPager(0)));
		$this->assertSame(['TLinkButton(&lt;&lt;,First,)', "\n", 'TLinkButton(&lt;,Previous,)', "\n", 'TLinkButton(&gt;,Next,)', "\n", 'TLinkButton(&gt;&gt;,Last,)'], $this->describe($this->createPager(1)));
		$this->assertSame(['TLinkButton(&lt;&lt;,First,)', "\n", 'TLinkButton(&lt;,Previous,)', "\n", 'TLabel(&gt;)', "\n", 'TLabel(&gt;&gt;)'], $this->describe($this->createPager(2)));
	}

	public function testNextPrevPagerWithoutFirstAndLastTexts()
	{
		$configure = function ($pager) {
			$pager->setFirstPageText('');
			$pager->setLastPageText('');
		};
		$this->assertSame(['TLabel(&lt;)', "\n", 'TLinkButton(&gt;,Next,)'], $this->describe($this->createPager(0, $configure)));
		$this->assertSame(['TLinkButton(&lt;,Previous,)', "\n", 'TLabel(&gt;)'], $this->describe($this->createPager(2, $configure)));
	}

	public function testNextPrevPagerPushButtonsAreDisabledOnTheEdges()
	{
		$pager = $this->createPager(0, fn ($pager) => $pager->setButtonType(TPagerButtonType::PushButton));
		$this->assertSame(['TButton(<<,First,)-', "\n", 'TButton(<,Previous,)-', "\n", 'TButton(>,Next,)', "\n", 'TButton(>>,Last,)'], $this->describe($pager));
	}

	public function testImageButtonPagerUsesThePageImages()
	{
		$pager = $this->createPager(1, function ($pager) {
			$pager->setButtonType(TPagerButtonType::ImageButton);
			$pager->setFirstPageImageUrl('first.png');
			$pager->setPrevPageImageUrl('prev.png');
			$pager->setNextPageImageUrl('next.png');
			$pager->setLastPageImageUrl('last.png');
		});
		$urls = [];
		foreach ($pager->getControls() as $control) {
			if ($control instanceof TImageButton) {
				$urls[] = $control->getImageUrl();
			}
		}
		$this->assertSame(['first.png', 'prev.png', 'next.png', 'last.png'], $urls);

		$pager = $this->createPager(1, function ($pager) {
			$pager->setButtonType(TPagerButtonType::ImageButton);
			$pager->setMode(TPagerMode::Numeric);
			$pager->setNumericPageImageUrl('page{0}.png');
		});
		$this->assertSame('page1.png', $pager->getControls()->itemAt(0)->getImageUrl());
		$this->assertSame('', PradoUnit::invoke($pager, 'getPageImageUrl', 'x', 'Unknown'));
	}

	public function testNumericPagerShowsThePageSetOfTheCurrentPage()
	{
		$this->assertSame(['TLabel(1)', "\n", 'TLinkButton(2,Page,2)', "\n", 'TLinkButton(3,Page,3)'], $this->describe($this->createPager(0, fn ($pager) => $pager->setMode(TPagerMode::Numeric))));

		$configure = function ($pager) {
			$pager->setMode(TPagerMode::Numeric);
			$pager->setPageButtonCount(2);
		};
		$this->assertSame(['TLabel(1)', "\n", 'TLinkButton(2,Page,2)', "\n", 'TLinkButton(&gt;,Page,3)', "\n", 'TLinkButton(&gt;&gt;,Last,)'], $this->describe($this->createPager(0, $configure)));
		$this->assertSame(['TLinkButton(&lt;&lt;,First,)', "\n", 'TLinkButton(&lt;,Page,1)', "\n", 'TLinkButton(2,Page,2)', "\n", 'TLabel(3)'], $this->describe($this->createPager(2, $configure)), 'the last page set is filled back to the button count');
	}

	public function testNumericPagerWithoutFirstAndLastTexts()
	{
		$configure = function ($pager) {
			$pager->setMode(TPagerMode::Numeric);
			$pager->setPageButtonCount(1);
			$pager->setFirstPageText('');
			$pager->setLastPageText('');
		};
		$this->assertSame(['TLinkButton(&lt;,Page,1)', "\n", 'TLabel(2)', "\n", 'TLinkButton(&gt;,Page,3)'], $this->describe($this->createPager(1, $configure)));
	}

	public function testDropDownListPagerSelectsTheCurrentPage()
	{
		$pager = $this->createPager(1, fn ($pager) => $pager->setMode(TPagerMode::DropDownList));
		$this->assertSame(['TDropDownList(3,1)'], $this->describe($pager));
		$list = $pager->getControls()->itemAt(0);
		$this->assertTrue($list->getAutoPostBack());

		$received = null;
		$pager->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$received) {
			$received = $param;
		});
		$list->setSelectedIndex(2);
		$list->onSelectedIndexChanged(new TEventParameter());
		$this->assertInstanceOf(TPagerPageChangedEventParameter::class, $received);
		$this->assertSame(2, $received->getNewPageIndex());
		$this->assertSame($list, $received->getCommandSource());
	}

	public function testBubbleEventRaisesThePageIndexOfEachCommand()
	{
		$pager = $this->createPager(1);
		$indices = [];
		$pager->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$indices) {
			$indices[] = $param->getNewPageIndex();
		});
		$source = new TControl();
		foreach ([['page', '3'], ['Next', ''], ['previous', ''], ['FIRST', ''], ['Last', '']] as [$command, $parameter]) {
			$this->assertTrue($pager->bubbleEvent($source, new TCommandEventParameter($command, $parameter)), $command);
		}
		$this->assertSame([2, 2, 0, 0, 2], $indices);
		$this->assertFalse($pager->bubbleEvent($source, new TCommandEventParameter('Sort', '')));
		$this->assertFalse($pager->bubbleEvent($source, new TEventParameter()));
	}

	public function testAPagerButtonCommandBubblesToThePager()
	{
		$pager = $this->createPager(0);
		$received = null;
		$pager->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$received) {
			$received = $param;
		});
		$next = $pager->getControls()->itemAt(4);
		$next->onCommand(new TCommandEventParameter($next->getCommandName(), $next->getCommandParameter()));
		$this->assertSame(1, $received->getNewPageIndex());
		$this->assertSame($next, $received->getCommandSource());
	}

	public function testRenderWithOnePageRendersNothing()
	{
		$page = new TPage();
		$list = new TCheckBoxList();
		$list->setID('List');
		$list->setAllowPaging(true);
		$list->setPageSize(5);
		$page->getControls()->add($list);
		$list->setDataSource([1, 2]);
		$list->dataBind();
		$pager = new TPager();
		$pager->setControlToPaginate('List');
		$page->getControls()->add($pager);
		$pager->onPreRender(null);
		$this->assertSame(1, $pager->getPageCount());
		$this->assertSame('', $this->render($pager));
	}

	public function testPreRenderWithoutPagingBuildsNothing()
	{
		$page = new TPage();
		$list = new TRepeater();
		$list->setID('List');
		$page->getControls()->add($list);
		$pager = new TPager();
		$pager->setControlToPaginate('List');
		$page->getControls()->add($pager);
		$pager->onPreRender(null);
		$this->assertSame(0, $pager->getControls()->getCount());
		$this->assertSame('', $this->render($pager));
	}

	public function testPreRenderRejectsAMissingOrUnboundTarget()
	{
		foreach (['Missing', 'Label'] as $id) {
			$page = new TPage();
			$label = new TLabel();
			$label->setID('Label');
			$page->getControls()->add($label);
			$pager = new TPager();
			$pager->setControlToPaginate($id);
			$page->getControls()->add($pager);
			try {
				$pager->onPreRender(null);
				$this->fail("$id accepted");
			} catch (TConfigurationException $e) {
				$this->assertStringContainsString($id, $e->getMessage());
			}
		}
	}

	public function testLoadStateRebuildsThePager()
	{
		$pager = $this->createPager(1);
		$pager->getControls()->clear();
		$pager->loadState();
		$this->assertSame(['TLinkButton(&lt;&lt;,First,)', "\n", 'TLinkButton(&lt;,Previous,)', "\n", 'TLinkButton(&gt;,Next,)', "\n", 'TLinkButton(&gt;&gt;,Last,)'], $this->describe($pager));

		$pager->getControls()->clear();
		$pager->setEnableViewState(false);
		$pager->loadState();
		$this->assertSame(0, $pager->getControls()->getCount(), 'without view state the pager is built at pre-render');
	}

	public function testPageChangedEventParameter()
	{
		$source = new TControl();
		$param = new TPagerPageChangedEventParameter($source, 4);
		$this->assertSame($source, $param->getCommandSource());
		$this->assertSame(4, $param->getNewPageIndex());
	}

	public function testNumericPagerPastThePageCountShowsTheLastPageSet()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TPagerTestClientScript($page));
		$list = new TCheckBoxList();
		$list->setID('List');
		$list->setAllowPaging(true);
		$list->setPageSize(1);
		$page->getControls()->add($list);
		$list->setDataSource([1, 2, 3]);
		$list->dataBind();
		$list->setCurrentPageIndex(5);
		$pager = new TPager();
		$pager->setControlToPaginate('List');
		$pager->setMode(TPagerMode::Numeric);
		$page->getControls()->add($pager);
		$pager->onPreRender(null);
		$this->assertSame(['TLinkButton(1,Page,1)', "\n", 'TLinkButton(2,Page,2)', "\n", 'TLinkButton(3,Page,3)'], $this->describe($pager));
	}

	public function testPageNumberLabelSkipsAControlThatIsNoWebControl()
	{
		$control = new TControl();
		PradoUnit::invoke(new TPager(), 'labelPageNumberButton', $control, 2, true);
		$this->assertFalse($control->getHasAttributes());
	}

	public function testNextPrevPagerForEveryPagePositionAndText()
	{
		foreach ([1, 3] as $pages) {
			for ($index = 0; $index < $pages; $index++) {
				foreach (['<<', ''] as $first) {
					foreach (['>>', ''] as $last) {
						$pager = $this->createPagerOf($pages, $index, function ($pager) use ($first, $last) {
							$pager->setFirstPageText($first);
							$pager->setLastPageText($last);
						});
						$expected = [];
						$atFirst = $index === 0;
						$atLast = $index === $pages - 1;
						if ($first !== '') {
							$expected[] = $atFirst ? 'TLabel(&lt;&lt;)' : 'TLinkButton(&lt;&lt;,First,)';
							$expected[] = "\n";
						}
						$expected[] = $atFirst ? 'TLabel(&lt;)' : 'TLinkButton(&lt;,Previous,)';
						$expected[] = "\n";
						$expected[] = $atLast ? 'TLabel(&gt;)' : 'TLinkButton(&gt;,Next,)';
						if ($last !== '') {
							$expected[] = "\n";
							$expected[] = $atLast ? 'TLabel(&gt;&gt;)' : 'TLinkButton(&gt;&gt;,Last,)';
						}
						$this->assertSame($expected, $this->describe($pager), "$pages pages, page $index, first '$first', last '$last'");
					}
				}
			}
		}
	}

	/**
	 * Creates a pager of a checkbox list with the given number of pages.
	 */
	private function createPagerOf(int $pages, int $pageIndex, callable $configure): TPager
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
		$list->setDataSource(range(1, $pages));
		$list->dataBind();
		$pager = new TPager();
		$pager->setControlToPaginate('List');
		$page->getControls()->add($pager);
		$configure($pager);
		$pager->onPreRender(null);
		return $pager;
	}
}
