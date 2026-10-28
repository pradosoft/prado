<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TSvgDocument;

class TSvgDocumentTest extends TestCase
{
	private function doc(string $inner, string $rootAttrs = ''): TSvgDocument
	{
		return new TSvgDocument('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"' . $rootAttrs . '>' . $inner . '</svg>');
	}

	private function errorCode(callable $fn): string
	{
		try {
			$fn();
		} catch (TInvalidDataValueException $e) {
			return $e->getErrorCode();
		}
		return '';
	}

	public function testRootAttributesAndInnerMarkup()
	{
		$doc = $this->doc('<circle r="2"/><g fill="red"><rect width="1" height="1"/></g>', ' viewBox="0 0 24 24" fill="none"');
		$this->assertSame(['viewBox' => '0 0 24 24', 'fill' => 'none'], $doc->getRootAttributes());
		$this->assertSame('<circle r="2"/><g fill="red"><rect width="1" height="1"/></g>', $doc->getInnerMarkup());
		$this->assertSame('svg', $doc->getRoot()->localName);
	}

	public function testInvalidMarkupThrows()
	{
		$this->assertSame('svgdocument_invalid', $this->errorCode(fn () => new TSvgDocument('<svg><g></svg>')));
		$this->assertSame('svgdocument_root_invalid', $this->errorCode(fn () => new TSvgDocument('<html/>')));
	}

	public function testEntityDeclarationsAreRefused()
	{
		$markup = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY a "aaaa"><!ENTITY b "&a;&a;&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>&b;</text></svg>';
		$this->assertSame('svgdocument_entity_invalid', $this->errorCode(fn () => new TSvgDocument($markup)));
	}

	public function testDoctypeWithoutEntitiesIsDropped()
	{
		$markup = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/></svg>';
		$doc = new TSvgDocument($markup);
		$this->assertSame('<circle r="1"/>', $doc->getInnerMarkup());
	}

	public function testFromFile()
	{
		$path = tempnam(sys_get_temp_dir(), 'svg');
		file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0"/></svg>');
		try {
			$this->assertSame(['viewBox' => '0 0 1 1'], TSvgDocument::fromFile($path)->getRootAttributes());
		} finally {
			unlink($path);
		}
		$this->assertSame('svgdocument_file_invalid', $this->errorCode(fn () => TSvgDocument::fromFile('/no/such/file.svg')));
	}

	public function testTitle()
	{
		$this->assertNull($this->doc('<circle r="1"/>')->getTitle());
		$this->assertNull($this->doc('<title> </title>')->getTitle());
		$doc = $this->doc('<title>Logo</title><desc>Round</desc><circle r="1"/>');
		$this->assertSame('Logo', $doc->getTitle()->textContent);
		$this->assertSame('t1', $doc->ensureTitleId('t1'));
		$this->assertSame('<title id="t1">Logo</title><desc>Round</desc><circle r="1"/>', $doc->getInnerMarkup());
		$this->assertSame('t1', $doc->ensureTitleId('t2'));
		$doc->removeTitles();
		$this->assertSame('<circle r="1"/>', $doc->getInnerMarkup());
		$this->assertNull($doc->ensureTitleId('t3'));
	}

	public function testSanitizeRemovesScriptAndEvents()
	{
		$doc = $this->doc('<script>alert(1)</script><foreignObject><div/></foreignObject><circle r="1" onclick="x()" onload="y()" fill="red"/>', ' onload="z()"');
		$doc->sanitize();
		$this->assertSame([], $doc->getRootAttributes());
		$this->assertSame('<circle r="1" fill="red"/>', $doc->getInnerMarkup());
	}

	public function testSanitizeReferences()
	{
		$doc = $this->doc(
			'<a href="javascript:alert(1)"><text>x</text></a>'
			. '<a xlink:href=" JaVa&#x09;script:alert(1)"><text>y</text></a>'
			. '<a href="https://example.com/"><text>z</text></a>'
			. '<use href="#icon"/><use href="https://evil.example/sprite.svg#a"/>'
			. '<image href="data:image/png;base64,AAAA"/><image href="data:text/html;base64,AAAA"/><image href="//evil.example/x.png"/>'
		);
		$doc->sanitize();
		$this->assertSame(
			'<a><text>x</text></a><a><text>y</text></a><a href="https://example.com/"><text>z</text></a>'
			. '<use href="#icon"/><use/><image href="data:image/png;base64,AAAA"/><image/><image/>',
			$doc->getInnerMarkup()
		);
	}

