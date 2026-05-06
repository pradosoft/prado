<?php

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;

class TJavaScriptAssetTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	// -----------------------------------------------------------------------
	// Constructor / getters / setters
	// -----------------------------------------------------------------------

	public function testConstructorSetsUrl()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		self::assertEquals('https://cdn.example.com/lib.js', $asset->getUrl());
	}

	public function testConstructorDefaultAsyncIsFalse()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		self::assertFalse($asset->getAsync());
	}

	public function testConstructorSetsAsync()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js', true);
		self::assertTrue($asset->getAsync());
	}

	public function testSetUrl()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$asset->setUrl('https://cdn.example.com/other.js');
		self::assertEquals('https://cdn.example.com/other.js', $asset->getUrl());
	}

	public function testSetAsync()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$asset->setAsync(true);
		self::assertTrue($asset->getAsync());
		$asset->setAsync(false);
		self::assertFalse($asset->getAsync());
	}

	// -----------------------------------------------------------------------
	// __toString — basic rendering
	// -----------------------------------------------------------------------

	public function testToStringRendersScriptTag()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = (string) $asset;
		self::assertStringStartsWith('<script', $out);
		self::assertStringEndsWith('></script>', $out);
	}

	public function testToStringContainsSrc()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = (string) $asset;
		self::assertStringContainsString('src=', $out);
		self::assertStringContainsString('cdn.example.com/lib.js', $out);
	}

	public function testToStringNoAsyncByDefault()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = (string) $asset;
		self::assertStringNotContainsString('async', $out);
	}

	public function testToStringWithAsync()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js', true);
		$out = (string) $asset;
		self::assertStringContainsString('async', $out);
	}

	public function testToStringWithAsyncFalseOmitsAsyncAttribute()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js', false);
		$out = (string) $asset;
		self::assertStringNotContainsString('async', $out);
	}

	// -----------------------------------------------------------------------
	// __toString — nonce
	// -----------------------------------------------------------------------

	public function testToStringNoNonceWhenNotSet()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = (string) $asset;
		self::assertStringNotContainsString('nonce', $out);
	}

	public function testToStringIncludesNonceWhenSet()
	{
		TJavaScript::setScriptNonce('testNonce123');
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = (string) $asset;
		self::assertStringContainsString('nonce="testNonce123"', $out);
	}

	public function testToStringNonceReflectsCurrentGlobalNonce()
	{
		TJavaScript::setScriptNonce('firstNonce');
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');

		TJavaScript::setScriptNonce('secondNonce');
		// __toString() reads the nonce at render time
		$out = (string) $asset;
		self::assertStringContainsString('nonce="secondNonce"', $out);
		self::assertStringNotContainsString('firstNonce', $out);
	}

	// -----------------------------------------------------------------------
	// __toString — URL encoding
	// -----------------------------------------------------------------------

	public function testToStringUrlSpecialCharsEncoded()
	{
		// Standard URL characters are safe; & must be HTML-encoded in attribute context
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js?v=1&t=2');
		$out = (string) $asset;
		// URL should appear encoded in the attribute
		self::assertStringContainsString('cdn.example.com/lib.js', $out);
	}

	// -----------------------------------------------------------------------
	// __toString — combinations
	// -----------------------------------------------------------------------

	public function testToStringAllAttributesCombined()
	{
		TJavaScript::setScriptNonce('combo');
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js', true);
		$out = (string) $asset;
		self::assertStringContainsString('src=', $out);
		self::assertStringContainsString('async', $out);
		self::assertStringContainsString('nonce="combo"', $out);
	}

	public function testToStringRelativeUrl()
	{
		$asset = new TJavaScriptAsset('/assets/js/app.js');
		$out = (string) $asset;
		self::assertStringContainsString('/assets/js/app.js', $out);
	}
}
