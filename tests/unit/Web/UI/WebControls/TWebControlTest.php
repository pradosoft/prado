<?php

use Prado\Web\UI\WebControls\TWebControl;
use Prado\Web\UI\WebControls\TWebControlDecorator;
use Prado\Web\UI\WebControls\TWebInputMode;
use Prado\Web\UI\WebControls\TEnterKeyHint;
use Prado\Web\UI\WebControls\TDisplayStyle;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TInvalidDataValueException;
use PHPUnit\Framework\TestCase;

class TWebControlSubclass extends TWebControl
{
	public function getTagName(): string
	{
		return parent::getTagName();
	}
}

class TWebControlTest extends TestCase
{
	use TWebControlRenderTrait;

	// ================================================================================
	// Basic Simple Properties Tests
	// ================================================================================

	public function testRenderUsesTagName()
	{
		$control = new TWebControl();
		$output = $this->render($control);
		$this->assertStringStartsWith('<span', $output);
		$this->assertStringEndsWith('</span>', $output);
	}

	public function testTagNameIsSpanViaSubclass()
	{
		$control = new TWebControlSubclass();
		$this->assertEquals('span', $control->getTagName());
	}

	public function testTagNameRenderedAsSpan()
	{
		$control = new TWebControl();
		$output = $this->renderBeginTag($control);
		$this->assertStringStartsWith('<span', $output);
	}

	public function testSubclassCanOverrideTagName()
	{
		$control = new class extends TWebControl {
			public function getTagName(): string
			{
				return 'div';
			}
		};
		$output = $this->render($control);
		$this->assertStringStartsWith('<div', $output);
		$this->assertStringEndsWith('</div>', $output);
	}

