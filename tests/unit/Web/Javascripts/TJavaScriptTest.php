<?php

/**
 * TJavaScriptTest
 *
 * Unit tests for {@see \Prado\Web\Javascripts\TJavaScript}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

require_once __DIR__ . '/../../PradoUnitRequires.php';

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\Javascripts\TJavaScriptString;

class TJavaScriptTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Fixtures
	// -----------------------------------------------------------------------

	private const REMOTE = 'https://cdn.example.com/script.js';
	private const LOCAL = '/js/local.js';

	protected function setUp(): void
	{
		TJavaScript::setScriptNonce(null);
		$this->resetScriptIntegrity();
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
		$this->resetScriptIntegrity();
	}

	/** Clears the static integrity registry between tests. */
	private function resetScriptIntegrity(): void
	{
		PradoUnit::setStaticProp(TJavaScript::class, '_scriptIntegrity', []);
	}

	// -----------------------------------------------------------------------
	// getScriptNonce / setScriptNonce
	// -----------------------------------------------------------------------

	public function testScriptNonceDefaultsToNull(): void
	{
		$this->assertNull(TJavaScript::getScriptNonce());
	}

	public function testSetAndGetScriptNonce(): void
	{
		TJavaScript::setScriptNonce('abc123');
		$this->assertSame('abc123', TJavaScript::getScriptNonce());
	}

	public function testSetScriptNonceOverwritesPrevious(): void
	{
		TJavaScript::setScriptNonce('first');
		TJavaScript::setScriptNonce('second');
		$this->assertSame('second', TJavaScript::getScriptNonce());
	}

	public function testSetScriptNonceToNull(): void
	{
		TJavaScript::setScriptNonce('abc123');
		TJavaScript::setScriptNonce(null);
		$this->assertNull(TJavaScript::getScriptNonce());
	}

	// -----------------------------------------------------------------------
	// hasScriptIntegrity / setScriptIntegrity / getScriptIntegrity
	// -----------------------------------------------------------------------

	public function testHasScriptIntegrityReturnsFalseWhenNotRegistered(): void
	{
		$this->assertFalse(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	public function testHasScriptIntegrityReturnsTrueAfterSet(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertTrue(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	public function testHasScriptIntegrityReturnsFalseAfterClear(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		$this->assertFalse(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	public function testHasScriptIntegrityDoesNotAffectOtherUrls(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertFalse(TJavaScript::hasScriptIntegrity('https://other.example.com/script.js'));
	}

	public function testGetScriptIntegrityUnregisteredReturnsNull(): void
	{
		$this->assertNull(TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityBareDigestDefaultHashMethod(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'AAABBBCCC');
		$this->assertSame('sha384-AAABBBCCC', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityBareDigestCustomHashMethod(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'AAABBBCCC', 'sha256');
		$this->assertSame('sha256-AAABBBCCC', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityBareDigestSha512(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'AAABBBCCC', 'sha512');
		$this->assertSame('sha512-AAABBBCCC', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityFullSriStoredAsIs(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-AAABBBCCC');
		$this->assertSame('sha384-AAABBBCCC', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	/**
	 * When a full `algo-hash` SRI string is passed, the `$hashMethod` parameter
	 * must be ignored — the stored value must be the SRI string unchanged.
	 */
	public function testSetScriptIntegrityFullSriIgnoresHashMethodParam(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha256-HASH', 'sha512');
		$this->assertSame('sha256-HASH', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityMultipleUrlsIndependent(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/a.js', 'HASH_A');
		TJavaScript::setScriptIntegrity('https://cdn.example.com/b.js', 'sha256-HASH_B');
		$this->assertSame('sha384-HASH_A', TJavaScript::getScriptIntegrity('https://cdn.example.com/a.js'));
		$this->assertSame('sha256-HASH_B', TJavaScript::getScriptIntegrity('https://cdn.example.com/b.js'));
	}

	public function testGetScriptIntegrityDifferentUrlReturnsNull(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'HASH');
		$this->assertNull(TJavaScript::getScriptIntegrity('https://other.example.com/script.js'));
	}

	public function testSetScriptIntegrityOverwritesExisting(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'FIRST');
		TJavaScript::setScriptIntegrity(self::REMOTE, 'SECOND');
		$this->assertSame('sha384-SECOND', TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityNullClearsRegistration(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		$this->assertNull(TJavaScript::getScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityNullOnUnregisteredUrlIsNoop(): void
	{
		// Clearing a URL that was never registered must not throw or corrupt state.
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		$this->assertNull(TJavaScript::getScriptIntegrity(self::REMOTE));
		$this->assertFalse(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	public function testSetScriptIntegrityNullOnlyAffectsTargetUrl(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/a.js', 'sha384-HASH_A');
		TJavaScript::setScriptIntegrity('https://cdn.example.com/b.js', 'sha384-HASH_B');
		TJavaScript::setScriptIntegrity('https://cdn.example.com/a.js', null);
		$this->assertNull(TJavaScript::getScriptIntegrity('https://cdn.example.com/a.js'));
		$this->assertSame('sha384-HASH_B', TJavaScript::getScriptIntegrity('https://cdn.example.com/b.js'));
	}

	/**
	 * After clearing with null, re-registering with a new hash must work normally.
	 */
	public function testSetScriptIntegrityCanReRegisterAfterClear(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-OLD');
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha256-NEW');
		$this->assertSame('sha256-NEW', TJavaScript::getScriptIntegrity(self::REMOTE));
		$this->assertTrue(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	/**
	 * renderScriptFile must not emit integrity attributes after the hash has been
	 * cleared with null.
	 */
	public function testRenderScriptFileNoIntegrityAfterClear(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
		$output = TJavaScript::renderScriptFile(self::REMOTE);
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	// -----------------------------------------------------------------------
	// URL normalization in the integrity registry
	// -----------------------------------------------------------------------

	public function testSetIntegrityProtocolRelativeNormalized(): void
	{
		// Registered as https://, looked up as protocol-relative — same key.
		TJavaScript::setScriptIntegrity('https://cdn.example.com/script.js', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('//cdn.example.com/script.js'));
	}

	public function testSetIntegrityProtocolRelativeCanBeRegisteredAndLookedUpEitherWay(): void
	{
		// Registered as protocol-relative, looked up as https://.
		TJavaScript::setScriptIntegrity('//cdn.example.com/script.js', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	public function testSetIntegrityMixedCaseSchemeAndHostNormalized(): void
	{
		TJavaScript::setScriptIntegrity('HTTPS://CDN.EXAMPLE.COM/script.js', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	public function testSetIntegrityDefaultPortStripped(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com:443/script.js', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	public function testSetIntegrityFragmentStripped(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/script.js#section', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	public function testSetIntegrityQueryStringPreserved(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/script.js?v=3.7.1', 'sha384-HASH');
		// Same query — hit.
		$this->assertSame('sha384-HASH', TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js?v=3.7.1'));
		// Different query — miss.
		$this->assertNull(TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js?v=3.6.0'));
		// No query at all — miss.
		$this->assertNull(TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	public function testHasScriptIntegrityNormalized(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/script.js', 'sha384-HASH');
		$this->assertTrue(TJavaScript::hasScriptIntegrity('HTTPS://CDN.EXAMPLE.COM:443/script.js#frag'));
	}

	public function testClearIntegrityNormalized(): void
	{
		TJavaScript::setScriptIntegrity('https://cdn.example.com/script.js', 'sha384-HASH');
		// Clear via a syntactically different but equivalent URL.
		TJavaScript::setScriptIntegrity('HTTPS://CDN.EXAMPLE.COM:443/script.js', null);
		$this->assertNull(TJavaScript::getScriptIntegrity('https://cdn.example.com/script.js'));
	}

	// -----------------------------------------------------------------------
	// renderScriptHeader
	// -----------------------------------------------------------------------

	public function testRenderScriptHeaderContainsScriptTag(): void
	{
		$output = TJavaScript::renderScriptHeader();
		$this->assertStringStartsWith('<script', $output);
	}

	public function testRenderScriptHeaderContainsCdataOpen(): void
	{
		$output = TJavaScript::renderScriptHeader();
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
	}

	public function testRenderScriptHeaderOmitsNonceWhenNotSet(): void
	{
		$output = TJavaScript::renderScriptHeader();
		$this->assertStringNotContainsString('nonce', $output);
	}

	public function testRenderScriptHeaderIncludesRegisteredNonce(): void
	{
		TJavaScript::setScriptNonce('testnonce');
		$output = TJavaScript::renderScriptHeader();
		$this->assertStringContainsString('nonce="testnonce"', $output);
	}

	/**
	 * An explicitly passed `nonce` attribute takes precedence over the
	 * registered nonce.
	 */
	public function testRenderScriptHeaderExplicitNonceTakesPrecedence(): void
	{
		TJavaScript::setScriptNonce('registered');
		$output = TJavaScript::renderScriptHeader(['nonce' => 'explicit']);
		$this->assertStringContainsString('nonce="explicit"', $output);
		$this->assertStringNotContainsString('nonce="registered"', $output);
	}

	/**
	 * A null value for the `nonce` key falls through `??=` to the registered
	 * nonce.
	 */
	public function testRenderScriptHeaderNullNonceKeyFallsBackToRegistered(): void
	{
		TJavaScript::setScriptNonce('fallback');
		$output = TJavaScript::renderScriptHeader(['nonce' => null]);
		$this->assertStringContainsString('nonce="fallback"', $output);
	}

	public function testRenderScriptHeaderPassesExtraAttributes(): void
	{
		$output = TJavaScript::renderScriptHeader(['type' => 'text/javascript']);
		$this->assertStringContainsString('type="text/javascript"', $output);
	}

	/**
	 * `nonce => false` is NOT replaced by `??=` (which only replaces null), so
	 * `false` propagates to buildHtmlAttributes() which omits it — effectively
	 * opting out of nonce injection even when a nonce is registered.
	 */
	public function testRenderScriptHeaderFalseNonceSuppressesRegisteredNonce(): void
	{
		TJavaScript::setScriptNonce('registered');
		$output = TJavaScript::renderScriptHeader(['nonce' => false]);
		$this->assertStringNotContainsString('nonce=', $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptFooter
	// -----------------------------------------------------------------------

	public function testRenderScriptFooterContainsCdataClose(): void
	{
		$this->assertStringContainsString('/*]]>*/', TJavaScript::renderScriptFooter());
	}

	public function testRenderScriptFooterContainsClosingTag(): void
	{
		$this->assertStringContainsString('</script>', TJavaScript::renderScriptFooter());
	}

	// -----------------------------------------------------------------------
	// renderScriptFile — string URL
	// -----------------------------------------------------------------------

	public function testRenderScriptFileLocalUrl(): void
	{
		$output = TJavaScript::renderScriptFile(self::LOCAL);
		$this->assertStringContainsString('src="' . self::LOCAL . '"', $output);
	}

	public function testRenderScriptFileEndsWithNewline(): void
	{
		$this->assertStringEndsWith("\n", TJavaScript::renderScriptFile(self::LOCAL));
	}

	public function testRenderScriptFileRemoteNoIntegrityNoAttributes(): void
	{
		$output = TJavaScript::renderScriptFile(self::REMOTE);
		$this->assertStringContainsString('src="' . self::REMOTE . '"', $output);
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	public function testRenderScriptFileIncludesRegisteredNonce(): void
	{
		TJavaScript::setScriptNonce('somenonce');
		$this->assertStringContainsString('nonce="somenonce"', TJavaScript::renderScriptFile(self::REMOTE));
	}

	public function testRenderScriptFileOmitsNonceWhenNotSet(): void
	{
		$this->assertStringNotContainsString('nonce', TJavaScript::renderScriptFile(self::REMOTE));
	}

	public function testRenderScriptFileRemoteWithRegisteredIntegrity(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-HASH');
		$output = TJavaScript::renderScriptFile(self::REMOTE);
		$this->assertStringContainsString('integrity="sha384-HASH"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	/**
	 * Integrity attributes must not be emitted for local URLs even when an
	 * entry exists in the registry for that path.
	 */
	public function testRenderScriptFileLocalUrlIntegritySuppressed(): void
	{
		TJavaScript::setScriptIntegrity(self::LOCAL, 'sha384-HASH');
		$output = TJavaScript::renderScriptFile(self::LOCAL);
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptFile — TJavaScriptAsset
	// -----------------------------------------------------------------------

	public function testRenderScriptFileWithAssetDelegatesToAsset(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$output = TJavaScript::renderScriptFile($asset);
		$this->assertStringContainsString('src="' . self::REMOTE . '"', $output);
	}

	public function testRenderScriptFileWithAssetEndsWithNewline(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertStringEndsWith("\n", TJavaScript::renderScriptFile($asset));
	}

	public function testRenderScriptFileWithAsyncAssetIncludesAsync(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE, true);
		$this->assertStringContainsString(' async', TJavaScript::renderScriptFile($asset));
	}

	public function testRenderScriptFileAssetPicksUpRegisteredNonce(): void
	{
		TJavaScript::setScriptNonce('mynonce');
		$output = TJavaScript::renderScriptFile(new TJavaScriptAsset(self::REMOTE));
		$this->assertStringContainsString('nonce="mynonce"', $output);
	}

	public function testRenderScriptFileAssetWithIntegrityAndNonce(): void
	{
		TJavaScript::setScriptNonce('mynonce');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-MYHASH');
		$output = TJavaScript::renderScriptFile($asset);
		$this->assertStringContainsString('nonce="mynonce"', $output);
		$this->assertStringContainsString('integrity="sha384-MYHASH"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptFiles
	// -----------------------------------------------------------------------

	public function testRenderScriptFilesMultiple(): void
	{
		$output = TJavaScript::renderScriptFiles([
			'https://cdn.example.com/a.js',
			'https://cdn.example.com/b.js',
		]);
		$this->assertStringContainsString('src="https://cdn.example.com/a.js"', $output);
		$this->assertStringContainsString('src="https://cdn.example.com/b.js"', $output);
	}

	public function testRenderScriptFilesEmptyArrayReturnsEmptyString(): void
	{
		$this->assertSame('', TJavaScript::renderScriptFiles([]));
	}

	public function testRenderScriptFilesPreservesOrder(): void
	{
		$output = TJavaScript::renderScriptFiles([
			'https://cdn.example.com/first.js',
			'https://cdn.example.com/second.js',
		]);
		$this->assertLessThan(
			strpos($output, 'second.js'),
			strpos($output, 'first.js')
		);
	}

	public function testRenderScriptFilesWithMixedStringAndAssets(): void
	{
		$asset = new TJavaScriptAsset('https://cdn.example.com/b.js');
		$output = TJavaScript::renderScriptFiles([
			'https://cdn.example.com/a.js',
			$asset,
		]);
		$this->assertStringContainsString('src="https://cdn.example.com/a.js"', $output);
		$this->assertStringContainsString('src="https://cdn.example.com/b.js"', $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptBlocks
	// -----------------------------------------------------------------------

	public function testRenderScriptBlocksMultipleScripts(): void
	{
		$output = TJavaScript::renderScriptBlocks(['alert(1)', 'alert(2)']);
		$this->assertStringContainsString('alert(1)', $output);
		$this->assertStringContainsString('alert(2)', $output);
	}

	public function testRenderScriptBlocksWrapsCdataSection(): void
	{
		$output = TJavaScript::renderScriptBlocks(['x=1']);
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
		$this->assertStringContainsString('/*]]>*/', $output);
	}

	public function testRenderScriptBlocksEmptyReturnsEmptyString(): void
	{
		$this->assertSame('', TJavaScript::renderScriptBlocks([]));
	}

	public function testRenderScriptBlocksIncludesNonce(): void
	{
		TJavaScript::setScriptNonce('blocknonce');
		$output = TJavaScript::renderScriptBlocks(['x=1']);
		$this->assertStringContainsString('nonce="blocknonce"', $output);
	}

	public function testRenderScriptBlocksContainsScriptTag(): void
	{
		$output = TJavaScript::renderScriptBlocks(['x=1']);
		$this->assertStringContainsString('<script', $output);
		$this->assertStringContainsString('</script>', $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptBlocksCallback
	// -----------------------------------------------------------------------

	public function testRenderScriptBlocksCallbackMultipleScripts(): void
	{
		$output = TJavaScript::renderScriptBlocksCallback(['alert(1)', 'alert(2)']);
		$this->assertStringContainsString('alert(1)', $output);
		$this->assertStringContainsString('alert(2)', $output);
	}

	public function testRenderScriptBlocksCallbackNoScriptTags(): void
	{
		$output = TJavaScript::renderScriptBlocksCallback(['x=1']);
		$this->assertStringNotContainsString('<script', $output);
		$this->assertStringNotContainsString('CDATA', $output);
	}

	public function testRenderScriptBlocksCallbackEmptyReturnsEmptyString(): void
	{
		$this->assertSame('', TJavaScript::renderScriptBlocksCallback([]));
	}

	public function testRenderScriptBlocksCallbackEndsWithNewline(): void
	{
		$output = TJavaScript::renderScriptBlocksCallback(['x=1']);
		$this->assertStringEndsWith("\n", $output);
	}

	// -----------------------------------------------------------------------
	// renderScriptBlock
	// -----------------------------------------------------------------------

	public function testRenderScriptBlockWrapsInHeaderAndFooter(): void
	{
		$output = TJavaScript::renderScriptBlock('alert(1)');
		$this->assertStringContainsString('<script', $output);
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
		$this->assertStringContainsString('alert(1)', $output);
		$this->assertStringContainsString('/*]]>*/', $output);
		$this->assertStringContainsString('</script>', $output);
	}

	public function testRenderScriptBlockIncludesNonce(): void
	{
		TJavaScript::setScriptNonce('mynonce');
		$output = TJavaScript::renderScriptBlock('x=1');
		$this->assertStringContainsString('nonce="mynonce"', $output);
	}

	/**
	 * renderScriptHeader() followed by renderScriptFooter() must produce a
	 * complete, balanced script block.
	 */
	public function testRenderScriptHeaderAndFooterPaired(): void
	{
		$output = TJavaScript::renderScriptHeader() . 'x=1;' . TJavaScript::renderScriptFooter();
		$this->assertStringContainsString('<script', $output);
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
		$this->assertStringContainsString('x=1;', $output);
		$this->assertStringContainsString('/*]]>*/', $output);
		$this->assertStringContainsString('</script>', $output);
	}

	// -----------------------------------------------------------------------
	// quoteString
	// -----------------------------------------------------------------------

	public function testQuoteStringReturnsJsonEncodedValue(): void
	{
		$this->assertSame('"hello"', TJavaScript::quoteString('hello'));
	}

	public function testQuoteStringEscapesDoubleQuotesViaHexEscape(): void
	{
		$output = TJavaScript::quoteString('say "hi"');
		$this->assertStringNotContainsString('"hi"', $output);
		$this->assertStringContainsString('\\u0022', $output);
	}

	public function testQuoteStringEscapesSingleQuotesViaHexEscape(): void
	{
		$output = TJavaScript::quoteString("it's");
		$this->assertStringContainsString('\\u0027', $output);
	}

	public function testQuoteStringEscapesHtmlTagsViaHexEscape(): void
	{
		$output = TJavaScript::quoteString('<script>');
		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString('\\u003C', $output);
	}

	public function testQuoteStringEscapesGreaterThanViaHexEscape(): void
	{
		$output = TJavaScript::quoteString('<b>bold</b>');
		// JSON_HEX_TAG covers both < and >
		$this->assertStringContainsString('\\u003E', $output);
	}

	public function testQuoteStringEmptyString(): void
	{
		$this->assertSame('""', TJavaScript::quoteString(''));
	}

	// -----------------------------------------------------------------------
	// quoteJsLiteral
	// -----------------------------------------------------------------------

	public function testQuoteJsLiteralWrapsStringInLiteral(): void
	{
		$result = TJavaScript::quoteJsLiteral('function(){}');
		$this->assertInstanceOf(TJavaScriptLiteral::class, $result);
	}

	public function testQuoteJsLiteralIdempotentOnExistingLiteral(): void
	{
		$literal = new TJavaScriptLiteral('function(){}');
		$result = TJavaScript::quoteJsLiteral($literal);
		$this->assertSame($literal, $result);
	}

	// -----------------------------------------------------------------------
	// isJsLiteral
	// -----------------------------------------------------------------------

	public function testIsJsLiteralTrueForLiteralInstance(): void
	{
		$this->assertTrue(TJavaScript::isJsLiteral(new TJavaScriptLiteral('fn()')));
	}

	public function testIsJsLiteralFalseForString(): void
	{
		$this->assertFalse(TJavaScript::isJsLiteral('fn()'));
	}

	public function testIsJsLiteralFalseForNull(): void
	{
		$this->assertFalse(TJavaScript::isJsLiteral(null));
	}

	public function testIsJsLiteralFalseForInteger(): void
	{
		$this->assertFalse(TJavaScript::isJsLiteral(42));
	}

	// -----------------------------------------------------------------------
	// encode
	// -----------------------------------------------------------------------

	public function testEncodeString(): void
	{
		$this->assertSame('"hello"', TJavaScript::encode('hello'));
	}

	public function testEncodeEmptyString(): void
	{
		$this->assertSame('""', TJavaScript::encode(''));
	}

	public function testEncodeBoolTrue(): void
	{
		$this->assertSame('true', TJavaScript::encode(true));
	}

	public function testEncodeBoolFalse(): void
	{
		$this->assertSame('false', TJavaScript::encode(false));
	}

	public function testEncodeNull(): void
	{
		$this->assertSame('null', TJavaScript::encode(null));
	}

	public function testEncodeInteger(): void
	{
		$this->assertSame('42', TJavaScript::encode(42));
	}

	public function testEncodeNegativeInteger(): void
	{
		$this->assertSame('-7', TJavaScript::encode(-7));
	}

	public function testEncodeFloat(): void
	{
		$result = TJavaScript::encode(3.14);
		$this->assertStringContainsString('3', $result);
		$this->assertStringContainsString('14', $result);
	}

	public function testEncodePositiveInfinity(): void
	{
		$this->assertSame('Number.POSITIVE_INFINITY', TJavaScript::encode(INF));
	}

	public function testEncodeNegativeInfinity(): void
	{
		$this->assertSame('Number.NEGATIVE_INFINITY', TJavaScript::encode(-INF));
	}

	public function testEncodeIndexedArray(): void
	{
		$this->assertSame('["a","b","c"]', TJavaScript::encode(['a', 'b', 'c']));
	}

	public function testEncodeEmptyArray(): void
	{
		$this->assertSame('[]', TJavaScript::encode([]));
	}

	public function testEncodeAssocArray(): void
	{
		$result = TJavaScript::encode(['key' => 'val']);
		$this->assertStringContainsString("'key'", $result);
		$this->assertStringContainsString('"val"', $result);
	}

	public function testEncodeAssocArrayBraces(): void
	{
		$result = TJavaScript::encode(['a' => 1]);
		$this->assertStringStartsWith('{', $result);
		$this->assertStringEndsWith('}', $result);
	}

	public function testEncodeIndexedArrayBrackets(): void
	{
		$result = TJavaScript::encode([1, 2]);
		$this->assertStringStartsWith('[', $result);
		$this->assertStringEndsWith(']', $result);
	}

	public function testEncodeNestedArray(): void
	{
		$result = TJavaScript::encode(['a' => ['b', 'c']]);
		$this->assertStringContainsString("'a'", $result);
		$this->assertStringContainsString('["b","c"]', $result);
	}

	public function testEncodeLiteralNotQuoted(): void
	{
		$literal = TJavaScript::quoteJsLiteral('function(){}');
		$this->assertSame('function(){}', TJavaScript::encode($literal));
	}

	public function testEncodeObjectUsesGetObjectVars(): void
	{
		$obj = new \stdClass();
		$obj->key = 'val';
		$result = TJavaScript::encode($obj);
		$this->assertStringContainsString("'key'", $result);
		$this->assertStringContainsString('"val"', $result);
	}

	/**
	 * Empty string values are silently skipped in arrays by default
	 * (encodeEmptyStrings = false).
	 */
	public function testEncodeEmptyStringInArraySkippedByDefault(): void
	{
		$this->assertSame('[]', TJavaScript::encode(['']));
	}

	/**
	 * Empty string values must be included when encodeEmptyStrings is true.
	 */
	public function testEncodeEmptyStringInArrayIncludedWhenFlagSet(): void
	{
		$this->assertSame('[""]', TJavaScript::encode([''], true, true));
	}

	public function testEncodeEmptyStringInAssocArraySkippedByDefault(): void
	{
		$result = TJavaScript::encode(['key' => '']);
		$this->assertSame('{}', $result);
	}

	public function testEncodeEmptyStringInAssocArrayIncludedWhenFlagSet(): void
	{
		$result = TJavaScript::encode(['key' => ''], true, true);
		$this->assertStringContainsString("'key'", $result);
		$this->assertStringContainsString('""', $result);
	}

	public function testEncodeMixedEmptyAndNonEmptyIndexedArraySkipsEmptyByDefault(): void
	{
		// The empty string is silently skipped; only 'x' survives — the result is
		// a single-element list, not an empty list.
		$result = TJavaScript::encode(['', 'x']);
		$this->assertSame('["x"]', $result);
	}

	public function testEncodeMixedEmptyAndNonEmptyAssocArraySkipsEmptyByDefault(): void
	{
		$result = TJavaScript::encode(['a' => '', 'b' => 'x']);
		$this->assertSame("{'b':\"x\"}", $result);
	}

	public function testEncodeNanFloat(): void
	{
		// NaN is a float that is neither INF nor -INF, so it falls through to the
		// default: branch and produces "NAN" via string interpolation. This documents
		// the silent fallback — callers should validate inputs before encoding.
		$this->assertSame('NAN', TJavaScript::encode(NAN));
	}

	public function testEncodeUnrecognizedTypeReturnsEmptyString(): void
	{
		// The else-branch at the end of encode() returns '' for types not covered
		// by any earlier branch (resource, etc.). This is the documented silent
		// fallback for backward compatibility.
		$resource = fopen('php://memory', 'r');
		try {
			$result = TJavaScript::encode($resource);
			$this->assertSame('', $result);
		} finally {
			fclose($resource);
		}
	}

	// -----------------------------------------------------------------------
	// jsonEncode / jsonDecode
	// -----------------------------------------------------------------------

	public function testJsonEncodeDecodeRoundtrip(): void
	{
		$data = ['key' => 'value', 'num' => 42, 'flag' => true];
		$json = TJavaScript::jsonEncode($data);
		$decoded = TJavaScript::jsonDecode($json, true);
		$this->assertSame($data, $decoded);
	}

	public function testJsonEncodeProducesValidJson(): void
	{
		$json = TJavaScript::jsonEncode(['a' => 1]);
		$this->assertJson($json);
	}

	public function testJsonDecodeInvalidJsonThrows(): void
	{
		$this->expectException(\JsonException::class);
		TJavaScript::jsonDecode('{not valid json}');
	}

	public function testJsonEncodeInvalidValueThrows(): void
	{
		$this->expectException(\JsonException::class);
		TJavaScript::jsonEncode(INF); // INF is not JSON-serializable
	}

	public function testJsonDecodeAssocTrue(): void
	{
		$json = '{"a":1}';
		$result = TJavaScript::jsonDecode($json, true);
		$this->assertIsArray($result);
		$this->assertSame(1, $result['a']);
	}

	public function testJsonDecodeAssocFalseReturnsObject(): void
	{
		$json = '{"a":1}';
		$result = TJavaScript::jsonDecode($json, false);
		$this->assertIsObject($result);
		$this->assertSame(1, $result->a);
	}

	// -----------------------------------------------------------------------
	// jsonEncode — globalization charset handling
	// -----------------------------------------------------------------------

	/**
	 * Temporarily swaps the application's globalization module for the
	 * duration of a callback. Passing `null` detaches it — `setGlobalization`
	 * itself only accepts a TGlobalization, so the swap goes through the
	 * private `_globalization` property via {@see PradoUnit::setProp()}.
	 * @param ?string $charset
	 * @param callable $fn
	 */
	private function withGlobalizationCharset(?string $charset, callable $fn): mixed
	{
		$app = Prado::getApplication();
		$original = PradoUnit::getProp($app, '_globalization');
		try {
			if ($charset === null) {
				PradoUnit::setProp($app, '_globalization', null);
			} else {
				$g = new \Prado\I18N\TGlobalization();
				$g->setCharset($charset);
				PradoUnit::setProp($app, '_globalization', $g);
			}
			return $fn();
		} finally {
			PradoUnit::setProp($app, '_globalization', $original);
		}
	}

	public function testJsonEncodeSkipsTranscodingWhenCharsetIsNotIconvName(): void
	{
		// Regression for the Windows CI failure: 'fr' is a locale code, not an
		// iconv encoding. jsonEncode used to call iconv('fr', ...) → warning →
		// TPhpErrorException. With the guard, the charset is recognised as not
		// being an iconv encoding and the value passes through unchanged.
		$this->withGlobalizationCharset('fr', function () {
			$json = TJavaScript::jsonEncode(['status' => 404, 'title' => 'Not Found']);
			$this->assertJson($json);
			$this->assertSame(
				['status' => 404, 'title' => 'Not Found'],
				json_decode($json, true)
			);
		});
	}

	public function testJsonEncodeSkipsTranscodingForEmptyCharset(): void
	{
		$this->withGlobalizationCharset('', function () {
			$json = TJavaScript::jsonEncode(['ok' => true]);
			$this->assertSame('{"ok":true}', $json);
		});
	}

	public function testJsonEncodeWithUtf8CharsetSkipsConversion(): void
	{
		$this->withGlobalizationCharset('UTF-8', function () {
			// json_encode escapes non-ASCII by default; decode-and-compare to
			// keep the test independent of the escaping flag.
			$json = TJavaScript::jsonEncode(['n' => 'naïve']);
			$this->assertSame(['n' => 'naïve'], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesStringsForValidIconvCharset(): void
	{
		// 'Café' encoded in ISO-8859-1 (Latin-1).
		$latin1 = "Caf" . chr(0xE9);
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$json = TJavaScript::jsonEncode(['name' => $latin1]);
			$this->assertSame(['name' => 'Café'], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesNestedArrayStrings(): void
	{
		$latin1 = chr(0xE9); // é in Latin-1
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$json = TJavaScript::jsonEncode(['a' => ['b' => $latin1]]);
			$this->assertSame(['a' => ['b' => 'é']], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesStdClassProperties(): void
	{
		// New: convertToUtf8 used to skip objects entirely; it now recurses
		// through stdClass-shaped values too.
		$latin1 = chr(0xE9);
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$obj = new \stdClass();
			$obj->label = $latin1;
			$json = TJavaScript::jsonEncode($obj);
			$decoded = json_decode($json, true);
			$this->assertSame('é', $decoded['label']);
		});
	}

	public function testJsonEncodeNonStdClassObjectsPassThrough(): void
	{
		// Objects of arbitrary classes are not transcoded — json_encode's own
		// rules apply. Verify the call still succeeds when globalization is
		// in a non-UTF-8 (but valid) mode.
		$this->withGlobalizationCharset('ISO-8859-1', function () {
			$obj = new class () {
				public string $field = 'plain';
			};
			$json = TJavaScript::jsonEncode($obj);
			$this->assertJson($json);
			$decoded = json_decode($json, true);
			$this->assertSame('plain', $decoded['field']);
		});
	}

	public function testJsonEncodeWithoutGlobalizationStaysQuiet(): void
	{
		// When no globalization module is attached, the charset path is not
		// consulted at all — the result is identical to a vanilla json_encode.
		$this->withGlobalizationCharset(null, function () {
			$this->assertSame('{"x":1}', TJavaScript::jsonEncode(['x' => 1]));
		});
	}

	public function testJsonDecodeAcceptsStringTypeStrictly(): void
	{
		// Sanity check that the tightened signature still round-trips the
		// canonical happy path used by callers across the framework.
		$this->assertSame(['k' => 'v'], TJavaScript::jsonDecode('{"k":"v"}', true));
	}

	// -----------------------------------------------------------------------
	// encode — $toMap parameter (backward-compatibility, unused)
	// -----------------------------------------------------------------------

	/**
	 * The $toMap parameter has no effect on the output; the associative/sequential
	 * distinction is determined solely by the array keys. Passing false must not
	 * change a map into a list.
	 */
	public function testEncodeToMapFalseHasNoEffectOnAssocArray(): void
	{
		$result = TJavaScript::encode(['a' => 1], false);
		$this->assertStringStartsWith('{', $result);
		$this->assertStringEndsWith('}', $result);
	}

	public function testEncodeToMapTrueHasNoEffectOnIndexedArray(): void
	{
		$result = TJavaScript::encode([1, 2, 3], true);
		$this->assertStringStartsWith('[', $result);
		$this->assertStringEndsWith(']', $result);
	}

	// -----------------------------------------------------------------------
	// JSMin
	// -----------------------------------------------------------------------

	public function testJSMinRemovesUnnecessaryWhitespace(): void
	{
		$input = "function hello() {\n    return 1;\n}";
		$minified = TJavaScript::JSMin($input);
		$this->assertStringContainsString('function hello()', $minified);
		$this->assertStringContainsString('return 1', $minified);
		$this->assertShorterThanOrEqual(strlen($input), strlen($minified));
	}

	public function testJSMinRemovesSingleLineComments(): void
	{
		$input = "// this is a comment\nvar x = 1;";
		$minified = TJavaScript::JSMin($input);
		$this->assertStringNotContainsString('this is a comment', $minified);
		$this->assertStringContainsString('x=1', $minified);
	}

	public function testJSMinRemovesMultiLineComments(): void
	{
		$input = "/* block comment */\nvar y = 2;";
		$minified = TJavaScript::JSMin($input);
		$this->assertStringNotContainsString('block comment', $minified);
		$this->assertStringContainsString('y=2', $minified);
	}

	public function testJSMinEmptyStringReturnsEmpty(): void
	{
		$this->assertSame('', trim(TJavaScript::JSMin('')));
	}

	// Helper: assert $actual length is ≤ $expected (minified must not grow).
	private function assertShorterThanOrEqual(int $expected, int $actual): void
	{
		$this->assertLessThanOrEqual($expected, $actual);
	}

	// -----------------------------------------------------------------------
	// TJavaScriptLiteral — direct unit tests
	// -----------------------------------------------------------------------

	public function testLiteralConstructorAndToString(): void
	{
		$lit = new TJavaScriptLiteral('alert(1)');
		$this->assertSame('alert(1)', (string) $lit);
	}

	public function testLiteralToJavaScriptLiteralReturnsRawString(): void
	{
		$lit = new TJavaScriptLiteral('function(){}');
		$this->assertSame('function(){}', $lit->toJavaScriptLiteral());
	}

	public function testLiteralToJavaScriptLiteralDoesNotReEncode(): void
	{
		// A string that would be JSON-encoded if passed to encode() is returned
		// verbatim when wrapped in TJavaScriptLiteral.
		$raw = 'alert("hello")';
		$lit = new TJavaScriptLiteral($raw);
		$this->assertSame($raw, $lit->toJavaScriptLiteral());
	}

	public function testLiteralEncodePassesThroughUnquoted(): void
	{
		// encode() must delegate to toJavaScriptLiteral() for TJavaScriptLiteral
		// instances, producing the raw expression rather than a JSON string.
		$lit = new TJavaScriptLiteral('myFn()');
		$this->assertSame('myFn()', TJavaScript::encode($lit));
	}

	// -----------------------------------------------------------------------
	// TJavaScriptString — direct unit tests
	// -----------------------------------------------------------------------

	public function testStringToJavaScriptLiteralJsonEncodesValue(): void
	{
		$str = new TJavaScriptString('hello');
		// toJavaScriptLiteral() produces a JSON-encoded string: "hello"
		$this->assertSame('"hello"', $str->toJavaScriptLiteral());
	}

	public function testStringToJavaScriptLiteralHexEscapesSpecialChars(): void
	{
		// TJavaScriptString encodes its value with HTML-safe hex escaping via
		// json_encode + JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG.
		// The output must differ from a plain json_encode (which would keep < literally).
		$str = new TJavaScriptString('<b>');
		$result = $str->toJavaScriptLiteral();
		$plainEncoded = json_encode('<b>');
		$this->assertNotSame($plainEncoded, $result);
	}

	public function testStringToStringReturnsBareValue(): void
	{
		// __toString() is inherited from TJavaScriptLiteral and returns the raw
		// stored string — NOT the JSON-encoded form.
		$str = new TJavaScriptString('raw');
		$this->assertSame('raw', (string) $str);
	}

	public function testStringIsInstanceOfLiteral(): void
	{
		$this->assertInstanceOf(TJavaScriptLiteral::class, new TJavaScriptString('x'));
	}

	public function testStringEncodeViaEncodeDelegatesToToJavaScriptLiteral(): void
	{
		// encode() must delegate to toJavaScriptLiteral() for TJavaScriptString,
		// producing the same output as calling the method directly.
		$str = new TJavaScriptString('hello');
		$this->assertSame($str->toJavaScriptLiteral(), TJavaScript::encode($str));
	}
}
