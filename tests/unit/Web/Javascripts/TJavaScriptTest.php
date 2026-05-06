<?php

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;

class TJavaScriptTest extends PHPUnit\Framework\TestCase
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
	// getScriptNonce / setScriptNonce
	// -----------------------------------------------------------------------

	public function testGetScriptNonceDefaultIsNull()
	{
		self::assertNull(TJavaScript::getScriptNonce());
	}

	public function testSetAndGetScriptNonce()
	{
		TJavaScript::setScriptNonce('abc123');
		self::assertEquals('abc123', TJavaScript::getScriptNonce());
	}

	public function testSetScriptNonceNull()
	{
		TJavaScript::setScriptNonce('abc123');
		TJavaScript::setScriptNonce(null);
		self::assertNull(TJavaScript::getScriptNonce());
	}

	public function testSetScriptNonceOverwrites()
	{
		TJavaScript::setScriptNonce('first');
		TJavaScript::setScriptNonce('second');
		self::assertEquals('second', TJavaScript::getScriptNonce());
	}

	// -----------------------------------------------------------------------
	// renderScriptHeader
	// -----------------------------------------------------------------------

	public function testRenderScriptHeaderNoNonce()
	{
		$out = TJavaScript::renderScriptHeader();
		self::assertStringStartsWith('<script>', $out);
		self::assertStringContainsString('/*<![CDATA[*/', $out);
		self::assertStringNotContainsString('nonce', $out);
	}

	public function testRenderScriptHeaderWithNonce()
	{
		TJavaScript::setScriptNonce('testNonce');
		$out = TJavaScript::renderScriptHeader();
		self::assertStringContainsString('nonce="testNonce"', $out);
		self::assertStringContainsString('<script ', $out);
		self::assertStringContainsString('/*<![CDATA[*/', $out);
	}

	public function testRenderScriptHeaderCustomAttributesMerged()
	{
		$out = TJavaScript::renderScriptHeader(['type' => 'module']);
		self::assertStringContainsString('type="module"', $out);
		self::assertStringContainsString('/*<![CDATA[*/', $out);
	}

	public function testRenderScriptHeaderExplicitNonceAttributeNotOverridden()
	{
		TJavaScript::setScriptNonce('globalNonce');
		// Caller-supplied nonce takes precedence over the global one
		$out = TJavaScript::renderScriptHeader(['nonce' => 'callerNonce']);
		self::assertStringContainsString('nonce="callerNonce"', $out);
		self::assertStringNotContainsString('globalNonce', $out);
	}

	public function testRenderScriptHeaderNonceNullWithGlobalNonceSet()
	{
		TJavaScript::setScriptNonce('globalNonce');
		// ??= only skips assignment when the key is absent or already non-null;
		// passing nonce=>null makes the key present-but-null, so ??= replaces it
		// with the global nonce — the global nonce IS emitted.
		$out = TJavaScript::renderScriptHeader(['nonce' => null]);
		self::assertStringContainsString('nonce="globalNonce"', $out);
	}

	// -----------------------------------------------------------------------
	// renderScriptFooter
	// -----------------------------------------------------------------------

	public function testRenderScriptFooter()
	{
		$out = TJavaScript::renderScriptFooter();
		self::assertStringContainsString('/*]]>*/', $out);
		self::assertStringContainsString('</script>', $out);
	}

	// -----------------------------------------------------------------------
	// renderScriptFile — string URL
	// -----------------------------------------------------------------------

	public function testRenderScriptFileStringUrl()
	{
		$out = TJavaScript::renderScriptFile('https://cdn.example.com/lib.js');
		self::assertStringContainsString('<script', $out);
		self::assertStringContainsString('src="https://cdn.example.com/lib.js"', $out);
		self::assertStringContainsString('</script>', $out);
		self::assertStringEndsWith("\n", $out);
	}

	public function testRenderScriptFileStringUrlWithNonce()
	{
		TJavaScript::setScriptNonce('myNonce');
		$out = TJavaScript::renderScriptFile('https://cdn.example.com/lib.js');
		self::assertStringContainsString('nonce="myNonce"', $out);
	}

	public function testRenderScriptFileStringUrlNoNonceWhenNotSet()
	{
		$out = TJavaScript::renderScriptFile('https://cdn.example.com/lib.js');
		self::assertStringNotContainsString('nonce', $out);
	}

	public function testRenderScriptFileUrlIsHtmlEncoded()
	{
		// & is preserved (not converted to &amp;) so query-string separators survive;
		// only angle brackets and quotes are encoded.
		$out = TJavaScript::renderScriptFile('https://cdn.example.com/lib.js?a=1&b=2');
		self::assertStringContainsString('src="https://cdn.example.com/lib.js?a=1&b=2"', $out);
	}

	// -----------------------------------------------------------------------
	// renderScriptFile — TJavaScriptAsset
	// -----------------------------------------------------------------------

	public function testRenderScriptFileDelegatesToAssetToString()
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = TJavaScript::renderScriptFile($asset);
		self::assertStringContainsString('src=', $out);
		self::assertStringEndsWith("\n", $out);
	}

	public function testRenderScriptFileAssetCarriesNonce()
	{
		TJavaScript::setScriptNonce('assetNonce');
		$asset = new TJavaScriptAsset('https://cdn.example.com/lib.js');
		$out = TJavaScript::renderScriptFile($asset);
		self::assertStringContainsString('nonce="assetNonce"', $out);
	}

	// -----------------------------------------------------------------------
	// renderScriptFiles
	// -----------------------------------------------------------------------

	public function testRenderScriptFilesRendersAll()
	{
		$out = TJavaScript::renderScriptFiles([
			'https://cdn.example.com/a.js',
			'https://cdn.example.com/b.js',
		]);
		self::assertEquals(2, substr_count($out, '<script'));
		self::assertStringContainsString('a.js', $out);
		self::assertStringContainsString('b.js', $out);
	}

	public function testRenderScriptFilesEmptyArray()
	{
		self::assertEquals('', TJavaScript::renderScriptFiles([]));
	}

	// -----------------------------------------------------------------------
	// renderScriptBlock / renderScriptBlocks
	// -----------------------------------------------------------------------

	public function testRenderScriptBlock()
	{
		$out = TJavaScript::renderScriptBlock('var x = 1;');
		self::assertStringContainsString('<script', $out);
		self::assertStringContainsString('var x = 1;', $out);
		self::assertStringContainsString('</script>', $out);
		self::assertStringContainsString('/*<![CDATA[*/', $out);
		self::assertStringContainsString('/*]]>*/', $out);
	}

	public function testRenderScriptBlockWithNonce()
	{
		TJavaScript::setScriptNonce('blkNonce');
		$out = TJavaScript::renderScriptBlock('var y = 2;');
		self::assertStringContainsString('nonce="blkNonce"', $out);
	}

	public function testRenderScriptBlocksMultiple()
	{
		$out = TJavaScript::renderScriptBlocks(['var a = 1;', 'var b = 2;']);
		self::assertStringContainsString('var a = 1;', $out);
		self::assertStringContainsString('var b = 2;', $out);
		self::assertStringContainsString('<script', $out);
		self::assertStringContainsString('</script>', $out);
	}

	public function testRenderScriptBlocksEmptyReturnsEmptyString()
	{
		self::assertEquals('', TJavaScript::renderScriptBlocks([]));
	}
}
