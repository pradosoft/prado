<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Web\UI\WebControls\TVerticalAlign;

class TTableItemStyleTest extends TestCase
{
	/**
	 * Renders a td tag carrying the style's attributes.
	 * @param TStyle $style the style
	 * @return string the rendered markup
	 */
	private function renderStyle(TStyle $style): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$style->addAttributesToRender($writer);
		$writer->renderBeginTag('td');
		$writer->renderEndTag();
		return $tw->flush();
	}

	/**
	 * Creates a style with every item property set.
	 * @return TTableItemStyle the style
	 */
	private function createFullStyle(): TTableItemStyle
	{
		$style = new TTableItemStyle();
		$style->setHorizontalAlign(THorizontalAlign::Center);
		$style->setVerticalAlign(TVerticalAlign::Middle);
		$style->setWrap(false);
		return $style;
	}

	/**
	 * Returns every item property of a style.
	 * @param TTableItemStyle $style the style
	 * @return array the property values by name
	 */
	private function properties(TTableItemStyle $style): array
	{
		return [
			'HorizontalAlign' => $style->getHorizontalAlign(),
			'VerticalAlign' => $style->getVerticalAlign(),
			'Wrap' => $style->getWrap(),
		];
	}

	private function defaults(): array
	{
		return [
			'HorizontalAlign' => THorizontalAlign::NotSet,
			'VerticalAlign' => TVerticalAlign::NotSet,
			'Wrap' => true,
		];
	}

	private function fullValues(): array
	{
		return [
			'HorizontalAlign' => THorizontalAlign::Center,
			'VerticalAlign' => TVerticalAlign::Middle,
			'Wrap' => false,
		];
	}

	/**
	 * Creates a style whose item properties differ from {@see createFullStyle()}.
	 * @return TTableItemStyle the style
	 */
	private function createOtherStyle(): TTableItemStyle
	{
		$style = new TTableItemStyle();
		$style->setHorizontalAlign(THorizontalAlign::Left);
		$style->setVerticalAlign(TVerticalAlign::Top);
		$style->setWrap(true);
		return $style;
	}

	public function testDefaults()
	{
		$this->assertSame($this->defaults(), $this->properties(new TTableItemStyle()));
	}

	public function testSetters()
	{
		$this->assertSame($this->fullValues(), $this->properties($this->createFullStyle()));
	}

	public function testWrapAcceptsStrings()
	{
		$style = new TTableItemStyle();
		$style->setWrap('false');
		$this->assertFalse($style->getWrap());
	}

	public function testEnumSettersRejectInvalidValues()
	{
		$style = new TTableItemStyle();
		foreach (['setHorizontalAlign', 'setVerticalAlign'] as $setter) {
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
		$this->assertSame('<td></td>', $this->renderStyle(new TTableItemStyle()));
	}

	public function testRenderFullStyle()
	{
		$style = $this->createFullStyle();
		$style->setBackColor('red');
		$this->assertSame(
			'<td style="white-space:nowrap;text-align:center;vertical-align:middle;background-color:red;"></td>',
			$this->renderStyle($style)
		);
		$this->assertSame(['background-color' => 'red'], $style->getStyleFields());
	}

	public function testResetRestoresDefaults()
	{
		$style = $this->createFullStyle();
		$style->setWidth('10px');
		$style->reset();
		$this->assertSame($this->defaults(), $this->properties($style));
		$this->assertSame('', $style->getWidth());
	}

	public function testCopyFromSourceWins()
	{
		$target = $this->createOtherStyle();
		$target->copyFrom($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromUnsetSourceKeepsTargetValues()
	{
		$target = $this->createFullStyle();
		$target->copyFrom(new TTableItemStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testMergeWithTargetWins()
	{
		$target = $this->createFullStyle();
		$target->mergeWith($this->createOtherStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testMergeWithFillsUnsetValues()
	{
		$target = new TTableItemStyle();
		$target->mergeWith($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testMergeOrderMatchesTheBaseFields()
	{
		// A selected item merges SelectedItemStyle, then ItemStyle: the first merged style wins.
		$selected = $this->createFullStyle();
		$selected->setBackColor('red');
		$item = $this->createOtherStyle();
		$item->setBackColor('blue');

		$target = new TTableItemStyle();
		$target->mergeWith($selected);
		$target->mergeWith($item);

		$this->assertSame('red', $target->getBackColor());
		$this->assertSame($this->fullValues(), $this->properties($target));
	}

	public function testCopyFromAndMergeWithBaseStyleUseBaseFieldsOnly()
	{
		$source = new TStyle();
		$source->setWidth('20px');

		$copied = new TTableItemStyle();
		$copied->copyFrom($source);
		$this->assertSame('20px', $copied->getWidth());
		$this->assertSame($this->defaults(), $this->properties($copied));

		$merged = new TTableItemStyle();
		$merged->mergeWith($source);
		$this->assertSame('20px', $merged->getWidth());
		$this->assertSame($this->defaults(), $this->properties($merged));
	}

	public function testSerializeDefaultsOmitsItemFields()
	{
		$data = serialize(new TTableItemStyle());
		foreach (['_horizontalAlign', '_verticalAlign', '_wrap'] as $field) {
			$this->assertStringNotContainsString($field, $data);
		}
		$this->assertSame($this->defaults(), $this->properties(unserialize($data)));
	}

	public function testSerializeRoundTripKeepsItemFields()
	{
		$data = serialize($this->createFullStyle());
		foreach (['_horizontalAlign', '_verticalAlign', '_wrap'] as $field) {
			$this->assertStringContainsString($field, $data);
		}
		$this->assertSame($this->fullValues(), $this->properties(unserialize($data)));
	}

	/**
	 * Yields every combination of set and unset item properties.
	 * @return \Generator<string, array{?string, ?string, ?bool}> label => [horizontal, vertical, wrap]
	 */
	private function combinations(): \Generator
	{
		foreach ([null, THorizontalAlign::Right] as $horizontal) {
			foreach ([null, TVerticalAlign::Bottom] as $vertical) {
				foreach ([null, false] as $wrap) {
					yield "h=$horizontal v=$vertical wrap=" . var_export($wrap, true) => [$horizontal, $vertical, $wrap];
				}
			}
		}
	}

	/**
	 * Creates a style with the given item properties set; null leaves one unset.
	 * @param ?string $horizontal the horizontal alignment
	 * @param ?string $vertical the vertical alignment
	 * @param ?bool $wrap the wrap
	 * @return TTableItemStyle the style
	 */
	private function createStyle(?string $horizontal, ?string $vertical, ?bool $wrap): TTableItemStyle
	{
		$style = new TTableItemStyle();
		if ($horizontal !== null) {
			$style->setHorizontalAlign($horizontal);
		}
		if ($vertical !== null) {
			$style->setVerticalAlign($vertical);
		}
		if ($wrap !== null) {
			$style->setWrap($wrap);
		}
		return $style;
	}

	public function testSerializeZapsEachUnsetField()
	{
		foreach ($this->combinations() as $label => [$horizontal, $vertical, $wrap]) {
			$data = serialize($this->createStyle($horizontal, $vertical, $wrap));
			$this->assertSame($horizontal !== null, str_contains($data, '_horizontalAlign'), $label);
			$this->assertSame($vertical !== null, str_contains($data, '_verticalAlign'), $label);
			$this->assertSame($wrap !== null, str_contains($data, '_wrap'), $label);
		}
	}

	public function testCopyFromEachCombination()
	{
		foreach ($this->combinations() as $label => [$horizontal, $vertical, $wrap]) {
			$target = $this->createOtherStyle();
			$target->copyFrom($this->createStyle($horizontal, $vertical, $wrap));
			$this->assertSame([
				'HorizontalAlign' => $horizontal ?? THorizontalAlign::Left,
				'VerticalAlign' => $vertical ?? TVerticalAlign::Top,
				'Wrap' => $wrap ?? true,
			], $this->properties($target), $label);
		}
	}

	public function testMergeWithEachCombination()
	{
		foreach ($this->combinations() as $label => [$horizontal, $vertical, $wrap]) {
			$target = $this->createStyle($horizontal, $vertical, $wrap);
			$target->mergeWith($this->createFullStyle());
			$this->assertSame([
				'HorizontalAlign' => $horizontal ?? THorizontalAlign::Center,
				'VerticalAlign' => $vertical ?? TVerticalAlign::Middle,
				'Wrap' => $wrap ?? false,
			], $this->properties($target), $label);
		}
	}

	public function testRenderEachCombination()
	{
		foreach ($this->combinations() as $label => [$horizontal, $vertical, $wrap]) {
			$style = $this->createStyle($horizontal, $vertical, $wrap);
			$css = ($wrap === false ? 'white-space:nowrap;' : '')
				. ($horizontal !== null ? 'text-align:right;' : '')
				. ($vertical !== null ? 'vertical-align:bottom;' : '');
			$this->assertSame($css === '' ? '<td></td>' : '<td style="' . $css . '"></td>', $this->renderStyle($style), $label);
		}
	}
}
