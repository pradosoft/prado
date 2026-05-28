<?php

require_once __DIR__ . '/../../../PradoUnitRequires.php';

use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\WebControls\THeader;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TFont;
use Prado\Web\UI\WebControls\TDisplayStyle;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Fixture subclasses for Section 23 — DEFAULT_DISPLAY_STYLE subclass override
// ---------------------------------------------------------------------------

/**
 * TStyle subclass whose default display style is {@see TDisplayStyle::None}.
 * Used to verify that `static::DEFAULT_DISPLAY_STYLE` late-binding works in
 * __construct, reset(), _getZappableSleepProps(), and __wakeup().
 */
class TStyleNoneDefault extends TStyle
{
	public const DEFAULT_DISPLAY_STYLE = TDisplayStyle::None;
}

/**
 * TStyle subclass whose default display style is {@see TDisplayStyle::Dynamic}.
 */
class TStyleDynamicDefault extends TStyle
{
	public const DEFAULT_DISPLAY_STYLE = TDisplayStyle::Dynamic;
}

/**
 * TStyle subclass whose default display style is {@see TDisplayStyle::Hidden}.
 */
class TStyleHiddenDefault extends TStyle
{
	public const DEFAULT_DISPLAY_STYLE = TDisplayStyle::Hidden;
}

/**
 * Comprehensive tests for {@see TStyle}.
 *
 * Sections:
 *   1.  Constructor
 *   2.  Clone
 *   3.  Width / Height
 *   4.  Color properties (ForeColor, BackColor, BorderColor)
 *   5.  Border properties (Style, Width, Radius)
 *   6.  CssClass (getHasCssClass / hasCssClass deprecated alias / property access)
 *   7.  Font (getHasFont / hasFont deprecated alias / property access)
 *   8.  DisplayStyle
 *   9.  CustomStyle (getHasCustomStyle / property access)
 *   10. StyleField (has/get/set/clear/getAll)
 *   11. ArrayAccess
 *   12. reset()
 *   13. copyFrom() — source wins
 *   14. mergeWith() — target wins
 *   15. addAttributesToRender() — render order and edge cases
 *   16. __call magic (getXxx / setXxx)
 *   17. __get / __set magic
 *   18. canGetProperty / canSetProperty / hasMethod
 *   19. Serialization (_getZappableSleepProps)
 *   20. methodToAttributeName — PascalCase/underscore/leading-dash/vendor-prefix → kebab
 *   21. TWebControl integration — template → property → style → render
 *   22. setSubProperty / TTemplate integration — Style.<attr> all forms
 *   23. DEFAULT_DISPLAY_STYLE subclass override — construction, reset, serialization
 */
class TStyleTest extends TestCase
{
	// ================================================================================
	// Helpers
	// ================================================================================

