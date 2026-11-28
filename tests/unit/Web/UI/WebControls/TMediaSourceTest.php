<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TMediaSource;
use Prado\Web\UI\WebControls\TWebControl;

class TMediaSourceTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testExtendsTWebControl()
	{
		$this->assertInstanceOf(TWebControl::class, new TMediaSource());
	}

	public function testRendersVoidElement()
	{
		$control = new TMediaSource();
		$control->getControls()->add('ignored');
		$this->assertSame('<source />', $this->render($control));
	}

	public function testMediaSourceAttributes()
	{
		$control = new TMediaSource();
		$control->setSrc('intro.webm');
		$control->setType('video/webm; codecs="vp9"');
		$control->setMedia('(min-width: 600px)');
		$this->assertSame('intro.webm', $control->getSrc());
		$this->assertSame('video/webm; codecs="vp9"', $control->getType());
		$this->assertSame('(min-width: 600px)', $control->getMedia());
		$this->assertSame('<source src="intro.webm" type="video/webm; codecs=&quot;vp9&quot;" media="(min-width: 600px)" />', $this->render($control));
	}

	public function testPictureSourceAttributes()
	{
		$control = new TMediaSource();
		$control->setSrcset('a.webp 1x, a@2x.webp 2x');
		$control->setSizes('100vw');
		$control->setWidth('640');
		$control->setHeight(360);
		$this->assertSame(640, $control->getWidth());
		$this->assertSame('<source srcset="a.webp 1x, a@2x.webp 2x" sizes="100vw" width="640" height="360" />', $this->render($control));
		$control->setWidth(-2);
		$this->assertSame(-1, $control->getWidth());
	}

	public function testSourceAttributeCombinations()
	{
		foreach ([false, true] as $all) {
			foreach ([-1, 640] as $width) {
				foreach ([-1, 360] as $height) {
					$control = new TMediaSource();
					if ($all) {
						$control->setSrc('a.webm');
						$control->setType('video/webm');
						$control->setMedia('print');
						$control->setSrcset('a.webp');
						$control->setSizes('100vw');
					}
					$control->setWidth($width);
					$control->setHeight($height);
					$expected = '<source'
						. ($all ? ' src="a.webm" type="video/webm" media="print" srcset="a.webp" sizes="100vw"' : '')
						. ($width === -1 ? '' : ' width="640"')
						. ($height === -1 ? '' : ' height="360"')
						. ' />';
					$this->assertSame($expected, $this->render($control));
				}
			}
		}
	}

	public function testDefaults()
	{
		$control = new TMediaSource();
		foreach (['Src', 'Type', 'Media', 'Srcset', 'Sizes'] as $name) {
			$this->assertSame('', $control->{'get' . $name}(), $name);
		}
		$this->assertSame(-1, $control->getWidth());
		$this->assertSame(-1, $control->getHeight());
	}

	public function testNegativeHeightUnsets()
	{
		$source = new \Prado\Web\UI\WebControls\TMediaSource();
		$source->setHeight(10);
		$source->setHeight(-3);
		$this->assertSame(-1, $source->getHeight());
	}
}
