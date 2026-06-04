<?php

/**
 * THttpHeaderTest
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\HttpHeaders\THttpHeader;
use Prado\Web\THttpHeaderName;

/**
 * Tests for {@see THttpHeader}.
 *
 * Covers name, value, replace override, auto-detection delegation, string
 * coercion, and the {@see THttpHeader::__toString()} rendering inherited from
 * {@see \Prado\Web\HttpHeaders\TBaseHttpHeader}.
 */
class THttpHeaderTest extends PHPUnit\Framework\TestCase
{
	private THttpHeader $h;

	protected function setUp(): void
	{
		$this->h = new THttpHeader();
	}

	// =========================================================================
	// HeaderName — defaults and round-trip
	// =========================================================================

	public function testGetHeaderNameDefaultIsEmptyString(): void
	{
		self::assertSame('', $this->h->getHeaderName());
	}

	public function testSetHeaderNameAndGetHeaderNameRoundTrip(): void
	{
		$this->h->setHeaderName('X-Custom-Header');
		self::assertSame('X-Custom-Header', $this->h->getHeaderName());
	}

	public function testSetHeaderNameOverwritesPreviousValue(): void
	{
		$this->h->setHeaderName('X-First');
		$this->h->setHeaderName('X-Second');
		self::assertSame('X-Second', $this->h->getHeaderName());
	}

	public function testSetHeaderNameCoercesIntegerToString(): void
	{
		$this->h->setHeaderName(42);
		self::assertSame('42', $this->h->getHeaderName());
	}

	public function testSetHeaderNameAcceptsEmptyString(): void
	{
		$this->h->setHeaderName('X-Temp');
		$this->h->setHeaderName('');
		self::assertSame('', $this->h->getHeaderName());
	}

	public function testSetHeaderNameTrimsLeadingAndTrailingWhitespace(): void
	{
		// Untrimmed names would silently break strcasecmp-based manager lookups.
		$this->h->setHeaderName('  X-Custom-Header  ');
		self::assertSame('X-Custom-Header', $this->h->getHeaderName());
	}

	public function testSetHeaderNameTrimsTabsAndNewlines(): void
	{
		$this->h->setHeaderName("\tContent-Type\n");
		self::assertSame('Content-Type', $this->h->getHeaderName());
	}

	// =========================================================================
	// HeaderValue — defaults and round-trip
	// =========================================================================

	public function testGetHeaderValueDefaultIsEmptyString(): void
	{
		self::assertSame('', $this->h->getHeaderValue());
	}

	public function testSetHeaderValueAndGetHeaderValueRoundTrip(): void
	{
		$this->h->setHeaderValue('text/html; charset=UTF-8');
		self::assertSame('text/html; charset=UTF-8', $this->h->getHeaderValue());
	}

	public function testSetHeaderValueOverwritesPreviousValue(): void
	{
		$this->h->setHeaderValue('first');
		$this->h->setHeaderValue('second');
		self::assertSame('second', $this->h->getHeaderValue());
	}

	public function testSetHeaderValueCoercesIntegerToString(): void
	{
		$this->h->setHeaderValue(0);
		self::assertSame('0', $this->h->getHeaderValue());
	}

	public function testSetHeaderValueAcceptsEmptyString(): void
	{
		$this->h->setHeaderValue('nosniff');
		$this->h->setHeaderValue('');
		self::assertSame('', $this->h->getHeaderValue());
	}

	public function testSetHeaderValuePreservesSpecialCharacters(): void
	{
		$this->h->setHeaderValue("default-src 'self'; script-src 'nonce-abc'");
		self::assertSame("default-src 'self'; script-src 'nonce-abc'", $this->h->getHeaderValue());
	}

	// =========================================================================
	// getReplace — no override set: delegates to base-class auto-detection
	// =========================================================================

