<?php

/**
 * THttpHeaderContentTypeTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeaderContentType}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\HttpHeaders\THttpHeaderContentType;
use Prado\Web\THttpHeaderName;
use Prado\Web\THttpResponse;
use Prado\Web\TMediaType;

class THttpHeaderContentTypeTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// getHeaderName
	// -----------------------------------------------------------------------

	public function testGetHeaderNameReturnsContentType(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertSame(THttpHeaderName::ContentType, $ct->getHeaderName());
		self::assertSame('Content-Type', $ct->getHeaderName());
	}

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public function testDefaultContentTypeIsTextHtml(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertSame(THttpResponse::DEFAULT_CONTENTTYPE, $ct->getContentType());
	}

	public function testDefaultCharsetIsNull(): void
	{
		// No charset has been set; finalizeHeader() has not run yet.
		$ct = new THttpHeaderContentType();
		self::assertNull($ct->getCharset());
	}

	// -----------------------------------------------------------------------
	// getHeaderValue — delegates to TMediaType::__toString()
	// -----------------------------------------------------------------------

	public function testGetHeaderValueWithoutFinalizeHasNoCharset(): void
	{
		// getHeaderValue() renders whatever TMediaType holds; charset is absent until
		// finalizeHeader() resolves it.
		$ct = new THttpHeaderContentType();
		self::assertSame(THttpResponse::DEFAULT_CONTENTTYPE, $ct->getHeaderValue());
	}

	public function testGetHeaderValueWithExplicitCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('ISO-8859-1');
		self::assertSame('text/html; charset=ISO-8859-1', $ct->getHeaderValue());
	}

	public function testGetHeaderValueWithNullCharsetHasNoCharsetParameter(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset(null);
		self::assertSame('text/html', $ct->getHeaderValue());
	}

	public function testGetHeaderValueWithFalseStringCharsetHasNoCharsetParameter(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset('false');
		self::assertSame('text/html', $ct->getHeaderValue());
	}

	public function testGetHeaderValueCustomMimeType(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('application/json');
		$ct->setCharset('UTF-8');
		self::assertSame('application/json; charset=UTF-8', $ct->getHeaderValue());
	}

	public function testGetHeaderValueBinaryTypeWithNoCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('image/png');
		self::assertSame('image/png', $ct->getHeaderValue());
	}

	// -----------------------------------------------------------------------
	// setContentType / getContentType
	// -----------------------------------------------------------------------

	public function testSetContentTypePersists(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('application/xml');
		self::assertSame('application/xml', $ct->getContentType());
	}

	public function testSetContentTypeUsedInValue(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('text/plain');
		$ct->setCharset('UTF-8');
		self::assertStringStartsWith('text/plain', $ct->getHeaderValue());
	}

	public function testSetContentTypeAfterCharsetDoesNotClearCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setContentType('application/json');
		self::assertSame('UTF-8', $ct->getCharset());
	}

	// -----------------------------------------------------------------------
	// setCharset / getCharset — delegates to TMediaType
	// -----------------------------------------------------------------------

	public function testSetCharsetStringPersists(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-16');
		self::assertSame('UTF-16', $ct->getCharset());
	}

	public function testSetCharsetNullRemovesCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset(null);
		self::assertNull($ct->getCharset());
	}

	public function testSetCharsetFalseStringRemovesCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset('false');
		self::assertNull($ct->getCharset());
	}

	public function testSetCharsetFalseStringCaseInsensitive(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset('FALSE');
		self::assertNull($ct->getCharset());
	}

	public function testSetCharsetEmptyStringRemovesCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setCharset('');
		self::assertNull($ct->getCharset());
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — parsing into TMediaType directly
	// -----------------------------------------------------------------------

	public function testSetHeaderValueParsesTypeOnly(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('application/json');
		self::assertSame('application/json', $ct->getContentType());
		self::assertNull($ct->getCharset());
	}

	public function testSetHeaderValueParsesTypeAndCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('text/html; charset=UTF-8');
		self::assertSame('text/html', $ct->getContentType());
		self::assertSame('UTF-8', $ct->getCharset());
	}

	public function testSetHeaderValueParsesTypeAndCharsetNoSpace(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('text/html;charset=UTF-8');
		self::assertSame('text/html', $ct->getContentType());
		self::assertSame('UTF-8', $ct->getCharset());
	}

	public function testSetHeaderValueCharsetCaseInsensitive(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('text/html; Charset=ISO-8859-1');
		self::assertSame('ISO-8859-1', $ct->getCharset());
	}

	public function testSetHeaderValueTrimsWhitespace(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('  text/plain  ;  charset = UTF-8  ');
		self::assertSame('text/plain', $ct->getContentType());
		self::assertSame('UTF-8', $ct->getCharset());
	}

	public function testSetHeaderValuePreservesBoundaryParameter(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('multipart/form-data; boundary=something');
		self::assertSame('multipart/form-data', $ct->getContentType());
		self::assertNull($ct->getCharset());
		self::assertSame('something', $ct->getMediaType()->getParameter('boundary'));
		self::assertStringContainsString('boundary=something', $ct->getHeaderValue());
	}

	public function testSetHeaderValuePreservesCharsetInMediaType(): void
	{
		// Charset is now stored directly in TMediaType, not extracted separately.
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('text/html; charset=UTF-8');
		self::assertSame('UTF-8', $ct->getMediaType()->getCharset());
	}

	public function testSetHeaderValueReplacesExistingMediaType(): void
	{
		$ct = new THttpHeaderContentType();
		$mt1 = $ct->getMediaType();
		$ct->setHeaderValue('application/json');
		$mt2 = $ct->getMediaType();
		self::assertNotSame($mt1, $mt2);
	}

	public function testSetHeaderValueResetsCascadeWhenNoCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$ct->setHeaderValue('application/json'); // no charset in this value
		self::assertNull($ct->getCharset());
	}

	public function testSetHeaderValueParsesCharsetAndBoundaryTogether(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('multipart/form-data; charset=UTF-8; boundary=----abc');
		self::assertSame('multipart/form-data', $ct->getContentType());
		self::assertSame('UTF-8', $ct->getCharset());
		self::assertSame('----abc', $ct->getMediaType()->getParameter('boundary'));
	}

	// -----------------------------------------------------------------------
	// getMediaType
	// -----------------------------------------------------------------------

	public function testGetMediaTypeReturnsMediaTypeInstance(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertInstanceOf(TMediaType::class, $ct->getMediaType());
	}

	public function testGetMediaTypeReturnsSameObjectOnRepeatedCalls(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertSame($ct->getMediaType(), $ct->getMediaType());
	}

	public function testGetMediaTypeDefaultMimeTypeIsTextHtml(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertSame('text/html', $ct->getMediaType()->getMimeType());
	}

	public function testGetMediaTypeReflectsSetContentType(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('application/json');
		self::assertSame('application/json', $ct->getMediaType()->getMimeType());
	}

	public function testGetMediaTypeParameterSetDirectlyAppearsInHeaderValue(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('multipart/form-data');
		$ct->getMediaType()->setParameter('boundary', '----abc');
		self::assertStringContainsString('boundary=----abc', $ct->getHeaderValue());
	}

	public function testGetHeaderValueCharsetAndBoundaryBothPresent(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('multipart/form-data');
		$ct->setCharset('UTF-8');
		$ct->getMediaType()->setBoundary('----abc');
		// TMediaType::__toString() emits params in insertion order:
		// charset was set first, then boundary.
		self::assertSame(
			'multipart/form-data; charset=UTF-8; boundary=----abc',
			$ct->getHeaderValue()
		);
	}

	// -----------------------------------------------------------------------
	// init — null config (base no-op; must not throw for any config shape)
	// -----------------------------------------------------------------------

	public function testInitWithNullConfigDoesNotThrow(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->init(null);
		$this->addToAssertionCount(1);
	}

	public function testInitWithNullConfigPreservesDefaults(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->init(null);
		self::assertNull($ct->getCharset());
		self::assertSame('text/html', $ct->getContentType());
	}

	// -----------------------------------------------------------------------
	// finalizeHeader — charset cascade
	// -----------------------------------------------------------------------

	public function testFinalizeHeaderWithCharsetAlreadySetDoesNotChange(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('ISO-8859-1');
		$ct->finalizeHeader();
		self::assertSame('ISO-8859-1', $ct->getCharset());
	}

	public function testFinalizeHeaderWithNullCharsetResolvesToDefaultCharset(): void
	{
		// Temporarily remove any active globalization so the DEFAULT_CHARSET
		// hard-fallback path is exercised regardless of the test environment.
		$app      = Prado::getApplication();
		$original = $app !== null ? PradoUnit::getProp($app, '_globalization') : null;
		if ($app !== null) {
			PradoUnit::setProp($app, '_globalization', null);
		}

		try {
			$ct = new THttpHeaderContentType();
			self::assertNull($ct->getCharset(), 'pre-condition: charset not yet set');
			$ct->finalizeHeader();
			self::assertSame(THttpResponse::DEFAULT_CHARSET, $ct->getCharset());
		} finally {
			if ($app !== null) {
				PradoUnit::setProp($app, '_globalization', $original);
			}
		}
	}

	public function testFinalizeHeaderIsIdempotent(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->finalizeHeader();
		$first = $ct->getCharset();
		$ct->finalizeHeader();
		self::assertSame($first, $ct->getCharset());
	}

	public function testFinalizeHeaderProducesCorrectHeaderValue(): void
	{
		// Temporarily remove any active globalization so DEFAULT_CHARSET is used.
		$app      = Prado::getApplication();
		$original = $app !== null ? PradoUnit::getProp($app, '_globalization') : null;
		if ($app !== null) {
			PradoUnit::setProp($app, '_globalization', null);
		}

		try {
			$ct = new THttpHeaderContentType();
			$ct->finalizeHeader();
			self::assertSame(
				THttpResponse::DEFAULT_CONTENTTYPE . '; charset=' . THttpResponse::DEFAULT_CHARSET,
				$ct->getHeaderValue()
			);
		} finally {
			if ($app !== null) {
				PradoUnit::setProp($app, '_globalization', $original);
			}
		}
	}

	public function testFinalizeHeaderThenGetHeaderValueContainsCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->finalizeHeader();
		self::assertStringContainsString('charset=', $ct->getHeaderValue());
	}

	public function testFinalizeHeaderAfterCharsetResetResolvesAgain(): void
	{
		// finalizeHeader() must re-resolve after the charset has been cleared.
		// Temporarily remove any active globalization so DEFAULT_CHARSET is used.
		$app      = Prado::getApplication();
		$original = $app !== null ? PradoUnit::getProp($app, '_globalization') : null;
		if ($app !== null) {
			PradoUnit::setProp($app, '_globalization', null);
		}

		try {
			$ct = new THttpHeaderContentType();
			$ct->finalizeHeader();
			$ct->setCharset(null); // clear the resolved value
			$ct->finalizeHeader();
			self::assertSame(THttpResponse::DEFAULT_CHARSET, $ct->getCharset());
		} finally {
			if ($app !== null) {
				PradoUnit::setProp($app, '_globalization', $original);
			}
		}
	}

	public function testFinalizeHeaderResolvesCharsetFromGlobalization(): void
	{
		// When a globalization module is active and reports a non-empty charset,
		// finalizeHeader() must prefer it over the hard DEFAULT_CHARSET fallback.
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No TApplication available in this test environment.');
		}

		// Inject a TGlobalization stub via PradoUnit helpers.
		$glob = new TGlobalization();
		$glob->setCharset('ISO-8859-1');

		$original = PradoUnit::getProp($app, '_globalization');
		PradoUnit::setProp($app, '_globalization', $glob);

		try {
			$ct = new THttpHeaderContentType();
			self::assertNull($ct->getCharset(), 'pre-condition: charset not yet resolved');
			$ct->finalizeHeader();
			self::assertSame('ISO-8859-1', $ct->getCharset(),
				'finalizeHeader() must use the globalization charset when available');
		} finally {
			PradoUnit::setProp($app, '_globalization', $original);
		}
	}

	public function testFinalizeHeaderFallsBackToDefaultWhenGlobalizationCharsetIsEmpty(): void
	{
		// A globalization module that returns '' for charset must be ignored;
		// the hard-fallback DEFAULT_CHARSET must apply instead.
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No TApplication available in this test environment.');
		}

		$glob = new TGlobalization();
		$glob->setCharset(''); // empty → must be skipped

		$original = PradoUnit::getProp($app, '_globalization');
		PradoUnit::setProp($app, '_globalization', $glob);

		try {
			$ct = new THttpHeaderContentType();
			$ct->finalizeHeader();
			self::assertSame(THttpResponse::DEFAULT_CHARSET, $ct->getCharset(),
				'Empty globalization charset must fall through to DEFAULT_CHARSET');
		} finally {
			PradoUnit::setProp($app, '_globalization', $original);
		}
	}

	// -----------------------------------------------------------------------
	// getReplace — Content-Type is a singleton header so replace must be true
	// -----------------------------------------------------------------------

	public function testGetReplaceReturnsTrue(): void
	{
		$ct = new THttpHeaderContentType();
		self::assertTrue($ct->getReplace());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringDefaultFormat(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setCharset('UTF-8');
		$str = (string) $ct;
		self::assertStringStartsWith('Content-Type: ', $str);
		self::assertStringContainsString('text/html', $str);
		self::assertStringContainsString('charset=UTF-8', $str);
	}

	public function testToStringWithNoCharsetOmitsCharset(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('image/png');
		self::assertSame('Content-Type: image/png', (string) $ct);
	}

	public function testToStringWithJsonType(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->setContentType('application/json');
		$ct->setCharset('UTF-8');
		self::assertSame('Content-Type: application/json; charset=UTF-8', (string) $ct);
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — empty string
	// -----------------------------------------------------------------------

	public function testSetHeaderValueWithEmptyString(): void
	{
		// setHeaderValue('') creates a new TMediaType('') which parses to an empty
		// MIME type with no parameters. The operation must not throw.
		$ct = new THttpHeaderContentType();
		$ct->setHeaderValue('');
		self::assertSame('', $ct->getContentType());
		self::assertNull($ct->getCharset());
	}

	// -----------------------------------------------------------------------
	// init — empty array config
	// -----------------------------------------------------------------------

	public function testInitWithEmptyArrayConfigDoesNotThrow(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->init([]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithEmptyArrayConfigPreservesDefaults(): void
	{
		$ct = new THttpHeaderContentType();
		$ct->init([]);
		self::assertSame('text/html', $ct->getContentType());
		self::assertNull($ct->getCharset());
	}
}
