<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTableGridLines;
use Prado\Web\UI\WebControls\TTableStyle;

class TTableStyleTest extends TestCase
{
	/**
	 * Renders a table tag carrying the style's attributes.
	 * @param TStyle $style the style
	 * @return string the rendered markup
	 */
	private function renderStyle(TStyle $style): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$style->addAttributesToRender($writer);
		$writer->renderBeginTag('table');
		$writer->renderEndTag();
		return $tw->flush();
	}

	/**
	 * Creates a style with every table property set.
	 * @return TTableStyle the style
	 */
	private function createFullStyle(): TTableStyle
	{
		$style = new TTableStyle();
		$style->setBackImageUrl('bg.png');
		$style->setHorizontalAlign(THorizontalAlign::Center);
		$style->setCellPadding(3);
		$style->setCellSpacing(4);
		$style->setGridLines(TTableGridLines::Both);
		$style->setBorderCollapse(true);
		return $style;
	}

	/**
	 * Returns every table property of a style.
	 * @param TTableStyle $style the style
	 * @return array the property values by name
	 */
	private function properties(TTableStyle $style): array
	{
		return [
			'BackImageUrl' => $style->getBackImageUrl(),
			'HorizontalAlign' => $style->getHorizontalAlign(),
			'CellPadding' => $style->getCellPadding(),
			'CellSpacing' => $style->getCellSpacing(),
			'GridLines' => $style->getGridLines(),
			'BorderCollapse' => $style->getBorderCollapse(),
		];
	}

	private function defaults(): array
	{
		return [
			'BackImageUrl' => '',
			'HorizontalAlign' => THorizontalAlign::NotSet,
			'CellPadding' => -1,
			'CellSpacing' => -1,
			'GridLines' => TTableGridLines::None,
			'BorderCollapse' => false,
		];
	}

	private function fullValues(): array
	{
		return [
			'BackImageUrl' => 'bg.png',
			'HorizontalAlign' => THorizontalAlign::Center,
			'CellPadding' => 3,
			'CellSpacing' => 4,
			'GridLines' => TTableGridLines::Both,
			'BorderCollapse' => true,
		];
	}

	public function testDefaults()
	{
		$this->assertSame($this->defaults(), $this->properties(new TTableStyle()));
	}

	public function testSetters()
	{
		$this->assertSame($this->fullValues(), $this->properties($this->createFullStyle()));
	}

	public function testSettersConvertStrings()
	{
		$style = new TTableStyle();
		$style->setCellPadding('5');
		$style->setCellSpacing('-1');
		$style->setBorderCollapse('true');
		$this->assertSame(5, $style->getCellPadding());
		$this->assertSame(-1, $style->getCellSpacing());
		$this->assertTrue($style->getBorderCollapse());
	}

	public function testCellPaddingBelowMinusOneThrows()
	{
		try {
			(new TTableStyle())->setCellPadding(-2);
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('tablestyle_cellpadding_invalid', $e->getErrorCode());
		}
	}

	public function testCellSpacingBelowMinusOneThrows()
	{
		try {
			(new TTableStyle())->setCellSpacing(-2);
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('tablestyle_cellspacing_invalid', $e->getErrorCode());
		}
	}

	public function testEnumSettersRejectInvalidValues()
	{
		$style = new TTableStyle();
		foreach (['setHorizontalAlign', 'setGridLines'] as $setter) {
			try {
				$style->$setter('Sideways');
				$this->fail("$setter accepted an invalid value");
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('Sideways', $e->getMessage());
			}
		}
	}

	public function testRenderDefaultsWritesNothing()
	{
		$this->assertSame('<table></table>', $this->renderStyle(new TTableStyle()));
	}

	public function testRenderFullStyle()
	{
		$this->assertSame(
			'<table cellpadding="3" cellspacing="4" rules="all" style="background-image:url(bg.png);text-align:center;border-collapse:collapse;"></table>',
			$this->renderStyle($this->createFullStyle())
		);
	}

	public function testRenderZeroPaddingAndSpacing()
	{
		$style = new TTableStyle();
		$style->setCellPadding(0);
		$style->setCellSpacing(0);
		$this->assertSame('<table cellpadding="0" cellspacing="0"></table>', $this->renderStyle($style));
	}

	public function testRenderTrimsBackImageUrl()
	{
		$style = new TTableStyle();
		$style->setBackImageUrl('  bg.png ');
		$this->assertSame('<table style="background-image:url(bg.png);"></table>', $this->renderStyle($style));

		$style->setBackImageUrl('  ');
		$this->assertSame('<table></table>', $this->renderStyle($style));
	}

	public static function gridLinesProvider(): array
	{
		return [
			'None' => [TTableGridLines::None, '<table></table>'],
			'Horizontal' => [TTableGridLines::Horizontal, '<table rules="rows"></table>'],
			'Vertical' => [TTableGridLines::Vertical, '<table rules="cols"></table>'],
			'Both' => [TTableGridLines::Both, '<table rules="all"></table>'],
		];
	}

	/**
	 * @dataProvider gridLinesProvider
	 */
	public function testRenderGridLines(string $gridLines, string $expected)
	{
		$style = new TTableStyle();
		$style->setGridLines($gridLines);
		$this->assertSame($expected, $this->renderStyle($style));
	}

	public function testRenderKeepsBaseStyle()
	{
		$style = new TTableStyle();
		$style->setWidth('10px');
		$style->setCssClass('grid');
		$style->setHorizontalAlign(THorizontalAlign::Right);
		$this->assertSame('<table class="grid" style="text-align:right;width:10px;"></table>', $this->renderStyle($style));
	}

	public function testRenderLeavesTheStyleFieldsUnchanged()
	{
		$style = $this->createFullStyle();
		$this->renderStyle($style);
		$this->assertFalse($style->getHasStyleFields());
	}

	public function testResetRestoresDefaults()
	{
		$style = $this->createFullStyle();
		$style->reset();
		$this->assertSame($this->defaults(), $this->properties($style));
	}

	public function testResetClearsTheBaseStyle()
	{
		$style = $this->createFullStyle();
		$style->setWidth('10px');
		$style->setCssClass('grid');
		$style->setCustomStyle('color:red');
		$style->getFont()->setBold(true);
		$style->reset();
		$this->assertFalse($style->getHasStyleFields());
		$this->assertFalse($style->getHasCssClass());
		$this->assertFalse($style->getHasCustomStyle());
		$this->assertFalse($style->getHasFont());
		$this->assertSame('<table></table>', $this->renderStyle($style));
	}

	public function testCopyFromTableStyleOverwrites()
	{
		$target = new TTableStyle();
		$target->setBackImageUrl('old.png');
		$target->setCellPadding(9);
		$target->copyFrom($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromEmptyTableStyleKeepsValues()
	{
		$target = $this->createFullStyle();
		$target->copyFrom(new TTableStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromBaseStyleCopiesBaseFieldsOnly()
	{
		$source = new TStyle();
		$source->setWidth('20px');
		$target = new TTableStyle();
		$target->copyFrom($source);
		$this->assertSame('20px', $target->getWidth());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testMergeWithFillsUnsetValues()
	{
		$target = new TTableStyle();
		$target->mergeWith($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testMergeWithKeepsSetValues()
	{
		$target = new TTableStyle();
		$target->setBackImageUrl('mine.png');
		$target->setHorizontalAlign(THorizontalAlign::Left);
		$target->setCellPadding(0);
		$target->setCellSpacing(0);
		$target->setGridLines(TTableGridLines::Vertical);
		$target->setBorderCollapse(false);
		$expected = $this->properties($target);
		$target->mergeWith($this->createFullStyle());
		$this->assertSame($expected, $this->properties($target));
	}

	public function testMergeWithEmptyTableStyleKeepsUnsetValues()
	{
		$target = new TTableStyle();
		$target->mergeWith(new TTableStyle());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testMergeWithBaseStyleMergesBaseFieldsOnly()
	{
		$source = new TStyle();
		$source->setHeight('5px');
		$target = new TTableStyle();
		$target->mergeWith($source);
		$this->assertSame('5px', $target->getHeight());
		$this->assertSame($this->defaults(), $this->properties($target));
	}

	public function testSerializeDefaultsOmitsTableFields()
	{
		$data = serialize(new TTableStyle());
		foreach (['_backImageUrl', '_horizontalAlign', '_cellPadding', '_cellSpacing', '_gridLines', '_borderCollapse'] as $field) {
			$this->assertStringNotContainsString($field, $data);
		}
		$this->assertSame($this->defaults(), $this->properties(unserialize($data)));
	}

	public function testSerializeRoundTripKeepsTableFields()
	{
		$data = serialize($this->createFullStyle());
		foreach (['_backImageUrl', '_horizontalAlign', '_cellPadding', '_cellSpacing', '_gridLines', '_borderCollapse'] as $field) {
			$this->assertStringContainsString($field, $data);
		}
		$this->assertSame($this->fullValues(), $this->properties(unserialize($data)));
	}
}
