<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Web\UI\JuiControls\TJuiCallbackPageStateTracker;
use Prado\Web\UI\JuiControls\TJuiControlAdapter;
use Prado\Web\UI\JuiControls\TJuiDraggable;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;

/**
 * Unit tests for {@see TJuiControlAdapter}.
 */
class TJuiControlAdapterTest extends TestCase
{
	use TPageLifecycleTrait;

	protected function setUp(): void
	{
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	/**
	 * @param ?callable $configure configures each draggable, receives it and the page
	 * @return TTestLifecyclePage a page with the draggables `A` and `B` in its form
	 */
	private function newPage(?callable $configure = null): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($configure) {
			$page->getControls()->insertAt(0, new THead());
			foreach (['A', 'B'] as $id) {
				$control = new TJuiDraggable();
				$control->setID($id);
				$form->getControls()->add($control);
				if ($configure) {
					$configure($control, $page);
				}
			}
		};
		return $page;
	}

	public function testJuiBaseStyle(): void
	{
		$control = new TJuiDraggable();
		$adapter = $control->getAdapter();
		$this->assertInstanceOf(TJuiControlAdapter::class, $adapter);
		$this->assertSame('base', $adapter->getJuiBaseStyle());
		$adapter->setJuiBaseStyle('smoothness');
		$this->assertSame('smoothness', $adapter->getJuiBaseStyle());
		$this->assertSame('smoothness', $control->getViewState('JuiBaseStyle'));
	}

	/**
	 * The jQuery UI script and the theme style sheet are registered once for all controls.
	 */
	public function testPreRenderRegistersTheScriptAndTheThemeStyleSheetOnce(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->getAdapter()->setJuiBaseStyle('smoothness');
		}));
		$this->assertSame(1, substr_count($output, 'themes/smoothness/jquery-ui.css'));
		$this->assertSame(1, substr_count($output, 'jquery-ui.js'));
	}

	public function testPublishJuiStyleReturnsTheThemeFileUrl(): void
	{
		$urls = [];
		$this->runPage($this->newPage(function ($control, $page) use (&$urls) {
			$page->preRender = function ($page) use (&$urls) {
				$adapter = $page->findControl('A')->getAdapter();
				$urls[] = $adapter->publishJuiStyle('extra.css');
				$urls[] = $adapter->publishJuiStyle('extra.css');
			};
		}));
		$this->assertCount(2, $urls);
		$this->assertSame($urls[0], $urls[1]);
		$this->assertStringEndsWith('/themes/base/extra.css', $urls[0]);
	}

	/**
	 * A callback tracks the page state with {@see TJuiCallbackPageStateTracker}, and the
	 * loaded options belong to the control of the callback page.
	 */
	public function testCallbackTracksTheStateAndRebindsTheOptions(): void
	{
		$seen = [];
		$configure = function ($control, $page) use (&$seen) {
			$control->getOptions()->axis = 'x';
			$control->attachEventHandler('OnStop', function ($sender) use (&$seen) {
				$seen[] = $sender->getAdapter()->getStateTracker();
				$seen[] = array_keys($sender->getOptions()->toArray());
			});
		};
		$this->runPage($this->newPage($configure));
		$this->runCallback($this->newPage($configure), 'A', ['event' => 'stop']);
		$this->assertInstanceOf(TJuiCallbackPageStateTracker::class, $seen[0]);
		$this->assertSame(['axis', 'stop'], $seen[1]);
	}
}
