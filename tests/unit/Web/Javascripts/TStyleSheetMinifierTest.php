<?php

/**
 * TStyleSheetMinifierTest
 *
 * Unit tests for {@see \Prado\Web\Javascripts\TStyleSheetMinifier}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Javascripts;

use Prado\Web\Javascripts\IMinifier;
use Prado\Web\Javascripts\TStyleSheetMinifier;

class TStyleSheetMinifierTest extends \PHPUnit\Framework\TestCase
{
	public function testIsAMinifier(): void
	{
		$this->assertInstanceOf(IMinifier::class, new TStyleSheetMinifier());
	}

	/**
	 * Source CSS and its minified form.
	 */
	public static function minifyProvider(): array
	{
		return [
			'empty' => ['', ''],
			'whitespace only' => [" \n\t ", ''],
			'rule' => ["/* c */ a  {  color : red ;  }  ", 'a{color :red}'],
			'license comment kept' => ["/*! keep */\na { b: c; }", '/*! keep */ a{b:c}'],
			'comment separates tokens' => ['a/**/b{}', 'a b{}'],
			'unterminated comment' => ['a{b:c}/* open', 'a{b:c}'],
			'double-quoted string' => ['a { content: "a  /* x */  b" ; }', 'a{content:"a  /* x */  b"}'],
			'single-quoted string with escape' => ["a { content: 'it\\'s  ;}' }", "a{content:'it\\'s  ;}'}"],
			'unterminated string' => ['a{content:"open}', 'a{content:"open}'],
			'calc keeps operator spaces' => ['a{width: calc( 100% - 2 * 3px ) ;}', 'a{width:calc(100% - 2 * 3px)}'],
			'media query keeps and (' => ['@media screen and ( max-width : 600px ) { a { b : c } }', '@media screen and (max-width :600px){a{b :c}}'],
			'media range keeps comparisons' => ['@media (width >= 600px) { a{b:c} }', '@media (width >= 600px){a{b:c}}'],
			'combinators' => ['a  >  b + c ~ d , e { x : y }', 'a>b+c~d,e{x :y}'],
			'descendant pseudo-class kept' => ['a :hover { x: y }', 'a :hover{x:y}'],
			'an+b keeps spaces' => ['li:nth-child( 2n + 1 ) { x: y }', 'li:nth-child(2n + 1){x:y}'],
			'escaped space kept' => ['.a\\ b { c: d }', '.a\\ b{c:d}'],
			'unquoted url copied' => ['a { background: url( x y.png ) no-repeat; }', 'a{background:url( x y.png ) no-repeat}'],
			'unquoted url with parenthesis escape' => ['a{b:url(x\\).png)}', 'a{b:url(x\\).png)}'],
			'quoted url' => ['a { background: url( "x  y.png" ) ; }', 'a{background:url("x  y.png")}'],
			'url-ending function is no url' => ['a{b:myurl( x )}', 'a{b:myurl(x)}'],
			'important' => ['a { color: red !important; }', 'a{color:red!important}'],
			'repeated semicolons' => ['a{b:c;;d:e;}', 'a{b:c;d:e}'],
			'trailing import semicolon kept' => ['@import url("x.css") screen ;', '@import url("x.css") screen;'],
			'nesting' => ['.x { & .y { a: b } }', '.x{& .y{a:b}}'],
			'crlf and form feed' => ["a\r\n{\fb:c\r\n}", 'a{b:c}'],
			'utf-8 passes through' => ['a::after { content: "→ é" }', 'a::after{content:"→ é"}'],
		];
	}

	/**
	 * @dataProvider minifyProvider
	 */
	public function testMinify(string $source, string $expected): void
	{
		$this->assertSame($expected, (new TStyleSheetMinifier())->minify($source));
	}

	public function testMinifyKeepsAPackageStyleSheetSmallerAndStable(): void
	{
		$path = dirname(__DIR__, 4) . '/vendor/bower-asset/jquery-ui/themes/base/core.css';
		if (!is_file($path)) {
			$this->markTestSkipped('The jquery-ui bower asset is not installed.');
		}
		$minifier = new TStyleSheetMinifier();
		$source = file_get_contents($path);
		$minified = $minifier->minify($source);
		$this->assertLessThan(strlen($source), strlen($minified));
		$this->assertSame($minified, $minifier->minify($minified), 'Minifying is idempotent.');
		$this->assertStringStartsWith('/*!', $minified, 'The license notice is kept.');
	}
}
