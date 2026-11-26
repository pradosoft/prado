<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TLabel;

/**
 * A TActivePanel renders in a callback after it prerenders, and before then defers
 * its render to the page adapter.
 */
class TActivePanelTest extends TestCase
{
	private function writer(): THtmlWriter
	{
		return new THtmlWriter(new TTextWriter());
	}

	public function testRenderAfterPreRenderReplacesTheClientContent()
	{
		$page = new TActiveTestPage();
		$panel = $page->addControl(new TActivePanel(), 'Box', TControl::CS_PRERENDERED);
		$panel->getControls()->add('text');
		$writer = $this->writer();
		$panel->render($writer);
		$this->assertSame('<div id="Box">text</div>', $writer->flush());
		$functions = $page->getClientFunctions();
		$this->assertCount(1, $functions);
		$this->assertSame('Box', $functions[0]['Prado.Element.replace'][0]);
	}

	public function testRenderAfterPreRenderOutsideACallbackOnlyRenders()
	{
		$page = new TActiveTestPage(false);
		$panel = $page->addControl(new TActivePanel(), 'Box', TControl::CS_PRERENDERED);
		$writer = $this->writer();
		$panel->render($writer);
		$this->assertSame('<div id="Box"></div>', $writer->flush());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRenderBeforePreRenderDefersAndDisablesTheActiveChildUpdates()
	{
		$page = new TActiveTestPage();
		$panel = $page->addControl(new TActivePanel(), 'Box');
		$active = new TActiveLabel();
		$plain = new TLabel();
		$panel->getControls()->add($active);
		$panel->getControls()->add($plain);
		$panel->render($this->writer());
		$this->assertSame(['Box' => $panel], $page->pageAdapter->rendered);
		$this->assertFalse($active->getActiveControl()->getEnableUpdate());
	}

	public function testRenderBeforePreRenderWithoutActiveChildrenDefers()
	{
		$page = new TActiveTestPage();
		$panel = $page->addControl(new TActivePanel(), 'Box');
		$panel->getControls()->add(new TLabel());
		$panel->render($this->writer());
		$this->assertSame(['Box' => $panel], $page->pageAdapter->rendered);
	}

	public function testRenderBeforePreRenderWithoutChildrenDefers()
	{
		$page = new TActiveTestPage();
		$panel = $page->addControl(new TActivePanel(), 'Box');
		$this->assertFalse($panel->getHasControls());
		$panel->render($this->writer());
		$this->assertSame(['Box' => $panel], $page->pageAdapter->rendered);
	}
}
