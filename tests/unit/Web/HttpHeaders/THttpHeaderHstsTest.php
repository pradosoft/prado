<?php

/**
 * THttpHeaderHstsTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeaderHsts}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\HttpHeaders\THttpHeaderHsts;
use Prado\Web\THttpHeaderName;

class THttpHeaderHstsTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// getHeaderName
	// -----------------------------------------------------------------------

	public function testGetHeaderNameReturnsStrictTransportSecurity(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertSame('Strict-Transport-Security', $hsts->getHeaderName());
		self::assertSame(THttpHeaderName::StrictTransportSecurity, $hsts->getHeaderName());
	}

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public function testDefaultMaxAgeIsOneYear(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertSame(31536000, $hsts->getMaxAge());
	}

	public function testDefaultIncludeSubDomainsFalse(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertFalse($hsts->getIncludeSubDomains());
	}

	public function testDefaultPreloadFalse(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertFalse($hsts->getPreload());
	}

	// -----------------------------------------------------------------------
	// getHeaderValue
	// -----------------------------------------------------------------------

	public function testGetHeaderValueDefaultContainsMaxAge(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertStringContainsString('max-age=31536000', $hsts->getHeaderValue());
	}

	public function testGetHeaderValueDefaultIsJustMaxAge(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertSame('max-age=31536000', $hsts->getHeaderValue());
	}

	public function testGetHeaderValueCustomMaxAge(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge(63072000);
		self::assertSame('max-age=63072000', $hsts->getHeaderValue());
	}

	public function testGetHeaderValueZeroMaxAge(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge(0);
		self::assertSame('max-age=0', $hsts->getHeaderValue());
	}

	public function testGetHeaderValueWithIncludeSubDomains(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		self::assertSame('max-age=31536000; includeSubDomains', $hsts->getHeaderValue());
	}

	public function testGetHeaderValueWithPreloadAndIncludeSubDomains(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setPreload(true);
		self::assertSame('max-age=31536000; includeSubDomains; preload', $hsts->getHeaderValue());
	}

	/**
	 * The value is still rendered even when Preload is set without IncludeSubDomains;
	 * the developer is warned via the log channel in finalizeHeader().
	 */
	public function testGetHeaderValuePreloadWithoutIncludeSubDomainsStillRendersPreload(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload(true);
		self::assertStringContainsString('preload', $hsts->getHeaderValue());
		self::assertStringNotContainsString('includeSubDomains', $hsts->getHeaderValue());
	}

	/**
	 * Directive order must be: max-age, includeSubDomains, preload.
	 */
	public function testGetHeaderValueDirectiveOrder(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setPreload(true);
		$value = $hsts->getHeaderValue();
		self::assertLessThan(strpos($value, 'includeSubDomains'), strpos($value, 'max-age'));
		self::assertLessThan(strpos($value, 'preload'), strpos($value, 'includeSubDomains'));
	}

	// -----------------------------------------------------------------------
	// setMaxAge / getMaxAge
	// -----------------------------------------------------------------------

	public function testSetMaxAgeIntZero(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge(0);
		self::assertSame(0, $hsts->getMaxAge());
	}

	public function testSetMaxAgeStringIsCoerced(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge('7776000');
		self::assertSame(7776000, $hsts->getMaxAge());
	}

	public function testSetMaxAgeOverwritesPrevious(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge(100);
		$hsts->setMaxAge(200);
		self::assertSame(200, $hsts->getMaxAge());
	}

	public function testSetMaxAgeNegativeValueIsAccepted(): void
	{
		// setMaxAge() delegates to TPropertyValue::ensureInteger() which coerces
		// any integer-like value including negatives; the class does not validate
		// the range — callers are responsible for meaningful values.
		$hsts = new THttpHeaderHsts();
		$hsts->setMaxAge(-1);
		self::assertSame(-1, $hsts->getMaxAge());
	}

	// -----------------------------------------------------------------------
	// setIncludeSubDomains / getIncludeSubDomains
	// -----------------------------------------------------------------------

	public function testSetIncludeSubDomainsTrueString(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains('true');
		self::assertTrue($hsts->getIncludeSubDomains());
	}

	public function testSetIncludeSubDomainsFalseString(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains('true');
		$hsts->setIncludeSubDomains('false');
		self::assertFalse($hsts->getIncludeSubDomains());
	}

	public function testSetIncludeSubDomainsOneString(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains('1');
		self::assertTrue($hsts->getIncludeSubDomains());
	}

	// -----------------------------------------------------------------------
	// setPreload / getPreload
	// -----------------------------------------------------------------------

	public function testSetPreloadTrueString(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload('true');
		self::assertTrue($hsts->getPreload());
	}

	public function testSetPreloadFalseString(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload('true');
		$hsts->setPreload('false');
		self::assertFalse($hsts->getPreload());
	}

	// -----------------------------------------------------------------------
	// getReplace — HSTS is a singleton header so replace must be true
	// -----------------------------------------------------------------------

	public function testGetReplaceReturnsTrue(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertTrue($hsts->getReplace());
	}

	// -----------------------------------------------------------------------
	// finalizeHeader — preload without includeSubDomains logs a warning (no throw)
	// -----------------------------------------------------------------------

	public function testFinalizeHeaderWithPreloadAndIncludeSubDomainsDoesNotThrow(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setPreload(true);
		$hsts->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderPreloadWithoutIncludeSubDomainsDoesNotThrow(): void
	{
		// Misconfiguration is warned via log, not by throwing.
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload(true);
		$hsts->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderDefaultPropertiesDoesNotThrow(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------
	// init — no-op by default, must not throw
	// -----------------------------------------------------------------------

	public function testInitWithNullDoesNotThrow(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->init(null);
		$this->addToAssertionCount(1);
	}

	public function testInitWithNullPreservesDefaults(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->init(null);
		self::assertSame(31536000, $hsts->getMaxAge());
		self::assertFalse($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testInitDoesNotThrow(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->init([]);
		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — parsing
	// -----------------------------------------------------------------------

	public function testSetHeaderValueMaxAgeOnly(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=86400');
		self::assertSame(86400, $hsts->getMaxAge());
		self::assertFalse($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testSetHeaderValueMaxAgeWithIncludeSubDomains(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=63072000; includeSubDomains');
		self::assertSame(63072000, $hsts->getMaxAge());
		self::assertTrue($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testSetHeaderValueAllThreeTokens(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=31536000; includeSubDomains; preload');
		self::assertSame(31536000, $hsts->getMaxAge());
		self::assertTrue($hsts->getIncludeSubDomains());
		self::assertTrue($hsts->getPreload());
	}

	public function testSetHeaderValueCaseInsensitive(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('MAX-AGE=7776000; IncludeSubDomains; PRELOAD');
		self::assertSame(7776000, $hsts->getMaxAge());
		self::assertTrue($hsts->getIncludeSubDomains());
		self::assertTrue($hsts->getPreload());
	}

	public function testSetHeaderValueZeroMaxAge(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=0');
		self::assertSame(0, $hsts->getMaxAge());
	}

	public function testSetHeaderValueUnknownTokensAreIgnored(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=31536000; unknownToken; includeSubDomains');
		self::assertSame(31536000, $hsts->getMaxAge());
		self::assertTrue($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testSetHeaderValueEmptyStringLeavesDefaultsUnchanged(): void
	{
		// An empty string produces no tokens after PREG_SPLIT_NO_EMPTY; the
		// pre-existing defaults (31536000 / false / false) must survive intact.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('');
		self::assertSame(31536000, $hsts->getMaxAge());
		self::assertFalse($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testSetHeaderValueWhitespaceOnlyLeavesDefaultsUnchanged(): void
	{
		// Whitespace-only input is trimmed to '' before the split; no tokens must
		// emerge and the object must not mutate — same as the empty-string case.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('   ');
		self::assertSame(31536000, $hsts->getMaxAge());
		self::assertFalse($hsts->getIncludeSubDomains());
		self::assertFalse($hsts->getPreload());
	}

	public function testSetHeaderValueWithSpacesAroundEqualsSign(): void
	{
		// The regex allows optional whitespace around the '=' in max-age.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age = 3600');
		self::assertSame(3600, $hsts->getMaxAge());
	}

	public function testSetHeaderValueTokensInReverseOrder(): void
	{
		// The parser iterates all semicolon-separated tokens regardless of order.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('preload; includeSubDomains; max-age=9999');
		self::assertSame(9999, $hsts->getMaxAge());
		self::assertTrue($hsts->getIncludeSubDomains());
		self::assertTrue($hsts->getPreload());
	}

	public function testSetHeaderValueRoundTripIsIdempotent(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setPreload(true);
		$hsts->setMaxAge(63072000);
		$serialised = $hsts->getHeaderValue();

		$hsts2 = new THttpHeaderHsts();
		$hsts2->setHeaderValue($serialised);
		self::assertSame($hsts->getMaxAge(), $hsts2->getMaxAge());
		self::assertSame($hsts->getIncludeSubDomains(), $hsts2->getIncludeSubDomains());
		self::assertSame($hsts->getPreload(), $hsts2->getPreload());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringFormat(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$str = (string) $hsts;
		self::assertStringStartsWith('Strict-Transport-Security: ', $str);
		self::assertStringContainsString('max-age=31536000', $str);
		self::assertStringContainsString('includeSubDomains', $str);
	}

	public function testToStringDefaultHasNoIncludeSubDomains(): void
	{
		$hsts = new THttpHeaderHsts();
		self::assertSame('Strict-Transport-Security: max-age=31536000', (string) $hsts);
	}

	public function testToStringFullDirectives(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setPreload(true);
		self::assertSame(
			'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload',
			(string) $hsts
		);
	}

	// -----------------------------------------------------------------------
	// setIncludeSubDomains — native bool
	// -----------------------------------------------------------------------

	public function testSetIncludeSubDomainsNativeBoolTrue(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		self::assertTrue($hsts->getIncludeSubDomains());
	}

	public function testSetIncludeSubDomainsNativeBoolFalse(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setIncludeSubDomains(true);
		$hsts->setIncludeSubDomains(false);
		self::assertFalse($hsts->getIncludeSubDomains());
	}

	// -----------------------------------------------------------------------
	// setPreload — native bool
	// -----------------------------------------------------------------------

	public function testSetPreloadNativeBoolTrue(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload(true);
		self::assertTrue($hsts->getPreload());
	}

	public function testSetPreloadNativeBoolFalse(): void
	{
		$hsts = new THttpHeaderHsts();
		$hsts->setPreload(true);
		$hsts->setPreload(false);
		self::assertFalse($hsts->getPreload());
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — duplicate max-age and non-numeric max-age
	// -----------------------------------------------------------------------

	public function testSetHeaderValueDuplicateMaxAgeLastValueWins(): void
	{
		// When max-age appears more than once, each occurrence overwrites the
		// previous; the last parsed value must be the one stored.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=100; max-age=200');
		self::assertSame(200, $hsts->getMaxAge());
	}

	public function testSetHeaderValueMaxAgeWithNonNumericValueIsIgnored(): void
	{
		// The regex /^max-age\s*=\s*(\d+)$/i requires \d+; a non-numeric value
		// like 'abc' will not match, so the token is silently ignored and the
		// default max-age remains unchanged.
		$hsts = new THttpHeaderHsts();
		$hsts->setHeaderValue('max-age=abc');
		self::assertSame(31536000, $hsts->getMaxAge());
	}

	// -----------------------------------------------------------------------
	// init — XML element input
	// -----------------------------------------------------------------------

	public function testInitWithXmlElementDoesNotThrow(): void
	{
		// init() must tolerate a TXmlElement config node without throwing;
		// THttpHeaderHsts does not override init() so the base no-op is used.
		$hsts = new THttpHeaderHsts();
		$hsts->init(new \Prado\Xml\TXmlElement('header'));
		$this->addToAssertionCount(1);
	}
}
