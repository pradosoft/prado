<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TTabPanel;
use Prado\Web\UI\WebControls\TTabView;

class TTabViewTest extends TestCase
{
	use TWebControlRenderTrait;

	private function renderTabContent(TTabView $view): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($view, 'renderTabContent', new THtmlWriter($tw));
		return $tw->flush();
	}

	private function renderTab(TTabView $view): string
	{
		$tw = new TTextWriter();
		$view->renderTab(new THtmlWriter($tw));
		return $tw->flush();
	}

	private function createView(string $id = 'v', bool $javaScript = true): TTabView
	{
		$page = new TPage();
		$page->setClientSupportsJavaScript($javaScript);
		$panel = new TTabPanel();
		$page->getControls()->add($panel);
		$view = new TTabView();
		$view->setID($id);
		$panel->getViews()->add($view);
		return $view;
	}

	public function testRenderTab()
	{
		$view = $this->createView();
		$view->setCaption('Cap');
		$this->assertSame('<div id="v_0" role="tab" aria-selected="false" aria-controls="v" tabindex="-1" class="tab-normal">Cap</div>', $this->renderTab($view));
		$view->setActive(true);
		$this->assertSame('<div id="v_0" role="tab" aria-selected="true" aria-controls="v" tabindex="0" class="tab-active">Cap</div>', $this->renderTab($view));
		$view->setNavigateUrl('/x?a=1&b="2"');
		$this->assertSame('<div id="v_0" class="tab-active"><a href="/x?a=1&b=&quot;2&quot;">Cap</a></div>', $this->renderTab($view));
		$view->setActive(false);
		$view->setCaption('');
		$this->assertSame('<div id="v_0" class="tab-normal"><a href="/x?a=1&b=&quot;2&quot;">&nbsp;</a></div>', $this->renderTab($view));
	}

	public function testRenderContentsEncodesTextWhenEncode()
	{
		$view = new TTabView();
		$this->assertFalse($view->getEncode());
		$view->setText('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderContents($view));

		$view->setEncode(true);
		$this->assertTrue($view->getEncode());
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($view));
	}

	public function testRenderTabContentEncodesCaptionWhenEncode()
	{
		$view = new TTabView();
		$this->assertSame('&nbsp;', $this->renderTabContent($view));

		$view->setCaption('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderTabContent($view));
		$view->setNavigateUrl('/x');
		$this->assertSame('<a href="/x"><b>a & b</b></a>', $this->renderTabContent($view));

		$view->setEncode(true);
		$this->assertSame('<a href="/x">&lt;b&gt;a & b&lt;/b&gt;</a>', $this->renderTabContent($view));
		$view->setNavigateUrl('');
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderTabContent($view));
		$view->setCaption('');
		$this->assertSame('&nbsp;', $this->renderTabContent($view));
	}
}
