<?php

/**
 * THttpHeaderReportingEndpointsTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints;
use Prado\Web\THttpHeaderName;

// NOTE: REPORT_URI is defined on THttpHeaderBase and inherited by all header subclasses.
// THttpHeaderReportingEndpoints::REPORT_URI resolves to 'REPORT_URI' via inheritance.
// A blank endpoint URL is normalized to this sentinel at storage time (addEndpoint()).
// THttpHeadersManager::finalizeReporterService() replaces it with the live reporter URL.

class THttpHeaderReportingEndpointsTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// getHeaderName
	// -----------------------------------------------------------------------

	public function testGetHeaderNameReturnsReportingEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		self::assertSame('Reporting-Endpoints', $re->getHeaderName());
		self::assertSame(THttpHeaderName::ReportingEndpoints, $re->getHeaderName());
	}

	// -----------------------------------------------------------------------
	// getHeaderValue
	// -----------------------------------------------------------------------

	public function testGetHeaderValueEmptyWhenNoEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		self::assertSame('', $re->getHeaderValue());
	}

	public function testGetHeaderValueSingleEndpoint(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
		]]);
		self::assertSame('csp-endpoint="https://example.com/csp-reports"', $re->getHeaderValue());
	}

	public function testGetHeaderValueMultipleEndpointsCommaSeparated(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
			['name' => 'default',      'url' => 'https://example.com/reports'],
		]]);

		$value = $re->getHeaderValue();
		self::assertStringContainsString('csp-endpoint="https://example.com/csp-reports"', $value);
		self::assertStringContainsString('default="https://example.com/reports"', $value);
		self::assertStringContainsString(', ', $value);
	}

	public function testGetHeaderValuePreservesInsertionOrder(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'alpha', 'url' => 'https://example.com/a'],
			['name' => 'beta',  'url' => 'https://example.com/b'],
			['name' => 'gamma', 'url' => 'https://example.com/g'],
		]]);

		self::assertSame(
			'alpha="https://example.com/a", beta="https://example.com/b", gamma="https://example.com/g"',
			$re->getHeaderValue()
		);
	}

	public function testGetHeaderValueSingleEndpointExactFormat(): void
	{
		// Verifies the exact `name="url"` token format with no extra spaces.
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('ep', 'https://reports.example.com/csp');
		self::assertSame('ep="https://reports.example.com/csp"', $re->getHeaderValue());
	}

	// -----------------------------------------------------------------------
	// getEndpointNames
	// -----------------------------------------------------------------------

	public function testGetEndpointNamesReturnsConfiguredNames(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
			['name' => 'default',      'url' => 'https://example.com/reports'],
		]]);

		self::assertSame(['csp-endpoint', 'default'], $re->getEndpointNames());
	}

	public function testGetEndpointNamesEmptyWhenNoEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		self::assertSame([], $re->getEndpointNames());
	}

	public function testGetEndpointNamesReflectsAddEndpoint(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		$re->addEndpoint('new-ep', 'https://example.com/new');
		self::assertSame(['new-ep'], $re->getEndpointNames());
	}

	// -----------------------------------------------------------------------
	// hasEndpoint
	// -----------------------------------------------------------------------

	public function testHasEndpointReturnsTrueForKnownName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
		]]);
		self::assertTrue($re->hasEndpoint('csp-endpoint'));
	}

	public function testHasEndpointReturnsFalseForUnknownName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
		]]);
		self::assertFalse($re->hasEndpoint('other-endpoint'));
	}

	public function testHasEndpointReturnsFalseWhenEmpty(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		self::assertFalse($re->hasEndpoint('csp-endpoint'));
	}

	public function testHasEndpointTrimsName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('csp-endpoint', 'https://example.com/csp');
		self::assertTrue($re->hasEndpoint(' csp-endpoint '));
	}

	// -----------------------------------------------------------------------
	// addEndpoint
	// -----------------------------------------------------------------------

	public function testAddEndpointAddsNewEntry(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		$re->addEndpoint('my-ep', 'https://example.com/my');
		self::assertTrue($re->hasEndpoint('my-ep'));
		self::assertStringContainsString('my-ep="https://example.com/my"', $re->getHeaderValue());
	}

	public function testAddEndpointAppearsInGetEndpointNames(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('first', 'https://example.com/first');
		$re->addEndpoint('second', 'https://example.com/second');
		self::assertSame(['first', 'second'], $re->getEndpointNames());
	}

	public function testAddEndpointReplacesExistingEntry(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/old'],
		]]);
		$re->addEndpoint('csp-endpoint', 'https://example.com/new');
		self::assertStringContainsString('https://example.com/new', $re->getHeaderValue());
		self::assertStringNotContainsString('https://example.com/old', $re->getHeaderValue());
	}

	public function testAddEndpointReplacePreservesOtherEntries(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('a', 'https://example.com/a');
		$re->addEndpoint('b', 'https://example.com/b');
		$re->addEndpoint('a', 'https://example.com/a-new');
		self::assertStringContainsString('a="https://example.com/a-new"', $re->getHeaderValue());
		self::assertStringContainsString('b="https://example.com/b"', $re->getHeaderValue());
		self::assertStringNotContainsString('a="https://example.com/a"', $re->getHeaderValue());
	}

	public function testAddEndpointTrimsNameAndUrl(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint(' my-ep ', '  https://example.com/ep  ');
		self::assertTrue($re->hasEndpoint('my-ep'));
		self::assertSame('my-ep="https://example.com/ep"', $re->getHeaderValue());
	}

	// -----------------------------------------------------------------------
	// REPORT_URI sentinel — addEndpoint / getEndpointUrl / hasReportUriPlaceholder
	// -----------------------------------------------------------------------

	public function testAddEndpointBlankUrlNormalizesToSentinel(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', '');
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('my-ep'));
	}

	public function testAddEndpointWhitespaceOnlyUrlNormalizesToSentinel(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', '   ');
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('my-ep'));
	}

	public function testAddEndpointExplicitSentinelStoredAsIs(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', THttpHeaderReportingEndpoints::REPORT_URI);
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('my-ep'));
	}

	public function testAddEndpointNonBlankUrlNotNormalized(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', 'https://example.com/csp');
		self::assertSame('https://example.com/csp', $re->getEndpointUrl('my-ep'));
	}

	public function testGetEndpointUrlReturnsNullForAbsentName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		self::assertNull($re->getEndpointUrl('absent'));
	}

	public function testGetEndpointUrlTrimsName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', 'https://example.com/ep');
		self::assertSame('https://example.com/ep', $re->getEndpointUrl('  my-ep  '));
	}

	public function testHasReportUriPlaceholderReturnsFalseWhenEmpty(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		self::assertFalse($re->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsFalseForRealUrl(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', 'https://example.com/csp');
		self::assertFalse($re->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueWhenBlankUrlAdded(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', '');
		self::assertTrue($re->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueForExplicitSentinel(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', THttpHeaderReportingEndpoints::REPORT_URI);
		self::assertTrue($re->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsTrueWhenMixedEndpoints(): void
	{
		// One real URL, one sentinel — should still return true.
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('real', 'https://example.com/real');
		$re->addEndpoint('sentinel', '');
		self::assertTrue($re->hasReportUriPlaceholder());
	}

	public function testHasReportUriPlaceholderReturnsFalseAfterSentinelReplaced(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', '');
		self::assertTrue($re->hasReportUriPlaceholder());
		$re->addEndpoint('my-ep', 'https://example.com/replaced');
		self::assertFalse($re->hasReportUriPlaceholder());
	}

	public function testGetHeaderValueEmitsSentinelBeforeReplacement(): void
	{
		// getHeaderValue() emits the raw sentinel; finalizeReporterService() replaces it.
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', '');
		self::assertStringContainsString(
			'my-ep="' . THttpHeaderReportingEndpoints::REPORT_URI . '"',
			$re->getHeaderValue()
		);
	}

	// -----------------------------------------------------------------------
	// removeEndpoint
	// -----------------------------------------------------------------------

	public function testRemoveEndpointReturnsTrueAndRemovesEntry(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('ep', 'https://example.com/ep');
		$result = $re->removeEndpoint('ep');
		self::assertTrue($result);
		self::assertFalse($re->hasEndpoint('ep'));
	}

	public function testRemoveEndpointReturnsFalseForUnknownName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		self::assertFalse($re->removeEndpoint('nonexistent'));
	}

	public function testRemoveEndpointPreservesOtherEntries(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('a', 'https://example.com/a');
		$re->addEndpoint('b', 'https://example.com/b');
		$re->removeEndpoint('a');
		self::assertFalse($re->hasEndpoint('a'));
		self::assertTrue($re->hasEndpoint('b'));
		self::assertStringNotContainsString('"a"', $re->getHeaderValue());
		self::assertStringContainsString('b="https://example.com/b"', $re->getHeaderValue());
	}

	public function testRemoveEndpointLastEntryLeavesEmptyValue(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('only', 'https://example.com/only');
		$re->removeEndpoint('only');
		self::assertSame('', $re->getHeaderValue());
		self::assertSame([], $re->getEndpointNames());
	}

	public function testRemoveEndpointTrimsName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', 'https://example.com/ep');
		$result = $re->removeEndpoint(' my-ep ');
		self::assertTrue($result);
		self::assertFalse($re->hasEndpoint('my-ep'));
	}

	// -----------------------------------------------------------------------
	// init — null config
	// -----------------------------------------------------------------------

	public function testInitWithNullDoesNotThrow(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(null);
		$this->addToAssertionCount(1);
	}

	public function testInitWithNullLeavesNoEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(null);
		self::assertEmpty($re->getEndpointNames());
	}

	// -----------------------------------------------------------------------
	// loadEndpoints — defensive loading (missing keys in endpoint entries)
	// -----------------------------------------------------------------------

	public function testInitWithMissingEndpointNameDoesNotThrow(): void
	{
		// loadEndpoints() uses ?? '' for absent 'name'; must not produce a TypeError.
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [['url' => 'https://example.com/reports']]]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithMissingEndpointUrlDoesNotThrow(): void
	{
		// loadEndpoints() uses ?? '' for absent 'url'; must not produce a TypeError.
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [['name' => 'my-ep']]]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithMissingEndpointUrlStoresReportUriSentinel(): void
	{
		// A missing 'url' key → empty string → normalized to REPORT_URI sentinel by addEndpoint().
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [['name' => 'my-ep']]]);
		self::assertTrue($re->hasEndpoint('my-ep'));
		self::assertStringContainsString('my-ep="' . THttpHeaderReportingEndpoints::REPORT_URI . '"', $re->getHeaderValue());
	}

	public function testInitWithMissingEndpointNameIsSkipped(): void
	{
		// An entry with no 'name' key would produce an empty-string endpoint name,
		// which generates a malformed `=""url""` header value. loadEndpoints() guards
		// against this by skipping entries where the name is absent or blank.
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['url' => 'https://example.com/reports'],               // no 'name' → skipped
			['name' => 'valid-ep', 'url' => 'https://example.com'], // valid
		]]);
		self::assertFalse($re->hasEndpoint(''),
			'An endpoint entry with no name must be silently skipped');
		self::assertTrue($re->hasEndpoint('valid-ep'));
		self::assertCount(1, $re->getEndpointNames());
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — parsing
	// -----------------------------------------------------------------------

	public function testSetHeaderValueParsesOnePair(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('csp-endpoint="https://example.com/csp-reports"');
		self::assertTrue($re->hasEndpoint('csp-endpoint'));
		self::assertStringContainsString('csp-endpoint="https://example.com/csp-reports"', $re->getHeaderValue());
	}

	public function testSetHeaderValueParsesMultiplePairs(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('csp-endpoint="https://example.com/csp", default="https://example.com/reports"');
		self::assertTrue($re->hasEndpoint('csp-endpoint'));
		self::assertTrue($re->hasEndpoint('default'));
		self::assertSame(['csp-endpoint', 'default'], $re->getEndpointNames());
	}

	public function testSetHeaderValueIgnoresMalformedEntries(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		// A well-formed entry followed by a malformed one — only the valid one is kept.
		$re->setHeaderValue('csp-endpoint="https://example.com/csp", notvalid');
		self::assertTrue($re->hasEndpoint('csp-endpoint'));
		self::assertCount(1, $re->getEndpointNames());
	}

	public function testSetHeaderValueIgnoresUnquotedUrl(): void
	{
		// Per the Reporting-Endpoints structured-header spec, URLs must be quoted
		// strings. An unquoted value does not match the regex and is silently skipped.
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('ep=https://example.com/unquoted');
		self::assertSame([], $re->getEndpointNames());
	}

	public function testSetHeaderValueEmptyStringResultsInNoEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('');
		self::assertSame([], $re->getEndpointNames());
	}

	public function testSetHeaderValueWithWhitespaceOnlyStringResultsInNoEndpoints(): void
	{
		// preg_split with PREG_SPLIT_NO_EMPTY strips blank tokens; whitespace-only
		// input must result in an empty endpoint map rather than a malformed entry.
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('   ');
		self::assertSame([], $re->getEndpointNames());
	}

	public function testSetHeaderValueRoundTripIsIdempotent(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('csp-endpoint', 'https://example.com/csp-reports');
		$re->addEndpoint('default', 'https://example.com/reports');
		$serialised = $re->getHeaderValue();

		$re2 = new THttpHeaderReportingEndpoints();
		$re2->setHeaderValue($serialised);
		self::assertSame($re->getEndpointNames(), $re2->getEndpointNames());
		self::assertSame($serialised, $re2->getHeaderValue());
	}

	public function testSetHeaderValueAcceptsHyphenAndUnderscoreInName(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('my-endpoint_1="https://example.com/ep"');
		self::assertTrue($re->hasEndpoint('my-endpoint_1'));
	}

	// -----------------------------------------------------------------------
	// configToArray / normalizeConfig — XML input path
	// -----------------------------------------------------------------------

	public function testInitWithXmlElementLoadsEndpointsToSameResultAsArrayConfig(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<header>'
			. '<endpoint Name="csp-endpoint" Url="https://example.com/csp-reports" />'
			. '<endpoint Name="default" Url="https://example.com/reports" />'
			. '</header>'
		);

		$re = new THttpHeaderReportingEndpoints();
		$re->init($doc);

		self::assertTrue($re->hasEndpoint('csp-endpoint'));
		self::assertTrue($re->hasEndpoint('default'));
		self::assertSame(['csp-endpoint', 'default'], $re->getEndpointNames());
	}

	public function testXmlConfigProducesSameHeaderValueAsArrayConfig(): void
	{
		// Build via PHP array.
		$arrayRe = new THttpHeaderReportingEndpoints();
		$arrayRe->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
			['name' => 'default',      'url' => 'https://example.com/reports'],
		]]);

		// Build via XML.
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<header>'
			. '<endpoint Name="csp-endpoint" Url="https://example.com/csp-reports" />'
			. '<endpoint Name="default" Url="https://example.com/reports" />'
			. '</header>'
		);
		$xmlRe = new THttpHeaderReportingEndpoints();
		$xmlRe->init($doc);

		self::assertSame($arrayRe->getHeaderValue(), $xmlRe->getHeaderValue());
	}

	public function testXmlConfigWithNoChildElementsIsNoOp(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<header></header>');

		$re = new THttpHeaderReportingEndpoints();
		$re->init($doc);

		self::assertSame([], $re->getEndpointNames());
		self::assertSame('', $re->getHeaderValue());
	}

	public function testXmlConfigPreservesInsertionOrder(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<header>'
			. '<endpoint Name="alpha" Url="https://example.com/a" />'
			. '<endpoint Name="beta"  Url="https://example.com/b" />'
			. '<endpoint Name="gamma" Url="https://example.com/g" />'
			. '</header>'
		);

		$re = new THttpHeaderReportingEndpoints();
		$re->init($doc);

		self::assertSame(['alpha', 'beta', 'gamma'], $re->getEndpointNames());
	}

	public function testXmlConfigAttributeNamesAreCaseInsensitive(): void
	{
		// Attributes use PascalCase in XML (Name=, Url=) but the internal map
		// uses lowercase keys. Verify that the normalisation bridge works.
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<header>'
			. '<endpoint Name="my-ep" Url="https://example.com/ep" />'
			. '</header>'
		);

		$re = new THttpHeaderReportingEndpoints();
		$re->init($doc);

		self::assertTrue($re->hasEndpoint('my-ep'));
		self::assertStringContainsString('my-ep="https://example.com/ep"', $re->getHeaderValue());
	}

	// -----------------------------------------------------------------------
	// getReplace — Reporting-Endpoints is a singleton so replace must be true
	// -----------------------------------------------------------------------

	public function testGetReplaceReturnsTrue(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		self::assertTrue($re->getReplace());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringFormat(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
		]]);

		$str = (string) $re;
		self::assertStringStartsWith('Reporting-Endpoints: ', $str);
		self::assertStringContainsString('csp-endpoint="https://example.com/csp-reports"', $str);
	}

	public function testToStringWithMultipleEndpoints(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('alpha', 'https://example.com/a');
		$re->addEndpoint('beta', 'https://example.com/b');
		$str = (string) $re;
		self::assertStringStartsWith('Reporting-Endpoints: ', $str);
		self::assertStringContainsString('alpha="https://example.com/a"', $str);
		self::assertStringContainsString('beta="https://example.com/b"', $str);
	}

	public function testToStringWithNoEndpointsEmitsEmptyValue(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->init([]);
		self::assertSame('Reporting-Endpoints: ', (string) $re);
	}

	// -----------------------------------------------------------------------
	// addEndpoint — empty name is stored (no guard inside addEndpoint itself)
	// -----------------------------------------------------------------------

	public function testAddEndpointWithEmptyNameStoresUnderEmptyKey(): void
	{
		// addEndpoint() trims the name; an already-empty string stays '' and is
		// stored. This documents current API behaviour: the guard against empty
		// names lives in loadEndpoints(), not in addEndpoint().
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('', 'https://example.com/ep');
		self::assertTrue($re->hasEndpoint(''));
	}

	// -----------------------------------------------------------------------
	// removeEndpoint — whitespace-only name trims to '' → not found → false
	// -----------------------------------------------------------------------

	public function testRemoveEndpointWithWhitespaceOnlyNameReturnsFalse(): void
	{
		// removeEndpoint() trims the name before lookup; '   ' → '' is not present
		// (unless explicitly added via addEndpoint('', …)).
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', 'https://example.com/ep');
		self::assertFalse($re->removeEndpoint('   '));
	}

	// -----------------------------------------------------------------------
	// loadEndpoints — whitespace-only name is now skipped (after fix)
	// -----------------------------------------------------------------------

	public function testLoadEndpointsWithWhitespaceOnlyNameIsSkipped(): void
	{
		// After the whitespace-only guard fix, a config entry whose 'name' is all
		// whitespace must be silently skipped — it would otherwise collapse to the
		// empty-string key after trim(), producing a malformed header value.
		$re = new THttpHeaderReportingEndpoints();
		$re->init(['endpoints' => [
			['name' => '   ', 'url' => 'https://example.com/ep'],   // whitespace-only → skipped
			['name' => 'valid-ep', 'url' => 'https://example.com'], // valid
		]]);
		self::assertFalse($re->hasEndpoint(''),
			'A whitespace-only endpoint name must be silently skipped');
		self::assertTrue($re->hasEndpoint('valid-ep'));
		self::assertCount(1, $re->getEndpointNames());
	}

	// -----------------------------------------------------------------------
	// REPORT_URI constant — inheritance from THttpHeaderBase
	// -----------------------------------------------------------------------

	public function testReportUriConstantIsInheritedFromBase(): void
	{
		// THttpHeaderReportingEndpoints::REPORT_URI resolves via the inheritance
		// chain to THttpHeaderBase::REPORT_URI; the values must be identical.
		self::assertSame(
			\Prado\Web\HttpHeaders\THttpHeaderBase::REPORT_URI,
			THttpHeaderReportingEndpoints::REPORT_URI,
			'THttpHeaderReportingEndpoints::REPORT_URI must be the constant inherited from THttpHeaderBase'
		);
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — 'REPORT_URI' as a literal URL value activates the sentinel
	// -----------------------------------------------------------------------

	public function testSetHeaderValueWithSentinelLiteralUrlActivatesPlaceholder(): void
	{
		// When setHeaderValue() parses 'ep="REPORT_URI"', the literal string
		// 'REPORT_URI' is passed to addEndpoint() as a non-blank URL, which stores
		// it directly. Since it equals the sentinel value, hasReportUriPlaceholder()
		// must return true.
		$re = new THttpHeaderReportingEndpoints();
		$re->setHeaderValue('my-ep="' . THttpHeaderReportingEndpoints::REPORT_URI . '"');
		self::assertTrue($re->hasEndpoint('my-ep'));
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('my-ep'));
		self::assertTrue($re->hasReportUriPlaceholder(),
			'A URL that equals the REPORT_URI sentinel string must activate the placeholder flag');
	}

	// -----------------------------------------------------------------------
	// hasReportUriPlaceholder — false after removeEndpoint on sentinel endpoint
	// -----------------------------------------------------------------------

	public function testHasReportUriPlaceholderReturnsFalseAfterSentinelEndpointRemoved(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('my-ep', ''); // blank → normalized to sentinel
		self::assertTrue($re->hasReportUriPlaceholder());

		$re->removeEndpoint('my-ep');
		self::assertFalse($re->hasReportUriPlaceholder(),
			'hasReportUriPlaceholder() must return false after the only sentinel endpoint is removed');
	}

	public function testHasReportUriPlaceholderMixedAfterRemovingSentinelLeavesRealUrl(): void
	{
		// Remove the sentinel endpoint but leave a real-URL endpoint; flag goes false.
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('real', 'https://example.com/real');
		$re->addEndpoint('sentinel', '');
		$re->removeEndpoint('sentinel');
		self::assertFalse($re->hasReportUriPlaceholder());
		self::assertTrue($re->hasEndpoint('real'));
	}

	// -----------------------------------------------------------------------
	// hasReportUriPlaceholder — all endpoints are sentinels
	// -----------------------------------------------------------------------

	public function testHasReportUriPlaceholderWhenAllEndpointsAreSentinels(): void
	{
		$re = new THttpHeaderReportingEndpoints();
		$re->addEndpoint('ep1', '');
		$re->addEndpoint('ep2', '');
		$re->addEndpoint('ep3', '');
		self::assertTrue($re->hasReportUriPlaceholder());
		self::assertCount(3, $re->getEndpointNames());
		// All three must individually be the sentinel.
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('ep1'));
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('ep2'));
		self::assertSame(THttpHeaderReportingEndpoints::REPORT_URI, $re->getEndpointUrl('ep3'));
	}

	// -----------------------------------------------------------------------
	// configToArray — returns the correct ['endpoints' => [...]] structure
	// -----------------------------------------------------------------------

	public function testConfigToArrayReturnsEndpointsStructureWithLowercaseKeys(): void
	{
		// configToArray() is protected; expose it via reflection.
		// It must return ['endpoints' => [['name' => …, 'url' => …], …]] with
		// all attribute names lowercased by array_change_key_case().
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<header>'
			. '<endpoint Name="ep1" Url="https://example.com/1" />'
			. '<endpoint Name="ep2" Url="https://example.com/2" />'
			. '</header>'
		);

		$re     = new THttpHeaderReportingEndpoints();
		$result = PradoUnit::invoke($re, 'configToArray', $doc);

		self::assertArrayHasKey('endpoints', $result);
		self::assertCount(2, $result['endpoints']);
		// Keys inside each entry must be lowercase (array_change_key_case).
		self::assertArrayHasKey('name', $result['endpoints'][0],
			"configToArray() must lowercase 'Name' to 'name'");
		self::assertArrayHasKey('url', $result['endpoints'][0],
			"configToArray() must lowercase 'Url' to 'url'");
		self::assertSame('ep1', $result['endpoints'][0]['name']);
		self::assertSame('https://example.com/1', $result['endpoints'][0]['url']);
		self::assertSame('ep2', $result['endpoints'][1]['name']);
		self::assertSame('https://example.com/2', $result['endpoints'][1]['url']);
	}

	public function testConfigToArrayReturnsEmptyEndpointsArrayForNoChildren(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<header></header>');

		$re     = new THttpHeaderReportingEndpoints();
		$result = PradoUnit::invoke($re, 'configToArray', $doc);

		self::assertSame(['endpoints' => []], $result,
			'configToArray() must return [\'endpoints\' => []] when there are no child elements');
	}
}