	private function renderStyle(TStyle $style): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$style->addAttributesToRender($writer);
		$writer->renderBeginTag('div');
		$writer->renderEndTag();
		return $tw->flush();
	}

	// ================================================================================
	// 1. Constructor
	// ================================================================================

	public function testConstructorNoArguments()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getWidth());
		$this->assertEquals('', $style->getHeight());
		$this->assertEquals('', $style->getForeColor());
		$this->assertEquals('', $style->getBackColor());
		$this->assertFalse($style->hasFont());
		$this->assertEquals('', $style->getCssClass());
		$this->assertEquals('', $style->getCustomStyle());
		$this->assertEquals(TDisplayStyle::Fixed, $style->getDisplayStyle());
		$this->assertEquals([], $style->getStyleFields());
	}

	public function testConstructorWithNull()
	{
		$style = new TStyle(null);
		$this->assertEquals('', $style->getWidth());
		$this->assertFalse($style->hasFont());
	}

	public function testConstructorWithTStyleCallsCopyFrom()
	{
		$source = new TStyle();
		$source->setWidth('100px');
		$source->setHeight('200px');
		$source->setForeColor('red');
		$source->setCssClass('my-class');

		$style = new TStyle($source);

		$this->assertEquals('100px', $style->getWidth());
		$this->assertEquals('200px', $style->getHeight());
		$this->assertEquals('red', $style->getForeColor());
		$this->assertEquals('my-class', $style->getCssClass());
	}

	// ================================================================================
	// 2. Clone
	// ================================================================================

	public function testCloneWithoutFont()
	{
		$style = new TStyle();
		$style->setWidth('100px');

		$cloned = clone $style;

		$this->assertEquals('100px', $cloned->getWidth());
		$this->assertFalse($cloned->hasFont());
	}

	public function testCloneWithFontIsDeepCopy()
	{
		$style = new TStyle();
		$style->getFont()->setBold(true);

		$cloned = clone $style;

		$this->assertTrue($cloned->hasFont());
		$this->assertNotSame($style->getFont(), $cloned->getFont());
		$this->assertTrue($cloned->getFont()->getBold());
	}

	public function testCloneFontIndependentAfterClone()
	{
		$style = new TStyle();
		$style->getFont()->setSize('12px');

		$cloned = clone $style;
		$cloned->getFont()->setSize('24px');

		$this->assertEquals('12px', $style->getFont()->getSize());
		$this->assertEquals('24px', $cloned->getFont()->getSize());
	}

	public function testCloneFieldsAreIndependent()
	{
		$style = new TStyle();
		$style->setWidth('100px');

		$cloned = clone $style;
		$cloned->setWidth('200px');

		$this->assertEquals('100px', $style->getWidth());
		$this->assertEquals('200px', $cloned->getWidth());
	}

	// ================================================================================
	// 3. Width / Height
	// ================================================================================

	public function testWidthGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getWidth());
		$style->setWidth('100px');
		$this->assertEquals('100px', $style->getWidth());
		$style->setWidth('50%');
		$this->assertEquals('50%', $style->getWidth());
		$style->setWidth('auto');
		$this->assertEquals('auto', $style->getWidth());
	}

	public function testWidthTrimmed()
	{
		$style = new TStyle();
		$style->setWidth('  100px  ');
		$this->assertEquals('100px', $style->getWidth());
	}

	public function testWidthClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setWidth('100px');
		$style->setWidth('');
		$this->assertEquals('', $style->getWidth());
		$this->assertFalse($style->hasStyleField('width'));
	}

	public function testHeightGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getHeight());
		$style->setHeight('200px');
		$this->assertEquals('200px', $style->getHeight());
	}

	public function testHeightTrimmed()
	{
		$style = new TStyle();
		$style->setHeight('  200px  ');
		$this->assertEquals('200px', $style->getHeight());
	}

	public function testHeightClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setHeight('200px');
		$style->setHeight('');
		$this->assertEquals('', $style->getHeight());
	}

	// ================================================================================
	// 4. Color properties
	// ================================================================================

	public function testForeColorGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getForeColor());
		$style->setForeColor('red');
		$this->assertEquals('red', $style->getForeColor());
		$style->setForeColor('#333333');
		$this->assertEquals('#333333', $style->getForeColor());
		$style->setForeColor('rgb(0, 0, 0)');
		$this->assertEquals('rgb(0, 0, 0)', $style->getForeColor());
	}

	public function testForeColorTrimmed()
	{
		$style = new TStyle();
		$style->setForeColor('  blue  ');
		$this->assertEquals('blue', $style->getForeColor());
	}

	public function testForeColorClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setForeColor('red');
		$style->setForeColor('');
		$this->assertEquals('', $style->getForeColor());
	}

	public function testBackColorGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getBackColor());
		$style->setBackColor('blue');
		$this->assertEquals('blue', $style->getBackColor());
		$style->setBackColor('#ffffff');
		$this->assertEquals('#ffffff', $style->getBackColor());
	}

	public function testBackColorTrimmed()
	{
		$style = new TStyle();
		$style->setBackColor('  #fff  ');
		$this->assertEquals('#fff', $style->getBackColor());
	}

	public function testBackColorClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setBackColor('blue');
		$style->setBackColor('');
		$this->assertEquals('', $style->getBackColor());
	}

	public function testBorderColorGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getBorderColor());
		$style->setBorderColor('black');
		$this->assertEquals('black', $style->getBorderColor());
	}

	public function testBorderColorTrimmed()
	{
		$style = new TStyle();
		$style->setBorderColor('  red  ');
		$this->assertEquals('red', $style->getBorderColor());
	}

	public function testBorderColorClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setBorderColor('black');
		$style->setBorderColor('');
		$this->assertEquals('', $style->getBorderColor());
	}

	// ================================================================================
	// 5. Border properties
	// ================================================================================

	public function testBorderStyleGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getBorderStyle());
		$style->setBorderStyle('solid');
		$this->assertEquals('solid', $style->getBorderStyle());
		$style->setBorderStyle('dashed');
		$this->assertEquals('dashed', $style->getBorderStyle());
	}

	public function testBorderStyleTrimmed()
	{
		$style = new TStyle();
		$style->setBorderStyle('  solid  ');
		$this->assertEquals('solid', $style->getBorderStyle());
	}

	public function testBorderStyleClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setBorderStyle('solid');
		$style->setBorderStyle('');
		$this->assertEquals('', $style->getBorderStyle());
	}

	public function testBorderWidthGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getBorderWidth());
		$style->setBorderWidth('1px');
		$this->assertEquals('1px', $style->getBorderWidth());
		$style->setBorderWidth('thick');
		$this->assertEquals('thick', $style->getBorderWidth());
	}

	public function testBorderWidthTrimmed()
	{
		$style = new TStyle();
		$style->setBorderWidth('  1px  ');
		$this->assertEquals('1px', $style->getBorderWidth());
	}

	public function testBorderWidthClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setBorderWidth('1px');
		$style->setBorderWidth('');
		$this->assertEquals('', $style->getBorderWidth());
	}

	public function testBorderRadiusGetSet()
	{
		$style = new TStyle();
		$this->assertEquals('', $style->getBorderRadius());
		$style->setBorderRadius('5px');
		$this->assertEquals('5px', $style->getBorderRadius());
		$style->setBorderRadius('50%');
		$this->assertEquals('50%', $style->getBorderRadius());
	}

	public function testBorderRadiusTrimmed()
	{
		$style = new TStyle();
		$style->setBorderRadius('  5px  ');
		$this->assertEquals('5px', $style->getBorderRadius());
	}

	public function testBorderRadiusClearedByEmptyString()
	{
		$style = new TStyle();
		$style->setBorderRadius('5px');
		$style->setBorderRadius('');
		$this->assertEquals('', $style->getBorderRadius());
	}

	// ================================================================================
	// 6. CssClass
	// ================================================================================

	public function testHasCssClassFalseInitially()
	{
		$this->assertFalse((new TStyle())->hasCssClass());
	}

	public function testHasCssClassTrueAfterSet()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$this->assertTrue($style->hasCssClass());
	}

	public function testHasCssClassTrueAfterEmptyStringSet()
	{
		// explicitly setting '' is different from never setting
		$style = new TStyle();
		$style->setCssClass('');
		$this->assertTrue($style->hasCssClass());
	}

	public function testGetCssClassEmptyWhenNeverSet()
	{
		$this->assertEquals('', (new TStyle())->getCssClass());
	}

	public function testGetCssClassReturnsValue()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$this->assertEquals('my-class', $style->getCssClass());
	}

	public function testGetCssClassEmptyStringAfterExplicitEmpty()
	{
		$style = new TStyle();
		$style->setCssClass('');
		$this->assertEquals('', $style->getCssClass());
	}

	public function testSetCssClassTrimmed()
	{
		$style = new TStyle();
		$style->setCssClass('  my-class  ');
		$this->assertEquals('my-class', $style->getCssClass());
	}

	public function testSetCssClassWhitespaceOnlyTrimsToEmpty()
	{
		$style = new TStyle();
		$style->setCssClass('   ');
		$this->assertEquals('', $style->getCssClass());
	}

	public function testSetCssClassMultipleClasses()
	{
		$style = new TStyle();
		$style->setCssClass('class1 class2 class3');
		$this->assertEquals('class1 class2 class3', $style->getCssClass());
	}

	public function testEmptyCssClassRendersEmptyAttribute()
	{
		// setCssClass('') marks _class as explicitly set (to empty string).
		// getHasCssClass() returns true, so class="" must appear in the output
		// so that WebDriver's getAttribute('class') returns '' (not null).
		$style = new TStyle();
		$style->setCssClass('');
		$this->assertStringContainsString('class=""', $this->renderStyle($style));
	}

	public function testNullCssClassNotRendered()
	{
		// _class === null means "never touched" — no class attribute at all.
		$style = new TStyle();
		$this->assertStringNotContainsString('class=', $this->renderStyle($style));
	}

	public function testGetHasCssClassFalseInitially()
	{
		$this->assertFalse((new TStyle())->getHasCssClass());
	}

	public function testGetHasCssClassTrueAfterSet()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$this->assertTrue($style->getHasCssClass());
	}

	public function testGetHasCssClassTrueAfterSetEmpty()
	{
		// explicitly setting '' is different from never setting
		$style = new TStyle();
		$style->setCssClass('');
		$this->assertTrue($style->getHasCssClass());
	}

	public function testGetHasCssClassAsProperty()
	{
		$style = new TStyle();
		$this->assertFalse($style->HasCssClass);
		$style->setCssClass('my-class');
		$this->assertTrue($style->HasCssClass);
	}

	public function testHasCssClassIsDeprecatedAlias()
	{
		$style = new TStyle();
		$this->assertSame($style->getHasCssClass(), $style->hasCssClass());
		$style->setCssClass('my-class');
		$this->assertSame($style->getHasCssClass(), $style->hasCssClass());
	}

	// ================================================================================
	// 7. Font
	// ================================================================================

	public function testHasFontFalseInitially()
	{
		$this->assertFalse((new TStyle())->hasFont());
	}

	public function testHasFontTrueAfterGetFont()
	{
		$style = new TStyle();
		$style->getFont();
		$this->assertTrue($style->hasFont());
	}

	public function testGetFontCreatesInstance()
	{
		$this->assertInstanceOf(TFont::class, (new TStyle())->getFont());
	}

	public function testGetFontReturnsSameInstance()
	{
		$style = new TStyle();
		$this->assertSame($style->getFont(), $style->getFont());
	}

	public function testGetHasFontFalseInitially()
	{
		$this->assertFalse((new TStyle())->getHasFont());
	}

	public function testGetHasFontTrueAfterGetFont()
	{
		$style = new TStyle();
		$style->getFont();
		$this->assertTrue($style->getHasFont());
	}

	public function testGetHasFontAsProperty()
	{
		$style = new TStyle();
		$this->assertFalse($style->HasFont);
		$style->getFont();
		$this->assertTrue($style->HasFont);
	}

	public function testHasFontIsDeprecatedAlias()
	{
		$style = new TStyle();
		$this->assertSame($style->getHasFont(), $style->hasFont());
		$style->getFont();
		$this->assertSame($style->getHasFont(), $style->hasFont());
	}

	// ================================================================================
	// 8. DisplayStyle
	// ================================================================================

	public function testDisplayStyleDefaultIsFixed()
	{
		$this->assertEquals(TDisplayStyle::Fixed, (new TStyle())->getDisplayStyle());
	}

	public function testDisplayStyleNoneSetsDisplayField()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$this->assertEquals(TDisplayStyle::None, $style->getDisplayStyle());
		$this->assertEquals('none', $style->getStyleField('display'));
		$this->assertFalse($style->hasStyleField('visibility'));
	}

	public function testDisplayStyleFixedSetsVisibilityVisible()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$this->assertEquals(TDisplayStyle::Fixed, $style->getDisplayStyle());
		$this->assertEquals('visible', $style->getStyleField('visibility'));
		$this->assertFalse($style->hasStyleField('display'));
	}

	public function testDisplayStyleHiddenSetsVisibilityHidden()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::Hidden);
		$this->assertEquals(TDisplayStyle::Hidden, $style->getDisplayStyle());
		$this->assertEquals('hidden', $style->getStyleField('visibility'));
		$this->assertFalse($style->hasStyleField('display'));
	}

	public function testDisplayStyleDynamicClearsBothFields()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$style->setDisplayStyle(TDisplayStyle::Dynamic);
		$this->assertEquals(TDisplayStyle::Dynamic, $style->getDisplayStyle());
		$this->assertFalse($style->hasStyleField('display'));
		$this->assertFalse($style->hasStyleField('visibility'));
	}

	public function testDisplayStyleTransitionNoneToFixedLeavesDisplayField()
	{
		// None → Fixed does NOT clear display:none; use Dynamic as an intermediate step
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);   // sets display:none
		$style->setDisplayStyle(TDisplayStyle::Fixed);  // sets visibility:visible; display:none remains
		$this->assertEquals('none', $style->getStyleField('display'));
		$this->assertEquals('visible', $style->getStyleField('visibility'));
	}

	public function testDisplayStyleTransitionNoneToDynamicThenFixed()
	{
		// Proper way to re-show after hiding: go through Dynamic
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$style->setDisplayStyle(TDisplayStyle::Dynamic);
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$this->assertFalse($style->hasStyleField('display'));
		$this->assertEquals('visible', $style->getStyleField('visibility'));
	}

	public function testDisplayStyleMultipleTransitions()
	{
		$style = new TStyle();

		$style->setDisplayStyle(TDisplayStyle::None);
		$this->assertEquals('none', $style->getStyleField('display'));

		$style->setDisplayStyle(TDisplayStyle::Dynamic);
		$this->assertFalse($style->hasStyleField('display'));
		$this->assertFalse($style->hasStyleField('visibility'));

		$style->setDisplayStyle(TDisplayStyle::Hidden);
		$this->assertEquals('hidden', $style->getStyleField('visibility'));

		$style->setDisplayStyle(TDisplayStyle::Dynamic);
		$this->assertFalse($style->hasStyleField('visibility'));
	}

	public function testDisplayStyleInvalidThrows()
	{
		$style = new TStyle();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$style->setDisplayStyle('InvalidStyle');
	}

	public function testDisplayStyleStringEnumValidation()
	{
		$style = new TStyle();
		$style->setDisplayStyle('Fixed');
		$this->assertEquals(TDisplayStyle::Fixed, $style->getDisplayStyle());
	}

	// ================================================================================
	// 9. CustomStyle
	// ================================================================================

	public function testCustomStyleEmptyInitially()
	{
		$this->assertEquals('', (new TStyle())->getCustomStyle());
	}

	public function testCustomStyleGetSet()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red; font-size: 12px');
		$this->assertEquals('color: red; font-size: 12px', $style->getCustomStyle());
	}

	public function testCustomStyleTrimmed()
	{
		$style = new TStyle();
		$style->setCustomStyle('  color: red  ');
		$this->assertEquals('color: red', $style->getCustomStyle());
	}

	public function testCustomStyleCanBeSetToEmpty()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$style->setCustomStyle('');
		$this->assertEquals('', $style->getCustomStyle());
	}

	public function testCustomStyleWhitespaceOnlyTrimsToEmpty()
	{
		$style = new TStyle();
		$style->setCustomStyle('   ');
		$this->assertEquals('', $style->getCustomStyle());
	}

	public function testGetHasCustomStyleFalseInitially()
	{
		$this->assertFalse((new TStyle())->getHasCustomStyle());
	}

	public function testGetHasCustomStyleTrueAfterSet()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$this->assertTrue($style->getHasCustomStyle());
	}

	public function testGetHasCustomStyleTrueAfterSetToEmpty()
	{
		// setting '' marks it as explicitly set; null means never set
		$style = new TStyle();
		$style->setCustomStyle('');
		$this->assertTrue($style->getHasCustomStyle());
	}

	public function testGetHasCustomStyleFalseAfterReset()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$style->reset();
		$this->assertFalse($style->getHasCustomStyle());
	}

	public function testGetHasCustomStyleAsProperty()
	{
		$style = new TStyle();
		$this->assertFalse($style->HasCustomStyle);
		$style->setCustomStyle('color: red');
		$this->assertTrue($style->HasCustomStyle);
	}

	// ================================================================================
	// 10. StyleField
	// ================================================================================

	public function testHasStyleFieldFalseInitially()
	{
		$this->assertFalse((new TStyle())->hasStyleField('color'));
	}

	public function testHasStyleFieldTrueAfterSet()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertTrue($style->hasStyleField('color'));
	}

	public function testHasStyleFieldNameTrimmed()
	{
		$style = new TStyle();
		$style->setStyleField('  color  ', 'red');
		$this->assertTrue($style->hasStyleField('color'));
		$this->assertTrue($style->hasStyleField('  color  '));
	}

	public function testHasStyleFieldFalseAfterClear()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('color', '');
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testGetStyleFieldEmptyWhenNotSet()
	{
		$this->assertEquals('', (new TStyle())->getStyleField('color'));
	}

	public function testGetStyleFieldReturnsValue()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertEquals('red', $style->getStyleField('color'));
	}

	public function testGetStyleFieldNameTrimmed()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertEquals('red', $style->getStyleField('  color  '));
	}

	public function testSetStyleFieldTrimsNameAndValue()
	{
		$style = new TStyle();
		$style->setStyleField('  margin  ', '  10px  ');
		$this->assertEquals('10px', $style->getStyleField('margin'));
	}

	public function testSetStyleFieldEmptyValueClears()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('color', '');
		$this->assertFalse($style->hasStyleField('color'));
		$this->assertEquals('', $style->getStyleField('color'));
	}

	public function testSetStyleFieldWhitespaceOnlyClears()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('color', '   ');
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testSetStyleFieldNumericValue()
	{
		$style = new TStyle();
		$style->setStyleField('z-index', 100);
		$this->assertEquals('100', $style->getStyleField('z-index'));
	}

	public function testSetStyleFieldSpecialCharsInValue()
	{
		$style = new TStyle();
		$style->setStyleField('font-family', 'Arial, Helvetica, sans-serif');
		$this->assertEquals('Arial, Helvetica, sans-serif', $style->getStyleField('font-family'));
	}

	public function testClearStyleField()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->clearStyleField('color');
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testClearStyleFieldNameTrimmed()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->clearStyleField('  color  ');
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testClearStyleFieldNoopForNonExistent()
	{
		$style = new TStyle();
		$style->clearStyleField('non-existent');
		// No exception, no state change
		$this->assertEquals([], $style->getStyleFields());
	}

	public function testGetHasStyleFieldsFalseInitially()
	{
		$this->assertFalse((new TStyle())->getHasStyleFields());
	}

	public function testGetHasStyleFieldsTrueAfterSet()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertTrue($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsFalseAfterClearingLastField()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->clearStyleField('color');
		$this->assertFalse($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsFalseAfterSetToEmpty()
	{
		// setStyleField with empty value calls clearStyleField internally.
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('color', '');
		$this->assertFalse($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsTrueWithMultipleFields()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('width', '100px');
		$this->assertTrue($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsFalseAfterReset()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->reset();
		$this->assertFalse($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsAsProperty()
	{
		$style = new TStyle();
		$this->assertFalse($style->HasStyleFields);
		$style->setStyleField('color', 'red');
		$this->assertTrue($style->HasStyleFields);
	}

	public function testGetHasStyleFieldsTrueAfterDisplayStyleNone()
	{
		// setDisplayStyle(None) writes display:none into _fields.
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$this->assertTrue($style->getHasStyleFields());
	}

	public function testGetHasStyleFieldsFalseAfterDisplayStyleDynamic()
	{
		// setDisplayStyle(Dynamic) clears display and visibility — if those were
		// the only fields, _fields becomes empty again.
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);    // sets display:none
		$style->setDisplayStyle(TDisplayStyle::Dynamic); // clears it
		$this->assertFalse($style->getHasStyleFields());
	}

	public function testGetStyleFieldsEmptyInitially()
	{
		$this->assertEquals([], (new TStyle())->getStyleFields());
	}

	public function testGetStyleFieldsReturnsAllFields()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->setStyleField('font-size', '12px');

		$fields = $style->getStyleFields();
		$this->assertCount(2, $fields);
		$this->assertEquals('red', $fields['color']);
		$this->assertEquals('12px', $fields['font-size']);
	}

	public function testGetStyleFieldsReturnedArrayIsIndependent()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');

		$fields = $style->getStyleFields();
		$fields['font-size'] = '12px';

		$this->assertCount(1, $style->getStyleFields());
		$this->assertFalse($style->hasStyleField('font-size'));
	}

	// ================================================================================
	// 11. ArrayAccess
	// ================================================================================

	public function testOffsetExistsTrue()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertTrue(isset($style['color']));
	}

	public function testOffsetExistsFalse()
	{
		$this->assertFalse(isset((new TStyle())['color']));
	}

	public function testOffsetGetReturnsValue()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$this->assertEquals('red', $style['color']);
	}

	public function testOffsetGetEmptyForNonExistent()
	{
		$this->assertEquals('', (new TStyle())['non-existent']);
	}

	public function testOffsetSet()
	{
		$style = new TStyle();
		$style['color'] = 'blue';
		$this->assertEquals('blue', $style->getStyleField('color'));
	}

	public function testOffsetSetTrimsNameAndValue()
	{
		$style = new TStyle();
		$style['  color  '] = '  blue  ';
		$this->assertEquals('blue', $style['color']);
	}

	public function testOffsetSetEmptyClears()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style['color'] = '';
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testOffsetUnset()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		unset($style['color']);
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testOffsetUnsetNameTrimmed()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		unset($style['  color  ']);
		$this->assertFalse($style->hasStyleField('color'));
	}

	public function testArrayAccessWithHyphenatedName()
	{
		$style = new TStyle();
		$style['font-family'] = 'Arial';
		$this->assertEquals('Arial', $style['font-family']);
		$this->assertTrue(isset($style['font-family']));
		unset($style['font-family']);
		$this->assertFalse(isset($style['font-family']));
	}

	public function testArrayAccessCssCustomPropertySet()
	{
		// Array access is the most direct path: no name transformation, literal key used
		$style = new TStyle();
		$style['--web-color'] = 'red';
		$this->assertEquals('red', $style['--web-color']);
		$this->assertTrue(isset($style['--web-color']));
	}

	public function testArrayAccessCssCustomPropertyUnset()
	{
		$style = new TStyle();
		$style['--web-color'] = 'red';
		unset($style['--web-color']);
		$this->assertFalse(isset($style['--web-color']));
		$this->assertEquals('', $style['--web-color']);
	}

	public function testArrayAccessCssCustomPropertySafariVendor()
	{
		$style = new TStyle();
		$style['--safari-transform'] = 'none';
		$this->assertEquals('none', $style['--safari-transform']);
	}

	public function testArrayAccessVendorPrefixSingleDash()
	{
		// Single-dash vendor prefix stored exactly as given
		$style = new TStyle();
		$style['-webkit-transform'] = 'translateX(10px)';
		$this->assertEquals('translateX(10px)', $style['-webkit-transform']);
		$this->assertTrue(isset($style['-webkit-transform']));
	}

	public function testArrayAccessCssCustomPropertyRendered()
	{
		// Custom properties and vendor prefixes must appear verbatim in the rendered output
		$style = new TStyle();
		$style['--brand-color'] = '#005fcc';
		$style['-webkit-transform'] = 'rotate(45deg)';
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('--brand-color:#005fcc', $output);
		$this->assertStringContainsString('-webkit-transform:rotate(45deg)', $output);
	}

	// ================================================================================
	// 12. reset()
	// ================================================================================

	public function testResetClearsFields()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$style->reset();
		$this->assertEquals([], $style->getStyleFields());
	}

	public function testResetClearsFont()
	{
		$style = new TStyle();
		$style->getFont();
		$style->reset();
		$this->assertFalse($style->hasFont());
	}

	public function testResetClearsCssClass()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$style->reset();
		$this->assertEquals('', $style->getCssClass());
		$this->assertFalse($style->hasCssClass());
	}

	public function testResetClearsCustomStyle()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$style->reset();
		$this->assertEquals('', $style->getCustomStyle());
	}

	public function testResetResetsDisplayStyleToDefault()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$style->reset();
		$this->assertEquals(TStyle::DEFAULT_DISPLAY_STYLE, $style->getDisplayStyle());
		$this->assertFalse($style->hasStyleField('display'));
	}

	public function testResetDisplayStyleFixedLeavesNoFields()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$style->reset();
		$this->assertEquals(TStyle::DEFAULT_DISPLAY_STYLE, $style->getDisplayStyle());
		$this->assertFalse($style->hasStyleField('visibility'));
	}

	public function testResetGetHasCssClassFalse()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$style->reset();
		$this->assertFalse($style->getHasCssClass());
	}

	public function testResetGetHasCustomStyleFalse()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$style->reset();
		$this->assertFalse($style->getHasCustomStyle());
	}

	public function testResetGetHasFontFalse()
	{
		$style = new TStyle();
		$style->getFont();
		$style->reset();
		$this->assertFalse($style->getHasFont());
	}

	// ================================================================================
	// 13. copyFrom() — source wins
	// ================================================================================

	public function testCopyFromSourceFieldsOverwriteTargetFields()
	{
		$source = new TStyle();
		$source->setStyleField('color', 'blue');

		$target = new TStyle();
		$target->setStyleField('color', 'red');
		$target->copyFrom($source);

		$this->assertEquals('blue', $target->getStyleField('color'));
	}

	public function testCopyFromNonConflictingFieldsMerged()
	{
		$source = new TStyle();
		$source->setStyleField('color', 'red');
		$source->setStyleField('font-size', '12px');

		$target = new TStyle();
		$target->setStyleField('background', 'blue');
		$target->copyFrom($source);

		$fields = $target->getStyleFields();
		$this->assertEquals('red', $fields['color']);
		$this->assertEquals('12px', $fields['font-size']);
		$this->assertEquals('blue', $fields['background']);
	}

	public function testCopyFromSourceCssClassOverwritesTarget()
	{
		$source = new TStyle();
		$source->setCssClass('source-class');

		$target = new TStyle();
		$target->setCssClass('target-class');
		$target->copyFrom($source);

		$this->assertEquals('source-class', $target->getCssClass());
	}

	public function testCopyFromNullSourceCssClassKeepsTarget()
	{
		$source = new TStyle(); // _class is null

		$target = new TStyle();
		$target->setCssClass('target-class');
		$target->copyFrom($source);

		$this->assertEquals('target-class', $target->getCssClass());
	}

	public function testCopyFromSourceCustomStyleOverwritesTarget()
	{
		$source = new TStyle();
		$source->setCustomStyle('color: red');

		$target = new TStyle();
		$target->setCustomStyle('color: blue');
		$target->copyFrom($source);

		$this->assertEquals('color: red', $target->getCustomStyle());
	}

	public function testCopyFromNullSourceCustomStyleKeepsTarget()
	{
		$source = new TStyle(); // _customStyle is null

		$target = new TStyle();
		$target->setCustomStyle('color: blue');
		$target->copyFrom($source);

		$this->assertEquals('color: blue', $target->getCustomStyle());
	}

	public function testCopyFromSourceFontMergedIntoTarget()
	{
		$source = new TStyle();
		$source->getFont()->setBold(true);

		$target = new TStyle();
		$target->copyFrom($source);

		$this->assertTrue($target->hasFont());
		$this->assertTrue($target->getFont()->getBold());
	}

	public function testCopyFromNoSourceFontLeavesTargetFontAlone()
	{
		$source = new TStyle(); // no font

		$target = new TStyle();
		$target->getFont()->setSize('12px');
		$target->copyFrom($source);

		$this->assertEquals('12px', $target->getFont()->getSize());
	}

	public function testCopyFromDisplayStyleEnumNotCopied()
	{
		$source = new TStyle();
		$source->setDisplayStyle(TDisplayStyle::None); // writes display:none to fields

		$target = new TStyle();
		$target->setDisplayStyle(TDisplayStyle::Fixed); // writes visibility:visible
		$target->copyFrom($source);

		// Enum is NOT copied
		$this->assertEquals(TDisplayStyle::Fixed, $target->getDisplayStyle());
		// But the field from None IS present (came from source _fields)
		$this->assertEquals('none', $target->getStyleField('display'));
	}

	public function testCopyFromDisplayStyleFieldsCopied()
	{
		$source = new TStyle();
		$source->setDisplayStyle(TDisplayStyle::None);

		$target = new TStyle();
		$target->copyFrom($source);

		$this->assertEquals('none', $target->getStyleField('display'));
	}

	public function testCopyFromNonTStyleIgnored()
	{
		$target = new TStyle();
		$target->setStyleField('color', 'red');
		$target->copyFrom('not-a-style');
		$this->assertEquals('red', $target->getStyleField('color'));
	}

	public function testCopyFromSelf()
	{
		$style = new TStyle();
		$style->setForeColor('red');
		$style->setWidth('100px');
		$style->copyFrom($style);
		$this->assertEquals('red', $style->getForeColor());
		$this->assertEquals('100px', $style->getWidth());
	}

	public function testCopyFromSourceEmptyStringCssClassCopied()
	{
		// source _class = '' (explicitly set) → source wins and overwrites target
		$source = new TStyle();
		$source->setCssClass('');

		$target = new TStyle();
		$target->setCssClass('target-class');
		$target->copyFrom($source);

		$this->assertEquals('', $target->getCssClass());
		$this->assertTrue($target->getHasCssClass());
	}

	public function testCopyFromSourceEmptyStringCustomStyleCopied()
	{
		// source _customStyle = '' (explicitly set) → source wins and overwrites target
		$source = new TStyle();
		$source->setCustomStyle('');

		$target = new TStyle();
		$target->setCustomStyle('color: blue');
		$target->copyFrom($source);

		$this->assertEquals('', $target->getCustomStyle());
		$this->assertTrue($target->getHasCustomStyle());
	}

	// ================================================================================
	// 14. mergeWith() — target wins
	// ================================================================================

	public function testMergeWithTargetFieldsWinOnConflict()
	{
		$base = new TStyle();
		$base->setStyleField('color', 'blue');

		$target = new TStyle();
		$target->setStyleField('color', 'red');
		$target->mergeWith($base);

		$this->assertEquals('red', $target->getStyleField('color'));
	}

	public function testMergeWithBaseFieldsFillInMissing()
	{
		$base = new TStyle();
		$base->setStyleField('font-size', '14px');
		$base->setStyleField('color', 'blue');

		$target = new TStyle();
		$target->setStyleField('background', 'white');
		$target->mergeWith($base);

		$fields = $target->getStyleFields();
		$this->assertEquals('white', $fields['background']);
		$this->assertEquals('14px', $fields['font-size']);
		$this->assertEquals('blue', $fields['color']);
	}

	public function testMergeWithTargetCssClassKept()
	{
		$base = new TStyle();
		$base->setCssClass('base-class');

		$target = new TStyle();
		$target->setCssClass('target-class');
		$target->mergeWith($base);

		$this->assertEquals('target-class', $target->getCssClass());
	}

	public function testMergeWithBaseCssClassUsedWhenTargetNull()
	{
		$base = new TStyle();
		$base->setCssClass('base-class');

		$target = new TStyle(); // _class is null
		$target->mergeWith($base);

		$this->assertEquals('base-class', $target->getCssClass());
	}

	public function testMergeWithTargetCustomStyleKept()
	{
		$base = new TStyle();
		$base->setCustomStyle('color: blue');

		$target = new TStyle();
		$target->setCustomStyle('color: red');
		$target->mergeWith($base);

		$this->assertEquals('color: red', $target->getCustomStyle());
	}

	public function testMergeWithBaseCustomStyleUsedWhenTargetNull()
	{
		$base = new TStyle();
		$base->setCustomStyle('color: blue');

		$target = new TStyle(); // _customStyle is null
		$target->mergeWith($base);

		$this->assertEquals('color: blue', $target->getCustomStyle());
	}

	public function testMergeWithTargetFontWins()
	{
		$base = new TStyle();
		$base->getFont()->setBold(true);
		$base->getFont()->setSize('14px');

		$target = new TStyle();
		$target->getFont()->setBold(false);
		$target->mergeWith($base);

		$this->assertFalse($target->getFont()->getBold());
		$this->assertEquals('14px', $target->getFont()->getSize());
	}

	public function testMergeWithBaseFontUsedWhenTargetHasNone()
	{
		$base = new TStyle();
		$base->getFont()->setSize('14px');

		$target = new TStyle();
		$target->mergeWith($base);

		$this->assertTrue($target->hasFont());
		$this->assertEquals('14px', $target->getFont()->getSize());
	}

	public function testMergeWithDisplayStyleEnumNotCopied()
	{
		$base = new TStyle();
		$base->setDisplayStyle(TDisplayStyle::Hidden);

		$target = new TStyle();
		$target->setDisplayStyle(TDisplayStyle::Fixed);
		$target->mergeWith($base);

		$this->assertEquals(TDisplayStyle::Fixed, $target->getDisplayStyle());
	}

	public function testMergeWithNonTStyleIgnored()
	{
		$target = new TStyle();
		$target->setStyleField('color', 'red');
		$target->mergeWith('not-a-style');
		$this->assertEquals('red', $target->getStyleField('color'));
	}

	public function testMergeWithSelf()
	{
		$style = new TStyle();
		$style->setForeColor('red');
		$style->mergeWith($style);
		$this->assertEquals('red', $style->getForeColor());
	}

	public function testMergeWithBothCssClassNullNoChange()
	{
		// Neither has css class set; result also has no css class
		$base = new TStyle();
		$target = new TStyle();
		$target->mergeWith($base);
		$this->assertFalse($target->getHasCssClass());
		$this->assertEquals('', $target->getCssClass());
	}

	public function testMergeWithBaseEmptyStringCssClassFillsNullTarget()
	{
		// base _class = '' (explicitly set); target _class = null → base fills in
		$base = new TStyle();
		$base->setCssClass('');

		$target = new TStyle(); // _class null
		$target->mergeWith($base);

		$this->assertTrue($target->getHasCssClass());
		$this->assertEquals('', $target->getCssClass());
	}

	public function testMergeWithBaseNullCustomStyleNotCopied()
	{
		// base _customStyle = null (never set) → target keeps its own value
		$base = new TStyle();

		$target = new TStyle();
		$target->setCustomStyle('color: red');
		$target->mergeWith($base);

		$this->assertEquals('color: red', $target->getCustomStyle());
	}

	public function testMergeWithBothCustomStyleNullNoChange()
	{
		// Neither has custom style; result also has none
		$base = new TStyle();
		$target = new TStyle();
		$target->mergeWith($base);
		$this->assertFalse($target->getHasCustomStyle());
	}

	// ================================================================================
	// 15. addAttributesToRender()
	// ================================================================================

	public function testRenderCustomStyleParsed()
	{
		$style = new TStyle();
		$style->setCustomStyle('border: 1px solid black; color: red');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('border:1px solid black', $output);
		$this->assertStringContainsString('color:red', $output);
	}

	public function testRenderFieldsOverrideCustomStyle()
	{
		// Same property in both — _fields wins because it is added after customStyle
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$style->setStyleField('color', 'blue');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('color:blue', $output);
		$this->assertStringNotContainsString('color:red', $output);
	}

	public function testRenderFieldsRendered()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'blue');
		$style->setStyleField('font-size', '12px');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('color:blue', $output);
		$this->assertStringContainsString('font-size:12px', $output);
	}

	public function testRenderFontRendered()
	{
		$style = new TStyle();
		$style->getFont()->setBold(true);
		$this->assertStringContainsString('font-weight:bold', $this->renderStyle($style));
	}

	public function testRenderCssClassRendered()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$this->assertStringContainsString('class="my-class"', $this->renderStyle($style));
	}

	public function testRenderEmptyCustomStyleNotRendered()
	{
		$style = new TStyle();
		$style->setCustomStyle('');
		$this->assertStringNotContainsString('style=', $this->renderStyle($style));
	}

	public function testRenderNullCustomStyleNotRendered()
	{
		$this->assertStringNotContainsString('style=', $this->renderStyle(new TStyle()));
	}

	public function testRenderEmptyCssClassRendersEmptyAttribute()
	{
		// _class = '' means "explicitly cleared" — getHasCssClass() is true,
		// so class="" must be rendered (enables getAttribute('class') == '' in browsers).
		$style = new TStyle();
		$style->setCssClass('');
		$this->assertStringContainsString('class=""', $this->renderStyle($style));
	}

	public function testRenderCustomStyleInvalidEntriesSkipped()
	{
		$style = new TStyle();
		$style->setCustomStyle('invalid-entry; color: red; ; another-invalid; border: 1px solid');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('color:red', $output);
		$this->assertStringContainsString('border:1px solid', $output);
	}

	public function testRenderCustomStyleEmptyPropertyNameSkipped()
	{
		$style = new TStyle();
		$style->setCustomStyle(': value; ; color: red');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('color:red', $output);
	}

	public function testRenderCustomStyleColonInValue()
	{
		$style = new TStyle();
		$style->setCustomStyle('content: "test: value"');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('content:&quot;test: value&quot;', $output);
	}

	public function testRenderCustomStyleUrlValue()
	{
		$style = new TStyle();
		$style->setCustomStyle('background: url(http://example.com/img.png) center');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('background:url(http://example.com/img.png) center', $output);
	}

	public function testRenderAllPropertiesTogether()
	{
		$style = new TStyle();
		$style->setWidth('100px');
		$style->setHeight('200px');
		$style->setForeColor('red');
		$style->setBackColor('blue');
		$style->setBorderColor('black');
		$style->setBorderStyle('solid');
		$style->setBorderWidth('1px');
		$style->setBorderRadius('5px');
		$style->setCssClass('my-class');
		$style->setCustomStyle('margin: 10px');
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('width:100px', $output);
		$this->assertStringContainsString('height:200px', $output);
		$this->assertStringContainsString('color:red', $output);
		$this->assertStringContainsString('background-color:blue', $output);
		$this->assertStringContainsString('border-color:black', $output);
		$this->assertStringContainsString('border-style:solid', $output);
		$this->assertStringContainsString('border-width:1px', $output);
		$this->assertStringContainsString('border-radius:5px', $output);
		$this->assertStringContainsString('class="my-class"', $output);
		$this->assertStringContainsString('margin:10px', $output);
	}

	// ================================================================================
	// 16. __call magic (getXxx / setXxx)
	// ================================================================================

	public function testCallGetMapsToStyleField()
	{
		$style = new TStyle();
		$style->setStyleField('background-color', 'blue');
		$this->assertEquals('blue', $style->getBackgroundColor());
	}

	public function testCallSetMapsToStyleField()
	{
		$style = new TStyle();
		$style->setBackgroundColor('blue');
		$this->assertEquals('blue', $style->getStyleField('background-color'));
	}

	public function testCallGetCamelCaseToKebab()
	{
		$style = new TStyle();
		$style->setStyleField('font-size', '14px');
		$this->assertEquals('14px', $style->getFontSize());
	}

	public function testCallSetCamelCaseToKebab()
	{
		$style = new TStyle();
		$style->setFontSize('14px');
		$this->assertEquals('14px', $style->getStyleField('font-size'));
	}

	public function testCallGetMultiWordProperty()
	{
		$style = new TStyle();
		$style->setStyleField('line-height', '1.5');
		$this->assertEquals('1.5', $style->getLineHeight());
	}

	public function testCallSetMultiWordProperty()
	{
		$style = new TStyle();
		$style->setLineHeight('1.5');
		$this->assertEquals('1.5', $style->getStyleField('line-height'));
	}

	public function testCallSetWithWrongArgCountFallsThrough()
	{
		$style = new TStyle();
		$this->expectException(\Prado\Exceptions\TUnknownMethodException::class);
		$style->setBackgroundColor('blue', 'extra');
	}

	public function testCallUnknownPrefixFallsThrough()
	{
		$style = new TStyle();
		$this->expectException(\Prado\Exceptions\TUnknownMethodException::class);
		$style->applyBackgroundColor('blue');
	}

	public function testCallGetEmptyWhenNotSet()
	{
		$this->assertEquals('', (new TStyle())->getBackgroundColor());
	}

	public function testCallRealMethodTakesPrecedenceOverCall()
	{
		// getWidth() is a real method; __call is not invoked
		$style = new TStyle();
		$style->setWidth('100px');
		$this->assertEquals('100px', $style->getWidth());
	}

	public function testCallGetWithLeadingUnderscoreSuffix()
	{
		// get_Color() → strips 'get' → '_Color' → methodToAttributeName → '-color'
		// Leading dash is preserved; reads the '-color' field (vendor-prefix shorthand).
		$style = new TStyle();
		$style->setStyleField('-color', 'green');
		$this->assertEquals('green', $style->get_Color());
		// The normal 'color' field is a distinct key
		$this->assertEquals('', $style->getStyleField('color'));
	}

	public function testCallSetWithLeadingUnderscoreSuffix()
	{
		// set_Color('red') → '-color' field (leading dash preserved)
		$style = new TStyle();
		$style->set_Color('red');
		$this->assertEquals('red', $style->getStyleField('-color'));
		// The normal 'color' field is untouched
		$this->assertEquals('', $style->getStyleField('color'));
	}

	public function testCallGetWithDoubleUnderscoreSuffix()
	{
		// get__WebColor() → '__WebColor' → '--web-color'; leading '--' preserved
		$style = new TStyle();
		$style->setStyleField('--web-color', 'blue');
		$this->assertEquals('blue', $style->get__WebColor());
	}

	public function testCallSetWithDoubleUnderscoreSuffix()
	{
		// set__WebColor('blue') writes '--web-color' (CSS custom property, leading '--' kept)
		$style = new TStyle();
		$style->set__WebColor('blue');
		$this->assertEquals('blue', $style->getStyleField('--web-color'));
		// The unprefixed 'web-color' field is untouched
		$this->assertEquals('', $style->getStyleField('web-color'));
	}

	// ================================================================================
	// 17. __get / __set magic
	// ================================================================================

	public function testMagicGetRoutesToRealGetter()
	{
		$style = new TStyle();
		$style->setWidth('100px');
		$this->assertEquals('100px', $style->Width);
	}

	public function testMagicSetRoutesToRealSetter()
	{
		$style = new TStyle();
		$style->Width = '200px';
		$this->assertEquals('200px', $style->getWidth());
	}

	public function testMagicGetFontRoutesToRealGetter()
	{
		$style = new TStyle();
		$this->assertInstanceOf(TFont::class, $style->Font);
	}

	public function testMagicGetFallsBackToStyleField()
	{
		$style = new TStyle();
		$style->setStyleField('background-color', 'green');
		$this->assertEquals('green', $style->BackgroundColor);
	}

	public function testMagicSetFallsBackToStyleField()
	{
		$style = new TStyle();
		$style->BackgroundColor = 'green';
		$this->assertEquals('green', $style->getStyleField('background-color'));
	}
	
	public function testMagicSetFontSize()
	{
		$style = new TStyle();
		$style->FontSize = '16px';
		$this->assertEquals('16px', $style->getStyleField('font-size'));
	}
	
	public function testMagicSetFont_SSize()
	{
		$style = new TStyle();
		$style->Font_Size = '16px';
		$this->assertEquals('16px', $style->getStyleField('font-size'));
	}
	
	public function testMagicSetFont_size()
	{
		$style = new TStyle();
		$style->Font_size = '16px';
		$this->assertEquals('16px', $style->getStyleField('font-size'));
	}

	public function testMagicGetFontSize()
	{
		$style = new TStyle();
		$style->setStyleField('font-size', '16px');
		$this->assertEquals('16px', $style->FontSize);
		$this->assertEquals('16px', $style->Font_Size);
		$this->assertEquals('16px', $style->Font_size);
	}

	public function testMagicSetUnderscoreAsDash()
	{
		$style = new TStyle();
		$style->Padding_Top = '8px';
		$this->assertEquals('8px', $style->getStyleField('padding-top'));
	}

	public function testMagicGetUnderscoreAsDash()
	{
		$style = new TStyle();
		$style->setStyleField('padding-top', '8px');
		$this->assertEquals('8px', $style->Padding_Top);
		$this->assertEquals('8px', $style->PaddingTop);
		$this->assertEquals('8px', $style->PaddingTop);
	}

	public function testMagicSetLeadingUnderscore()
	{
		// Leading _ → leading - (vendor-prefix shorthand).
		// Unlike __call, __set does NOT ltrim, so the leading dash is preserved.
		$style = new TStyle();
		$style->_WebkitTransform = 'rotate(45deg)';
		$this->assertEquals('rotate(45deg)', $style->getStyleField('-webkit-transform'));
	}

	public function testMagicGetLeadingUnderscore()
	{
		$style = new TStyle();
		$style->setStyleField('-webkit-transform', 'rotate(45deg)');
		$this->assertEquals('rotate(45deg)', $style->_WebkitTransform);
	}

	public function testMagicSetLeadingUnderscoreSingleWord()
	{
		// _Color → '-color' (single-word, no extra dashes inserted)
		$style = new TStyle();
		$style->_Color = 'blue';
		$this->assertEquals('blue', $style->getStyleField('-color'));
		// Importantly, the normal 'color' field is untouched
		$this->assertEquals('', $style->getStyleField('color'));
	}

	public function testMagicGetLeadingUnderscoreSingleWord()
	{
		$style = new TStyle();
		$style->setStyleField('-color', 'blue');
		$this->assertEquals('blue', $style->_Color);
	}

	public function testMagicSetDoubleUnderscore()
	{
		// Leading __ → leading -- (CSS custom property / double-dash vendor prefix).
		// __get/__set preserves the full '--' prefix; this is the correct PHP idiom.
		$style = new TStyle();
		$style->__WebColor = '#005fcc';
		$this->assertEquals('#005fcc', $style->getStyleField('--web-color'));
		// The 'web-color' field without leading dashes must remain untouched
		$this->assertEquals('', $style->getStyleField('web-color'));
	}

	public function testMagicGetDoubleUnderscore()
	{
		$style = new TStyle();
		$style->setStyleField('--web-color', '#005fcc');
		$this->assertEquals('#005fcc', $style->__WebColor);
		$this->assertEquals('#005fcc', $style->__Web_Color);
	}

	public function testMagicSetDoubleUnderscoreWithUnderscoreWord()
	{
		// __web_color → '--web-color' (double leading __ + underscore-as-dash inside)
		$style = new TStyle();
		$style->__web_color = 'red';
		$this->assertEquals('red', $style->getStyleField('--web-color'));
	}

	public function testMagicGetDoubleUnderscoreWithUnderscoreWord()
	{
		$style = new TStyle();
		$style->setStyleField('--web-color', 'red');
		$this->assertEquals('red', $style->__web_color);
	}

	public function testMagicSetSafariCssCustomProperty()
	{
		$style = new TStyle();
		$style->__safari_transform = 'none';
		$this->assertEquals('none', $style->getStyleField('--safari-transform'));
	}

	public function testMagicGetSafariCssCustomProperty()
	{
		$style = new TStyle();
		$style->setStyleField('--safari-transform', 'none');
		$this->assertEquals('none', $style->__safari_transform);
	}

	public function testMagicCssCustomPropertyRendered()
	{
		$style = new TStyle();
		$style->__BrandColor = '#ff0000';
		$style->_WebkitTransform = 'translateX(10px)';
		$output = $this->renderStyle($style);
		$this->assertStringContainsString('--brand-color:#ff0000', $output);
		$this->assertStringContainsString('-webkit-transform:translateX(10px)', $output);
	}

	// ================================================================================
	// 18. canGetProperty / canSetProperty / hasMethod
	// ================================================================================

	public function testCanGetPropertyAlwaysTrue()
	{
		$style = new TStyle();
		$this->assertTrue($style->canGetProperty('Width'));
		$this->assertTrue($style->canGetProperty('AnythingAtAll'));
		$this->assertTrue($style->canGetProperty('background-color'));
	}

	public function testCanSetPropertyAlwaysTrue()
	{
		$style = new TStyle();
		$this->assertTrue($style->canSetProperty('Width'));
		$this->assertTrue($style->canSetProperty('AnythingAtAll'));
		$this->assertTrue($style->canSetProperty('background-color'));
	}

	public function testHasMethodTrueForGetPrefix()
	{
		$this->assertTrue((new TStyle())->hasMethod('getAnything'));
		$this->assertTrue((new TStyle())->hasMethod('GETANYTHING'));
	}

	public function testHasMethodTrueForSetPrefix()
	{
		$this->assertTrue((new TStyle())->hasMethod('setAnything'));
		$this->assertTrue((new TStyle())->hasMethod('SETANYTHING'));
	}

	public function testHasMethodTrueForDefinedMethods()
	{
		$style = new TStyle();
		$this->assertTrue($style->hasMethod('getWidth'));
		$this->assertTrue($style->hasMethod('setBackColor'));
		$this->assertTrue($style->hasMethod('reset'));
	}

	public function testHasMethodFalseForUnknownNonGetSet()
	{
		$this->assertFalse((new TStyle())->hasMethod('unknownMethod'));
		$this->assertFalse((new TStyle())->hasMethod('applyStyle'));
	}

	// ================================================================================
	// 19. Serialization (_getZappableSleepProps)
	// ================================================================================

	public function testSerializeFreshStyleIsCompact()
	{
		$style = new TStyle();
		$data = serialize($style);
		// Default state: _fields, _font, _class, _customStyle, _displayStyle all zapped
		$this->assertStringNotContainsString('_fields', $data);
		$this->assertStringNotContainsString('_font', $data);
		$this->assertStringNotContainsString('_class', $data);
		$this->assertStringNotContainsString('_customStyle', $data);
		$this->assertStringNotContainsString('_displayStyle', $data);
	}

	public function testSerializeWithFieldsIncludesFields()
	{
		$style = new TStyle();
		$style->setStyleField('color', 'red');
		$data = serialize($style);
		$this->assertStringContainsString('_fields', $data);
	}

	public function testSerializeWithFontIncludesFont()
	{
		$style = new TStyle();
		$style->getFont();
		$data = serialize($style);
		$this->assertStringContainsString('_font', $data);
	}

	public function testSerializeWithCssClassIncludesClass()
	{
		$style = new TStyle();
		$style->setCssClass('my-class');
		$data = serialize($style);
		$this->assertStringContainsString('_class', $data);
	}

	public function testSerializeWithCustomStyleIncludesCustomStyle()
	{
		$style = new TStyle();
		$style->setCustomStyle('color: red');
		$data = serialize($style);
		$this->assertStringContainsString('_customStyle', $data);
	}

	public function testSerializeWithNonDefaultDisplayStyleIncludesDisplayStyle()
	{
		$style = new TStyle();
		$style->setDisplayStyle(TDisplayStyle::None);
		$data = serialize($style);
		$this->assertStringContainsString('_displayStyle', $data);
	}

	public function testSerializeRoundTripPreservesState()
	{
		$style = new TStyle();
		$style->setWidth('100px');
		$style->setCssClass('my-class');
		$style->setCustomStyle('margin: 5px');
		$style->getFont()->setBold(true);
		$style->setDisplayStyle(TDisplayStyle::None);

		$restored = unserialize(serialize($style));

		$this->assertEquals('100px', $restored->getWidth());
		$this->assertEquals('my-class', $restored->getCssClass());
		$this->assertEquals('margin: 5px', $restored->getCustomStyle());
		$this->assertTrue($restored->getFont()->getBold());
		$this->assertEquals(TDisplayStyle::None, $restored->getDisplayStyle());
	}

	public function testSerializeDefaultStateRoundTripRestoresDisplayStyle()
	{
		// _displayStyle is zapped during __sleep; __wakeup must restore it to
		// DEFAULT_DISPLAY_STYLE so getDisplayStyle() returns the correct default.
		$style = new TStyle();
		$restored = unserialize(serialize($style));
		$this->assertEquals(TStyle::DEFAULT_DISPLAY_STYLE, $restored->getDisplayStyle());
	}

	public function testSerializeWithEmptyCssClassIncludesField()
	{
		// _class = '' (explicitly set to empty string) → getHasCssClass() true → must be serialized
		$style = new TStyle();
		$style->setCssClass('');
		$data = serialize($style);
		$this->assertStringContainsString('_class', $data);
	}

	public function testSerializeWithEmptyStringCustomStyleIncludesField()
	{
		// _customStyle = '' (explicitly set to empty string) → getHasCustomStyle() true → must be serialized
		$style = new TStyle();
		$style->setCustomStyle('');
		$data = serialize($style);
		$this->assertStringContainsString('_customStyle', $data);
	}

	public function testSerializeEmptyCssClassRoundTrip()
	{
		$style = new TStyle();
		$style->setCssClass('');
		$restored = unserialize(serialize($style));
		$this->assertTrue($restored->getHasCssClass());
		$this->assertEquals('', $restored->getCssClass());
	}

	public function testSerializeEmptyCustomStyleRoundTrip()
	{
		$style = new TStyle();
		$style->setCustomStyle('');
		$restored = unserialize(serialize($style));
		$this->assertTrue($restored->getHasCustomStyle());
		$this->assertEquals('', $restored->getCustomStyle());
	}

	// ================================================================================
	// 20. methodToAttributeName — direct tests for the PascalCase/underscore → kebab conversion
	// ================================================================================

	/**
	 * Access the protected methodToAttributeName via a subclass shim.
	 */
	private function toAttr(string $input): string
	{
		return (new class extends TStyle {
			public function convert(string $n): string { return $this->methodToAttributeName($n); }
		})->convert($input);
	}

	public function testMethodToAttributeNameSimplePascalCase()
	{
		// Each capital after position 0 becomes a dash prefix
		$this->assertEquals('font-size', $this->toAttr('FontSize'));
		$this->assertEquals('background-color', $this->toAttr('BackgroundColor'));
		$this->assertEquals('border-radius', $this->toAttr('BorderRadius'));
		$this->assertEquals('line-height', $this->toAttr('LineHeight'));
		$this->assertEquals('z-index', $this->toAttr('ZIndex'));
	}

	public function testMethodToAttributeNameLeadingUppercaseNotDashed()
	{
		// Capital at position 0 must NEVER produce a leading dash
		$this->assertStringStartsNotWith('-', $this->toAttr('Color'));
		$this->assertStringStartsNotWith('-', $this->toAttr('Width'));
		$this->assertStringStartsNotWith('-', $this->toAttr('BackgroundColor'));
	}

	public function testMethodToAttributeNameAllLowerPassesThrough()
	{
		// Already lowercase: no transformation except lowercasing (no-op)
		$this->assertEquals('color', $this->toAttr('color'));
		$this->assertEquals('margin', $this->toAttr('margin'));
		$this->assertEquals('width', $this->toAttr('width'));
	}

	public function testMethodToAttributeNameUnderscoreBecomesOneDash()
	{
		// Underscore is replaced by a single dash; the uppercase that follows
		// must NOT get a second dash prefix — this was the core bug that was fixed.
		$this->assertEquals('padding-top', $this->toAttr('Padding_Top'));
		$this->assertEquals('font-size', $this->toAttr('Font_Size'));
		$this->assertEquals('border-radius', $this->toAttr('Border_Radius'));
		$this->assertEquals('margin-bottom', $this->toAttr('Margin_Bottom'));
	}

	public function testMethodToAttributeNameLowercaseAfterUnderscore()
	{
		// Underscore + lowercase letter: still just one dash
		$this->assertEquals('font-size', $this->toAttr('Font_size'));
		$this->assertEquals('padding-top', $this->toAttr('padding_top'));
	}

	public function testMethodToAttributeNameMultipleUnderscores()
	{
		// Each underscore becomes one dash; consecutive underscores become
		// consecutive dashes (developer error — not normalized per design decision)
		$this->assertEquals('a-b-c', $this->toAttr('A_B_C'));
		$this->assertEquals('a-b', $this->toAttr('A_b'));
	}

	public function testMethodToAttributeNameCamelCase()
	{
		// camelCase (lowercase first letter): no leading dash
		$this->assertEquals('background-color', $this->toAttr('backgroundColor'));
		$this->assertEquals('font-size', $this->toAttr('fontSize'));
		$this->assertEquals('line-height', $this->toAttr('lineHeight'));
	}

	public function testMethodToAttributeNameSingleWord()
	{
		$this->assertEquals('color', $this->toAttr('Color'));
		$this->assertEquals('width', $this->toAttr('Width'));
		$this->assertEquals('margin', $this->toAttr('Margin'));
	}

	public function testMethodToAttributeNameAlreadyKebab()
	{
		// A name already containing a dash: the letter after the dash is NOT re-dashed
		$this->assertEquals('border-color', $this->toAttr('border-color'));
		$this->assertEquals('font-size', $this->toAttr('font-size'));
	}

	public function testMethodToAttributeNameLeadingDash()
	{
		// A name starting with '-' keeps that leading dash;
		// the capital after it must NOT gain a second dash.
		$this->assertEquals('-color', $this->toAttr('-Color'));
		$this->assertEquals('-webkit-transform', $this->toAttr('-WebkitTransform'));
		$this->assertEquals('-moz-border-radius', $this->toAttr('-MozBorderRadius'));
	}

	public function testMethodToAttributeNameLeadingUnderscore()
	{
		// Leading '_' becomes a leading '-' (underscore→dash rule applied to position 0).
		// Equivalent to the single-dash vendor-prefix shorthand.
		$this->assertEquals('-color', $this->toAttr('_Color'));
		$this->assertEquals('-webkit-transform', $this->toAttr('_WebkitTransform'));
		$this->assertEquals('-moz-border-radius', $this->toAttr('_MozBorderRadius'));
	}

	public function testMethodToAttributeNameDoubleLeadingUnderscore()
	{
		// '__' → '--': the CSS custom property / double-dash vendor-prefix mapping.
		$this->assertEquals('--web-color', $this->toAttr('__WebColor'));
		$this->assertEquals('--my-var', $this->toAttr('__MyVar'));
		$this->assertEquals('--safari-transform', $this->toAttr('__safari_transform'));
		$this->assertEquals('--web-kit-transform', $this->toAttr('__WebKitTransform'));
	}

	public function testMethodToAttributeNameDoubleLeadingUnderscoreAllLower()
	{
		// Double-underscore with no capitals: underscores become dashes throughout
		$this->assertEquals('--web-color', $this->toAttr('__web_color'));
		$this->assertEquals('--brand-primary', $this->toAttr('__brand_primary'));
	}

	// ================================================================================
	// 21. TWebControl integration — template → property → style → render
	// ================================================================================

	/**
	 * Render a TWebControl subclass to a string.
	 */
	private function renderControl(\Prado\Web\UI\WebControls\TWebControl $control): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	public function testIntegrationStyleAttributeRawCss()
	{
		// Template equivalent:
		//   <com:THeader Style="font-weight:bold;color:red" />
		$control = new THeader();
		$control->setStyle('font-weight:bold;color:red');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('font-weight:bold', $output);
		$this->assertStringContainsString('color:red', $output);
	}

	public function testIntegrationNamedConvenienceProperties()
	{
		// Template equivalent:
		//   <com:THeader BackColor="#f5f5f5" ForeColor="#333" Width="300px"
		//                BorderStyle="solid" BorderWidth="1px" BorderRadius="4px"
		//                CssClass="card" />
		$control = new THeader();
		$control->setBackColor('#f5f5f5');
		$control->setForeColor('#333');
		$control->setWidth('300px');
		$control->setBorderStyle('solid');
		$control->setBorderWidth('1px');
		$control->getStyle()->setBorderRadius('4px');
		$control->setCssClass('card');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('background-color:#f5f5f5', $output);
		$this->assertStringContainsString('color:#333', $output);
		$this->assertStringContainsString('width:300px', $output);
		$this->assertStringContainsString('border-style:solid', $output);
		$this->assertStringContainsString('border-width:1px', $output);
		$this->assertStringContainsString('border-radius:4px', $output);
		$this->assertStringContainsString('class="card"', $output);
	}

	public function testIntegrationStyleMagicPascalCaseProperty()
	{
		// TStyle magic __set: $control->getStyle()->FontSize = '14px'
		// maps FontSize → font-size automatically.
		// Template equivalent for arbitrary CSS not in the convenience set:
		//   <com:THeader Style="font-size:14px" />
		$control = new THeader();
		$control->getStyle()->FontSize = '14px';
		$output = $this->renderControl($control);
		$this->assertStringContainsString('font-size:14px', $output);
	}

	public function testIntegrationStyleMagicUnderscoreProperty()
	{
		// Underscore in magic property name maps to dash in CSS name.
		// Padding_Top → padding-top (one dash, not two).
		$control = new THeader();
		$control->getStyle()->Padding_Top = '8px';
		$output = $this->renderControl($control);
		$this->assertStringContainsString('padding-top:8px', $output);
	}

	public function testIntegrationStyleMagicCallSetter()
	{
		// TStyle __call: $control->getStyle()->setLineHeight('1.5')
		// maps setLineHeight → line-height automatically.
		$control = new THeader();
		$control->getStyle()->setLineHeight('1.5');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('line-height:1.5', $output);
	}

	public function testIntegrationStyleArrayAccess()
	{
		// TStyle ArrayAccess: $control->getStyle()['padding'] = '10px'
		$control = new THeader();
		$control->getStyle()['padding'] = '10px';
		$output = $this->renderControl($control);
		$this->assertStringContainsString('padding:10px', $output);
	}

	public function testIntegrationStyleArrayAccessHyphenatedName()
	{
		// Direct hyphenated CSS name via array access (most explicit form)
		$control = new THeader();
		$control->getStyle()['letter-spacing'] = '0.05em';
		$output = $this->renderControl($control);
		$this->assertStringContainsString('letter-spacing:0.05em', $output);
	}

	public function testIntegrationStylePropSubTag()
	{
		// Sub-tag equivalent:
		//   <com:THeader>
		//       <prop:Style>padding:16px;margin-bottom:8px</prop:Style>
		//   </com:THeader>
		// setStyle() is the PHP equivalent of the <prop:Style> sub-tag.
		$control = new THeader();
		$control->setStyle('padding:16px;margin-bottom:8px');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('padding:16px', $output);
		$this->assertStringContainsString('margin-bottom:8px', $output);
	}

	public function testIntegrationNamedPropertyOverridesStyleAttribute()
	{
		// Named properties (_fields) win over raw Style string (customStyle)
		// at render time regardless of set order.
		// Template equivalent:
		//   <com:THeader Style="color:red" ForeColor="blue" />
		$control = new THeader();
		$control->setStyle('color:red');
		$control->setForeColor('blue');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('color:blue', $output);
		$this->assertStringNotContainsString('color:red', $output);
	}

	public function testIntegrationDisplayStyleNone()
	{
		// DisplayStyle is a TStyle property, not a TWebControl delegation.
		// Template equivalent: <com:THeader Style="display:none" />
		$control = new THeader();
		$control->getStyle()->setDisplayStyle(TDisplayStyle::None);
		$output = $this->renderControl($control);
		$this->assertStringContainsString('display:none', $output);
	}

	// ================================================================================
	// 22. setSubProperty / TTemplate integration — Style.<attr> all forms
	//
	// TTemplate calls attributeToMethodName(attr) — replacing '-' with '_' — then
	// setSubProperty('Style.<converted>', value).  setSubProperty resolves to
	// $control->getStyle()-><converted> = value, which invokes TStyle::__set or
	// __call (both now preserve leading dashes).
	//
	//   Template attribute      attributeToMethodName      CSS field resolved
	//   ─────────────────────── ────────────────────── ──────────────────────
	//   Style.Width             Style.Width             width  (real setter)
	//   Style.FontSize          Style.FontSize          font-size  (magic)
	//   Style.font-size         Style.font_size         font-size
	//   Style.--web-color       Style.__web_color       --web-color
	//   Style.--safari-*        Style.__safari_*        --safari-*
	//   Style.-webkit-*         Style._webkit_*         -webkit-*
	// ================================================================================

	private function newTemplate(string $html): TTemplate
	{
		return new TTemplate($html, sys_get_temp_dir());
	}

	private function newTemplateUnvalidated(string $html): TTemplate
	{
		$ref = PradoUnit::reflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$props = [
			'_sourceTemplate' => true,
			'_contextPath' => sys_get_temp_dir(),
			'_tplFile' => null,
			'_startingLine' => 0,
			'_content' => $html,
			'_attributevalidation' => false,
			'_hashCode' => md5($html),
		];
		foreach ($props as $name => $val) {
			PradoUnit::setProp($tplObj, $name, $val);
		}
		$ref->getParentClass()->getParentClass()->getConstructor()->invoke($tplObj);
		PradoUnit::invoke($tplObj, 'parse', $html);
		PradoUnit::setProp($tplObj, '_content', null);
		return $tplObj;
	}

	private function createControlWithPage(): TControl
	{
		$page = new TPage();
		$page->setID('StyleTestPage');
		$control = new TControl();
		$control->setID('StyleTestControl');
		$page->getControls()->add($control);
		return $control;
	}

	// ------ setSubProperty tests (the PHP-level API that TTemplate calls) ------

	public function testSetSubPropertyStyleExistingGetterSetter()
	{
		// Style.Width → existing getter/setter: $control->getStyle()->setWidth('100px')
		$control = new THeader();
		$control->setSubProperty('Style.Width', '100px');
		$this->assertEquals('100px', $control->getStyle()->getWidth());
	}

	public function testSetSubPropertyStyleExistingGetterSetterCssClass()
	{
		$control = new THeader();
		$control->setSubProperty('Style.CssClass', 'card');
		$this->assertEquals('card', $control->getStyle()->getCssClass());
	}

	public function testGetSubPropertyStyleExistingGetter()
	{
		$control = new THeader();
		$control->getStyle()->setWidth('200px');
		$this->assertEquals('200px', $control->getSubProperty('Style.Width'));
	}

	public function testSetSubPropertyStylePascalCaseNotMethod()
	{
		// Style.FontSize → not a real method → __set('FontSize') → methodToAttributeName →
		// 'font-size' → setStyleField('font-size', '14px')
		$control = new THeader();
		$control->setSubProperty('Style.FontSize', '14px');
		$this->assertEquals('14px', $control->getStyle()->getStyleField('font-size'));
	}

	public function testGetSubPropertyStylePascalCaseNotMethod()
	{
		$control = new THeader();
		$control->getStyle()->setStyleField('font-size', '14px');
		$this->assertEquals('14px', $control->getSubProperty('Style.FontSize'));
	}

	public function testSetSubPropertyStyleDashConvertedToUnderscore()
	{
		// TTemplate converts 'font-size' → 'font_size' via attributeToMethodName before
		// calling setSubProperty.  'font_size' → methodToAttributeName → 'font-size'.
		$control = new THeader();
		$control->setSubProperty('Style.font_size', '16px');
		$this->assertEquals('16px', $control->getStyle()->getStyleField('font-size'));
	}

	public function testGetSubPropertyStyleDashConvertedToUnderscore()
	{
		$control = new THeader();
		$control->getStyle()->setStyleField('font-size', '16px');
		$this->assertEquals('16px', $control->getSubProperty('Style.font_size'));
	}

	public function testSetSubPropertyStyleCssCustomProperty()
	{
		// Template 'Style.--web-color' → attributeToMethodName → 'Style.__web_color'
		// → setSubProperty → $style->__web_color = 'red' → methodToAttributeName →
		// '--web-color' → setStyleField('--web-color', 'red')
		$control = new THeader();
		$control->setSubProperty('Style.__web_color', 'red');
		$this->assertEquals('red', $control->getStyle()->getStyleField('--web-color'));
	}

	public function testGetSubPropertyStyleCssCustomProperty()
	{
		$control = new THeader();
		$control->getStyle()->setStyleField('--web-color', 'red');
		$this->assertEquals('red', $control->getSubProperty('Style.__web_color'));
	}

	public function testSetSubPropertyStyleSafariCssCustomProperty()
	{
		// Template 'Style.--safari-transform' → '__safari_transform'
		$control = new THeader();
		$control->setSubProperty('Style.__safari_transform', 'none');
		$this->assertEquals('none', $control->getStyle()->getStyleField('--safari-transform'));
	}

	public function testSetSubPropertyStyleWebkitVendorPrefix()
	{
		// Template 'Style.-webkit-transform' → '_webkit_transform'
		$control = new THeader();
		$control->setSubProperty('Style._webkit_transform', 'rotate(45deg)');
		$this->assertEquals('rotate(45deg)', $control->getStyle()->getStyleField('-webkit-transform'));
	}

	public function testGetSubPropertyStyleWebkitVendorPrefix()
	{
		$control = new THeader();
		$control->getStyle()->setStyleField('-webkit-transform', 'rotate(45deg)');
		$this->assertEquals('rotate(45deg)', $control->getSubProperty('Style._webkit_transform'));
	}

	public function testSetSubPropertyStyleCssCustomPropertyRendered()
	{
		$control = new THeader();
		$control->setSubProperty('Style.__brand_color', '#005fcc');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('--brand-color:#005fcc', $output);
	}

	public function testSetSubPropertyStyleVendorPrefixRendered()
	{
		$control = new THeader();
		$control->setSubProperty('Style._webkit_transform', 'translateX(10px)');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('-webkit-transform:translateX(10px)', $output);
	}

	// ------ TTemplate instantiation tests (actual template parsing) ------

	public function testTemplateStyleExistingGetterSetter()
	{
		// <com:THeader Style.Width="100px" />
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.Width="100px" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertInstanceOf(THeader::class, $header);
		$this->assertEquals('100px', $header->getStyle()->getWidth());
	}

	public function testTemplateStylePascalCaseNotMethod()
	{
		// <com:THeader Style.FontSize="14px" /> — magic property access
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.FontSize="14px" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertEquals('14px', $header->getStyle()->getStyleField('font-size'));
	}

	public function testTemplateStyleDashInAttributeName()
	{
		// <com:THeader Style.font-size="16px" />
		// TTemplate converts 'font-size' → 'font_size' → resolves to 'font-size' field
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.font-size="16px" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertEquals('16px', $header->getStyle()->getStyleField('font-size'));
	}

	public function testTemplateStyleCssCustomPropertyDoubleDash()
	{
		// <com:THeader Style.--web-color="red" />
		// '--web-color' → '__web_color' → resolves to '--web-color' field
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.--web-color="red" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertEquals('red', $header->getStyle()->getStyleField('--web-color'));
	}

	public function testTemplateStyleSafariCssCustomProperty()
	{
		// <com:THeader Style.--safari-transform="none" />
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.--safari-transform="none" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertEquals('none', $header->getStyle()->getStyleField('--safari-transform'));
	}

	public function testTemplateStyleWebkitVendorPrefix()
	{
		// <com:THeader Style.-webkit-transform="rotate(45deg)" />
		// '-webkit-transform' → '_webkit_transform' → '-webkit-transform' field
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.-webkit-transform="rotate(45deg)" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$this->assertEquals('rotate(45deg)', $header->getStyle()->getStyleField('-webkit-transform'));
	}

	public function testTemplateStyleCssCustomPropertyRendered()
	{
		$tpl = $this->newTemplateUnvalidated('<com:THeader ID="h1" Style.--brand-color="#005fcc" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$header = $parent->getControls()[0];
		$output = $this->renderControl($header);
		$this->assertStringContainsString('--brand-color:#005fcc', $output);
	}

	// ================================================================================
	// 23. DEFAULT_DISPLAY_STYLE subclass override
	//
	// Verifies that the late-static-binding mechanic (`static::DEFAULT_DISPLAY_STYLE`
	// in __construct, reset, _getZappableSleepProps, and __wakeup) allows subclasses
	// to change the out-of-the-box display style without touching any other behaviour.
	//
	// NOTE: The constructor sets `_displayStyle` directly via `static::DEFAULT_DISPLAY_STYLE`
	// but does NOT call setDisplayStyle(), so no CSS fields are written on construction —
	// exactly mirroring TStyle itself (Fixed default, but no 'visibility' field until
	// setDisplayStyle(Fixed) is called explicitly).
	// ================================================================================

	public function testDefaultDisplayStyleConstantOnBaseClass()
	{
		$this->assertSame(TDisplayStyle::Fixed, TStyle::DEFAULT_DISPLAY_STYLE);
	}

	public function testSubclassCanOverrideDefaultDisplayStyleConstant()
	{
		$this->assertSame(TDisplayStyle::None, TStyleNoneDefault::DEFAULT_DISPLAY_STYLE);
		$this->assertSame(TDisplayStyle::Dynamic, TStyleDynamicDefault::DEFAULT_DISPLAY_STYLE);
		$this->assertSame(TDisplayStyle::Hidden, TStyleHiddenDefault::DEFAULT_DISPLAY_STYLE);
	}

	public function testSubclassNoneDefaultNewInstanceUsesNone()
	{
		$style = new TStyleNoneDefault();
		$this->assertSame(TDisplayStyle::None, $style->getDisplayStyle());
	}

	public function testSubclassDynamicDefaultNewInstanceUsesDynamic()
	{
		$style = new TStyleDynamicDefault();
		$this->assertSame(TDisplayStyle::Dynamic, $style->getDisplayStyle());
	}

	public function testSubclassHiddenDefaultNewInstanceUsesHidden()
	{
		$style = new TStyleHiddenDefault();
		$this->assertSame(TDisplayStyle::Hidden, $style->getDisplayStyle());
	}

	public function testSubclassConstructorDoesNotWriteCssFieldsForDefault()
	{
		// Constructor sets _displayStyle directly; it does NOT call setDisplayStyle(),
		// so no CSS fields are written automatically — same as TStyle(Fixed) having
		// no 'visibility' field until setDisplayStyle(Fixed) is called.
		$none = new TStyleNoneDefault();
		$this->assertFalse($none->hasStyleField('display'),
			'constructor must not auto-write display:none; CSS fields are only set by setDisplayStyle()');
		$this->assertFalse($none->getHasStyleFields(),
			'fresh subclass instance must have no style fields');

		$dyn = new TStyleDynamicDefault();
		$this->assertFalse($dyn->hasStyleField('display'));
		$this->assertFalse($dyn->hasStyleField('visibility'));
	}

	public function testBaseClassNewInstanceUnaffectedBySubclasses()
	{
		// Subclass constant overrides must never bleed into TStyle itself.
		$base = new TStyle();
		$this->assertSame(TDisplayStyle::Fixed, $base->getDisplayStyle());
	}

	public function testTwoDifferentSubclassesAreIndependent()
	{
		$none = new TStyleNoneDefault();
		$dyn = new TStyleDynamicDefault();
		$this->assertSame(TDisplayStyle::None, $none->getDisplayStyle());
		$this->assertSame(TDisplayStyle::Dynamic, $dyn->getDisplayStyle());
	}

	public function testSubclassResetRestoresSubclassDefault()
	{
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$style->reset();
		$this->assertSame(TDisplayStyle::None, $style->getDisplayStyle(),
			'reset() must restore the subclass DEFAULT_DISPLAY_STYLE, not TStyle::Fixed');
	}

	public function testSubclassResetDoesNotRestoreBaseDefault()
	{
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$style->reset();
		$this->assertNotSame(TDisplayStyle::Fixed, $style->getDisplayStyle(),
			'reset() must not restore TDisplayStyle::Fixed when subclass overrides to None');
	}

	public function testBaseClassResetUnaffectedBySubclassConstants()
	{
		$base = new TStyle();
		$base->setDisplayStyle(TDisplayStyle::None);
		$base->reset();
		$this->assertSame(TDisplayStyle::Fixed, $base->getDisplayStyle(),
			'TStyle::reset() must still restore to Fixed regardless of any subclass that exists');
	}

	public function testSubclassResetClearsAllFieldsAndRestoresDefault()
	{
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Dynamic); // clears display & visibility
		$style->setWidth('100px');
		$style->setCssClass('card');
		$style->setCustomStyle('margin:0');
		$style->getFont()->setBold(true);

		$style->reset();

		$this->assertSame(TDisplayStyle::None, $style->getDisplayStyle());
		$this->assertFalse($style->getHasStyleFields());
		$this->assertFalse($style->getHasCssClass());
		$this->assertFalse($style->getHasCustomStyle());
		$this->assertFalse($style->getHasFont());
	}

	public function testSubclassDynamicDefaultResetClearsAll()
	{
		$style = new TStyleDynamicDefault();
		$style->setDisplayStyle(TDisplayStyle::None);
		$style->setWidth('50px');
		$style->reset();
		$this->assertSame(TDisplayStyle::Dynamic, $style->getDisplayStyle());
		$this->assertFalse($style->getHasStyleFields());
	}

	// ---- serialization ----

	public function testSubclassSerializationZapsDisplayStyleAtDefault()
	{
		// At default (None), _displayStyle must be omitted from serialized data.
		$style = new TStyleNoneDefault();
		$data = serialize($style);
		$this->assertStringNotContainsString('_displayStyle', $data,
			'_displayStyle must be zapped when it equals the subclass DEFAULT_DISPLAY_STYLE');
	}

	public function testSubclassSerializationKeepsDisplayStyleWhenNotAtDefault()
	{
		// At a non-default value (Fixed ≠ None), _displayStyle must be included.
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$data = serialize($style);
		$this->assertStringContainsString('_displayStyle', $data,
			'_displayStyle must be serialized when it differs from the subclass DEFAULT_DISPLAY_STYLE');
	}

	public function testSubclassSerializationBaseDefaultZappedBaseNotSubclass()
	{
		// A TStyle with Fixed (its own default) zaps; TStyleNoneDefault with Fixed does NOT.
		$base = new TStyle();
		$sub = new TStyleNoneDefault();
		$sub->setDisplayStyle(TDisplayStyle::Fixed); // non-default for the subclass

		$baseData = serialize($base);
		$subData = serialize($sub);

		$this->assertStringNotContainsString('_displayStyle', $baseData,
			'TStyle at its Fixed default must not include _displayStyle');
		$this->assertStringContainsString('_displayStyle', $subData,
			'TStyleNoneDefault at Fixed (non-default) must include _displayStyle');
	}

	public function testSubclassSerializationDefaultStateRoundTrip()
	{
		// The key regression: after serialize+unserialize of a default-state subclass,
		// __wakeup must re-initialise _displayStyle to static::DEFAULT_DISPLAY_STYLE
		// so the subclass's own default (not TStyle::Fixed) is returned.
		$style = new TStyleNoneDefault();
		$restored = unserialize(serialize($style));

		$this->assertInstanceOf(TStyleNoneDefault::class, $restored);
		$this->assertSame(TDisplayStyle::None, $restored->getDisplayStyle(),
			'__wakeup must restore the subclass DEFAULT_DISPLAY_STYLE, not TStyle::Fixed');
	}

	public function testSubclassSerializationNonDefaultStateRoundTrip()
	{
		// Non-default value is serialized and restored verbatim.
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Fixed);
		$style->setWidth('200px');
		$restored = unserialize(serialize($style));

		$this->assertSame(TDisplayStyle::Fixed, $restored->getDisplayStyle());
		$this->assertSame('200px', $restored->getWidth());
	}

	public function testSubclassDynamicDefaultSerializationRoundTrip()
	{
		$style = new TStyleDynamicDefault();
		$restored = unserialize(serialize($style));
		$this->assertSame(TDisplayStyle::Dynamic, $restored->getDisplayStyle());
	}

	public function testSubclassHiddenDefaultSerializationRoundTrip()
	{
		$style = new TStyleHiddenDefault();
		$restored = unserialize(serialize($style));
		$this->assertSame(TDisplayStyle::Hidden, $restored->getDisplayStyle());
	}

	public function testSubclassSerializationAllOtherFieldsRoundTrip()
	{
		// Prove that the subclass override doesn't interfere with field serialization.
		$style = new TStyleNoneDefault();
		$style->setDisplayStyle(TDisplayStyle::Fixed); // non-default → serialised
		$style->setWidth('100px');
		$style->setCssClass('card');
		$style->setCustomStyle('margin:0');
		$style->getFont()->setBold(true);

		$restored = unserialize(serialize($style));

		$this->assertSame(TDisplayStyle::Fixed, $restored->getDisplayStyle());
		$this->assertSame('100px', $restored->getWidth());
		$this->assertSame('card', $restored->getCssClass());
		$this->assertSame('margin:0', $restored->getCustomStyle());
		$this->assertTrue($restored->getFont()->getBold());
	}

	// ---- constructor with source arg ----

	public function testSubclassConstructorWithSourceArgDoesNotOverwriteDisplayStyle()
	{
		// copyFrom (called from __construct) must not copy _displayStyle.
		// The subclass default set by the constructor must survive.
		$source = new TStyle();
		$source->setDisplayStyle(TDisplayStyle::Hidden);
		$source->setWidth('50px');

		$style = new TStyleNoneDefault($source);

		$this->assertSame(TDisplayStyle::None, $style->getDisplayStyle(),
			'copyFrom must not overwrite _displayStyle; subclass default must be preserved');
		$this->assertSame('50px', $style->getWidth(), 'non-display fields must be copied');
	}

	public function testSubclassConstructorOrderSetsThenCopies()
	{
		// Constructor: (1) init _displayStyle = static::DEFAULT_DISPLAY_STYLE
		//              (2) copyFrom($source) — adds fields but never touches _displayStyle
		// Result: subclass default wins regardless of source's display state.
		$source = new TStyle();
		$source->setDisplayStyle(TDisplayStyle::None);

		$style = new TStyleDynamicDefault($source);

		$this->assertSame(TDisplayStyle::Dynamic, $style->getDisplayStyle());
	}
}
