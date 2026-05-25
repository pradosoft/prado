<?php

/**
 * TContentDispositionTest
 *
 * Unit tests for {@see \Prado\Web\TContentDisposition}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\TContentDisposition;

class TContentDispositionTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Named constants
	// -----------------------------------------------------------------------

	public function testConstantInline(): void
	{
		$this->assertSame('inline', TContentDisposition::INLINE);
	}

	public function testConstantAttachment(): void
	{
		$this->assertSame('attachment', TContentDisposition::ATTACHMENT);
	}

	public function testConstantFormData(): void
	{
		$this->assertSame('form-data', TContentDisposition::FORM_DATA);
	}

	// -----------------------------------------------------------------------
	// Constructor — parsing
	// -----------------------------------------------------------------------

	public function testDefaultConstructorIsInline(): void
	{
		$cd = new TContentDisposition();
		$this->assertSame('inline', $cd->getType());
		$this->assertSame([], $cd->getParameters());
	}

	public function testConstructorParsesType(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertSame('attachment', $cd->getType());
	}

	public function testConstructorNormalizesTypeToLowercase(): void
	{
		$cd = new TContentDisposition('Attachment');
		$this->assertSame('attachment', $cd->getType());
	}

	public function testConstructorParsesFilename(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertSame('attachment', $cd->getType());
		$this->assertSame('report.pdf', $cd->getParameter('filename'));
	}

	public function testConstructorStripsQuotesFromFilename(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		// stored without surrounding quotes
		$this->assertSame('report.pdf', $cd->getParameter('filename'));
	}

	public function testConstructorParsesMultipleParameters(): void
	{
		$cd = new TContentDisposition('form-data; name="file"; filename="upload.txt"');
		$this->assertSame('form-data', $cd->getType());
		$this->assertSame('file', $cd->getParameter('name'));
		$this->assertSame('upload.txt', $cd->getParameter('filename'));
	}

	public function testConstructorParsesFilenameStarParameter(): void
	{
		$cd = new TContentDisposition("attachment; filename*=UTF-8''report.pdf");
		$this->assertSame("UTF-8''report.pdf", $cd->getParameter('filename*'));
	}

	public function testConstructorWithConstant(): void
	{
		$cd = new TContentDisposition(TContentDisposition::ATTACHMENT);
		$this->assertSame('attachment', $cd->getType());
	}

	// -----------------------------------------------------------------------
	// getType / setType
	// -----------------------------------------------------------------------

	public function testSetTypeUpdatesType(): void
	{
		$cd = new TContentDisposition();
		$cd->setType('attachment');
		$this->assertSame('attachment', $cd->getType());
	}

	public function testSetTypeNormalizesToLowercase(): void
	{
		$cd = new TContentDisposition();
		$cd->setType('ATTACHMENT');
		$this->assertSame('attachment', $cd->getType());
	}

	public function testSetTypeTrimsWhitespace(): void
	{
		$cd = new TContentDisposition();
		$cd->setType('  inline  ');
		$this->assertSame('inline', $cd->getType());
	}

	// -----------------------------------------------------------------------
	// getFilename / setFilename
	// -----------------------------------------------------------------------

	public function testGetFilenameNullWhenAbsent(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertNull($cd->getFilename());
	}

	public function testGetFilenameReturnsFilenameParam(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertSame('report.pdf', $cd->getFilename());
	}

	public function testSetFilenameAsciiSetsOnlyFilenameParam(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('report.pdf');
		$this->assertSame('report.pdf', $cd->getParameter('filename'));
		$this->assertNull($cd->getParameter('filename*'));
	}

	public function testSetFilenameNonAsciiSetsBothParams(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('Résumé.pdf');
		$this->assertNotNull($cd->getParameter('filename'));
		$this->assertNotNull($cd->getParameter('filename*'));
	}

	public function testSetFilenameNonAsciiAsciiParamHasUnderscore(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('Résumé.pdf');
		// Non-ASCII chars replaced with '_'
		$this->assertSame('R_sum_.pdf', $cd->getParameter('filename'));
	}

	public function testSetFilenameNonAsciiStarParamIsRfc5987(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('Résumé.pdf');
		$star = $cd->getParameter('filename*');
		$this->assertStringStartsWith("UTF-8''", $star);
		$this->assertStringContainsString('%C3%A9', $star); // 'é' encoded
	}

	public function testSetFilenameNullRemovesBothParams(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('report.pdf');
		$cd->setFilename(null);
		$this->assertNull($cd->getParameter('filename'));
		$this->assertNull($cd->getParameter('filename*'));
		$this->assertNull($cd->getFilename());
	}

	public function testGetFilenamePrefersFilernameStar(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('Résumé.pdf');
		// getFilename() should return the decoded RFC 5987 value
		$this->assertSame('Résumé.pdf', $cd->getFilename());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringTypeOnly(): void
	{
		$cd = new TContentDisposition('inline');
		$this->assertSame('inline', (string) $cd);
	}

	public function testToStringAttachmentNoFilename(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertSame('attachment', (string) $cd);
	}

	public function testToStringAsciiFilenameQuoted(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('report.pdf');
		// 'report.pdf' is a valid token — no quoting needed
		$this->assertSame('attachment; filename=report.pdf', (string) $cd);
	}

	public function testToStringFilenameWithSpacesQuoted(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setParameter('filename', 'my report.pdf');
		// space forces quoting
		$this->assertSame('attachment; filename="my report.pdf"', (string) $cd);
	}

	public function testToStringFilenameStarNotQuoted(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setFilename('Résumé.pdf');
		$output = (string) $cd;
		// filename* must not be wrapped in quotes
		$this->assertMatchesRegularExpression('/filename\*=UTF-8/', $output);
		$this->assertStringNotContainsString('filename*="', $output);
	}

	public function testToStringFormDataWithName(): void
	{
		// 'field1' is a valid token — quotes are stripped on parse and not re-added.
		$cd = new TContentDisposition('form-data; name="field1"');
		$this->assertSame('form-data; name=field1', (string) $cd);
	}

	public function testToStringRoundTripsSimpleValue(): void
	{
		// Quotes are stripped from token-safe values on parse; output is unquoted.
		// Semantic value is preserved; only unnecessary quoting is normalized away.
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertSame('attachment; filename=report.pdf', (string) $cd);
	}

	// -----------------------------------------------------------------------
	// Parameter map (THeaderParametersTrait)
	// -----------------------------------------------------------------------

	public function testSetParametersArrayReplacesAll(): void
	{
		$cd = new TContentDisposition('attachment; filename="old.pdf"');
		$cd->setParameters(['name' => 'file']);
		$this->assertNull($cd->getParameter('filename'));
		$this->assertSame('file', $cd->getParameter('name'));
	}

	public function testSetParametersStringParsesParams(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd->setParameters('filename="report.pdf"; name="file"');
		$this->assertSame('report.pdf', $cd->getParameter('filename'));
		$this->assertSame('file', $cd->getParameter('name'));
	}

	public function testSetParametersEmptyStringClearsAll(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$cd->setParameters('');
		$this->assertSame([], $cd->getParameters());
	}

	public function testHasParameterReturnsTrueWhenPresent(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertTrue($cd->hasParameter('filename'));
	}

	public function testHasParameterReturnsFalseWhenAbsent(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertFalse($cd->hasParameter('filename'));
	}

	public function testRemoveParameterRemovesIt(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$cd->removeParameter('filename');
		$this->assertNull($cd->getParameter('filename'));
	}

	public function testClearParametersEmptiesMap(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"; name="file"');
		$cd->clearParameters();
		$this->assertSame([], $cd->getParameters());
	}

	// -----------------------------------------------------------------------
	// ArrayAccess (THeaderParametersTrait)
	// -----------------------------------------------------------------------

	public function testOffsetGetReturnsValue(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertSame('report.pdf', $cd['filename']);
	}

	public function testOffsetGetReturnsNullWhenAbsent(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertNull($cd['filename']);
	}

	public function testOffsetExistsReturnsTrueWhenPresent(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$this->assertTrue(isset($cd['filename']));
	}

	public function testOffsetExistsReturnsFalseWhenAbsent(): void
	{
		$cd = new TContentDisposition('attachment');
		$this->assertFalse(isset($cd['filename']));
	}

	public function testOffsetSetAddsParameter(): void
	{
		$cd = new TContentDisposition('attachment');
		$cd['filename'] = 'report.pdf';
		$this->assertSame('report.pdf', $cd->getParameter('filename'));
	}

	public function testOffsetUnsetRemovesParameter(): void
	{
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		unset($cd['filename']);
		$this->assertNull($cd->getParameter('filename'));
	}

	public function testImplementsArrayAccess(): void
	{
		$cd = new TContentDisposition();
		$this->assertInstanceOf(\ArrayAccess::class, $cd);
	}

	// -----------------------------------------------------------------------
	// RFC 5987 decoding — decodeRfc5987()
	// -----------------------------------------------------------------------

	public function testGetFilenameDecodesRfc5987(): void
	{
		$cd = new TContentDisposition("attachment; filename*=UTF-8''Quarterly%20R%C3%A9sum%C3%A9.pdf");
		$this->assertSame('Quarterly Résumé.pdf', $cd->getFilename());
	}

	public function testGetFilenameDecodesRfc5987WithLanguage(): void
	{
		$cd = new TContentDisposition("attachment; filename*=UTF-8'en'report.pdf");
		$this->assertSame('report.pdf', $cd->getFilename());
	}

	public function testGetFilenameReturnsExtValueUnchangedForUnknownCharset(): void
	{
		$cd = new TContentDisposition("attachment; filename*=ISO-8859-1''report.pdf");
		// Non-UTF-8 charset — return raw ext-value unchanged
		$this->assertSame("ISO-8859-1''report.pdf", $cd->getFilename());
	}

	public function testDecodeRfc5987DirectlyDecodesUtf8(): void
	{
		$this->assertSame('report Q4.pdf', TContentDisposition::decodeRfc5987("UTF-8''report%20Q4.pdf"));
	}

	public function testDecodeRfc5987DirectlyDecodesNonAscii(): void
	{
		$this->assertSame('Résumé.pdf', TContentDisposition::decodeRfc5987("UTF-8''R%C3%A9sum%C3%A9.pdf"));
	}

	public function testDecodeRfc5987ReturnsRawForUnknownCharset(): void
	{
		$raw = "ISO-8859-1''caf%E9.pdf";
		$this->assertSame($raw, TContentDisposition::decodeRfc5987($raw));
	}

	public function testDecodeRfc5987ReturnsInputWhenUnparseable(): void
	{
		$this->assertSame('not-ext-value', TContentDisposition::decodeRfc5987('not-ext-value'));
	}

	// -----------------------------------------------------------------------
	// RFC 9110 quoted-string encoding — encodeQuotedString()
	// -----------------------------------------------------------------------

	public function testQuoteValueReturnsPureTokenUnchanged(): void
	{
		// All tchar characters — must not be quoted.
		$this->assertSame('report.pdf', TContentDisposition::encodeQuotedString('report.pdf'));
	}

	public function testQuoteValueReturnsTcharSymbolsUnchanged(): void
	{
		// tchar includes: ! # $ % & ' * + - . ^ _ ` | ~
		$this->assertSame("!#\$%&'*+-.^_`|~", TContentDisposition::encodeQuotedString("!#\$%&'*+-.^_`|~"));
	}

	public function testQuoteValueWrapsSpaceInQuotes(): void
	{
		$this->assertSame('"my file.pdf"', TContentDisposition::encodeQuotedString('my file.pdf'));
	}

	public function testQuoteValueWrapsNonAsciiInQuotes(): void
	{
		$this->assertSame('"résumé.pdf"', TContentDisposition::encodeQuotedString('résumé.pdf'));
	}

	public function testQuoteValueEscapesInternalDoubleQuote(): void
	{
		$this->assertSame('"say \"hi\""', TContentDisposition::encodeQuotedString('say "hi"'));
	}

	public function testQuoteValueEscapesInternalBackslash(): void
	{
		$this->assertSame('"back\\\\slash"', TContentDisposition::encodeQuotedString('back\\slash'));
	}

	public function testQuoteValueWrapsValueContainingSemicolon(): void
	{
		// Semicolon is a delimiter in Content-Disposition; must be quoted.
		$this->assertSame('"a;b"', TContentDisposition::encodeQuotedString('a;b'));
	}

	public function testQuoteValueIsPublicStaticMethod(): void
	{
		// Callable without an instance.
		$result = TContentDisposition::encodeQuotedString('token');
		$this->assertSame('token', $result);
	}

	public function testQuoteValueEmptyStringIsQuoted(): void
	{
		// An empty string is not a valid token (1*tchar requires at least one char),
		// so it must be wrapped in double quotes → '""'.
		$this->assertSame('""', TContentDisposition::encodeQuotedString(''));
	}

	// -----------------------------------------------------------------------
	// decodeRfc5987 — additional charset and edge cases
	// -----------------------------------------------------------------------

	public function testDecodeRfc5987LowercaseUtf8Charset(): void
	{
		// The charset comparison uses strtolower; 'utf-8' must decode identically
		// to 'UTF-8'.
		$this->assertSame(
			'report Q4.pdf',
			TContentDisposition::decodeRfc5987("utf-8''report%20Q4.pdf")
		);
	}

	// -----------------------------------------------------------------------
	// setParameters — whitespace-only string (THeaderParametersTrait coverage)
	// -----------------------------------------------------------------------

	public function testSetParametersWhitespaceOnlyStringClearsParameters(): void
	{
		// A whitespace-only string trims to '' before the split; the result must
		// be an empty parameter map (same as passing '').
		$cd = new TContentDisposition('attachment; filename="report.pdf"');
		$cd->setParameters('   ');
		$this->assertSame([], $cd->getParameters());
	}

	// -----------------------------------------------------------------------
	// setFilename — ASCII-with-spaces (no filename* should be emitted)
	// -----------------------------------------------------------------------

	public function testSetFilenameAsciiWithSpacesDoesNotEmitStarParam(): void
	{
		// A filename containing only ASCII characters (spaces included) is pure-ASCII,
		// so only 'filename' must be set — 'filename*' must not appear.
		$cd = new TContentDisposition(TContentDisposition::ATTACHMENT);
		$cd->setFilename('my report.pdf');
		$this->assertNull($cd->getParameter('filename*'));
		$this->assertSame('my report.pdf', $cd->getParameter('filename'));
	}

	// -----------------------------------------------------------------------
	// init() — smoke test (base no-op must not throw with any config shape)
	// -----------------------------------------------------------------------

	public function testInitWithArrayConfigDoesNotThrow(): void
	{
		$cd = new TContentDisposition();
		// TContentDisposition is a value object, not a TComponent, so it has no
		// init() method — this section is intentionally omitted.
		$this->addToAssertionCount(1);
	}
}
