<?php

use Prado\Web\UI\WebControls\TFigure;
use Prado\Web\UI\WebControls\TFigureCaption;
use PHPUnit\Framework\TestCase;

class TFigureCaptionTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersFigcaptionTag()
	{
		$control = new TFigureCaption();
		$output = $this->render($control);
		$this->assertStringContainsString('<figcaption', $output);
		$this->assertStringContainsString('</figcaption>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TFigureCaption();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testRendersWithAttributes()
	{
		$control = new TFigureCaption();
		$control->setCssClass('caption');
		$output = $this->render($control);
		$this->assertStringContainsString('class="caption"', $output);
		$this->assertStringContainsString('<figcaption', $output);
	}

	public function testRendersWithInnerText()
	{
		$figure = new TFigure();
		$caption = new TFigureCaption();
		$figure->getControls()->add($caption);
		$caption->getControls()->add('Photo of a sunset');
		$output = $this->render($caption);
		$this->assertStringContainsString('Photo of a sunset', $output);
	}

	public function testOnInitThrowsWhenParentIsNotTFigure()
	{
		$caption = new TFigureCaption();
		// Parent is null — not a TFigure
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$caption->onInit(null);
	}

	public function testOnInitThrowsWhenParentIsWrongType()
	{
		$outer = new \Prado\Web\UI\WebControls\TPanel();
		$caption = new TFigureCaption();
		$outer->getControls()->add($caption);
		// Parent is TPanel — not a TFigure
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$caption->onInit(null);
	}

	public function testOnInitDoesNotThrowWhenParentIsTFigure()
	{
		$figure = new TFigure();
		$caption = new TFigureCaption();
		$figure->getControls()->add($caption);
		// Should not throw
		$caption->onInit(null);
		$this->assertInstanceOf(TFigure::class, $caption->getParent());
	}
}
