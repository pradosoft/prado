<?php

namespace Prado\Test\Unit\Util\Email\UI;

use PHPUnit\Framework\TestCase;
use Prado\Util\Email\UI\TEmailCssInliner;

class TEmailCssInlinerTest extends TestCase
{
	public function testInlinesInCascadeOrder()
	{
		$html = '<!DOCTYPE html><html><head><style>p{color:red;margin:0} .x{color:blue} p.x{font-weight:bold !important} #i{color:green}</style></head>'
			. '<body><p class="x" style="color:black;font-weight:normal">A</p><p id="i" class="x">B</p><p>C</p></body></html>';
		$out = (new TEmailCssInliner())->inline($html);
		$this->assertStringContainsString('<p class="x" style="color: black; margin: 0; font-weight: bold;">A</p>', $out);
		$this->assertStringContainsString('<p id="i" class="x" style="color: green; margin: 0; font-weight: bold;">B</p>', $out);
		$this->assertStringContainsString('<p style="color: red; margin: 0;">C</p>', $out);
		$this->assertStringContainsString('<style>', $out);
	}

	public function testSkipsPseudoAndAtRulesAndKeepsPlaceholders()
	{
		$html = '<html><head><style>@media (max-width:600px){a{color:green}} a:hover{color:pink} a{color:navy}</style></head>'
			. '<body><a href="{{URL}}">{{NAME}}</a></body></html>';
		$out = (new TEmailCssInliner())->inline($html);
		$this->assertStringContainsString('<a href="{{URL}}" style="color: navy;">{{NAME}}</a>', $out);
	}

	public function testUnchangedWithoutStyle()
	{
		$html = '<p>x</p>';
		$this->assertSame($html, (new TEmailCssInliner())->inline($html));
	}

	public function testSelectorToXPath()
	{
		$inliner = new TEmailCssInliner();
		$this->assertSame("//div[contains(concat(' ', normalize-space(@class), ' '), ' n ')]/p//a[starts-with(@href, 'https')]", $inliner->selectorToXPath('div.n > p a[href^="https"]'));
		$this->assertSame('//h1/following-sibling::*[1]/self::p', $inliner->selectorToXPath('h1 + p'));
		$this->assertSame('//h1/following-sibling::p', $inliner->selectorToXPath('h1 ~ p'));
		$this->assertSame("//*[@id='a']", $inliner->selectorToXPath('#a'));
		$this->assertNull($inliner->selectorToXPath('a:hover'));
		$this->assertNull($inliner->selectorToXPath('p::before'));
	}

	public function testSpecificityAndDeclarations()
	{
		$inliner = new TEmailCssInliner();
		$this->assertSame(10101, $inliner->getSpecificity('#a .b p'));
		$this->assertSame(1, $inliner->getSpecificity('p'));
		$this->assertSame(
			[['background', 'url("data:image/png;base64,AA")', false], ['color', 'red', true]],
			$inliner->parseDeclarations('background: url("data:image/png;base64,AA"); color: red !important;')
		);
	}
}
