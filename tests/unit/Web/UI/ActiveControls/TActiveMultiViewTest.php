<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Web\UI\ActiveControls\TActiveMultiView;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TView;

/**
 * A TActiveMultiView renders its active view inside a container span, and renders
 * again in a callback when its active view changes.
 */
class TActiveMultiViewTest extends TestCase
{
	/**
	 * Initializes the application response, whose output buffer starts with it, outside a test.
	 */
	public static function setUpBeforeClass(): void
	{
		Prado::getApplication()->getResponse();
	}

	private function createMultiView(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$multiView = new TActiveMultiView();
		foreach (['First', 'Second'] as $id) {
			$view = new TView();
			$view->setID($id);
			$view->getControls()->add($id);
			$multiView->getControls()->add($view);
		}
		$page->addControl($multiView, 'Views', $stage);
		return [$page, $multiView];
	}

	private function writer(): THtmlWriter
	{
		return new THtmlWriter(new TTextWriter());
	}

	public function testSetActiveViewIndexRegistersTheRenderInACallback()
	{
		[$page, $multiView] = $this->createMultiView();
		$multiView->setActiveViewIndex(1);
		$this->assertSame(['Views' => $multiView], $page->pageAdapter->rendered);
		$this->assertSame('Second', $multiView->getActiveView()->getID());
	}

	public function testSetActiveViewRegistersTheRenderInACallback()
	{
		[$page, $multiView] = $this->createMultiView();
		$multiView->setActiveView($multiView->getViews()->itemAt(0));
		$this->assertSame(['Views' => $multiView], $page->pageAdapter->rendered);
		$this->assertSame(0, $multiView->getActiveViewIndex());
	}

	public function testSetActiveViewOutsideACallbackRegistersNothing()
	{
		[$page, $multiView] = $this->createMultiView(false);
		$multiView->setActiveViewIndex(1);
		$multiView->setActiveView($multiView->getViews()->itemAt(0));
		$this->assertSame([], $page->pageAdapter->rendered);
		$this->assertSame(0, $multiView->getActiveViewIndex());
	}

	public function testRenderAfterPreRenderReplacesTheContainer()
	{
		[$page, $multiView] = $this->createMultiView(true, TControl::CS_PRERENDERED);
		$multiView->setActiveViewIndex(0);
		$writer = $this->writer();
		$multiView->render($writer);
		$this->assertSame('<span id="Views_Container">First</span>', $writer->flush());
		$functions = $page->getClientFunctions();
		$this->assertSame('Views_Container', end($functions)['Prado.Element.replace'][0]);
	}

	public function testRenderAfterPreRenderOutsideACallbackOnlyRenders()
	{
		[$page, $multiView] = $this->createMultiView(false, TControl::CS_PRERENDERED);
		$writer = $this->writer();
		$multiView->render($writer);
		$this->assertSame('<span id="Views_Container"></span>', $writer->flush());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRenderBeforePreRenderDefers()
	{
		[$page, $multiView] = $this->createMultiView();
		$multiView->render($this->writer());
		$this->assertSame(['Views' => $multiView], $page->pageAdapter->rendered);
	}
}
