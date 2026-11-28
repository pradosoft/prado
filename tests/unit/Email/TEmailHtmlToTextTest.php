<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmailHtmlToText;

class TEmailHtmlToTextTest extends TestCase
{
	public function testEmpty()
	{
		$this->assertSame('', TEmailHtmlToText::convert(''));
		$this->assertSame('', TEmailHtmlToText::convert("  \n "));
	}

	public function testDropsHeadStyleAndScript()
	{
		$html = '<html><head><title>T</title><style>p{color:red}</style></head><body><p>Hello</p><script>var a=1;</script></body></html>';
		$this->assertSame('Hello', TEmailHtmlToText::convert($html));
	}

	public function testLinks()
	{
		$this->assertSame('Read the docs (https://example.com/docs)', TEmailHtmlToText::convert('<p>Read <a href="https://example.com/docs">the docs</a></p>'));
		$this->assertSame('https://example.com', TEmailHtmlToText::convert('<a href="https://example.com">https://example.com</a>'));
		$this->assertSame('https://example.com', TEmailHtmlToText::convert('<a href="https://example.com"></a>'));
		$this->assertSame('ann@example.com', TEmailHtmlToText::convert('<a href="mailto:ann@example.com">ann@example.com</a>'));
		$this->assertSame('Write us (ann@example.com)', TEmailHtmlToText::convert('<a href="mailto:ann@example.com">Write us</a>'));
		$this->assertSame('Top', TEmailHtmlToText::convert('<a href="#top">Top</a>'));
	}

	public function testBlocksAndBreaks()
	{
		$html = '<h1>Title</h1><p>One<br>Two</p><div>Three</div><div>Four</div>';
		$this->assertSame("Title\n\nOne\nTwo\n\nThree\nFour", TEmailHtmlToText::convert($html));
	}

	public function testLists()
	{
		$this->assertSame("- One\n- Two", TEmailHtmlToText::convert('<ul><li>One</li><li> Two </li></ul>'));
	}

	public function testTables()
	{
		$html = '<table><tr><td>A</td><td>B</td></tr><tr><td>C</td><td>D</td></tr></table>';
		$this->assertSame("A B\nC D", TEmailHtmlToText::convert($html));
	}

	public function testImagesUseAlternateText()
	{
		$this->assertSame('Logo of Shop', TEmailHtmlToText::convert('<p><img src="a.png" alt="Logo of Shop"></p>'));
		$this->assertSame('', TEmailHtmlToText::convert('<img src="pixel.gif" alt="">'));
	}

	public function testHiddenElementsAreDropped()
	{
		$html = '<div style="display:none;max-height:0">Preview</div><div aria-hidden="true">Pad</div><p hidden>Gone</p><p>Shown</p>';
		$this->assertSame('Shown', TEmailHtmlToText::convert($html));
	}

	public function testWhitespaceEntitiesAndUtf8()
	{
		$this->assertSame('Tom & Zoë – 5 < 6', TEmailHtmlToText::convert("<p>Tom   &amp;\n\tZoë &ndash; 5 &lt; 6</p>"));
		$this->assertSame('a b', TEmailHtmlToText::convert('<p>a&nbsp;&nbsp;b</p>'));
	}

	public function testPlaceholdersPassThrough()
	{
		$this->assertSame('Hello {{NAME}} {{{URL}}}', TEmailHtmlToText::convert('<p>Hello {{NAME}} <a href="{{{URL}}}"></a></p>'));
		$this->assertSame('Hello {{NAME}}, order {{email:tracking-id}}', TEmailHtmlToText::convert('<p>Hello {{NAME}}, order {{email:tracking-id}}</p>'));
	}
}