	public function testSanitizeAnimationsAndCss()
	{
		$doc = $this->doc(
			'<a><set attributeName="href" to="javascript:alert(1)"/><animate attributeName="opacity" values="0;1"/></a>'
			. '<style>@import url(https://evil.example/a.css); .a { fill: url(#g); background: url(https://evil.example/t.png); }</style>'
			. '<rect style="fill: url(https://evil.example/t.png)"/>'
		);
		$doc->sanitize();
		$markup = $doc->getInnerMarkup();
		$this->assertStringNotContainsString('<set', $markup);
		$this->assertStringContainsString('<animate attributeName="opacity" values="0;1"/>', $markup);
		$this->assertStringNotContainsString('@import', $markup);
		$this->assertStringNotContainsString('evil.example', $markup);
		$this->assertStringContainsString('fill: url(#g)', $markup);
	}

	public function testPrefixIdsRewritesReferences()
	{
		$doc = $this->doc(
			'<defs><linearGradient id="g"/><clipPath id="c"/></defs>'
			. '<title id="t">T</title>'
			. '<rect fill="url(#g)" clip-path="url(\'#c\')" aria-labelledby="t other"/>'
			. '<use href="#g"/><use xlink:href="#c"/><use href="#unknown"/>'
			. '<animate id="a1" begin="0s"/><animate begin="a1.end; 2s"/>'
			. '<style>#g { stop-color: red; } .x { fill: url(#g); } #unknown {}</style>'
		);
		$map = $doc->prefixIds('p');
		$this->assertSame(['g' => 'p_g', 'c' => 'p_c', 't' => 'p_t', 'a1' => 'p_a1'], $map);
		$markup = $doc->getInnerMarkup();
		$this->assertStringContainsString('<linearGradient id="p_g"/>', $markup);
		$this->assertStringContainsString('fill="url(#p_g)" clip-path="url(\'#p_c\')" aria-labelledby="p_t other"', $markup);
		$this->assertStringContainsString('<use href="#p_g"/><use xlink:href="#p_c"/><use href="#unknown"/>', $markup);
		$this->assertStringContainsString('<animate begin="p_a1.end; 2s"/>', $markup);
		$this->assertStringContainsString('#p_g { stop-color: red; } .x { fill: url(#p_g); } #unknown {}', $markup);
	}

	public function testPrefixIdsWithoutIds()
	{
		$doc = $this->doc('<circle r="1"/>');
		$this->assertSame([], $doc->prefixIds('p'));
		$this->assertSame('<circle r="1"/>', $doc->getInnerMarkup());
	}

	public function testExtractStyles()
	{
		$doc = $this->doc('<style>.a{fill:red}</style><g><style> </style><style>.b{fill:blue}</style></g><circle class="a"/>');
		$this->assertSame(['.a{fill:red}', '.b{fill:blue}'], $doc->extractStyles());
		$this->assertSame('<g/><circle class="a"/>', $doc->getInnerMarkup());
	}

	public function testScopeCss()
	{
		$this->assertSame('#s .a {fill:red}', TSvgDocument::scopeCss('.a{fill:red}', '#s'));
		$this->assertSame('#s .a, #s g > .b {fill:red}', TSvgDocument::scopeCss('.a, g > .b {fill:red}', '#s'));
		$this->assertSame('#s, #s:hover .a {x:1}', TSvgDocument::scopeCss('svg, svg:hover .a {x:1}', '#s'));
		$this->assertSame('#s {x:1}', TSvgDocument::scopeCss(':root {x:1}', '#s'));
		$this->assertSame('#s .svgicon {x:1}', TSvgDocument::scopeCss('.svgicon {x:1}', '#s'));
		$this->assertSame('#s :is(.a, .b) {x:1}', TSvgDocument::scopeCss(':is(.a, .b) {x:1}', '#s'));
		$this->assertSame('@media (min-width: 1px) {#s .a {x:1}}', TSvgDocument::scopeCss('@media (min-width: 1px) {.a {x:1}}', '#s'));
		$this->assertSame('@keyframes spin {to { transform: rotate(1turn) }}', TSvgDocument::scopeCss('@keyframes spin {to { transform: rotate(1turn) }}', '#s'));
		$this->assertSame('@charset "utf-8"; #s .a {x:1}', TSvgDocument::scopeCss('@charset "utf-8"; .a {x:1}', '#s'));
		$this->assertSame('#s .a {x:1} #s .b {y:2}', TSvgDocument::scopeCss('/* c */ .a {x:1} .b {y:2}', '#s'));
	}

	public function testScopeStyles()
	{
		$doc = $this->doc('<style>.a{fill:red}</style><circle class="a"/>');
		$doc->scopeStyles('#p');
		$this->assertSame('<style>#p .a {fill:red}</style><circle class="a"/>', $doc->getInnerMarkup());
	}
}
