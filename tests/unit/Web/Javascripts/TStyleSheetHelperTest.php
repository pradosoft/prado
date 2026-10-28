<?php

/**
 * TStyleSheetHelperTest
 *
 * Unit tests for {@see \Prado\Web\Javascripts\TStyleSheetHelper}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Javascripts;

use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TStyleSheetHelper;

class TStyleSheetHelperTest extends \PHPUnit\Framework\TestCase
{
	private const REMOTE = 'https://cdn.example.com/style.css';
	private const LOCAL = '/css/local.css';

	protected function setUp(): void
	{
		$this->reset();
	}

	protected function tearDown(): void
	{
		$this->reset();
	}

	private function reset(): void
	{
		TJavaScript::setScriptNonce(null);
		TStyleSheetHelper::setRequireStyleIntegrity(false);
		PradoUnit::setStaticProp(TStyleSheetHelper::class, '_styleIntegrity', []);
	}

	// -----------------------------------------------------------------------
	// getStyleNonce
	// -----------------------------------------------------------------------

	public function testStyleNonceDefaultsToNull(): void
	{
		$this->assertNull(TStyleSheetHelper::getStyleNonce());
	}

	public function testStyleNonceIsTheScriptNonce(): void
	{
		TJavaScript::setScriptNonce('abc123');
		$this->assertSame('abc123', TStyleSheetHelper::getStyleNonce());
	}

	// -----------------------------------------------------------------------
	// hasStyleIntegrity / setStyleIntegrity / getStyleIntegrity
	// -----------------------------------------------------------------------

	public function testStyleIntegrityRegistry(): void
	{
		$this->assertFalse(TStyleSheetHelper::hasStyleIntegrity(self::REMOTE));
		$this->assertNull(TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertTrue(TStyleSheetHelper::hasStyleIntegrity(self::REMOTE));
		$this->assertSame('sha384-HASH', TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
		$this->assertFalse(TStyleSheetHelper::hasStyleIntegrity('https://other.example.com/style.css'));
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, null);
		$this->assertFalse(TStyleSheetHelper::hasStyleIntegrity(self::REMOTE));
	}

	public function testStyleIntegrityBareDigestGetsMethodPrefix(): void
	{
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'AAABBB');
		$this->assertSame('sha384-AAABBB', TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'AAABBB', 'sha512');
		$this->assertSame('sha512-AAABBB', TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'sha256-CCC', 'sha512');
		$this->assertSame('sha256-CCC', TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
	}

	public function testStyleIntegrityNormalizesUrl(): void
	{
		TStyleSheetHelper::setStyleIntegrity('HTTPS://CDN.example.com:443/style.css#frag', 'sha384-HASH');
		$this->assertSame('sha384-HASH', TStyleSheetHelper::getStyleIntegrity(self::REMOTE));
	}

	public function testStyleIntegrityIsSeparateFromScriptIntegrity(): void
	{
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertFalse(TJavaScript::hasScriptIntegrity(self::REMOTE));
	}

	// -----------------------------------------------------------------------
	// renderStyleHeader / renderStyleFooter / blocks
	// -----------------------------------------------------------------------

	public function testRenderStyleHeaderWithoutNonce(): void
	{
		$this->assertSame("<style type=\"text/css\">\n/*<![CDATA[*/\n", TStyleSheetHelper::renderStyleHeader());
	}

	public function testRenderStyleHeaderIncludesNonce(): void
	{
		TJavaScript::setScriptNonce('n0nce');
		$this->assertSame("<style type=\"text/css\" nonce=\"n0nce\">\n/*<![CDATA[*/\n", TStyleSheetHelper::renderStyleHeader());
	}

	public function testRenderStyleHeaderExplicitNonceTakesPrecedence(): void
	{
		TJavaScript::setScriptNonce('registered');
		$this->assertStringContainsString('nonce="explicit"', TStyleSheetHelper::renderStyleHeader(['nonce' => 'explicit']));
		$this->assertStringNotContainsString('nonce=', TStyleSheetHelper::renderStyleHeader(['nonce' => false]));
	}

	public function testRenderStyleHeaderPassesExtraAttributes(): void
	{
		$this->assertSame("<style type=\"text/css\" media=\"print\">\n/*<![CDATA[*/\n", TStyleSheetHelper::renderStyleHeader(['media' => 'print']));
	}

	public function testRenderStyleFooter(): void
	{
		$this->assertSame("\n/*]]>*/\n</style>\n", TStyleSheetHelper::renderStyleFooter());
	}

	public function testRenderStyleBlock(): void
	{
		TJavaScript::setScriptNonce('n0nce');
		$this->assertSame("<style type=\"text/css\" nonce=\"n0nce\">\n/*<![CDATA[*/\n.a{}\n/*]]>*/\n</style>\n", TStyleSheetHelper::renderStyleBlock('.a{}'));
	}

	public function testRenderStyleBlocks(): void
	{
		$this->assertSame('', TStyleSheetHelper::renderStyleBlocks([]));
		$this->assertSame("<style type=\"text/css\">\n/*<![CDATA[*/\n.a{}\n.b{}\n/*]]>*/\n</style>\n", TStyleSheetHelper::renderStyleBlocks(['.a{}', '.b{}']));
	}

	// -----------------------------------------------------------------------
	// renderStyleSheetFile / renderStyleSheetFiles
	// -----------------------------------------------------------------------

	public function testRenderStyleSheetFileLocal(): void
	{
		$this->assertSame("<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/local.css\" />\n", TStyleSheetHelper::renderStyleSheetFile(self::LOCAL));
	}

	public function testRenderStyleSheetFileMediaAndEncoding(): void
	{
		$this->assertSame(
			"<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"/a.css?x=1&amp;y=&quot;2&quot;\" />\n",
			TStyleSheetHelper::renderStyleSheetFile('/a.css?x=1&y="2"', 'print')
		);
	}

	public function testRenderStyleSheetFileIncludesNonce(): void
	{
		TJavaScript::setScriptNonce('n0nce');
		$this->assertStringContainsString(' nonce="n0nce"', TStyleSheetHelper::renderStyleSheetFile(self::LOCAL));
	}

	public function testRenderStyleSheetFileRemoteWithoutIntegrity(): void
	{
		$html = TStyleSheetHelper::renderStyleSheetFile(self::REMOTE);
		$this->assertStringNotContainsString('integrity', $html);
		$this->assertStringNotContainsString('crossorigin', $html);
	}

	public function testRenderStyleSheetFileRemoteWithIntegrity(): void
	{
		TJavaScript::setScriptNonce('n0nce');
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertSame(
			"<link rel=\"stylesheet\" type=\"text/css\" href=\"https://cdn.example.com/style.css\" nonce=\"n0nce\" integrity=\"sha384-HASH\" crossorigin=\"anonymous\" />\n",
			TStyleSheetHelper::renderStyleSheetFile(self::REMOTE)
		);
	}

	public function testRenderStyleSheetFileLocalIgnoresIntegrity(): void
	{
		TStyleSheetHelper::setStyleIntegrity(self::LOCAL, 'sha384-HASH');
		$this->assertStringNotContainsString('integrity', TStyleSheetHelper::renderStyleSheetFile(self::LOCAL));
	}

	public function testRequireStyleIntegrity(): void
	{
		$this->assertFalse(TStyleSheetHelper::getRequireStyleIntegrity());
		TStyleSheetHelper::setRequireStyleIntegrity(true);
		$this->assertTrue(TStyleSheetHelper::getRequireStyleIntegrity());
		$this->assertStringContainsString('href="/css/local.css"', TStyleSheetHelper::renderStyleSheetFile(self::LOCAL));
		TStyleSheetHelper::setStyleIntegrity(self::REMOTE, 'sha384-HASH');
		$this->assertStringContainsString('integrity="sha384-HASH"', TStyleSheetHelper::renderStyleSheetFile(self::REMOTE));
	}

	public function testRequireStyleIntegrityThrowsForUnpinnedRemote(): void
	{
		TStyleSheetHelper::setRequireStyleIntegrity(true);
		try {
			TStyleSheetHelper::renderStyleSheetFile(self::REMOTE);
			$this->fail('expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('stylesheet_integrity_required', $e->getErrorCode());
		}
	}

	public function testRequireStyleIntegrityIsSeparateFromScripts(): void
	{
		TJavaScript::setRequireScriptIntegrity(true);
		try {
			$this->assertStringContainsString('href="https://cdn.example.com/style.css"', TStyleSheetHelper::renderStyleSheetFile(self::REMOTE));
		} finally {
			TJavaScript::setRequireScriptIntegrity(false);
		}
	}

	public function testRenderStyleSheetFiles(): void
	{
		$this->assertSame('', TStyleSheetHelper::renderStyleSheetFiles([]));
		$this->assertSame(
			"<link rel=\"stylesheet\" type=\"text/css\" href=\"/a.css\" />\n<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"/b.css\" />\n",
			TStyleSheetHelper::renderStyleSheetFiles(['a' => '/a.css', 'b' => ['/b.css', 'print']])
		);
	}
}
