<?php

use Prado\IO\ITextWriter;
use Prado\IO\TTextWriter;
use Prado\Web\THttpUtility;
use Prado\Web\UI\THtmlWriter;
use PHPUnit\Framework\TestCase;

class TestWriter implements ITextWriter
{
	private $_str = '';
	private $_flushedContent = '';

	public function flush(): string
	{
		$this->_flushedContent = $this->_str;
		$this->_str = '';
		return $this->_flushedContent;
	}

	public function write($str): void
	{
		$this->_str .= $str;
	}

	public function writeLine($str = ''): void
	{
		$this->write($str . "\n");
	}

	public function getFlushedContent(): string
	{
		return $this->_flushedContent;
	}

	public function getStr(): string
	{
		return $this->_str;
	}
}

class THtmlWriterTest extends TestCase
{
	private TestWriter $writer;

	protected function setUp(): void
	{
		$this->writer = new TestWriter();
	}

	// ================================================================================
	// Constructor and Writer Management
	// ================================================================================

	public function testConstructorWithExplicitWriter()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($this->writer, $htmlWriter->getWriter());
	}

	public function testConstructorTrueCreatesInternalTTextWriter()
	{
		$htmlWriter = new THtmlWriter(true);
		$this->assertInstanceOf(TTextWriter::class, $htmlWriter->getWriter());
	}

	public function testConstructorDefaultArgEqualsTrue()
	{
		$htmlWriter = new THtmlWriter();
		$this->assertInstanceOf(TTextWriter::class, $htmlWriter->getWriter());
	}

	public function testConstructorNullSetsNoWriter()
	{
		$htmlWriter = new THtmlWriter(null);
		$this->assertNull($htmlWriter->getWriter());
	}

	public function testConstructorTrueWriterIsUsable()
	{
		$htmlWriter = new THtmlWriter(true);
		$htmlWriter->write('hello');
		$this->assertSame('hello', $htmlWriter->flush());
	}

	public function testSetWriter()
	{
		$htmlWriter = new THtmlWriter(null);
		$this->assertNull($htmlWriter->getWriter());

		$htmlWriter->setWriter($this->writer);
		$this->assertSame($this->writer, $htmlWriter->getWriter());
	}

	public function testSetWriterReturnsThis()
	{
		$htmlWriter = new THtmlWriter(null);
		$this->assertSame($htmlWriter, $htmlWriter->setWriter($this->writer));
	}

	public function testSetWriterReplacesExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$other = new TestWriter();
		$htmlWriter->setWriter($other);
		$this->assertSame($other, $htmlWriter->getWriter());
	}

	// ================================================================================
	// Void Elements Static Methods
	// ================================================================================

	public function testGetVoidElementsReturnsArray()
	{
		$this->assertIsArray(THtmlWriter::getVoidElements());
	}

	public function testGetVoidElementsContainsHtml5Elements()
	{
		$voidElements = THtmlWriter::getVoidElements();
		foreach (['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'] as $el) {
			$this->assertContains($el, $voidElements, "'$el' must be in void elements");
		}
	}

	public function testGetVoidElementsTotalCount()
	{
		$this->assertCount(13, THtmlWriter::getVoidElements());
	}

	public function testIsVoidElementHtml5VoidElements()
	{
		foreach (['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'] as $el) {
			$this->assertTrue(THtmlWriter::isVoidElement($el), "'$el' must be void");
		}
	}

	public function testIsVoidElementLegacyElementsRemoved()
	{
		foreach (['basefont', 'bgsound', 'frame', 'isindex'] as $el) {
			$this->assertFalse(THtmlWriter::isVoidElement($el), "Legacy '$el' is no longer a void element");
		}
	}

	public function testIsVoidElementNonVoidElements()
	{
		foreach (['div', 'span', 'a', 'p', 'table', 'form', 'button', 'script', 'style'] as $el) {
			$this->assertFalse(THtmlWriter::isVoidElement($el), "'$el' must not be void");
		}
	}

	public function testIsVoidElementCaseInsensitive()
	{
		$this->assertTrue(THtmlWriter::isVoidElement('BR'));
		$this->assertTrue(THtmlWriter::isVoidElement('Img'));
		$this->assertTrue(THtmlWriter::isVoidElement('INPUT'));
		$this->assertFalse(THtmlWriter::isVoidElement('DIV'));
	}

	public function testIsVoidElementUnknownReturnsFalse()
	{
		$this->assertFalse(THtmlWriter::isVoidElement('custom'));
		$this->assertFalse(THtmlWriter::isVoidElement('myelement'));
		$this->assertFalse(THtmlWriter::isVoidElement(''));
	}

	// ================================================================================
	// Attribute Methods
	// ================================================================================

	public function testAddAttributeSingle()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$this->assertSame('text', $this->getAttributes($htmlWriter)['type']);
	}

	public function testAddAttributeHtmlEncodesValue()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Rock & Roll');
		$this->assertSame(THttpUtility::htmlEncode('Rock & Roll'), $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributeDoubleQuoteEncoded()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Say "hi"');
		$this->assertSame('Say &quot;hi&quot;', $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributeAmpersandNotEncoded()
	{
		// htmlEncode only encodes <, >, "; & is left as-is
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'A&B');
		$this->assertSame('A&B', $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributeOverwritesExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->addAttribute('type', 'password');
		$this->assertSame('password', $this->getAttributes($htmlWriter)['type']);
	}

	public function testAddAttributeMultiple()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('name1', 'value1');
		$htmlWriter->addAttribute('name2', 'value2');
		$attrs = $this->getAttributes($htmlWriter);
		$this->assertSame('value1', $attrs['name1']);
		$this->assertSame('value2', $attrs['name2']);
	}

	public function testAddAttributeReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->addAttribute('type', 'text'));
	}

	public function testAddAttributesHtmlEncodesValues()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttributes(['name' => 'value', 'title' => 'A<B>C']);
		$this->assertSame('value', $this->getAttributes($htmlWriter)['name']);
		$this->assertSame(THttpUtility::htmlEncode('A<B>C'), $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributesOverwritesExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('class', 'old');
		$htmlWriter->addAttributes(['class' => 'new']);
		$this->assertSame('new', $this->getAttributes($htmlWriter)['class']);
	}

	public function testAddAttributesEmptyArrayNoOp()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('id', 'x');
		$htmlWriter->addAttributes([]);
		$this->assertCount(1, $this->getAttributes($htmlWriter));
	}

	public function testAddAttributesReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->addAttributes([]));
	}

	public function testRemoveAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->addAttribute('value', 'test');
		$htmlWriter->removeAttribute('type');

		$attrs = $this->getAttributes($htmlWriter);
		$this->assertArrayNotHasKey('type', $attrs);
		$this->assertArrayHasKey('value', $attrs);
	}

	public function testRemoveAttributeNonExistentNoThrow()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->removeAttribute('non-existent');
		$this->assertEmpty($this->getAttributes($htmlWriter));
	}

	public function testRemoveAttributeReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->removeAttribute('x'));
	}

	public function testAddAndRemoveAttributeNameNormalization()
	{
		// htmlStrip does not strip whitespace — '  type  ' and 'type' are distinct keys
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->removeAttribute('  type  ');   // different key → 'type' still present
		$this->assertArrayHasKey('type', $this->getAttributes($htmlWriter));
	}

	// ================================================================================
	// Style Attribute Methods
	// ================================================================================

	public function testAddStyleAttributeSingle()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$this->assertSame('1em', $this->getStyles($htmlWriter)['font-size']);
	}

	public function testAddStyleAttributeHtmlEncodesValue()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('background', 'url("img.png")');
		$this->assertSame('url(&quot;img.png&quot;)', $this->getStyles($htmlWriter)['background']);
	}

	public function testAddStyleAttributeAmpersandNotEncoded()
	{
		// htmlEncode leaves & untouched — same as attribute values
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-family', 'Arial & Helvetica');
		$this->assertSame('Arial & Helvetica', $this->getStyles($htmlWriter)['font-family']);
	}

	public function testAddStyleAttributeOverwritesExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttribute('color', 'blue');
		$this->assertSame('blue', $this->getStyles($htmlWriter)['color']);
	}

	public function testAddStyleAttributeMultiple()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$styles = $this->getStyles($htmlWriter);
		$this->assertSame('red', $styles['color']);
		$this->assertSame('1em', $styles['font-size']);
	}

	public function testAddStyleAttributeReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->addStyleAttribute('color', 'red'));
	}

	public function testAddStyleAttributesHtmlEncodesValues()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttributes(['font-size' => '1em', 'color' => 'red & blue']);
		$this->assertSame('1em', $this->getStyles($htmlWriter)['font-size']);
		$this->assertSame(THttpUtility::htmlEncode('red & blue'), $this->getStyles($htmlWriter)['color']);
	}

	public function testAddStyleAttributesOverwritesExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttributes(['color' => 'green']);
		$this->assertSame('green', $this->getStyles($htmlWriter)['color']);
	}

	public function testAddStyleAttributesEmptyArrayNoOp()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttributes([]);
		$this->assertCount(1, $this->getStyles($htmlWriter));
	}

	public function testAddStyleAttributesReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->addStyleAttributes([]));
	}

	public function testRemoveStyleAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$htmlWriter->addStyleAttribute('color', 'blue');
		$htmlWriter->removeStyleAttribute('font-size');

		$styles = $this->getStyles($htmlWriter);
		$this->assertArrayNotHasKey('font-size', $styles);
		$this->assertArrayHasKey('color', $styles);
	}

	public function testRemoveStyleAttributeNonExistentNoThrow()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->removeStyleAttribute('non-existent');
		$this->assertEmpty($this->getStyles($htmlWriter));
	}

	public function testRemoveStyleAttributeReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->removeStyleAttribute('x'));
	}

	// ================================================================================
	// Writer Interface Methods
	// ================================================================================

	public function testWrite()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Hello');
		$this->assertSame('Hello', $this->writer->getStr());
	}

	public function testWriteAppends()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Hello');
		$htmlWriter->write(' World');
		$this->assertSame('Hello World', $this->writer->getStr());
	}

	public function testWriteReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->write('x'));
	}

	public function testWriteLine()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeLine('Hello');
		$this->assertSame("Hello\n", $this->writer->getStr());
	}

	public function testWriteLineEmpty()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeLine();
		$this->assertSame("\n", $this->writer->getStr());
	}

	public function testWriteLineReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->writeLine());
	}

	public function testWriteBreak()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeBreak();
		$this->assertSame('<br/>', $this->writer->getStr());
	}

	public function testWriteBreakReturnsThis()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($htmlWriter, $htmlWriter->writeBreak());
	}

	public function testFlushReturnsContent()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Test Content');
		$this->assertSame('Test Content', $htmlWriter->flush());
	}

	public function testFlushClearsBuffer()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Test Content');
		$htmlWriter->flush();
		$this->assertSame('', $this->writer->getStr());
	}

	public function testFlushSecondCallReturnsEmpty()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Test');
		$htmlWriter->flush();
		$this->assertSame('', $htmlWriter->flush());
	}

	// ================================================================================
	// renderBeginTag
	// ================================================================================

	public function testRenderBeginTagNonVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('class', 'myclass');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div class="myclass">', $this->writer->flush());
	}

	public function testRenderBeginTagNoAttributes()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('span');
		$this->assertSame('<span>', $this->writer->flush());
	}

	public function testRenderBeginTagVoidElementSelfCloses()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$this->assertSame('<br />', $this->writer->flush());
	}

	public function testRenderBeginTagVoidElementWithAttributes()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->addAttribute('value', 'test');
		$htmlWriter->renderBeginTag('input');
		$this->assertSame('<input type="text" value="test" />', $this->writer->flush());
	}

	public function testRenderBeginTagVoidNoAttributes()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('hr');
		$this->assertSame('<hr />', $this->writer->flush());
	}

	public function testRenderBeginTagWithStylesOnly()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div style="color:red;font-size:1em;">', $this->writer->flush());
	}

	public function testRenderBeginTagWithAttributesAndStyles()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('id', 'myid');
		$htmlWriter->addStyleAttribute('color', 'blue');
		$htmlWriter->renderBeginTag('p');
		$this->assertSame('<p id="myid" style="color:blue;">', $this->writer->flush());
	}

	public function testRenderBeginTagVoidWithStyles()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('src', 'image.png');
		$htmlWriter->addStyleAttribute('border', '0');
		$htmlWriter->renderBeginTag('img');
		$this->assertSame('<img src="image.png" style="border:0;" />', $this->writer->flush());
	}

	public function testRenderBeginTagClearsAttributesAndStyles()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->renderBeginTag('input');
		$this->assertEmpty($this->getAttributes($htmlWriter));
		$this->assertEmpty($this->getStyles($htmlWriter));
	}

	public function testRenderBeginTagCaseInsensitiveVoidCheck()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('IMG');
		$this->assertSame('<IMG />', $this->writer->flush());
	}

	public function testRenderBeginTagMixedCaseVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->renderBeginTag('INPUT');
		$this->assertSame('<INPUT type="text" />', $this->writer->flush());
	}

	public function testRenderBeginTagPushesTagOntoStack()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->renderBeginTag('br');
		$this->assertSame(['div', 'span', ''], $this->getOpenTags($htmlWriter));
	}

	public function testRenderBeginTagAttributeHtmlEncoded()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Quote: "test"');
		$htmlWriter->renderBeginTag('span');
		$this->assertSame('<span title="Quote: &quot;test&quot;">', $this->writer->flush());
	}

	public function testRenderBeginTagStyleAmpersandPreserved()
	{
		// htmlEncode leaves & untouched; only <, >, " are encoded
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-family', 'Arial & Helvetica');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div style="font-family:Arial & Helvetica;">', $this->writer->flush());
	}

	public function testRenderBeginTagCustomElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('custom-element');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();
		$this->assertSame('<custom-element>Content</custom-element>', $this->writer->flush());
	}

	public function testRenderBeginTagEmptyAttributeName()
	{
		// Degenerate case: empty attribute name renders as ="value"
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('', 'value');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div ="value">', $this->writer->flush());
	}

	public function testRenderBeginTagEmptyStyleName()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('', 'value');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div style=":value;">', $this->writer->flush());
	}

	public function testRenderBeginTagUnicodeAttributeValue()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('data-name', "\xC3\xA9");
		$htmlWriter->renderBeginTag('span');
		$this->assertStringContainsString("\xC3\xA9", $this->writer->flush());
	}

	public function testRenderBeginTagDataAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('data-123', 'value');
		$htmlWriter->renderBeginTag('div');
		$this->assertSame('<div data-123="value">', $this->writer->flush());
	}

	// ================================================================================
	// renderEndTag
	// ================================================================================

	public function testRenderEndTagNonVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();
		$this->assertSame('<div>Content</div>', $this->writer->flush());
	}

	public function testRenderEndTagVoidElementIsNoop()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$htmlWriter->renderEndTag();
		$htmlWriter->write('after');
		$this->assertSame('<br />after', $this->writer->flush());
	}

	public function testRenderEndTagNested()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('Inner');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$this->assertSame('<div><span>Inner</span></div>', $this->writer->flush());
	}

	public function testRenderEndTagEmptyStackIsNoop()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderEndTag();
		$this->assertSame('', $this->writer->flush());
	}

	public function testRenderEndTagExcessCallsAreNoop()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();  // stack is empty — no output
		$this->assertSame('<div></div>', $this->writer->flush());
	}

	public function testRenderEndTagPopsOnlyMostRecent()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->renderEndTag();
		$this->assertSame(['div'], $this->getOpenTags($htmlWriter));
	}

	// ================================================================================
	// Full Render Sequences
	// ================================================================================

	public function testFullRenderFormWithInputs()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('form');
		$htmlWriter->renderBeginTag('input');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderBeginTag('input');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$this->assertSame('<form><input /><input /></form>', $this->writer->flush());
	}

	public function testFullRenderStyledParagraph()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('class', 'highlight');
		$htmlWriter->addStyleAttribute('background-color', 'yellow');
		$htmlWriter->addStyleAttribute('padding', '10px');
		$htmlWriter->renderBeginTag('p');
		$htmlWriter->write('Important message');
		$htmlWriter->renderEndTag();
		$this->assertSame('<p class="highlight" style="background-color:yellow;padding:10px;">Important message</p>', $this->writer->flush());
	}

	public function testFullRenderImageAllAttributes()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('src', 'photo.jpg');
		$htmlWriter->addAttribute('alt', 'A beautiful sunset');
		$htmlWriter->addAttribute('width', '800');
		$htmlWriter->addAttribute('height', '600');
		$htmlWriter->addStyleAttribute('border', '1px solid black');
		$htmlWriter->renderBeginTag('img');
		$this->assertSame('<img src="photo.jpg" alt="A beautiful sunset" width="800" height="600" style="border:1px solid black;" />', $this->writer->flush());
	}

	public function testFullRenderAnchor()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('href', 'https://example.com');
		$htmlWriter->addAttribute('target', '_blank');
		$htmlWriter->renderBeginTag('a');
		$htmlWriter->write('Click here');
		$htmlWriter->renderEndTag();
		$this->assertSame('<a href="https://example.com" target="_blank">Click here</a>', $this->writer->flush());
	}

	public function testFullRenderUnorderedList()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('ul');
		$htmlWriter->renderBeginTag('li');
		$htmlWriter->write('Item 1');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderBeginTag('li');
		$htmlWriter->write('Item 2');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$this->assertSame('<ul><li>Item 1</li><li>Item 2</li></ul>', $this->writer->flush());
	}

	public function testFullRenderTable()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('table');
		$htmlWriter->renderBeginTag('tr');
		$htmlWriter->renderBeginTag('td');
		$htmlWriter->write('Cell 1');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$this->assertSame('<table><tr><td>Cell 1</td></tr></table>', $this->writer->flush());
	}

	public function testFullRenderDoubleQuoteInAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Say: "Hello"');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();
		$this->assertStringContainsString('title="Say: &quot;Hello&quot;"', $this->writer->flush());
	}

	public function testFullRenderAmpersandInWrittenContent()
	{
		// write() passes content as-is — no encoding
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('AT&T');
		$htmlWriter->renderEndTag();
		$this->assertStringContainsString('AT&T', $this->writer->flush());
	}

	public function testFullRenderVoidElementsOnly()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$htmlWriter->renderBeginTag('hr');
		$htmlWriter->renderBeginTag('img');
		$this->assertSame('<br /><hr /><img />', $this->writer->flush());
	}

	public function testFullRenderInternalDefaultWriter()
	{
		// Verify the auto-created TTextWriter round-trips correctly
		$htmlWriter = new THtmlWriter(true);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->write('hello');
		$htmlWriter->renderEndTag();
		$this->assertSame('<div>hello</div>', $htmlWriter->flush());
	}

	// ================================================================================
	// Helper Methods
	// ================================================================================

	private function getAttributes(THtmlWriter $writer): array
	{
		return PradoUnit::getProp($writer, '_attributes');
	}

	private function getStyles(THtmlWriter $writer): array
	{
		return PradoUnit::getProp($writer, '_styles');
	}

	private function getOpenTags(THtmlWriter $writer): array
	{
		return PradoUnit::getProp($writer, '_openTags');
	}
}
