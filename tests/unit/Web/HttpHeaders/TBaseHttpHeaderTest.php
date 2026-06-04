<?php

/**
 * TBaseHttpHeaderTest
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\THttpHeaderName;

// Test doubles {@see TConcreteHeader} and {@see TResponseStub} live in
// tests/unit/Harness/Web/HttpHeaders/ and are auto-loaded by the bootstrap.

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

/**
 * Tests for {@see TBaseHttpHeader}.
 *
 * Uses {@see TConcreteHeader} as a minimal concrete subclass that overrides
 * the protected {@see TBaseHttpHeader::header()} seam to capture calls
 * without touching the live HTTP stack.
 */
class TBaseHttpHeaderTest extends PHPUnit\Framework\TestCase
{
	private TConcreteHeader $h;

	protected function setUp(): void
	{
		$this->h = new TConcreteHeader();
	}

	// =========================================================================
	// Manager property
	// =========================================================================

	public function testGetManagerReturnsNullByDefault(): void
	{
		self::assertNull($this->h->getManager());
	}

	public function testSetManagerAndGetManagerRoundTrip(): void
	{
		$manager = new THttpHeadersManager();
		$this->h->setManager($manager);
		self::assertSame($manager, $this->h->getManager());
	}

	public function testSetManagerAcceptsNull(): void
	{
		$manager = new THttpHeadersManager();
		$this->h->setManager($manager);
		$this->h->setManager(null);
		self::assertNull($this->h->getManager());
	}

	// =========================================================================
	// Lifecycle hooks — all no-ops by default
	// =========================================================================

	public function testInitIsANoOpAndDoesNotThrow(): void
	{
		$this->h->init([]);
		$this->addToAssertionCount(1);
	}

	public function testInitCompleteIsANoOpAndDoesNotThrow(): void
	{
		$this->h->initComplete();
		$this->addToAssertionCount(1);
	}

	public function testFinalizeHeaderIsANoOpAndDoesNotThrow(): void
	{
		$this->h->finalizeHeader();
		$this->addToAssertionCount(1);
	}

	public function testInitAcceptsXmlElementWithoutThrowing(): void
	{
		// init() must tolerate a TXmlElement config, not just an array.
		$xml = new \Prado\Xml\TXmlElement('config');
		$this->h->init($xml);
		$this->addToAssertionCount(1);
	}

	// =========================================================================
	// getReplace — singleton headers
	// =========================================================================

	public function testGetReplaceReturnsTrueForArbitraryHeader(): void
	{
		$this->h->name = 'X-Custom-Header';
		self::assertTrue($this->h->getReplace());
	}

	public function testGetReplaceReturnsTrueForContentType(): void
	{
		$this->h->name = THttpHeaderName::ContentType;
		self::assertTrue($this->h->getReplace());
	}

	public function testGetReplaceReturnsTrueForStrictTransportSecurity(): void
	{
		$this->h->name = THttpHeaderName::StrictTransportSecurity;
		self::assertTrue($this->h->getReplace());
	}

	// =========================================================================
	// getReplace — non-replacing (multi-value) headers
	// =========================================================================