	public function testGetReplaceDefaultReturnsTrueForArbitraryHeader(): void
	{
		$this->h->setHeaderName('X-Custom');
		self::assertTrue($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsTrueForContentType(): void
	{
		$this->h->setHeaderName(THttpHeaderName::ContentType);
		self::assertTrue($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsFalseForSetCookie(): void
	{
		$this->h->setHeaderName(THttpHeaderName::SetCookie);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsFalseForLink(): void
	{
		$this->h->setHeaderName(THttpHeaderName::Link);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsFalseForWWWAuthenticate(): void
	{
		$this->h->setHeaderName(THttpHeaderName::WWWAuthenticate);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsFalseForContentSecurityPolicy(): void
	{
		$this->h->setHeaderName(THttpHeaderName::ContentSecurityPolicy);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceDefaultReturnsFalseForCspReportOnly(): void
	{
		$this->h->setHeaderName(THttpHeaderName::ContentSecurityPolicyReportOnly);
		self::assertFalse($this->h->getReplace());
	}

	// =========================================================================
	// setReplace / getReplace — explicit override
	// =========================================================================

	public function testSetReplaceTrueOverridesAutoDetectionForNonReplacingHeader(): void
	{
		// Set-Cookie would normally return false; explicit true overrides it.
		$this->h->setHeaderName(THttpHeaderName::SetCookie);
		$this->h->setReplace(true);
		self::assertTrue($this->h->getReplace());
	}

	public function testSetReplaceFalseOverridesAutoDetectionForSingletonHeader(): void
	{
		// Content-Type would normally return true; explicit false overrides it.
		$this->h->setHeaderName(THttpHeaderName::ContentType);
		$this->h->setReplace(false);
		self::assertFalse($this->h->getReplace());
	}

	public function testSetReplaceNullRevertsToAutoDetectionTrue(): void
	{
		$this->h->setHeaderName('X-Custom');
		$this->h->setReplace(false);
		$this->h->setReplace(null);
		// Auto-detection for X-Custom → true.
		self::assertTrue($this->h->getReplace());
	}

	public function testSetReplaceNullRevertsToAutoDetectionFalse(): void
	{
		$this->h->setHeaderName(THttpHeaderName::SetCookie);
		$this->h->setReplace(true);
		$this->h->setReplace(null);
		// Auto-detection for Set-Cookie → false.
		self::assertFalse($this->h->getReplace());
	}

	// =========================================================================
	// setReplace — string coercion via TPropertyValue::ensureBoolean
	// =========================================================================

	public function testSetReplaceTrueStringCoercedToTrue(): void
	{
		$this->h->setReplace('true');
		self::assertTrue($this->h->getReplace());
	}

	public function testSetReplaceFalseStringCoercedToFalse(): void
	{
		$this->h->setHeaderName('X-Custom');
		$this->h->setReplace('false');
		self::assertFalse($this->h->getReplace());
	}

	public function testSetReplaceOneStringCoercedToTrue(): void
	{
		$this->h->setReplace('1');
		self::assertTrue($this->h->getReplace());
	}

	public function testSetReplaceZeroStringCoercedToFalse(): void
	{
		$this->h->setHeaderName('X-Custom');
		$this->h->setReplace('0');
		self::assertFalse($this->h->getReplace());
	}

	public function testSetReplaceNonTrueNonNumericStringCoercedToFalse(): void
	{
		// ensureBoolean only treats 'true' (case-insensitive) and non-zero
		// numeric strings as true; all other strings coerce to false.
		$this->h->setHeaderName('X-Custom');
		foreach (['on', 'off', 'yes', 'no', 'enabled', 'disabled'] as $word) {
			$this->h->setReplace($word);
			self::assertFalse($this->h->getReplace(), "Expected false for '$word'");
		}
	}

	// =========================================================================
	// setReplace — false is not null (null-coalescing semantics)
	// =========================================================================

	public function testExplicitFalseDoesNotFallThroughToParent(): void
	{
		// If ?? were falsy instead of null-coalescing, false would delegate to
		// parent::getReplace() for 'X-Custom' and return true.  It must not.
		$this->h->setHeaderName('X-Custom');
		$this->h->setReplace(false);
		self::assertFalse($this->h->getReplace());
	}

	public function testExplicitTrueDoesNotFallThroughToParent(): void
	{
		// Set-Cookie auto-detects as false; explicit true must win.
		$this->h->setHeaderName(THttpHeaderName::SetCookie);
		$this->h->setReplace(true);
		self::assertTrue($this->h->getReplace());
	}

	// =========================================================================
	// __toString — inherited from TBaseHttpHeader
	// =========================================================================

	public function testToStringReturnsNameColonSpaceValue(): void
	{
		$this->h->setHeaderName('X-Frame-Options');
		$this->h->setHeaderValue('DENY');
		self::assertSame('X-Frame-Options: DENY', (string) $this->h);
	}

	public function testToStringWithBothEmpty(): void
	{
		self::assertSame(': ', (string) $this->h);
	}

	public function testToStringWithComplexValue(): void
	{
		$this->h->setHeaderName(THttpHeaderName::ContentSecurityPolicy);
		$this->h->setHeaderValue("default-src 'self'; img-src *");
		self::assertSame(
			"Content-Security-Policy: default-src 'self'; img-src *",
			(string) $this->h
		);
	}

	// =========================================================================
	// Lifecycle hooks — no-op, must not throw
	// =========================================================================

	public function testInitDoesNotThrow(): void
	{
		$this->h->init([]);
		$this->addToAssertionCount(1);
	}

	public function testInitCompleteDoesNotThrow(): void
	{
		$this->h->initComplete();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderDoesNotThrow(): void
	{
		$this->h->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	// =========================================================================
	// setHeaderValue — null coercion
	// =========================================================================

	public function testSetHeaderValueAcceptsNull(): void
	{
		// THttpHeader::setHeaderValue() casts the value to string; null must coerce
		// to the empty string rather than throwing a TypeError.
		$this->h->setHeaderValue(null);
		self::assertSame('', $this->h->getHeaderValue());
	}
}
