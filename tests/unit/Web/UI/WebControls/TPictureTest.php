<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TImage;
use Prado\Web\UI\WebControls\TMediaSource;
use Prado\Web\UI\WebControls\TPicture;

class TPictureTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testExtendsTImage()
	{
		$this->assertInstanceOf(TImage::class, new TPicture());
	}

	public function testRendersPictureWithImage()
	{
		$control = new TPicture();
		$control->setImageUrl('chart.png');
		$control->setAlternateText('Sales rose');
		$this->assertSame('<picture><img src="chart.png" alt="Sales rose" /></picture>', $this->render($control));
	}

	public function testRendersSourcesBeforeImage()
	{
		$control = new TPicture();
		$control->setImageUrl('chart.png');
		$avif = new TMediaSource();
		$avif->setSrcset('chart.avif');
		$avif->setType('image/avif');
		$control->getControls()->add($avif);
		$this->assertSame('<picture><source type="image/avif" srcset="chart.avif" /><img src="chart.png" alt="" /></picture>', $this->render($control));
	}

	public function testAttributesRenderOnImage()
	{
		$control = new TPicture();
		$control->setID('chart');
		$control->setCssClass('wide');
		$control->setImageUrl('chart.png');
		$this->assertSame('<picture><img src="chart.png" alt="" id="chart" class="wide" /></picture>', $this->render($control));
	}

	public function testDecorativeImageRendersEmptyAlt()
	{
		$control = new TPicture();
		$this->assertStringContainsString('alt=""', $this->render($control));
	}
}