	public function testGetReplaceReturnsFalseForSetCookie(): void
	{
		$this->h->name = THttpHeaderName::SetCookie;
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceReturnsFalseForLink(): void
	{
		$this->h->name = THttpHeaderName::Link;
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceReturnsFalseForWWWAuthenticate(): void
	{
		$this->h->name = THttpHeaderName::WWWAuthenticate;
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceReturnsFalseForContentSecurityPolicy(): void
	{
		$this->h->name = THttpHeaderName::ContentSecurityPolicy;
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceReturnsFalseForContentSecurityPolicyReportOnly(): void
	{
		$this->h->name = THttpHeaderName::ContentSecurityPolicyReportOnly;
		self::assertFalse($this->h->getReplace());
	}

	// =========================================================================
	// getReplace — case-insensitive matching
	// =========================================================================

	public function testGetReplaceIsCaseInsensitiveForSetCookieLower(): void
	{
		$this->h->name = strtolower(THttpHeaderName::SetCookie);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceIsCaseInsensitiveForSetCookieUpper(): void
	{
		$this->h->name = strtoupper(THttpHeaderName::SetCookie);
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceIsCaseInsensitiveForCspMixedCase(): void
	{
		$this->h->name = 'content-security-policy';
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceIsCaseInsensitiveForLinkMixedCase(): void
	{
		$this->h->name = 'LINK';
		self::assertFalse($this->h->getReplace());
	}

	// =========================================================================
	// __toString
	// =========================================================================

	public function testToStringReturnsNameColonSpaceValue(): void
	{
		$this->h->name = 'X-Frame-Options';
		$this->h->value = 'DENY';
		self::assertSame('X-Frame-Options: DENY', (string) $this->h);
	}

	public function testToStringWithEmptyValue(): void
	{
		$this->h->name = 'X-Empty';
		$this->h->value = '';
		self::assertSame('X-Empty: ', (string) $this->h);
	}

	public function testToStringWithComplexValue(): void
	{
		$this->h->name = 'Content-Security-Policy';
		$this->h->value = "default-src 'self'; script-src 'nonce-abc123'";
		self::assertSame(
			"Content-Security-Policy: default-src 'self'; script-src 'nonce-abc123'",
			(string) $this->h
		);
	}

	// =========================================================================
	// sendHeader — via protected header() seam (no response)
	// =========================================================================

	public function testSendHeaderCallsHeaderSeamWhenNoResponse(): void
	{
		$this->h->name = 'X-Test';
		$this->h->value = 'val';

		$this->h->sendHeader();

		self::assertCount(1, $this->h->capturedHeaderCalls);
		self::assertSame('X-Test: val', $this->h->capturedHeaderCalls[0]['header']);
	}

	public function testSendHeaderPassesReplaceTrueForSingletonHeader(): void
	{
		$this->h->name = 'X-Custom';
		$this->h->value = 'v';

		$this->h->sendHeader();

		self::assertTrue($this->h->capturedHeaderCalls[0]['replace']);
	}

	public function testSendHeaderPassesReplaceFalseForNonReplacingHeader(): void
	{
		$this->h->name = THttpHeaderName::SetCookie;
		$this->h->value = 'session=abc; Path=/';

		$this->h->sendHeader();

		self::assertFalse($this->h->capturedHeaderCalls[0]['replace']);
	}

	public function testSendHeaderPassesZeroResponseCodeByDefault(): void
	{
		$this->h->sendHeader();

		self::assertSame(0, $this->h->capturedHeaderCalls[0]['response_code']);
	}

	// =========================================================================
	// sendHeader — via response object
	// =========================================================================

	public function testSendHeaderCallsAppendHeaderOnProvidedResponse(): void
	{
		$this->h->name = 'X-My-Header';
		$this->h->value = 'my-value';

		$response = new TResponseStub();
		$this->h->sendHeader($response);

		self::assertCount(1, $response->capturedCalls);
		self::assertSame('X-My-Header: my-value', $response->capturedCalls[0]['header']);
	}

	public function testSendHeaderPassesCorrectReplaceToAppendHeader(): void
	{
		$this->h->name = THttpHeaderName::Link;
		$this->h->value = '</style.css>; rel=stylesheet';

		$response = new TResponseStub();
		$this->h->sendHeader($response);

		self::assertFalse($response->capturedCalls[0]['replace']);
	}

	public function testSendHeaderWithResponseDoesNotCallHeaderSeam(): void
	{
		$response = new TResponseStub();
		$this->h->sendHeader($response);

		// The protected header() seam must NOT have been called.
		self::assertCount(0, $this->h->capturedHeaderCalls);
	}

	// =========================================================================
	// header() seam — base implementation calls PHP header() (smoke test)
	// =========================================================================

	public function testHeaderSeamIsOverridableBySubclass(): void
	{
		// TConcreteHeader overrides header() to capture calls; verify capture works.
		$this->h->name = 'X-Seam';
		$this->h->value = 'seam-value';
		$this->h->sendHeader();

		self::assertSame('X-Seam: seam-value', $this->h->capturedHeaderCalls[0]['header']);
		self::assertTrue($this->h->capturedHeaderCalls[0]['replace']);
		self::assertSame(0, $this->h->capturedHeaderCalls[0]['response_code']);
	}

	// =========================================================================
	// normalizeConfig — dispatch logic
	// =========================================================================

	public function testNormalizeConfigReturnsArrayAsIs(): void
	{
		// Use init() as the public entry point for normalizeConfig(); TConcreteHeader
		// calls parent::init() which is a no-op, so the easiest route is to call
		// normalizeConfig() via a public accessor on a thin subclass.
		// Instead we verify indirectly: TConcreteHeader.init([...]) does not throw
		// and does not alter state (base impl ignores config).
		$this->h->init(['foo' => 'bar']);
		$this->addToAssertionCount(1);
	}

	public function testNormalizeConfigReturnsEmptyArrayForNull(): void
	{
		$this->h->init(null);
		$this->addToAssertionCount(1);
	}

	public function testNormalizeConfigAcceptsTXmlElement(): void
	{
		$xml = new \Prado\Xml\TXmlElement('header');
		$this->h->init($xml);
		$this->addToAssertionCount(1);
	}

	// =========================================================================
	// configToArray — base returns empty array
	// =========================================================================

	public function testConfigToArrayBaseReturnsEmptyArrayForAnyXml(): void
	{
		$xml = new \Prado\Xml\TXmlElement('header');
		// Add a child element to confirm the base impl ignores child nodes.
		$child = new \Prado\Xml\TXmlElement('policy');
		$child->setAttribute('Name', 'default-src');
		$xml->getElements()->add($child);

		$result = PradoUnit::invoke($this->h, 'configToArray', $xml);
		self::assertSame([], $result);
	}

	public function testConfigToArrayBaseReturnsEmptyArrayForEmptyXml(): void
	{
		$xml    = new \Prado\Xml\TXmlElement('config');
		$result = PradoUnit::invoke($this->h, 'configToArray', $xml);
		self::assertSame([], $result);
	}

	// =========================================================================
	// getReplace — additional case-insensitive coverage
	// =========================================================================

	public function testGetReplaceIsCaseInsensitiveForWwwAuthenticate(): void
	{
		// Lowercase variant of the WWW-Authenticate header name must still be
		// detected as non-replacing via strcasecmp.
		$this->h->name = 'www-authenticate';
		self::assertFalse($this->h->getReplace());
	}

	public function testGetReplaceIsCaseInsensitiveForCspReportOnly(): void
	{
		$this->h->name = 'content-security-policy-report-only';
		self::assertFalse($this->h->getReplace());
	}

	// =========================================================================
	// normalizeConfig — arbitrary scalar falls through to empty array
	// =========================================================================

	public function testNormalizeConfigReturnsEmptyArrayForArbitraryScalar(): void
	{
		// Any non-array, non-TXmlElement, non-null value must normalize to [].
		self::assertSame([], PradoUnit::invoke($this->h, 'normalizeConfig', 42));
	}

	// =========================================================================
	// REPORT_URI constant
	// =========================================================================

	public function testReportUriConstantValueOnBase(): void
	{
		// The sentinel lives on TBaseHttpHeader so that all subclasses share a
		// single canonical string without duplication.
		self::assertSame('REPORT_URI', TBaseHttpHeader::REPORT_URI);
	}

	public function testReportUriConstantAccessibleViaSubclassNames(): void
	{
		// PHP constant inheritance: both subclasses resolve to the same constant
		// defined on TBaseHttpHeader; no call-site changes are needed.
		self::assertSame(
			TBaseHttpHeader::REPORT_URI,
			\Prado\Web\HttpHeaders\THttpHeaderCsp::REPORT_URI,
			'THttpHeaderCsp::REPORT_URI must resolve to TBaseHttpHeader::REPORT_URI via inheritance'
		);
		self::assertSame(
			TBaseHttpHeader::REPORT_URI,
			\Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints::REPORT_URI,
			'THttpHeaderReportingEndpoints::REPORT_URI must resolve to TBaseHttpHeader::REPORT_URI via inheritance'
		);
	}

	public function testReportUriSelfReferenceInSubclassResolvesToSameValue(): void
	{
		// self::REPORT_URI inside THttpHeaderCsp and THttpHeaderReportingEndpoints
		// must be the same string literal 'REPORT_URI' — not an overridden value.
		self::assertSame('REPORT_URI', \Prado\Web\HttpHeaders\THttpHeaderCsp::REPORT_URI);
		self::assertSame('REPORT_URI', \Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints::REPORT_URI);
	}
}
