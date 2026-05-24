<?php

/**
 * THttpHeaderContentDispositionTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeaderContentDisposition}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\HttpHeaders\THttpHeaderContentDisposition;
use Prado\Web\TContentDisposition;
use Prado\Web\THttpHeaderName;

class THttpHeaderContentDispositionTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// getHeaderName
	// -----------------------------------------------------------------------

	public function testGetHeaderNameReturnsContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame(THttpHeaderName::ContentDisposition, $h->getHeaderName());
		$this->assertSame('Content-Disposition', $h->getHeaderName());
	}

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	public function testDefaultDispositionTypeIsAttachment(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame(TContentDisposition::ATTACHMENT, $h->getDispositionType());
	}

	public function testDefaultFilenameIsEmptyString(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame('', $h->getFilename());
	}

	// -----------------------------------------------------------------------
	// getContentDisposition
	// -----------------------------------------------------------------------

	public function testGetContentDispositionReturnsTContentDispositionInstance(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertInstanceOf(TContentDisposition::class, $h->getContentDisposition());
	}

	public function testGetContentDispositionReturnsSameObjectOnRepeatedCalls(): void
	{
		$h = new THttpHeaderContentDisposition();
		$cd1 = $h->getContentDisposition();
		$cd2 = $h->getContentDisposition();
		$this->assertSame($cd1, $cd2);
	}

	public function testGetContentDispositionDefaultTypeIsAttachment(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame(TContentDisposition::ATTACHMENT, $h->getContentDisposition()->getType());
	}

	public function testGetContentDispositionReflectsDispositionTypeChange(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('inline');
		$this->assertSame('inline', $h->getContentDisposition()->getType());
	}

	public function testGetContentDispositionReflectsFilenameChange(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('data.csv');
		$this->assertSame('data.csv', $h->getContentDisposition()->getFilename());
	}

	public function testGetContentDispositionAfterSetHeaderValueReturnsSameObject(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('inline; filename="doc.txt"');
		$cd = $h->getContentDisposition();
		// Same object must be returned on repeated calls after parse.
		$this->assertSame($cd, $h->getContentDisposition());
	}

	public function testGetContentDispositionDirectParameterAccessWorksViaAccessor(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->getContentDisposition()->setParameter('creation-date', 'Mon, 01 Jan 2024 00:00:00 GMT');
		$this->assertSame('Mon, 01 Jan 2024 00:00:00 GMT', $h->getContentDisposition()->getParameter('creation-date'));
	}

	// -----------------------------------------------------------------------
	// getHeaderValue
	// -----------------------------------------------------------------------

	public function testGetHeaderValueNoFilenameReturnsDispositionOnly(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame('attachment', $h->getHeaderValue());
	}

	public function testGetHeaderValueInlineNoFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('inline');
		$this->assertSame('inline', $h->getHeaderValue());
	}

	public function testGetHeaderValueWithAsciiFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('report.pdf');
		$value = $h->getHeaderValue();
		$this->assertStringContainsString('attachment', $value);
		$this->assertStringContainsString('filename=', $value);
		$this->assertStringContainsString('report.pdf', $value);
	}

	public function testGetHeaderValueWithNonAsciiFilenameIncludesFilenameStar(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('Résumé.pdf');
		$value = $h->getHeaderValue();
		$this->assertStringContainsString('filename=', $value);
		$this->assertStringContainsString('filename*=', $value);
		$this->assertStringContainsString("UTF-8''", $value);
	}

	public function testGetHeaderValueWithNonAsciiFilenameAsciiPartHasUnderscore(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('Résumé.pdf');
		$value = $h->getHeaderValue();
		$this->assertStringContainsString('R_sum_.pdf', $value);
	}

	// -----------------------------------------------------------------------
	// getDispositionType / setDispositionType
	// -----------------------------------------------------------------------

	public function testSetDispositionTypeAttachment(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('attachment');
		$this->assertSame('attachment', $h->getDispositionType());
	}

	public function testSetDispositionTypeInline(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('inline');
		$this->assertSame('inline', $h->getDispositionType());
	}

	public function testSetDispositionTypeNormalizesToLowercase(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('ATTACHMENT');
		$this->assertSame('attachment', $h->getDispositionType());
	}

	public function testSetDispositionTypeTrimsWhitespace(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('  inline  ');
		$this->assertSame('inline', $h->getDispositionType());
	}

	public function testSetDispositionTypeDoesNotClearFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('report.pdf');
		$h->setDispositionType('inline');
		$this->assertSame('report.pdf', $h->getFilename());
	}

	// -----------------------------------------------------------------------
	// getFilename / setFilename
	// -----------------------------------------------------------------------

	public function testSetFilenameStoresValue(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('report.pdf');
		$this->assertSame('report.pdf', $h->getFilename());
	}

	public function testSetFilenameEmptyStringNoFilenameInHeader(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('');
		$this->assertSame('attachment', $h->getHeaderValue());
	}

	public function testSetFilenameEmptyStringRemovesFilenameFromContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('report.pdf');
		$h->setFilename('');
		$this->assertNull($h->getContentDisposition()->getFilename());
	}

	public function testSetFilenameEmptyStringRemovesFilenameStarFromContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('Résumé.pdf');
		// Verify filename* was set.
		$this->assertNotNull($h->getContentDisposition()->getParameter('filename*'));
		$h->setFilename('');
		// Both filename and filename* must be gone.
		$this->assertNull($h->getContentDisposition()->getParameter('filename'));
		$this->assertNull($h->getContentDisposition()->getParameter('filename*'));
	}

	public function testSetFilenameDelegatesNullToContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('doc.pdf');
		// '' maps to null in TContentDisposition::setFilename().
		$h->setFilename('');
		$this->assertSame('', $h->getFilename());
	}

	// -----------------------------------------------------------------------
	// setHeaderValue (parse)
	// -----------------------------------------------------------------------

	public function testSetHeaderValueParsesDispositionOnly(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('inline');
		$this->assertSame('inline', $h->getDispositionType());
		$this->assertSame('', $h->getFilename());
	}

	public function testSetHeaderValueParsesDispositionAndFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="report.pdf"');
		$this->assertSame('attachment', $h->getDispositionType());
		$this->assertSame('report.pdf', $h->getFilename());
	}

	public function testSetHeaderValueParsesFilenameStarDecoded(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue("attachment; filename*=UTF-8''R%C3%A9sum%C3%A9.pdf");
		$this->assertSame('attachment', $h->getDispositionType());
		$this->assertSame('Résumé.pdf', $h->getFilename());
	}

	public function testSetHeaderValueParsesFormData(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('form-data; name="field1"; filename="upload.txt"');
		$this->assertSame('form-data', $h->getDispositionType());
		$this->assertSame('upload.txt', $h->getFilename());
	}

	public function testSetHeaderValuePreservesExtendedParametersInContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('form-data; name="field1"; filename="upload.txt"');
		// The 'name' parameter must survive in the persistent TContentDisposition object.
		$this->assertSame('field1', $h->getContentDisposition()->getParameter('name'));
	}

	public function testSetHeaderValuePreservesCreationDateParameter(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="doc.pdf"; creation-date="Mon, 01 Jan 2024 00:00:00 GMT"');
		// The parser strips the HTTP quoted-string delimiters; the stored value is the bare string.
		$this->assertSame('Mon, 01 Jan 2024 00:00:00 GMT', $h->getContentDisposition()->getParameter('creation-date'));
	}

	public function testSetHeaderValueReplacesExistingContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="first.pdf"');
		$h->setHeaderValue('inline');
		$this->assertSame('inline', $h->getDispositionType());
		$this->assertSame('', $h->getFilename());
	}

	public function testSetHeaderValueReplacedObjectIsReturnedByGetContentDisposition(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="first.pdf"');
		$cd1 = $h->getContentDisposition();
		$h->setHeaderValue('inline');
		$cd2 = $h->getContentDisposition();
		// setHeaderValue() creates a fresh TContentDisposition, so the reference changes.
		$this->assertNotSame($cd1, $cd2);
	}

	// -----------------------------------------------------------------------
	// Extended parameter write-through via getContentDisposition()
	// -----------------------------------------------------------------------

	public function testExtendedParameterSetViaAccessorSurvivesGetHeaderValue(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->getContentDisposition()->setParameter('creation-date', 'Mon, 01 Jan 2024 00:00:00 GMT');
		$value = $h->getHeaderValue();
		$this->assertStringContainsString('creation-date=', $value);
	}

	public function testExtendedParameterRemovedViaAccessorAbsentFromGetHeaderValue(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="doc.pdf"; creation-date="Mon, 01 Jan 2024 00:00:00 GMT"');
		$h->getContentDisposition()->removeParameter('creation-date');
		$value = $h->getHeaderValue();
		$this->assertStringNotContainsString('creation-date', $value);
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringProducesHeaderLine(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertSame('Content-Disposition: attachment', (string) $h);
	}

	public function testToStringWithFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setFilename('report.pdf');
		$output = (string) $h;
		$this->assertStringStartsWith('Content-Disposition: ', $output);
		$this->assertStringContainsString('attachment', $output);
		$this->assertStringContainsString('report.pdf', $output);
	}

	public function testToStringWithInlineAndNoFilename(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('inline');
		$this->assertSame('Content-Disposition: inline', (string) $h);
	}

	public function testToStringAfterSetHeaderValueIsValid(): void
	{
		// setHeaderValue() replaces the internal TContentDisposition object;
		// __toString() must still produce a valid header line from the new object.
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('inline; filename="doc.txt"');
		$output = (string) $h;
		$this->assertStringStartsWith('Content-Disposition: ', $output);
		$this->assertStringContainsString('inline', $output);
		$this->assertStringContainsString('doc.txt', $output);
	}

	// -----------------------------------------------------------------------
	// getReplace (singleton header)
	// -----------------------------------------------------------------------

	public function testGetReplaceReturnsTrue(): void
	{
		$h = new THttpHeaderContentDisposition();
		$this->assertTrue($h->getReplace());
	}

	// -----------------------------------------------------------------------
	// init() — smoke test (base no-op must not throw with any config shape)
	// -----------------------------------------------------------------------

	public function testInitWithNullConfigDoesNotThrow(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->init(null);
		$this->addToAssertionCount(1);
	}

	public function testInitWithEmptyArrayConfigDoesNotThrow(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->init([]);
		$this->addToAssertionCount(1);
	}

	public function testInitWithPopulatedArrayConfigDoesNotThrow(): void
	{
		$h = new THttpHeaderContentDisposition();
		$h->init(['DispositionType' => 'inline', 'Filename' => 'test.txt']);
		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------
	// getContentDisposition() — re-initialisation after null clear
	// -----------------------------------------------------------------------

	public function testGetContentDispositionReinitAfterClearViaNullHeaderValue(): void
	{
		// setHeaderValue() replaces the backing TContentDisposition; a subsequent
		// setHeaderValue() call must replace it again cleanly.
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('attachment; filename="first.pdf"');
		self::assertSame('first.pdf', $h->getFilename());
		$h->setHeaderValue('inline; filename="second.pdf"');
		self::assertSame('second.pdf', $h->getFilename());
		self::assertSame('inline', $h->getDispositionType());
	}

	// -----------------------------------------------------------------------
	// setDispositionType — empty string
	// -----------------------------------------------------------------------

	public function testSetDispositionTypeEmptyString(): void
	{
		// setDispositionType delegates to TContentDisposition::setType() which
		// applies strtolower(trim()); an empty string normalises to '' and is
		// stored as-is without throwing.
		$h = new THttpHeaderContentDisposition();
		$h->setDispositionType('');
		$this->assertSame('', $h->getDispositionType());
	}

	// -----------------------------------------------------------------------
	// setHeaderValue — empty string
	// -----------------------------------------------------------------------

	public function testSetHeaderValueEmptyString(): void
	{
		// new TContentDisposition('') parses a blank value: type becomes '',
		// no filename is set. The call must not throw.
		$h = new THttpHeaderContentDisposition();
		$h->setHeaderValue('');
		$this->assertSame('', $h->getDispositionType());
		$this->assertSame('', $h->getFilename());
	}
}
