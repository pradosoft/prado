<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TAccordion;
use Prado\Web\UI\WebControls\TAccordionView;
use Prado\Web\UI\WebControls\TLiteral;

class TAccordionViewTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createView(string $id = 'v', bool $javaScript = true): TAccordionView
	{
		$page = new TPage();
		$page->setClientSupportsJavaScript($javaScript);
		$accordion = new TAccordion();
		$page->getControls()->add($accordion);
		$view = new TAccordionView();
		$view->setID($id);
		$accordion->getViews()->add($view);
		return $view;
	}

	private function renderHeader(TAccordionView $view): string
	{
		$tw = new TTextWriter();
		$view->renderHeader(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaults()
	{
		$view = new TAccordionView();
		$this->assertSame('div', PradoUnit::invoke($view, 'getTagName'));
		$this->assertSame('', $view->getCaption());
		$this->assertSame('', $view->getNavigateUrl());
		$this->assertSame('', $view->getText());
		$this->assertFalse($view->getActive());
	}

	public function testSetters()
	{
		$view = new TAccordionView();
		$view->setCaption('Cap');
		$this->assertSame('Cap', $view->getCaption());
		$view->setNavigateUrl('/x');
		$this->assertSame('/x', $view->getNavigateUrl());
		$view->setText('Body');
		$this->assertSame('Body', $view->getText());
		$view->setActive('true');
		$this->assertTrue($view->getActive());
	}

	public function testRenderAnInactiveView()
	{
		$view = $this->createView();
		$this->assertSame('<div id="v" class="accordion-view" role="region" aria-labelledby="v_0" style="display:none;"></div>', $this->render($view));
	}

	public function testRenderAnActiveView()
	{
		$view = $this->createView();
		$view->setActive(true);
		$view->getParent()->setViewCssClass('body');
		$this->assertSame('<div id="v" class="body" role="region" aria-labelledby="v_0"></div>', $this->render($view));
	}

	public function testRenderWithoutJavaScriptShowsTheView()
	{
		$view = $this->createView('v', false);
		$this->assertSame('<div id="v" class="accordion-view" role="region" aria-labelledby="v_0"></div>', $this->render($view));
	}

	public function testRenderANavigationView()
	{
		$view = $this->createView();
		$view->setActive(true);
		$view->setNavigateUrl('/x');
		$this->assertSame('<div id="v" class="accordion-view"></div>', $this->render($view));
	}

	public function testRenderAnInactiveNavigationView()
	{
		$view = $this->createView();
		$view->setNavigateUrl('/x');
		$this->assertSame('<div id="v" class="accordion-view" style="display:none;"></div>', $this->render($view));
		$view = $this->createView('v', false);
		$view->setNavigateUrl('/x');
		$this->assertSame('<div id="v" class="accordion-view"></div>', $this->render($view));
	}

	public function testRenderContents()
	{
		$view = $this->createView();
		$this->assertSame('', $this->renderContents($view));
		$literal = new TLiteral();
		$literal->setText('child');
		$view->getControls()->add($literal);
		$this->assertSame('child', $this->renderContents($view));
		$view->setText('<b>text</b>');
		$this->assertSame('<b>text</b>', $this->renderContents($view));
	}

	public function testRenderContentsEncodesTextWhenEncode()
	{
		$view = new TAccordionView();
		$this->assertFalse($view->getEncode());
		$view->setText('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderContents($view));

		$view->setEncode(true);
		$this->assertTrue($view->getEncode());
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($view));
	}

	public function testRenderHeaderEncodesCaptionWhenEncode()
	{
		$view = $this->createView();
		$view->setCaption('<b>a & b</b>');
		$view->setNavigateUrl('/x');
		$this->assertSame('<div id="v_0" class="accordion-header"><a href="/x"><b>a & b</b></a></div>', $this->renderHeader($view));

		$view->setEncode(true);
		$this->assertSame('<div id="v_0" class="accordion-header"><a href="/x">&lt;b&gt;a & b&lt;/b&gt;</a></div>', $this->renderHeader($view));
		$view->setCaption('');
		$this->assertSame('<div id="v_0" class="accordion-header"><a href="/x">&nbsp;</a></div>', $this->renderHeader($view));
	}

	public function testRenderHeader()
	{
		$view = $this->createView();
		$this->assertSame('<div id="v_0" role="button" aria-expanded="false" aria-controls="v" tabindex="0" class="accordion-header">&nbsp;</div>', $this->renderHeader($view));

		$view->setActive(true);
		$view->setCaption('Cap');
		$this->assertSame('<div id="v_0" role="button" aria-expanded="true" aria-controls="v" tabindex="0" class="accordion-header-active">Cap</div>', $this->renderHeader($view));
	}

	public function testRenderANavigationHeader()
	{
		$view = $this->createView();
		$view->setCaption('Cap');
		$view->setNavigateUrl('/x?a=1&b="2"');
		$this->assertSame('<div id="v_0" class="accordion-header"><a href="/x?a=1&b=&quot;2&quot;">Cap</a></div>', $this->renderHeader($view));
		$view->setActive(true);
		$view->setCaption('');
		$this->assertSame('<div id="v_0" class="accordion-header-active"><a href="/x?a=1&b=&quot;2&quot;">&nbsp;</a></div>', $this->renderHeader($view));
	}

	public function testRenderHeaderOfAHiddenViewOrWithoutJavaScript()
	{
		$view = $this->createView();
		$view->setVisible(false);
		$this->assertSame('', $this->renderHeader($view));

		$view = $this->createView('v', false);
		$this->assertSame('', $this->renderHeader($view));
	}
}
