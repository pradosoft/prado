<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TDisplayStyle;
use Prado\Web\UI\WebControls\TPagerMode;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TActiveImageButton;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActivePager;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPagerButtonType;

class TActivePagerTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response', '_errorHandler']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	/**
	 * Builds a page with the active pagers `Pager` and `Second` for `List`, a drop-down pager
	 * `Drop` for `List`, and `Other` for another control. A callback page has an active page adapter.
	 * @return array{TTestCallbackPage, TActivePager, TActivePager, TActivePager, TActivePager}
	 */
	private function createPagers(bool $callback = true, int $stage = TControl::CS_PRERENDERED): array
	{
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		if ($callback) {
			$page->setAdapter(new TActivePageAdapter($page));
		}
		$pagers = [];
		foreach (['Pager' => 'List', 'Second' => 'List', 'Drop' => 'List', 'Other' => 'Elsewhere'] as $id => $target) {
			$pager = new TActivePager();
			$pager->setID($id);
			$pager->setControlToPaginate($target);
			$page->getControls()->add($pager);
			PradoUnit::invoke($pager, 'setControlStage', $stage);
			$pagers[] = $pager;
		}
		$pagers[2]->setMode(TPagerMode::DropDownList);
		return [$page, ...$pagers];
	}

	private function render(TControl $control): string
	{
		$writer = new THtmlWriter(new TTextWriter());
		$control->render($writer);
		return $writer->flush();
	}

	public function testCallbackEventRaisesOnCallback()
	{
		$pager = new TActivePager();
		$this->assertInstanceOf(TCallbackClientSide::class, $pager->getClientSide());
		$raised = null;
		$pager->attachEventHandler('OnCallback', function ($sender, $param) use (&$raised) {
			$raised = $param;
		});
		$pager->raiseCallbackEvent('p');
		$this->assertSame('p', $raised);
	}

	public function testListPagerIsAnActiveDropDownListOfThePages()
	{
		$pager = new TActivePager();
		$pager->setID('Pager');
		PradoUnit::invoke($pager, 'setPageCount', 3);
		PradoUnit::invoke($pager, 'setCurrentPageIndex', 1);
		PradoUnit::invoke($pager, 'buildListPager');
		$list = $pager->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveDropDownList::class, $list);
		$this->assertSame(3, $list->getItemCount());
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertTrue($list->getAutoPostBack());
		$this->assertSame($pager->getClientSide(), $list->getClientSide());
		$this->assertTrue($list->hasEventHandler('OnCallback'));
	}

	public function testRenderInACallbackHidesASinglePageAndReplacesThePager()
	{
		[$page, $pager] = $this->createPagers();
		PradoUnit::invoke($pager, 'setPageCount', 1);
		$output = $this->render($pager);
		$this->assertSame(TDisplayStyle::None, $pager->getDisplay());
		$this->assertStringContainsString('id="Pager"', $output);
		$this->assertSame('Pager', $page->getClientFunctions()[0]['Prado.Element.replace'][0]);
	}

	public function testRenderOutsideACallbackShowsSeveralPages()
	{
		[$page, $pager] = $this->createPagers(false);
		PradoUnit::invoke($pager, 'setPageCount', 2);
		$this->render($pager);
		$this->assertSame(TDisplayStyle::Dynamic, $pager->getDisplay());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRenderBeforePreRenderRegistersThePager()
	{
		[$page, $pager] = $this->createPagers(true, TControl::CS_LOADED);
		$this->assertSame('', $this->render($pager));
		$this->assertSame(['Pager'], array_keys(PradoUnit::getProp($page->getAdapter(), '_controlsToRender')));
	}

	public function testHandleCallbackRendersTheButtonPagersOfTheControlAndRaisesOnCallback()
	{
		[$page, $pager, $second] = $this->createPagers();
		$raised = null;
		$pager->attachEventHandler('OnCallback', function ($sender, $param) use (&$raised) {
			$raised = $param;
		});
		$param = new TCallbackEventParameter($page->getResponse(), 'p');
		$pager->handleCallback($pager, $param);
		$this->assertSame($param, $raised);
		$replaced = array_map(fn ($function) => $function['Prado.Element.replace'][0], $page->getClientFunctions());
		$this->assertSame(['Pager', 'Second'], $replaced);
	}

	public function testCreatePagerButtonEncodesDefaultTextOfLinksAndLabels()
	{
		$pager = new TActivePager();
		$cases = [
			[TPagerButtonType::LinkButton, true, TActiveLinkButton::class, '&lt;&lt;'],
			[TPagerButtonType::LinkButton, false, TLabel::class, '&lt;&lt;'],
			[TPagerButtonType::PushButton, true, TActiveButton::class, '<<'],
			[TPagerButtonType::ImageButton, true, TActiveImageButton::class, '<<'],
		];
		foreach ($cases as [$type, $enabled, $class, $text]) {
			$button = PradoUnit::invoke($pager, 'createPagerButton', $type, $enabled, '<<', TActivePager::CMD_PAGE_FIRST, '');
			$this->assertInstanceOf($class, $button);
			$this->assertSame($text, $button->getText(), "$type " . (int) $enabled);
		}

		$button = PradoUnit::invoke($pager, 'createPagerButton', TPagerButtonType::LinkButton, true, '<b>First</b>', TActivePager::CMD_PAGE_FIRST, '');
		$this->assertSame('<b>First</b>', $button->getText());
	}

	public function testDisabledPushButtonIsDisabled()
	{
		$button = PradoUnit::invoke(new TActivePager(), 'createPagerButton', TPagerButtonType::PushButton, false, '<<', TActivePager::CMD_PAGE_FIRST, '');
		$this->assertInstanceOf(TActiveButton::class, $button);
		$this->assertFalse($button->getEnabled());
	}

	public function testRenderOutsideACallbackHidesASinglePage()
	{
		[$page, $pager] = $this->createPagers(false);
		PradoUnit::invoke($pager, 'setPageCount', 1);
		$this->render($pager);
		$this->assertSame(TDisplayStyle::None, $pager->getDisplay());
		$this->assertSame([], $page->getClientFunctions());
	}

	public static function singlePagerProvider(): array
	{
		return [
			'button pager' => [TPagerMode::NextPrev, 1],
			'drop-down pager' => [TPagerMode::DropDownList, 0],
		];
	}

	/**
	 * The pager is the only one in its naming container; a drop-down pager does not render again.
	 * @dataProvider singlePagerProvider
	 */
	public function testHandleCallbackWithOnePagerInTheContainer(string $mode, int $rendered)
	{
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		$page = new TTestCallbackPage();
		$page->setAdapter(new TActivePageAdapter($page));
		$single = new TActivePager();
		$single->setID('Single');
		$single->setControlToPaginate('List');
		$single->setMode($mode);
		$page->getControls()->add($single);
		PradoUnit::invoke($single, 'setControlStage', TControl::CS_PRERENDERED);
		$single->handleCallback($single, new TCallbackEventParameter($page->getResponse(), null));
		$this->assertCount($rendered, $page->getClientFunctions());
	}
}
