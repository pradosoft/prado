<?php

/**
 * TJavaScriptAssetTest
 *
 * Unit tests for {@see \Prado\Web\Javascripts\TJavaScriptAsset}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;

/**
 * Exposes the protected Direct accessors of TJavaScriptAsset for unit testing.
 */
class TTestableJavaScriptAsset extends TJavaScriptAsset
{
	public function getIntegrityDirect(): null|false|string
	{
		return parent::getIntegrityDirect();
	}

	public function setIntegrityDirect(null|false|string $integrity): void
	{
		parent::setIntegrityDirect($integrity);
	}
}

class TJavaScriptAssetTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Fixtures
	// -----------------------------------------------------------------------

	/** Remote URL that triggers SRI / crossorigin attribute emission. */
	private const REMOTE = 'https://cdn.example.com/script.js';

	/** Local URL that suppresses SRI attribute emission. */
	private const LOCAL = '/js/local.js';

	protected function setUp(): void
	{
		// Clear global nonce and integrity registry so rendering is deterministic.
		TJavaScript::setScriptNonce(null);
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
		TJavaScript::setScriptIntegrity(self::REMOTE, null);
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testDefaultIntegrityValueConstantIsNull(): void
	{
		$this->assertNull(TJavaScriptAsset::DEFAULT_INTEGRITY_VALUE);
	}

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function testConstructorSetsUrlAndDefaultsAsyncToFalse(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertSame(self::REMOTE, $asset->getUrl());
		$this->assertFalse($asset->getAsync());
	}

	public function testConstructorWithExplicitAsyncTrue(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE, true);
		$this->assertTrue($asset->getAsync());
	}

	public function testConstructorWithExplicitAsyncFalse(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE, false);
		$this->assertFalse($asset->getAsync());
	}

	// -----------------------------------------------------------------------
	// Url getter / setter
	// -----------------------------------------------------------------------

	public function testSetUrlRoundTrip(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setUrl(self::LOCAL);
		$this->assertSame(self::LOCAL, $asset->getUrl());
	}

	public function testSetUrlOverwritesPrevious(): void
	{
		$asset = new TJavaScriptAsset('https://a.example.com/a.js');
		$asset->setUrl('https://b.example.com/b.js');
		$this->assertSame('https://b.example.com/b.js', $asset->getUrl());
	}

	public function testSetUrlAppearsInToString(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setUrl(self::LOCAL);
		$this->assertStringContainsString('src="' . self::LOCAL . '"', (string) $asset);
		$this->assertStringNotContainsString(self::REMOTE, (string) $asset);
	}

	// -----------------------------------------------------------------------
	// Async getter / setter
	// -----------------------------------------------------------------------

	public function testSetAsyncTrue(): void
	{
		$asset = new TJavaScriptAsset(self::LOCAL);
		$asset->setAsync(true);
		$this->assertTrue($asset->getAsync());
	}

	public function testSetAsyncFalse(): void
	{
		$asset = new TJavaScriptAsset(self::LOCAL, true);
		$asset->setAsync(false);
		$this->assertFalse($asset->getAsync());
	}

	// -----------------------------------------------------------------------
	// Integrity getter / setter
	// -----------------------------------------------------------------------

	public function testIntegrityDefaultsToNull(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertNull($asset->getIntegrity());
	}

	public function testSetIntegrityStoresValue(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-AAABBBCCC===');
		$this->assertSame('sha384-AAABBBCCC===', $asset->getIntegrity());
	}

	public function testSetIntegritySha256(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-AAABBBCCC');
		$this->assertSame('sha256-AAABBBCCC', $asset->getIntegrity());
	}

	public function testSetIntegrityNullClears(): void
	{
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-HASH');
		$asset->setIntegrity(null);
		$this->assertNull($asset->getIntegrityDirect());
	}

	public function testSetIntegrityNullClearedGetIntegrityReturnsNull(): void
	{
		// No registry entry either, so getIntegrity() must return null after clear.
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-HASH');
		$asset->setIntegrity(null);
		$this->assertNull($asset->getIntegrity());
	}

	public function testSetIntegrityOverwritesPrevious(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-FIRST');
		$asset->setIntegrity('sha256-SECOND');
		$this->assertSame('sha256-SECOND', $asset->getIntegrity());
	}

	// -----------------------------------------------------------------------
	// Integrity — false (explicit suppression)
	// -----------------------------------------------------------------------

	public function testSetIntegrityFalseGetIntegrityReturnsNull(): void
	{
		// false means "off" — getIntegrity() must resolve to null.
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$this->assertNull($asset->getIntegrity());
	}

	public function testSetIntegrityFalseGetIntegrityDirectReturnsFalse(): void
	{
		// The raw backing field must preserve false so the suppression is distinguishable
		// from the "not set" (null) state.
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$this->assertFalse($asset->getIntegrityDirect());
	}

	public function testSetIntegrityFalseSuppressesRegistryFallback(): void
	{
		// Even when the registry holds a value, false must prevent it from being used.
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$this->assertNull($asset->getIntegrity());
	}

	public function testSetIntegrityFalseCanBeOverwrittenByString(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$asset->setIntegrity('sha384-RESTORED');
		$this->assertSame('sha384-RESTORED', $asset->getIntegrity());
	}

	public function testSetIntegrityFalseCanBeResetToNull(): void
	{
		// Resetting to null re-enables the registry fallback.
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$asset->setIntegrity(null);
		$this->assertSame('sha384-REGISTRYHASH', $asset->getIntegrity());
	}

	// -----------------------------------------------------------------------
	// getIntegrityDirect / setIntegrityDirect
	// -----------------------------------------------------------------------

	public function testGetIntegrityDirectReturnsStoredField(): void
	{
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-HASH');
		$this->assertSame('sha384-HASH', $asset->getIntegrityDirect());
	}

	public function testGetIntegrityDirectDoesNotFallBackToRegistry(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		// No asset-level integrity set — direct accessor must return null.
		$this->assertNull($asset->getIntegrityDirect());
	}

	public function testSetIntegrityDirectWritesField(): void
	{
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrityDirect('sha512-DIRECTHASH');
		$this->assertSame('sha512-DIRECTHASH', $asset->getIntegrityDirect());
	}

	public function testSetIntegrityDirectNullClearsField(): void
	{
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrityDirect('sha384-HASH');
		$asset->setIntegrityDirect(null);
		$this->assertNull($asset->getIntegrityDirect());
	}

	public function testSetIntegrityDelegatesToSetIntegrityDirect(): void
	{
		// Verify the public setter and the direct setter are in sync.
		$asset = new TTestableJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-VIA_PUBLIC');
		$this->assertSame('sha256-VIA_PUBLIC', $asset->getIntegrityDirect());
	}

	// -----------------------------------------------------------------------
	// getIntegrity — registry fallback
	// -----------------------------------------------------------------------

	/**
	 * When the asset carries no integrity of its own but the URL is registered
	 * in TJavaScript::setScriptIntegrity(), getIntegrity() must return the
	 * registry value.
	 */
	public function testGetIntegrityFallsBackToRegistry(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertSame('sha384-REGISTRYHASH', $asset->getIntegrity());
	}

	public function testGetIntegrityAssetValueTakesPrecedenceOverRegistry(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-ASSETHASH');
		$this->assertSame('sha256-ASSETHASH', $asset->getIntegrity());
	}

	public function testGetIntegrityFallbackReappliedAfterClear(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-ASSETHASH');
		$asset->setIntegrity(null);
		$this->assertSame('sha384-REGISTRYHASH', $asset->getIntegrity());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringLocalUrlMinimal(): void
	{
		$asset = new TJavaScriptAsset(self::LOCAL);
		$this->assertSame('<script src="' . self::LOCAL . '"></script>', (string) $asset);
	}

	public function testToStringRemoteUrlNoIntegrity(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$output = (string) $asset;
		$this->assertStringContainsString('src="' . self::REMOTE . '"', $output);
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	public function testToStringAsyncTrueIncludesAttribute(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE, true);
		$this->assertStringContainsString(' async', (string) $asset);
	}

	public function testToStringAsyncFalseOmitsAttribute(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE, false);
		$this->assertStringNotContainsString('async', (string) $asset);
	}

	public function testToStringIncludesNonceWhenSet(): void
	{
		TJavaScript::setScriptNonce('abc123');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertStringContainsString('nonce="abc123"', (string) $asset);
	}

	public function testToStringOmitsNonceWhenNotSet(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertStringNotContainsString('nonce', (string) $asset);
	}

	public function testToStringRemoteUrlEmitsIntegrityAndCrossorigin(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha384-MYFINGERPRINT');
		$output = (string) $asset;
		$this->assertStringContainsString('integrity="sha384-MYFINGERPRINT"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	public function testToStringSha256IntegrityEmittedAsIs(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-MYFINGERPRINT');
		$output = (string) $asset;
		$this->assertStringContainsString('integrity="sha256-MYFINGERPRINT"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	/**
	 * Integrity attributes must be suppressed for local URLs regardless of
	 * whether an integrity value is set.
	 */
	public function testToStringLocalUrlIntegrityAttributesSuppressed(): void
	{
		$asset = new TJavaScriptAsset(self::LOCAL);
		$asset->setIntegrity('sha384-HASH');
		$output = (string) $asset;
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	/**
	 * Without an integrity value there must be no `crossorigin` attribute
	 * even for remote URLs.
	 */
	public function testToStringRemoteUrlNoIntegrityNoCrossorigin(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertStringNotContainsString('crossorigin', (string) $asset);
	}

	/**
	 * When integrity is explicitly suppressed with `false`, the rendered tag
	 * must omit both `integrity` and `crossorigin` even for remote URLs.
	 */
	public function testToStringIntegrityFalseSuppressesAttributes(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$output = (string) $asset;
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	/**
	 * `false` must suppress integrity even when the registry holds a value
	 * for the same URL.
	 */
	public function testToStringIntegrityFalseSuppressesRegistryValue(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity(false);
		$output = (string) $asset;
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	public function testToStringAllAttributesTogether(): void
	{
		TJavaScript::setScriptNonce('nonce123');
		$asset = new TJavaScriptAsset(self::REMOTE, true);
		$asset->setIntegrity('sha384-HASH');
		$output = (string) $asset;
		$this->assertStringContainsString('src="' . self::REMOTE . '"', $output);
		$this->assertStringContainsString(' async', $output);
		$this->assertStringContainsString('nonce="nonce123"', $output);
		$this->assertStringContainsString('integrity="sha384-HASH"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	public function testToStringProducesScriptTag(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$output = (string) $asset;
		$this->assertStringStartsWith('<script ', $output);
		$this->assertStringEndsWith('></script>', $output);
	}

	// -----------------------------------------------------------------------
	// __toString — TJavaScript registry fallback
	// -----------------------------------------------------------------------

	/**
	 * When the asset carries no integrity of its own but the URL is registered
	 * in TJavaScript::setScriptIntegrity(), the rendered tag must include the
	 * registry value verbatim.
	 */
	public function testToStringFallsBackToTJavaScriptRegistry(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$output = (string) $asset;
		$this->assertStringContainsString('integrity="sha384-REGISTRYHASH"', $output);
		$this->assertStringContainsString('crossorigin="anonymous"', $output);
	}

	/**
	 * The asset's own integrity takes precedence over the TJavaScript registry.
	 */
	public function testToStringAssetIntegrityTakesPrecedenceOverRegistry(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-ASSETHASH');
		$this->assertStringContainsString('integrity="sha256-ASSETHASH"', (string) $asset);
	}

	/**
	 * After the asset's own integrity is cleared, the registry fallback applies again.
	 */
	public function testToStringFallbackReappliedAfterClearingAssetIntegrity(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::REMOTE);
		$asset->setIntegrity('sha256-ASSETHASH');
		$asset->setIntegrity(null);
		$this->assertStringContainsString('integrity="sha384-REGISTRYHASH"', (string) $asset);
	}

	/**
	 * The registry fallback must be suppressed for local URLs, matching the same
	 * rule that suppresses the asset's own integrity for local URLs.
	 */
	public function testToStringRegistryFallbackSuppressedForLocalUrl(): void
	{
		TJavaScript::setScriptIntegrity(self::REMOTE, 'sha384-REGISTRYHASH');
		$asset = new TJavaScriptAsset(self::LOCAL);
		$output = (string) $asset;
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	/**
	 * No registry entry and no asset integrity — no integrity / crossorigin attributes.
	 */
	public function testToStringNoIntegrityWhenRegistryIsEmpty(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$output = (string) $asset;
		$this->assertStringNotContainsString('integrity', $output);
		$this->assertStringNotContainsString('crossorigin', $output);
	}

	/**
	 * __toString() itself must not append a trailing newline; only
	 * TJavaScript::renderScriptFile() adds one when delegating here.
	 */
	public function testToStringDoesNotAddTrailingNewline(): void
	{
		$asset = new TJavaScriptAsset(self::REMOTE);
		$this->assertStringEndsWith('></script>', (string) $asset);
	}

	/**
	 * Local URL + async must render both attributes correctly.
	 */
	public function testToStringLocalUrlWithAsync(): void
	{
		$asset = new TJavaScriptAsset(self::LOCAL, true);
		$output = (string) $asset;
		$this->assertStringContainsString('src="' . self::LOCAL . '"', $output);
		$this->assertStringContainsString(' async', $output);
		$this->assertStringNotContainsString('integrity', $output);
	}

	/**
	 * getIntegrity() reaches the TJavaScript registry via a normalized URL;
	 * a syntactically different but equivalent URL must resolve to the same entry.
	 */
	public function testGetIntegrityFallbackUsesNormalizedUrl(): void
	{
		// Register with explicit default port; asset URL has no port.
		TJavaScript::setScriptIntegrity('https://cdn.example.com:443/script.js', 'sha384-NORMHASH');
		$asset = new TJavaScriptAsset(self::REMOTE); // https://cdn.example.com/script.js
		$this->assertSame('sha384-NORMHASH', $asset->getIntegrity());
	}
}
