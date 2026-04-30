<?php

use Prado\IO\ITextWriter;
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

	public function testConstructor()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$this->assertSame($this->writer, $htmlWriter->getWriter());
	}

	public function testSetWriter()
	{
		$htmlWriter = new THtmlWriter(null);
		$this->assertNull($htmlWriter->getWriter());

		$htmlWriter->setWriter($this->writer);
		$this->assertSame($this->writer, $htmlWriter->getWriter());
	}

	// ================================================================================
	// Void Elements Static Methods
	// ================================================================================

	public function testGetVoidElementsReturnsArray()
	{
		$voidElements = THtmlWriter::getVoidElements();
		$this->assertIsArray($voidElements);
	}

	public function testGetVoidElementsContainsStandardElements()
	{
		$voidElements = THtmlWriter::getVoidElements();

		$expected = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
		foreach ($expected as $element) {
			$this->assertContains($element, $voidElements, "Void element '$element' should be in list");
		}
	}

	public function testGetVoidElementsContainsLegacyElements()
	{
		$voidElements = THtmlWriter::getVoidElements();

		$legacy = ['basefont', 'bgsound', 'frame', 'isindex'];
		foreach ($legacy as $element) {
			$this->assertContains($element, $voidElements, "Legacy element '$element' should be in list");
		}
	}

	public function testGetVoidElementsReturnsAllElements()
	{
		$voidElements = THtmlWriter::getVoidElements();
		$this->assertCount(17, $voidElements, 'Should have 17 void elements');
		$this->assertContains('br', $voidElements);
		$this->assertContains('area', $voidElements);
		$this->assertContains('basefont', $voidElements);
	}

	public function testIsVoidElementStandardVoidElement()
	{
		$this->assertTrue(THtmlWriter::isVoidElement('br'));
		$this->assertTrue(THtmlWriter::isVoidElement('img'));
		$this->assertTrue(THtmlWriter::isVoidElement('input'));
		$this->assertTrue(THtmlWriter::isVoidElement('meta'));
		$this->assertTrue(THtmlWriter::isVoidElement('link'));
		$this->assertTrue(THtmlWriter::isVoidElement('area'));
		$this->assertTrue(THtmlWriter::isVoidElement('base'));
		$this->assertTrue(THtmlWriter::isVoidElement('col'));
		$this->assertTrue(THtmlWriter::isVoidElement('embed'));
		$this->assertTrue(THtmlWriter::isVoidElement('hr'));
		$this->assertTrue(THtmlWriter::isVoidElement('source'));
		$this->assertTrue(THtmlWriter::isVoidElement('track'));
		$this->assertTrue(THtmlWriter::isVoidElement('wbr'));
	}

	public function testIsVoidElementLegacyVoidElement()
	{
		$this->assertTrue(THtmlWriter::isVoidElement('basefont'));
		$this->assertTrue(THtmlWriter::isVoidElement('bgsound'));
		$this->assertTrue(THtmlWriter::isVoidElement('frame'));
		$this->assertTrue(THtmlWriter::isVoidElement('isindex'));
	}

	public function testIsVoidElementNonVoidElement()
	{
		$this->assertFalse(THtmlWriter::isVoidElement('div'));
		$this->assertFalse(THtmlWriter::isVoidElement('span'));
		$this->assertFalse(THtmlWriter::isVoidElement('a'));
		$this->assertFalse(THtmlWriter::isVoidElement('p'));
		$this->assertFalse(THtmlWriter::isVoidElement('table'));
		$this->assertFalse(THtmlWriter::isVoidElement('form'));
		$this->assertFalse(THtmlWriter::isVoidElement('button'));
	}

	public function testIsVoidElementCaseInsensitive()
	{
		$this->assertTrue(THtmlWriter::isVoidElement('BR'));
		$this->assertTrue(THtmlWriter::isVoidElement('Img'));
		$this->assertTrue(THtmlWriter::isVoidElement('INPUT'));
		$this->assertTrue(THtmlWriter::isVoidElement('Br'));
	}

	public function testIsVoidElementLowercaseNormalization()
	{
		$this->assertTrue(THtmlWriter::isVoidElement('BR'));
		$this->assertFalse(THtmlWriter::isVoidElement('DIV'));
	}

	// ================================================================================
	// Attribute Methods
	// ================================================================================

	public function testAddAttributesHtmlEncodesValues()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttributes(['name' => 'value', 'title' => 'Rock & Roll']);
		$this->assertEquals('value', $this->getAttributes($htmlWriter)['name']);
		$this->assertEquals(THttpUtility::htmlEncode('Rock & Roll'), $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributesPreservesWhitespaceInNames()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttributes(['  name  ' => 'value']);
		$this->assertArrayHasKey('  name  ', $this->getAttributes($htmlWriter));
	}

	public function testAddAttributesPreservesValueWhitespace()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttributes(['name' => '  value  ']);
		$this->assertEquals('  value  ', $this->getAttributes($htmlWriter)['name']);
	}

	public function testAddAttributeSingle()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$this->assertEquals('text', $this->getAttributes($htmlWriter)['type']);
	}

	public function testAddAttributeHtmlEncodesValue()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Rock & Roll');
		$this->assertEquals(THttpUtility::htmlEncode('Rock & Roll'), $this->getAttributes($htmlWriter)['title']);
	}

	public function testAddAttributePreservesWhitespaceInName()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('  type  ', 'text');
		$this->assertArrayHasKey('  type  ', $this->getAttributes($htmlWriter));
	}

	public function testAddAttributeMultipleAddsToExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('name1', 'value1');
		$htmlWriter->addAttribute('name2', 'value2');
		$attrs = $this->getAttributes($htmlWriter);
		$this->assertEquals('value1', $attrs['name1']);
		$this->assertEquals('value2', $attrs['name2']);
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

	public function testRemoveAttributePreservesWhitespace()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->removeAttribute('  type  ');

		$attrs = $this->getAttributes($htmlWriter);
		$this->assertArrayHasKey('type', $attrs);
	}

	public function testRemoveAttributeNonExistentDoesNotThrow()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->removeAttribute('non-existent');
		$this->assertTrue(true);
	}

	// ================================================================================
	// Style Attribute Methods
	// ================================================================================

	public function testAddStyleAttributesHtmlEncodesValues()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttributes(['font-size' => '1em', 'color' => 'red & blue']);
		$this->assertEquals('1em', $this->getStyles($htmlWriter)['font-size']);
		$this->assertEquals(THttpUtility::htmlEncode('red & blue'), $this->getStyles($htmlWriter)['color']);
	}

	public function testAddStyleAttributesPreservesWhitespaceInNames()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttributes(['  font-size  ' => '1em']);
		$this->assertArrayHasKey('  font-size  ', $this->getStyles($htmlWriter));
	}

	public function testAddStyleAttributeSingle()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$this->assertEquals('1em', $this->getStyles($htmlWriter)['font-size']);
	}

	public function testAddStyleAttributeMultipleAddsToExisting()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$styles = $this->getStyles($htmlWriter);
		$this->assertEquals('red', $styles['color']);
		$this->assertEquals('1em', $styles['font-size']);
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

	public function testRemoveStyleAttributePreservesWhitespace()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$htmlWriter->removeStyleAttribute('  font-size  ');

		$styles = $this->getStyles($htmlWriter);
		$this->assertArrayHasKey('font-size', $styles);
	}

	public function testRemoveStyleAttributeNonExistentDoesNotThrow()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->removeStyleAttribute('non-existent');
		$this->assertTrue(true);
	}

	// ================================================================================
	// Writer Interface Methods
	// ================================================================================

	public function testWrite()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Hello');
		$this->assertEquals('Hello', $this->writer->getStr());
	}

	public function testWriteAppends()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Hello');
		$htmlWriter->write(' World');
		$this->assertEquals('Hello World', $this->writer->getStr());
	}

	public function testWriteLine()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeLine('Hello');
		$this->assertEquals("Hello\n", $this->writer->getStr());
	}

	public function testWriteLineEmpty()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeLine();
		$this->assertEquals("\n", $this->writer->getStr());
	}

	public function testFlushReturnsContent()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Test Content');
		$flushed = $htmlWriter->flush();
		$this->assertEquals('Test Content', $flushed);
	}

	public function testFlushClearsBuffer()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->write('Test Content');
		$htmlWriter->flush();
		$this->assertEquals('', $this->writer->getStr());
	}

	public function testWriteBreak()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->writeBreak();
		$this->assertEquals('<br/>', $this->writer->getStr());
	}

	// ================================================================================
	// Render Begin Tag
	// ================================================================================

	public function testRenderBeginTagVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->addAttribute('value', 'test');
		$htmlWriter->renderBeginTag('input');

		$output = $this->writer->flush();
		$this->assertEquals('<input type="text" value="test" />', $output);
	}

	public function testRenderBeginTagVoidElementSelfCloses()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$output = $this->writer->flush();
		$this->assertEquals('<br />', $output);
	}

	public function testRenderBeginTagNonVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('class', 'myclass');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div class="myclass">', $output);
	}

	public function testRenderBeginTagNoAttributesVoid()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('hr');
		$output = $this->writer->flush();
		$this->assertEquals('<hr />', $output);
	}

	public function testRenderBeginTagNoAttributesNonVoid()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('span');
		$output = $this->writer->flush();
		$this->assertEquals('<span>', $output);
	}

	public function testRenderBeginTagWithStylesOnly()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('color', 'red');
		$htmlWriter->addStyleAttribute('font-size', '1em');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div style="color:red;font-size:1em;">', $output);
	}

	public function testRenderBeginTagWithAttributesAndStyles()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('id', 'myid');
		$htmlWriter->addStyleAttribute('color', 'blue');
		$htmlWriter->renderBeginTag('p');

		$output = $this->writer->flush();
		$this->assertEquals('<p id="myid" style="color:blue;">', $output);
	}

	public function testRenderBeginTagVoidElementWithStyles()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('src', 'image.png');
		$htmlWriter->addStyleAttribute('border', '0');
		$htmlWriter->renderBeginTag('img');

		$output = $this->writer->flush();
		$this->assertEquals('<img src="image.png" style="border:0;" />', $output);
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
		$output = $this->writer->flush();
		$this->assertEquals('<IMG />', $output);
	}

	public function testRenderBeginTagMixedCaseVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'text');
		$htmlWriter->renderBeginTag('INPUT');
		$output = $this->writer->flush();
		$this->assertEquals('<INPUT type="text" />', $output);
	}

	public function testRenderBeginTagTracksOpenTags()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->renderBeginTag('br');

		$this->assertEquals(['div', 'span', ''], $this->getOpenTags($htmlWriter));
	}

	// ================================================================================
	// Render End Tag
	// ================================================================================

	public function testRenderEndTagNonVoidElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<div>Content</div>', $output);
	}

	public function testRenderEndTagVoidElementDoesNotWrite()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$htmlWriter->renderEndTag();
		$htmlWriter->write('after');

		$output = $this->writer->flush();
		$this->assertEquals('<br />after', $output);
	}

	public function testRenderEndTagNestedElements()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('div');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('Inner');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<div><span>Inner</span></div>', $output);
	}

	public function testRenderEndTagMultipleVoidElementsDoNotClose()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('br');
		$htmlWriter->renderBeginTag('hr');
		$htmlWriter->renderBeginTag('img');
		$htmlWriter->write('after');

		$output = $this->writer->flush();
		$this->assertEquals('<br /><hr /><img />after', $output);
	}

	public function testRenderEndTagWithContentBetween()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('p');
		$htmlWriter->write('Some text');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<p>Some text</p>', $output);
	}

	public function testRenderEndTagEmptyStackDoesNothing()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('', $output);
	}

	// ================================================================================
	// Full Render Sequences
	// ================================================================================

	public function testFullRenderFormWithInputs()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->renderBeginTag('form');
		$htmlWriter->renderBeginTag('input');
		$htmlWriter->renderBeginTag('input');
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<form><input /><input /></form>', $output);
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

		$output = $this->writer->flush();
		$this->assertEquals('<p class="highlight" style="background-color:yellow;padding:10px;">Important message</p>', $output);
	}

	public function testFullRenderImageWithAllAttributes()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->addAttribute('src', 'photo.jpg');
		$htmlWriter->addAttribute('alt', 'A beautiful sunset');
		$htmlWriter->addAttribute('width', '800');
		$htmlWriter->addAttribute('height', '600');
		$htmlWriter->addStyleAttribute('border', '1px solid black');
		$htmlWriter->renderBeginTag('img');

		$output = $this->writer->flush();
		$this->assertEquals('<img src="photo.jpg" alt="A beautiful sunset" width="800" height="600" style="border:1px solid black;" />', $output);
	}

	public function testFullRenderAnchor()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->addAttribute('href', 'https://example.com');
		$htmlWriter->addAttribute('target', '_blank');
		$htmlWriter->renderBeginTag('a');
		$htmlWriter->write('Click here');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<a href="https://example.com" target="_blank">Click here</a>', $output);
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

		$output = $this->writer->flush();
		$this->assertEquals('<ul><li>Item 1</li><li>Item 2</li></ul>', $output);
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

		$output = $this->writer->flush();
		$this->assertEquals('<table><tr><td>Cell 1</td></tr></table>', $output);
	}

	public function testFullRenderWithDoubleQuoteInAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->addAttribute('title', 'Say: "Hello"');
		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertStringContainsString('title="Say: &quot;Hello&quot;"', $output);
	}

	public function testFullRenderWithAmpersandInContent()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->renderBeginTag('span');
		$htmlWriter->write('AT&T');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertStringContainsString('AT&T', $output);
	}

	public function testFullRenderVoidElementsOnly()
	{
		$htmlWriter = new THtmlWriter($this->writer);

		$htmlWriter->renderBeginTag('br');
		$htmlWriter->renderBeginTag('hr');
		$htmlWriter->renderBeginTag('img');

		$output = $this->writer->flush();
		$this->assertEquals('<br /><hr /><img />', $output);
	}

	// ================================================================================
	// Edge Cases and Error Handling
	// ================================================================================

	public function testRenderBeginTagWithEmptyAttributeName()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('', 'value');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div ="value">', $output);
	}

	public function testRenderBeginTagWithEmptyStyleName()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('', 'value');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div style=":value;">', $output);
	}

	public function testRenderBeginTagSpecialCharsInAttribute()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('title', 'Quote: "test"');
		$htmlWriter->renderBeginTag('span');

		$output = $this->writer->flush();
		$this->assertEquals('<span title="Quote: &quot;test&quot;">', $output);
	}

	public function testRenderBeginTagUnicodeContent()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('data-name', "\xC3\xA9");
		$htmlWriter->renderBeginTag('span');

		$output = $this->writer->flush();
		$this->assertStringContainsString("\xC3\xA9", $output);
	}

	public function testRenderBeginTagNumericAttributeName()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('data-123', 'value');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div data-123="value">', $output);
	}

	public function testRenderBeginTagCustomElement()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->renderBeginTag('custom-element');
		$htmlWriter->write('Content');
		$htmlWriter->renderEndTag();

		$output = $this->writer->flush();
		$this->assertEquals('<custom-element>Content</custom-element>', $output);
	}

	public function testRenderBeginTagCustomElementNotVoid()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addAttribute('type', 'custom');
		$htmlWriter->renderBeginTag('custom-element');

		$output = $this->writer->flush();
		$this->assertEquals('<custom-element type="custom">', $output);
	}

	public function testIsVoidElementUnknownElement()
	{
		$this->assertFalse(THtmlWriter::isVoidElement('custom'));
		$this->assertFalse(THtmlWriter::isVoidElement('myelement'));
		$this->assertFalse(THtmlWriter::isVoidElement('x-vide'));
	}

	public function testRenderBeginTagStyleValueNotEncoded()
	{
		$htmlWriter = new THtmlWriter($this->writer);
		$htmlWriter->addStyleAttribute('font-family', 'Arial & Helvetica');
		$htmlWriter->renderBeginTag('div');

		$output = $this->writer->flush();
		$this->assertEquals('<div style="font-family:Arial & Helvetica;">', $output);
	}

	// ================================================================================
	// Helper Methods
	// ================================================================================

	private function getAttributes(THtmlWriter $writer): array
	{
		$reflection = new ReflectionClass($writer);
		$prop = $reflection->getProperty('_attributes');
		$prop->setAccessible(true);
		return $prop->getValue($writer);
	}

	private function getStyles(THtmlWriter $writer): array
	{
		$reflection = new ReflectionClass($writer);
		$prop = $reflection->getProperty('_styles');
		$prop->setAccessible(true);
		return $prop->getValue($writer);
	}

	private function getOpenTags(THtmlWriter $writer): array
	{
		$reflection = new ReflectionClass($writer);
		$prop = $reflection->getProperty('_openTags');
		$prop->setAccessible(true);
		return $prop->getValue($writer);
	}
	
}
