<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TContentDirection;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TPanelStyle;
use Prado\Web\UI\WebControls\TScrollBars;
use Prado\Web\UI\WebControls\TStyle;

class TPanelStyleTest extends TestCase
{
	/**
	 * Renders a div carrying the style's attributes.
	 * @param TStyle $style the style
	 * @return string the rendered markup
	 */
	private function renderStyle(TStyle $style): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$style->addAttributesToRender($writer);
		$writer->renderBeginTag('div');
		$writer->renderEndTag();
		return $tw->flush();
	}

	/**
	 * Creates a style with every panel property set.
	 * @return TPanelStyle the style
	 */
	private function createFullStyle(): TPanelStyle
	{
		$style = new TPanelStyle();
		$style->setBackImageUrl('bg.png');
		$style->setDirection(TContentDirection::RightToLeft);
		$style->setHorizontalAlign(THorizontalAlign::Center);
		$style->setScrollBars(TScrollBars::Both);
		$style->setWrap(false);
		$style->setBoxShadow('1px 1px 2px #000');
		return $style;
	}

	/**
	 * Returns every panel property of a style.
	 * @param TPanelStyle $style the style
	 * @return array the property values by name
	 */
	private function properties(TPanelStyle $style): array
	{
		return [
			'BackImageUrl' => $style->getBackImageUrl(),
			'Direction' => $style->getDirection(),
			'HorizontalAlign' => $style->getHorizontalAlign(),
			'ScrollBars' => $style->getScrollBars(),
			'Wrap' => $style->getWrap(),
			'BoxShadow' => $style->getBoxShadow(),
		];
	}

	private function defaults(): array
	{
		return [
			'BackImageUrl' => '',
			'Direction' => TContentDirection::NotSet,
			'HorizontalAlign' => THorizontalAlign::NotSet,
			'ScrollBars' => TScrollBars::None,
			'Wrap' => true,
			'BoxShadow' => '',
		];
	}

	private function fullValues(): array
	{
		return [
			'BackImageUrl' => 'bg.png',
			'Direction' => TContentDirection::RightToLeft,
			'HorizontalAlign' => THorizontalAlign::Center,
			'ScrollBars' => TScrollBars::Both,
			'Wrap' => false,
			'BoxShadow' => '1px 1px 2px #000',
		];
	}

	public function testDefaults()
	{
		$this->assertSame($this->defaults(), $this->properties(new TPanelStyle()));
	}

	public function testSetters()
	{
		$this->assertSame($this->fullValues(), $this->properties($this->createFullStyle()));
	}

	public function testWrapAcceptsStrings()
	{
		$style = new TPanelStyle();
		$style->setWrap('false');
		$this->assertFalse($style->getWrap());
		$style->setWrap('true');
		$this->assertTrue($style->getWrap());
	}

	public function testEnumSettersRejectInvalidValues()
	{
		$style = new TPanelStyle();
		foreach (['setDirection', 'setHorizontalAlign', 'setScrollBars'] as $setter) {
			try {
				$style->$setter('Sideways');
				$this->fail("$setter accepted an invalid value");
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('Sideways', $e->getMessage());
			}
		}
	}

	public function testRenderDefaultsWritesNoStyle()
	{
		$this->assertSame('<div></div>', $this->renderStyle(new TPanelStyle()));
	}

	public function testRenderFullStyle()
	{
		$html = $this->renderStyle($this->createFullStyle());
		$this->assertStringContainsString('background-image:url(bg.png);', $html);
		$this->assertStringContainsString('overflow:scroll;', $html);
		$this->assertStringContainsString('text-align:center;', $html);
		$this->assertStringContainsString('white-space:nowrap;', $html);
		$this->assertStringContainsString('direction:rtl;', $html);
		$this->assertStringContainsString('box-shadow:1px 1px 2px #000;', $html);
	}

	public function testRenderTrimsBackImageUrl()
	{
		$style = new TPanelStyle();
		$style->setBackImageUrl('  bg.png  ');
		$this->assertStringContainsString('background-image:url(bg.png);', $this->renderStyle($style));

		$blank = new TPanelStyle();
		$blank->setBackImageUrl('   ');
		$this->assertStringNotContainsString('background-image', $this->renderStyle($blank));
	}

	public static function scrollBarsProvider(): array
	{
		return [
			'None' => [TScrollBars::None, null],
			'Horizontal' => [TScrollBars::Horizontal, 'overflow-x:scroll;'],
			'Vertical' => [TScrollBars::Vertical, 'overflow-y:scroll;'],
			'Both' => [TScrollBars::Both, 'overflow:scroll;'],
			'Auto' => [TScrollBars::Auto, 'overflow:auto;'],
		];
	}

	/**
	 * @dataProvider scrollBarsProvider
	 */
	public function testRenderScrollBars(string $scrollBars, ?string $expected)
	{
		$style = new TPanelStyle();
		$style->setScrollBars($scrollBars);
		$html = $this->renderStyle($style);
		if ($expected === null) {
			$this->assertStringNotContainsString('overflow', $html);
		} else {
			$this->assertStringContainsString($expected, $html);
		}
	}

	public function testRenderDirectionLeftToRight()
	{
		$style = new TPanelStyle();
		$style->setDirection(TContentDirection::LeftToRight);
		$this->assertStringContainsString('direction:ltr;', $this->renderStyle($style));
	}

	public function testRenderHorizontalAlignIsLowercase()
	{
		$style = new TPanelStyle();
		$style->setHorizontalAlign(THorizontalAlign::Justify);
		$this->assertStringContainsString('text-align:justify;', $this->renderStyle($style));
	}

	public function testRenderKeepsBaseStyle()
	{
		$style = new TPanelStyle();
		$style->setWidth('10px');
		$style->setCssClass('box');
		$style->setWrap(false);
		$html = $this->renderStyle($style);
		$this->assertStringContainsString('width:10px;', $html);
		$this->assertStringContainsString('white-space:nowrap;', $html);
		$this->assertStringContainsString('class="box"', $html);
	}

	public function testResetRestoresDefaults()
	{
		$style = $this->createFullStyle();
		$style->setWidth('10px');
		$style->reset();
		$this->assertSame($this->defaults(), $this->properties($style));
		$this->assertSame('', $style->getWidth());
	}

	public function testCopyFromPanelStyleOverwrites()
	{
		$target = new TPanelStyle();
		$target->setBackImageUrl('old.png');
		$target->setWrap(true);
		$target->copyFrom($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromEmptyPanelStyleKeepsValues()
	{
		$target = $this->createFullStyle();
		$target->copyFrom(new TPanelStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromBaseStyleCopiesBaseFieldsOnly()
	{
		$source = new TStyle();
		$source->setWidth('20px');
		$target = new TPanelStyle();
		$target->copyFrom($source);
		$this->assertSame('20px', $target->getWidth());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testMergeWithFillsUnsetValues()
	{
		$target = new TPanelStyle();
		$target->mergeWith($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testMergeWithKeepsSetValues()
	{
		$target = new TPanelStyle();
		$target->setBackImageUrl('mine.png');
		$target->setDirection(TContentDirection::LeftToRight);
		$target->setHorizontalAlign(THorizontalAlign::Left);
		$target->setScrollBars(TScrollBars::Auto);
		$target->setWrap(true);
		$target->setBoxShadow('none');
		$expected = $this->properties($target);
		$target->mergeWith($this->createFullStyle());
		$this->assertSame($expected, $this->properties($target));
	}

	public function testMergeWithEmptyPanelStyleKeepsUnsetValues()
	{
		$target = new TPanelStyle();
		$target->mergeWith(new TPanelStyle());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testMergeWithBaseStyleMergesBaseFieldsOnly()
	{
		$source = new TStyle();
		$source->setHeight('5px');
		$target = new TPanelStyle();
		$target->mergeWith($source);
		$this->assertSame('5px', $target->getHeight());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testSerializeDefaultsOmitsPanelFields()
	{
		$data = serialize(new TPanelStyle());
		foreach (['_backImageUrl', '_direction', '_horizontalAlign', '_scrollBars', '_wrap', '_boxshadow'] as $field) {
			$this->assertStringNotContainsString($field, $data);
		}
		$this->assertSame($this->defaults(), $this->properties(unserialize($data)));
	}

	public function testSerializeRoundTripKeepsPanelFields()
	{
		$data = serialize($this->createFullStyle());
		foreach (['_backImageUrl', '_direction', '_horizontalAlign', '_scrollBars', '_wrap', '_boxshadow'] as $field) {
			$this->assertStringContainsString($field, $data);
		}
		$this->assertSame($this->fullValues(), $this->properties(unserialize($data)));
	}
}
