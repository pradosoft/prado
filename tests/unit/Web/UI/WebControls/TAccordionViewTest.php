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
}
