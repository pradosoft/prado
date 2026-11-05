<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
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
