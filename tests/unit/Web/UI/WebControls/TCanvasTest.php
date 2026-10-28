<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TCanvas;

class TCanvasTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TCanvas();
	}

	protected function defaultTagName(): string
	{
		return 'canvas';
	}

	public function testWidthAndHeightRenderAsAttributes()
	{
		$control = new TCanvas();
		$this->assertSame(-1, $control->getWidth());
		$this->assertSame(-1, $control->getHeight());
		$this->assertSame('<canvas></canvas>', $this->render($control));
		$control->setWidth('400');
		$control->setHeight(200);
		$this->assertSame(400, $control->getWidth());
		$this->assertSame('<canvas width="400" height="200"></canvas>', $this->render($control));
		$control->setWidth(-5);
		$this->assertSame(-1, $control->getWidth());
	}

	public function testDisplaySizeIsStyle()
	{
		$control = new TCanvas();
		$control->getStyle()->setWidth('100%');
		$control->setWidth(400);
		$this->assertSame('<canvas width="400" style="width:100%;"></canvas>', $this->render($control));
	}

	public function testAlternateTextRendersImageRoleAndLabel()
	{
		$control = new TCanvas();
		$control->setAlternateText('Sales rose');
		$this->assertSame('<canvas role="img" aria-label="Sales rose"></canvas>', $this->render($control));
	}

	public function testRoleAndAriaLabelReplaceAlternateText()
	{
		$control = new TCanvas();
		$control->setAlternateText('Sales rose');
		$control->setRole('figure');
		$control->getAria()->add('aria-label', 'Sales chart');
		$this->assertSame('<canvas aria-label="Sales chart" role="figure"></canvas>', $this->render($control));
	}

	public function testFallbackContentRenders()
	{
		$control = new TCanvas();
		$control->getControls()->add('<table></table>');
		$this->assertSame('<canvas><table></table></canvas>', $this->render($control));
	}
}
