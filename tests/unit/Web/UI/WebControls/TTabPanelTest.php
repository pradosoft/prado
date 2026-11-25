<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTabPanel;
use Prado\Web\UI\WebControls\TTabView;
use Prado\Web\UI\WebControls\TTabViewCollection;

class TTabPanelTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
	}

	/**
	 * Creates a tab panel on a page with a view per ID.
	 */
	private function createPanel(array $ids = ['a', 'b', 'c']): TTabPanel
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TViewSwitcherTestClientScript($page));
		$panel = new TTabPanel();
		$panel->setID('tp');
		foreach ($ids as $id) {
			$view = new TTabView();
			$view->setID($id);
			$view->setCaption(strtoupper($id));
			$panel->addParsedObject($view);
		}
		$page->getControls()->add($panel);
		return $panel;
	}

	private function clientScript(TTabPanel $panel): TViewSwitcherTestClientScript
	{
		return $panel->getPage()->getClientScript();
	}

	private function activeFlags(TTabPanel $panel): array
	{
		return array_map(fn ($view) => $view->getActive(), $panel->getViews()->toArray());
	}

	public function testEmptyCssUrlRegistersNoStyleSheet()
	{
		$panel = $this->createPanel();
		// a template sets attributes with tracking off, so a value equal to the default is dropped
		$panel->trackViewState(false);
		$panel->setCssUrl('');
		$this->assertSame('', $panel->getCssUrl());
		$panel->onPreRender(new TEventParameter());
		$this->assertSame([], $this->clientScript($panel)->styleSheets);

		$panel->setCssUrl('default');
		$this->assertSame('default', $panel->getCssUrl());
	}

	public function testLoadPostDataIgnoresAnIndexOutsideTheViews()
	{
		$panel = $this->createPanel();
		$key = $panel->getClientID() . '_1';
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => '3'])));
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => '-1'])));
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => 'x'])));
		$this->assertSame(0, $panel->getActiveViewIndex());
		$this->assertFalse($panel->getDataChanged());
	}

	public function testRenderWithoutViews()
	{
		$panel = $this->createPanel([]);
		$panel->setCssUrl('');
		$panel->onPreRender(new TEventParameter());
		$this->assertSame('<div id="tp" class="tab-panel"></div>', $this->render($panel));
		$this->assertSame(['tp_1' => 0], $this->clientScript($panel)->hiddenFields);
	}

	public function testRenderWithoutAnActiveView()
	{
		$panel = $this->createPanel();
		$panel->setCssUrl('');
		$panel->setActiveViewIndex(-1);
		$panel->onPreRender(new TEventParameter());
		$this->render($panel);
		$this->assertSame(['tp_1' => 0], $this->clientScript($panel)->hiddenFields);
	}
}