	public function testEnsureIdLogic()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getEnsureId());

		$control->setEnsureId('true');
		$this->assertTrue($control->getEnsureId());

		$control->setEnsureId(false);
		$this->assertTrue($control->getEnsureId(), 'EnsureId is sticky once set true');
	}

	public function testEnsureIdRenderWithId()
	{
		$control = new TWebControl();
		$control->setID('MyControl');
		$control->setEnsureId(true);
		$output = $this->renderBeginTag($control);
		$this->assertStringContainsString('id="MyControl"', $output);
	}

	public function testEnsureIdRenderWithExplicitId()
	{
		$control = new TWebControl();
		$control->setID('explicitId');
		$control->setEnsureId(true);
		$output = $this->renderBeginTag($control);
		$this->assertStringContainsString('id="explicitId"', $output);
	}

	public function testDecoratorLazyCreation()
	{
		$control = new TWebControl();
		$this->assertNull($control->getDecorator(false));

		$decorator = $control->getDecorator(true);
		$this->assertInstanceOf(TWebControlDecorator::class, $decorator);
		$this->assertSame($decorator, $control->getDecorator(false));
	}

	public function testDecoratorSameInstance()
	{
		$control = new TWebControl();
		$d1 = $control->getDecorator();
		$d2 = $control->getDecorator();
		$this->assertSame($d1, $d2);
	}

	public function testDecoratorPreTagText()
	{
		$control = new TWebControl();
		$decorator = $control->getDecorator();
		$decorator->setPreTagText('<div>');
		$decorator->setPostTagText('</div>');

		$output = $this->render($control);
		$this->assertStringContainsString('<div><span></span></div>', $output);
	}

	public function testDecoratorPreContentsText()
	{
		$control = new TWebControl();
		$decorator = $control->getDecorator();
		$decorator->setPreContentsText('<span>');
		$decorator->setPostContentsText('</span>');

		$output = $this->render($control);
		$this->assertStringContainsString('<span></span>', $output);
	}

	public function testCopyBaseAttributes()
	{
		$source = new TWebControl();
		$source->setAccessKey('a');
		$source->setToolTip('tooltip');
		$source->setTabIndex(5);
		$source->setEnabled(false);
		$source->getAttributes()->add('data-x', 'y');

		$target = new TWebControl();
		$target->copyBaseAttributes($source);

		$this->assertEquals('a', $target->getAccessKey());
		$this->assertEquals('tooltip', $target->getToolTip());
		$this->assertEquals(5, $target->getTabIndex());
		$this->assertFalse($target->getEnabled());
		$this->assertEquals('y', $target->getAttributes()->itemAt('data-x'));
	}

	public function testCopyBaseAttributesOnlyCopiesEnabledWhenSourceDisabled()
	{
		$source = new TWebControl();
		$source->setEnabled(false);

		$target = new TWebControl();
		$target->setEnabled(true);
		$target->copyBaseAttributes($source);
		$this->assertFalse($target->getEnabled());
	}

	public function testCopyBaseAttributesWithAttributes()
	{
		$source = new TWebControl();
		$source->getAttributes()->add('attr1', 'value1');
		$source->getAttributes()->add('attr2', 'value2');

		$target = new TWebControl();
		$target->copyBaseAttributes($source);

		$this->assertEquals('value1', $target->getAttributes()->itemAt('attr1'));
		$this->assertEquals('value2', $target->getAttributes()->itemAt('attr2'));
	}

	public function testCopyBaseAttributesHtml5Attributes()
	{
		$source = new TWebControl();
		$source->setLang('fr');
		$source->setDir('rtl');
		$source->setHidden(true);
		$source->setSpellCheck(false);
		$source->setDraggable(true);
		$source->setInert(true);
		$source->setPopover(true);
		$source->setContentEditable('plaintext-only');
		$source->setInputMode(TWebInputMode::Email);
		$source->setEnterKeyHint(TEnterKeyHint::Done);
		$source->setTranslate('no');
		$source->setRole('banner');
		$source->getAria()->add('label', 'nav');
		$source->getDataset()->add('id', '42');

		$target = new TWebControl();
		$target->copyBaseAttributes($source);

		$this->assertEquals('fr', $target->getLang());
		$this->assertEquals('rtl', $target->getDir());
		$this->assertTrue($target->getHidden());
		$this->assertFalse($target->getSpellCheck());
		$this->assertTrue($target->getDraggable());
		$this->assertTrue($target->getInert());
		$this->assertTrue($target->getPopover());
		$this->assertEquals('plaintext-only', $target->getContentEditable());
		$this->assertEquals(TWebInputMode::Email, $target->getInputMode());
		$this->assertEquals(TEnterKeyHint::Done, $target->getEnterKeyHint());
		$this->assertEquals('no', $target->getTranslate());
		$this->assertEquals('banner', $target->getRole());
		$this->assertEquals('nav', $target->getAria()->itemAt('label'));
		$this->assertEquals('42', $target->getDataset()->itemAt('id'));
	}

	public function testCopyBaseAttributesHiddenFalseNotCopied()
	{
		$source = new TWebControl();
		$source->setHidden(false);

		$target = new TWebControl();
		$target->setHidden(true);
		$target->copyBaseAttributes($source);

		$this->assertFalse($target->getHidden());
	}

	// AccessKey Tests

	public function testAccessKeyValid()
	{
		$control = new TWebControl();
		$control->setAccessKey('z');
		$this->assertEquals('z', $control->getAccessKey());
		$this->assertStringContainsString('accesskey="z"', $this->render($control));
	}

	public function testAccessKeyEmptyString()
	{
		$control = new TWebControl();
		$control->setAccessKey('');
		$this->assertEquals('', $control->getAccessKey());
		$this->assertStringNotContainsString('accesskey', $this->render($control));
	}

	public function testAccessKeyNull()
	{
		$control = new TWebControl();
		$control->setAccessKey(null);
		$this->assertEquals('', $control->getAccessKey());
	}

	public function testAccessKeyTooLongThrows()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setAccessKey('ab');
	}

	public function testAccessKeySingleCharacter()
	{
		$control = new TWebControl();
		$control->setAccessKey('A');
		$this->assertEquals('A', $control->getAccessKey());
	}

	// Style Property Tests

	public function testStyleGettersDelegatesToStyle()
	{
		$control = new TWebControl();

		$control->setBackColor('#000');
		$this->assertEquals('#000', $control->getBackColor());

		$control->setForeColor('red');
		$this->assertEquals('red', $control->getForeColor());

		$control->setBorderColor('blue');
		$this->assertEquals('blue', $control->getBorderColor());

		$control->setBorderStyle('solid');
		$this->assertEquals('solid', $control->getBorderStyle());

		$control->setBorderWidth('1px');
		$this->assertEquals('1px', $control->getBorderWidth());

		$control->setWidth('100px');
		$this->assertEquals('100px', $control->getWidth());

		$control->setHeight('50px');
		$this->assertEquals('50px', $control->getHeight());
	}

	public function testStyleEmptyWhenNoStyleSet()
	{
		$control = new TWebControl();
		$this->assertEquals('', $control->getBackColor());
		$this->assertEquals('', $control->getForeColor());
		$this->assertEquals('', $control->getBorderColor());
		$this->assertEquals('', $control->getBorderStyle());
		$this->assertEquals('', $control->getBorderWidth());
		$this->assertEquals('', $control->getWidth());
		$this->assertEquals('', $control->getHeight());
		$this->assertEquals('', $control->getCssClass());
		$this->assertFalse($control->getHasStyle());
	}

	public function testFontGetterCreatesStyleFont()
	{
		$control = new TWebControl();
		$font = $control->getFont();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TFont::class, $font);
	}

	public function testCssClass()
	{
		$control = new TWebControl();
		$control->setCssClass('class-a class-b');
		$this->assertEquals('class-a class-b', $control->getCssClass());
		$this->assertStringContainsString('class="class-a class-b"', $this->render($control));
	}

	public function testCssClassTrimming()
	{
		$control = new TWebControl();
		$control->setCssClass('  class-a  ');
		$this->assertEquals('class-a', $control->getCssClass());
	}

	public function testDisplayStyleNone()
	{
		$control = new TWebControl();
		$control->setDisplay(TDisplayStyle::None);
		$this->assertStringContainsString('display:none', $this->render($control));
	}

	public function testDisplayStyleFixed()
	{
		$control = new TWebControl();
		$control->setDisplay(TDisplayStyle::Fixed);
		$this->assertStringContainsString('visibility:visible', $this->render($control));
	}

	public function testDisplayStyleHidden()
	{
		$control = new TWebControl();
		$control->setDisplay(TDisplayStyle::Hidden);
		$this->assertStringContainsString('visibility:hidden', $this->render($control));
	}

	public function testDisplayStyleDynamic()
	{
		$control = new TWebControl();
		$control->setDisplay(TDisplayStyle::Dynamic);
		$this->assertStringNotContainsString('style=', $this->render($control));
	}

	public function testClearStyle()
	{
		$control = new TWebControl();
		$control->setBackColor('red');
		$this->assertTrue($control->getHasStyle());

		$control->clearStyle();
		$this->assertFalse($control->getHasStyle());
		$this->assertEquals('', $control->getBackColor());
		$this->assertStringNotContainsString('style=', $this->render($control));
	}

	public function testSetStyleString()
	{
		$control = new TWebControl();
		$control->setStyle('color: red; background-color: blue');
		$output = $this->render($control);
		$this->assertStringContainsString('color:red', $output);
		$this->assertStringContainsString('background-color:blue', $output);
	}

	public function testSetStyleInvalidThrows()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setStyle(123);
	}

	public function testSetStyleWithExtraSpaces()
	{
		$control = new TWebControl();
		$control->setStyle('color: red;  padding: 10px  ');
		$output = $this->render($control);
		$this->assertStringContainsString('color:red', $output);
	}

	public function testCreateStyle()
	{
		$control = new TWebControl();
		$style = $control->getStyle();
		$this->assertInstanceOf(TStyle::class, $style);
	}

	// TabIndex Tests

	public function testTabIndexDefaultsToNull()
	{
		$control = new TWebControl();
		$this->assertNull($control->getTabIndex());
	}

	public function testTabIndexSetInteger()
	{
		$control = new TWebControl();
		$control->setTabIndex(5);
		$this->assertEquals(5, $control->getTabIndex());
		$this->assertStringContainsString('tabindex="5"', $this->render($control));
	}

	public function testTabIndexSetNullClears()
	{
		$control = new TWebControl();
		$control->setTabIndex(5);
		$control->setTabIndex(null);
		$this->assertNull($control->getTabIndex());
		$this->assertStringNotContainsString('tabindex', $this->render($control));
	}

	public function testTabIndexSetEmptyStringClears()
	{
		$control = new TWebControl();
		$control->setTabIndex(3);
		$control->setTabIndex('');
		$this->assertNull($control->getTabIndex());
	}

	public function testTabIndexStringToInteger()
	{
		$control = new TWebControl();
		$control->setTabIndex('7');
		$this->assertEquals(7, $control->getTabIndex());
	}

	public function testTabIndexZero()
	{
		$control = new TWebControl();
		$control->setTabIndex(0);
		$this->assertEquals(0, $control->getTabIndex());
		$this->assertStringContainsString('tabindex="0"', $this->render($control));
	}

	public function testTabIndexNegative()
	{
		$control = new TWebControl();
		$control->setTabIndex(-1);
		$this->assertEquals(-1, $control->getTabIndex());
		$this->assertStringContainsString('tabindex="-1"', $this->render($control));
	}

	// ToolTip Tests

	public function testToolTip()
	{
		$control = new TWebControl();
		$control->setToolTip('my tooltip');
		$this->assertEquals('my tooltip', $control->getToolTip());
		$this->assertStringContainsString('title="my tooltip"', $this->render($control));
	}

	public function testToolTipEmptyString()
	{
		$control = new TWebControl();
		$control->setToolTip('');
		$this->assertEquals('', $control->getToolTip());
		$this->assertStringNotContainsString('title', $this->render($control));
	}

	public function testToolTipNull()
	{
		$control = new TWebControl();
		$control->setToolTip(null);
		$this->assertEquals('', $control->getToolTip());
	}

	// ================================================================================
	// Complex Functionality Tests
	// ================================================================================

	public function testLangGetSet()
	{
		$control = new TWebControl();
		$control->setLang('en-US');
		$this->assertEquals('en-US', $control->getLang());
	}

	public function testLangRender()
	{
		$control = new TWebControl();
		$control->setLang('en');
		$this->assertStringContainsString('lang="en"', $this->render($control));
	}

	public function testLangTrimmed()
	{
		$control = new TWebControl();
		$control->setLang('  en  ');
		$this->assertEquals('en', $control->getLang());
	}

	public function testLangEmpty()
	{
		$control = new TWebControl();
		$control->setLang('');
		$this->assertEquals('', $control->getLang());
		$this->assertStringNotContainsString('lang=', $this->render($control));
	}

	public function testDirValidValues()
	{
		$control = new TWebControl();

		$control->setDir('ltr');
		$this->assertEquals('ltr', $control->getDir());
		$this->assertStringContainsString('dir="ltr"', $this->render($control));

		$control = new TWebControl();
		$control->setDir('rtl');
		$this->assertEquals('rtl', $control->getDir());
		$this->assertStringContainsString('dir="rtl"', $this->render($control));

		$control = new TWebControl();
		$control->setDir('auto');
		$this->assertEquals('auto', $control->getDir());
		$this->assertStringContainsString('dir="auto"', $this->render($control));
	}

	public function testDirCaseInsensitive()
	{
		$control = new TWebControl();
		$control->setDir('RTL');
		$this->assertEquals('rtl', $control->getDir());

		$control = new TWebControl();
		$control->setDir('LTR');
		$this->assertEquals('ltr', $control->getDir());
	}

	public function testDirEmptyString()
	{
		$control = new TWebControl();
		$control->setDir('');
		$this->assertEquals('', $control->getDir());
		$this->assertStringNotContainsString('dir=', $this->render($control));
	}

	public function testDirInvalidThrows()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setDir('invalid');
	}

	public function testDirInvalidWithWhitespace()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setDir('  invalid  ');
	}

	public function testHiddenGetSet()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getHidden());

		$control->setHidden(true);
		$this->assertTrue($control->getHidden());

		$control->setHidden(false);
		$this->assertFalse($control->getHidden());
	}

	public function testHiddenRender()
	{
		$control = new TWebControl();
		$control->setHidden(true);
		$this->assertStringContainsString('hidden', $this->render($control));
	}

	public function testHiddenFalseNotRendered()
	{
		$control = new TWebControl();
		$control->setHidden(false);
		$this->assertStringNotContainsString('hidden', $this->render($control));
	}

	public function testHiddenStringConversion()
	{
		$control = new TWebControl();
		$control->setHidden('true');
		$this->assertTrue($control->getHidden());
	}

	public function testSpellCheckGetSet()
	{
		$control = new TWebControl();
		$this->assertNull($control->getSpellCheck());

		$control->setSpellCheck(true);
		$this->assertTrue($control->getSpellCheck());

		$control->setSpellCheck(false);
		$this->assertFalse($control->getSpellCheck());

		$control->setSpellCheck(null);
		$this->assertNull($control->getSpellCheck());
	}

	public function testSpellCheckRenderTrue()
	{
		$control = new TWebControl();
		$control->setSpellCheck(true);
		$this->assertStringContainsString('spellcheck="true"', $this->render($control));
	}

	public function testSpellCheckRenderFalse()
	{
		$control = new TWebControl();
		$control->setSpellCheck(false);
		$this->assertStringContainsString('spellcheck="false"', $this->render($control));
	}

	public function testSpellCheckNullNotRendered()
	{
		$control = new TWebControl();
		$control->setSpellCheck(null);
		$this->assertStringNotContainsString('spellcheck', $this->render($control));
	}

	public function testSpellCheckEmptyStringResets()
	{
		$control = new TWebControl();
		$control->setSpellCheck(true);
		$control->setSpellCheck('');
		$this->assertNull($control->getSpellCheck());
	}

	public function testDraggableGetSet()
	{
		$control = new TWebControl();
		$this->assertNull($control->getDraggable());

		$control->setDraggable(true);
		$this->assertTrue($control->getDraggable());

		$control->setDraggable(false);
		$this->assertFalse($control->getDraggable());

		$control->setDraggable(null);
		$this->assertNull($control->getDraggable());
	}

	public function testDraggableRenderTrue()
	{
		$control = new TWebControl();
		$control->setDraggable(true);
		$this->assertStringContainsString('draggable="true"', $this->render($control));
	}

	public function testDraggableRenderFalse()
	{
		$control = new TWebControl();
		$control->setDraggable(false);
		$this->assertStringContainsString('draggable="false"', $this->render($control));
	}

	public function testDraggableNullNotRendered()
	{
		$control = new TWebControl();
		$control->setDraggable(null);
		$this->assertStringNotContainsString('draggable', $this->render($control));
	}

	public function testInertGetSet()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getInert());

		$control->setInert(true);
		$this->assertTrue($control->getInert());

		$control->setInert(false);
		$this->assertFalse($control->getInert());
	}

	public function testInertRender()
	{
		$control = new TWebControl();
		$control->setInert(true);
		$this->assertStringContainsString('inert', $this->render($control));
	}

	public function testInertFalseNotRendered()
	{
		$control = new TWebControl();
		$control->setInert(false);
		$this->assertStringNotContainsString('inert', $this->render($control));
	}

	public function testPopoverGetSet()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getPopover());

		$control->setPopover(true);
		$this->assertTrue($control->getPopover());

		$control->setPopover(false);
		$this->assertFalse($control->getPopover());
	}

	public function testPopoverRender()
	{
		$control = new TWebControl();
		$control->setPopover(true);
		$this->assertStringContainsString('popover', $this->render($control));
	}

	public function testPopoverFalseNotRendered()
	{
		$control = new TWebControl();
		$control->setPopover(false);
		$this->assertStringNotContainsString('popover', $this->render($control));
	}

	// ContentEditable Tests

	public function testContentEditableTrue()
	{
		$control = new TWebControl();
		$control->setContentEditable(true);
		$this->assertTrue($control->getContentEditable());
		$this->assertStringContainsString('contenteditable="true"', $this->render($control));
	}

	public function testContentEditableFalse()
	{
		$control = new TWebControl();
		$control->setContentEditable(false);
		$this->assertFalse($control->getContentEditable());
		$this->assertStringContainsString('contenteditable="false"', $this->render($control));
	}

	public function testContentEditablePlaintextOnly()
	{
		$control = new TWebControl();
		$control->setContentEditable('plaintext-only');
		$this->assertEquals('plaintext-only', $control->getContentEditable());
		$this->assertStringContainsString('contenteditable="plaintext-only"', $this->render($control));
	}

	public function testContentEditablePlaintextOnlyCaseInsensitive()
	{
		$control = new TWebControl();
		$control->setContentEditable('PLAINTEXT-ONLY');
		$this->assertEquals('plaintext-only', $control->getContentEditable());
	}

	public function testContentEditablePlaintextOnlyWithWhitespace()
	{
		$control = new TWebControl();
		$control->setContentEditable('  plaintext-only  ');
		$this->assertEquals('plaintext-only', $control->getContentEditable());
	}

	public function testContentEditableNull()
	{
		$control = new TWebControl();
		$control->setContentEditable(null);
		$this->assertNull($control->getContentEditable());
		$this->assertStringNotContainsString('contenteditable', $this->render($control));
	}

	public function testContentEditableEmptyString()
	{
		$control = new TWebControl();
		$control->setContentEditable('');
		$this->assertNull($control->getContentEditable());
		$this->assertStringNotContainsString('contenteditable', $this->render($control));
	}

	// InputMode Tests

	public function testInputModeValidValues()
	{
		$control = new TWebControl();

		$control->setInputMode(TWebInputMode::None);
		$this->assertEquals(TWebInputMode::None, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Text);
		$this->assertEquals(TWebInputMode::Text, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Numeric);
		$this->assertEquals(TWebInputMode::Numeric, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Decimal);
		$this->assertEquals(TWebInputMode::Decimal, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Tel);
		$this->assertEquals(TWebInputMode::Tel, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Email);
		$this->assertEquals(TWebInputMode::Email, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Url);
		$this->assertEquals(TWebInputMode::Url, $control->getInputMode());

		$control->setInputMode(TWebInputMode::Search);
		$this->assertEquals(TWebInputMode::Search, $control->getInputMode());
	}

	public function testInputModeRender()
	{
		$control = new TWebControl();
		$control->setInputMode(TWebInputMode::Email);
		$this->assertStringContainsString('inputmode="email"', $this->render($control));
	}

	public function testInputModeInvalidThrows()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setInputMode('InvalidMode');
	}

	public function testInputModeInvalidString()
	{
		$control = new TWebControl();
		try {
			$control->setInputMode('notavalidentry');
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertStringContainsString('notavalidentry', $e->getMessage());
		}
	}

	// EnterKeyHint Tests

	public function testEnterKeyHintValidValues()
	{
		$control = new TWebControl();

		$control->setEnterKeyHint(TEnterKeyHint::Done);
		$this->assertEquals(TEnterKeyHint::Done, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Enter);
		$this->assertEquals(TEnterKeyHint::Enter, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Go);
		$this->assertEquals(TEnterKeyHint::Go, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Next);
		$this->assertEquals(TEnterKeyHint::Next, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Previous);
		$this->assertEquals(TEnterKeyHint::Previous, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Search);
		$this->assertEquals(TEnterKeyHint::Search, $control->getEnterKeyHint());

		$control->setEnterKeyHint(TEnterKeyHint::Send);
		$this->assertEquals(TEnterKeyHint::Send, $control->getEnterKeyHint());
	}

	public function testEnterKeyHintRender()
	{
		$control = new TWebControl();
		$control->setEnterKeyHint(TEnterKeyHint::Search);
		$this->assertStringContainsString('enterkeyhint="search"', $this->render($control));
	}

	public function testEnterKeyHintInvalidThrows()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setEnterKeyHint('InvalidHint');
	}

	public function testEnterKeyHintInvalidString()
	{
		$control = new TWebControl();
		try {
			$control->setEnterKeyHint('notavalidhint');
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertStringContainsString('notavalidhint', $e->getMessage());
		}
	}

	// Translate Tests

	public function testTranslateYesValues()
	{
		$control = new TWebControl();
		foreach ([true, 'true', 'yes', 'YES', 1, '1'] as $val) {
			$control->setTranslate($val);
			$this->assertEquals('yes', $control->getTranslate());
		}
	}

	public function testTranslateNoValues()
	{
		$control = new TWebControl();
		foreach ([false, 'false', 'no', 'NO', 0, '0'] as $val) {
			$control->setTranslate($val);
			$this->assertEquals('no', $control->getTranslate());
		}
	}

	public function testTranslateNull()
	{
		$control = new TWebControl();
		$control->setTranslate(null);
		$this->assertNull($control->getTranslate());
	}

	public function testTranslateRenderYes()
	{
		$control = new TWebControl();
		$control->setTranslate(true);
		$this->assertStringContainsString('translate="yes"', $this->render($control));
	}

	public function testTranslateRenderNo()
	{
		$control = new TWebControl();
		$control->setTranslate(false);
		$this->assertStringContainsString('translate="no"', $this->render($control));
	}

	public function testTranslateStringYesNo()
	{
		$control = new TWebControl();
		$control->setTranslate('yes');
		$this->assertEquals('yes', $control->getTranslate());

		$control->setTranslate('no');
		$this->assertEquals('no', $control->getTranslate());
	}

	// ARIA Tests

	public function testHasAriaFalseWhenEmpty()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getHasAria());
	}

	public function testHasAriaTrueWhenHasAttributes()
	{
		$control = new TWebControl();
		$control->getAria()->add('label', 'test');
		$this->assertTrue($control->getHasAria());
	}

	public function testAriaRoleGetSet()
	{
		$control = new TWebControl();
		$this->assertNull($control->getRole());

		$control->setRole('alert');
		$this->assertEquals('alert', $control->getRole());
	}

	public function testAriaRoleRender()
	{
		$control = new TWebControl();
		$control->setRole('button');
		$this->assertStringContainsString('role="button"', $this->render($control));
	}

	public function testAriaRoleNullClears()
	{
		$control = new TWebControl();
		$control->setRole('alert');
		$control->setRole(null);
		$this->assertNull($control->getRole());
		$this->assertStringNotContainsString('role=', $this->render($control));
	}

	public function testAriaRoleEmptyStringClearsRole()
	{
		$control = new TWebControl();
		$control->setRole('alert');
		$control->setRole('');
		$this->assertNull($control->getRole());
		$this->assertStringNotContainsString('role=', $this->render($control));
	}

	public function testAriaRoleTrimmed()
	{
		$control = new TWebControl();
		$control->setRole('  button  ');
		$this->assertEquals('button', $control->getRole());
	}

	public function testAriaAttributesRender()
	{
		$control = new TWebControl();
		$control->getAria()->add('label', 'Close');
		$control->getAria()->add('describedby', 'desc1');

		$output = $this->render($control);
		$this->assertStringContainsString('aria-label="Close"', $output);
		$this->assertStringContainsString('aria-describedby="desc1"', $output);
	}

	public function testAriaAttributesRoleSpecialCase()
	{
		$control = new TWebControl();
		$control->getAria()->add('role', 'alert');

		$output = $this->render($control);
		$this->assertStringContainsString('role="alert"', $output);
	}

	public function testAriaAttributesCaseNormalization()
	{
		$control = new TWebControl();
		$control->getAria()->add('label', 'test');

		$output = $this->render($control);
		$this->assertStringContainsString('aria-label="test"', $output);
	}

	// Data Attributes Tests

	public function testHasDataFalseWhenEmpty()
	{
		$control = new TWebControl();
		$this->assertFalse($control->getHasDataset());
	}

	public function testHasDataTrueWhenHasAttributes()
	{
		$control = new TWebControl();
		$control->getDataset()->add('id', '5');
		$this->assertTrue($control->getHasDataset());
	}

	public function testDataAttributesRender()
	{
		$control = new TWebControl();
		$control->getDataset()->add('user-id', '5');

		$output = $this->render($control);
		$this->assertStringContainsString('data-user-id="5"', $output);
	}

	public function testDataAttributesCaseNormalization()
	{
		$control = new TWebControl();
		$control->getDataset()->add('USER-ID', '5');

		$output = $this->render($control);
		$this->assertStringContainsString('data-user-id="5"', $output);
	}

	public function testDataAttributesAlreadyPrefixed()
	{
		$control = new TWebControl();
		$control->getDataset()->add('data-prefixed', 'true');

		$output = $this->render($control);
		$this->assertStringContainsString('data-prefixed="true"', $output);
		$this->assertStringNotContainsString('data-data-prefixed', $output);
	}

	// Rendering Tests

	public function testRenderBeginTag()
	{
		$control = new TWebControl();
		$control->setID('test');
		$output = $this->renderBeginTag($control);
		$this->assertEquals('<span id="test">', $output);
	}

	public function testRenderBeginTagWithAttributes()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->setAccessKey('a');
		$output = $this->renderBeginTag($control);
		$this->assertEquals('<span id="test" accesskey="a">', $output);
	}

	public function testRenderContents()
	{
		$control = new TWebControl();
		$control->getControls()->add('Hello');
		$output = $this->renderContents($control);
		$this->assertEquals('Hello', $output);
	}

	public function testRenderEndTagRequiresBeginTag()
	{
		$control = new TWebControl();
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$writer->renderBeginTag('span');
		$writer->renderEndTag();
		$this->assertEquals('<span></span>', $tw->flush());
	}

	public function testRenderEndTagWithDecorator()
	{
		$control = new TWebControl();
		$decorator = $control->getDecorator();
		$decorator->setPreTagText('<div>');
		$decorator->setPostTagText('</div>');
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderBeginTag($writer);
		$control->renderEndTag($writer);
		$output = $tw->flush();
		$this->assertStringContainsString('<div>', $output);
		$this->assertStringContainsString('</div>', $output);
	}

	public function testRenderFull()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->getControls()->add('Content');
		$output = $this->render($control);
		$this->assertEquals('<span id="test">Content</span>', $output);
	}

	public function testRenderWithStyle()
	{
		$control = new TWebControl();
		$control->setBackColor('red');
		$output = $this->render($control);
		$this->assertStringContainsString('style=', $output);
		$this->assertStringContainsString('background-color:red', $output);
	}

	public function testRenderWithAttributes()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->getAttributes()->add('data-x', 'y');
		$output = $this->render($control);
		$this->assertStringContainsString('data-x="y"', $output);
	}

	public function testRenderWithDisabled()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->setEnabled(false);
		$output = $this->render($control);
		$this->assertStringContainsString('disabled="disabled"', $output);
	}

	public function testRenderNoIdWithoutEnsureId()
	{
		$control = new TWebControl();
		$output = $this->renderBeginTag($control);
		$this->assertStringNotContainsString('id=', $output);
	}

	public function testRenderWithID()
	{
		$control = new TWebControl();
		$control->setID('testId');
		$output = $this->renderBeginTag($control);
		$this->assertStringContainsString('id="testId"', $output);
	}

	// Combined Attribute Tests

	public function testMultipleHtml5Attributes()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->setLang('en');
		$control->setDir('ltr');
		$control->setHidden(false);
		$control->setSpellCheck(true);
		$control->setDraggable(true);
		$control->setContentEditable(true);

		$output = $this->render($control);
		$this->assertStringContainsString('lang="en"', $output);
		$this->assertStringContainsString('dir="ltr"', $output);
		$this->assertStringContainsString('spellcheck="true"', $output);
		$this->assertStringContainsString('draggable="true"', $output);
		$this->assertStringContainsString('contenteditable="true"', $output);
	}

	// Style Combined Tests

	public function testAllStylePropertiesCombined()
	{
		$control = new TWebControl();
		$control->setBackColor('#fff');
		$control->setForeColor('#000');
		$control->setBorderColor('black');
		$control->setBorderStyle('solid');
		$control->setBorderWidth('1px');
		$control->setWidth('100%');
		$control->setHeight('200px');
		$control->setCssClass('my-class');

		$output = $this->render($control);
		$this->assertStringContainsString('background-color:#fff', $output);
		$this->assertStringContainsString('color:#000', $output);
		$this->assertStringContainsString('border-color:black', $output);
		$this->assertStringContainsString('border-style:solid', $output);
		$this->assertStringContainsString('border-width:1px', $output);
		$this->assertStringContainsString('width:100%', $output);
		$this->assertStringContainsString('height:200px', $output);
		$this->assertStringContainsString('class="my-class"', $output);
	}

	// Enabled tests

	public function testEnabledAffectsRender()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->setEnabled(false);

		$output = $this->render($control);
		$this->assertStringContainsString('disabled="disabled"', $output);
	}

	public function testEnabledTrueDoesNotRender()
	{
		$control = new TWebControl();
		$control->setID('test');
		$control->setEnabled(true);

		$output = $this->render($control);
		$this->assertStringNotContainsString('disabled', $output);
	}
}