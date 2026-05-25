<?php

/**
 * TMediaTypeTest
 *
 * Unit tests for {@see \Prado\Web\TMediaType}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\TMediaType;

class TMediaTypeTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Named constants — value verification (in class declaration order)
	// -----------------------------------------------------------------------

	// ---- Text ----

	public function testConstantHtml(): void
	{
		$this->assertSame('text/html', TMediaType::HTML);
	}

	public function testConstantPlain(): void
	{
		$this->assertSame('text/plain', TMediaType::PLAIN);
	}

	public function testConstantCss(): void
	{
		$this->assertSame('text/css', TMediaType::CSS);
	}

	public function testConstantJavascript(): void
	{
		$this->assertSame('text/javascript', TMediaType::JAVASCRIPT);
	}

	public function testConstantXmlText(): void
	{
		$this->assertSame('text/xml', TMediaType::XML_TEXT);
	}

	public function testConstantCsv(): void
	{
		$this->assertSame('text/csv', TMediaType::CSV);
	}

	public function testConstantEventStream(): void
	{
		$this->assertSame('text/event-stream', TMediaType::EVENT_STREAM);
	}

	public function testConstantMarkdown(): void
	{
		$this->assertSame('text/markdown', TMediaType::MARKDOWN);
	}

	public function testConstantCalendar(): void
	{
		$this->assertSame('text/calendar', TMediaType::CALENDAR);
	}

	// ---- Application ----

	public function testConstantJson(): void
	{
		$this->assertSame('application/json', TMediaType::JSON);
	}

	public function testConstantXml(): void
	{
		$this->assertSame('application/xml', TMediaType::XML);
	}

	public function testConstantXhtml(): void
	{
		$this->assertSame('application/xhtml+xml', TMediaType::XHTML);
	}

	public function testConstantForm(): void
	{
		$this->assertSame('application/x-www-form-urlencoded', TMediaType::FORM);
	}

	public function testConstantOctetStream(): void
	{
		$this->assertSame('application/octet-stream', TMediaType::OCTET_STREAM);
	}

	public function testConstantPdf(): void
	{
		$this->assertSame('application/pdf', TMediaType::PDF);
	}

	public function testConstantZip(): void
	{
		$this->assertSame('application/zip', TMediaType::ZIP);
	}

	public function testConstantJsonLd(): void
	{
		$this->assertSame('application/ld+json', TMediaType::JSON_LD);
	}

	public function testConstantWasm(): void
	{
		$this->assertSame('application/wasm', TMediaType::WASM);
	}

	public function testConstantGzip(): void
	{
		$this->assertSame('application/gzip', TMediaType::GZIP);
	}

	public function testConstantTar(): void
	{
		$this->assertSame('application/x-tar', TMediaType::TAR);
	}

	public function testConstantBzip2(): void
	{
		$this->assertSame('application/x-bzip2', TMediaType::BZIP2);
	}

	public function testConstantXz(): void
	{
		$this->assertSame('application/x-xz', TMediaType::XZ);
	}

	public function testConstantRtf(): void
	{
		$this->assertSame('application/rtf', TMediaType::RTF);
	}

	// ---- Multipart ----

	public function testConstantMultipart(): void
	{
		$this->assertSame('multipart/form-data', TMediaType::MULTIPART);
	}

	// ---- Syndication / feeds ----

	public function testConstantRss(): void
	{
		$this->assertSame('application/rss+xml', TMediaType::RSS);
	}

	public function testConstantAtom(): void
	{
		$this->assertSame('application/atom+xml', TMediaType::ATOM);
	}

	public function testConstantRdf(): void
	{
		$this->assertSame('application/rdf+xml', TMediaType::RDF);
	}

	// ---- Office documents ----

	public function testConstantDocx(): void
	{
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			TMediaType::DOCX
		);
	}

	public function testConstantXlsx(): void
	{
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			TMediaType::XLSX
		);
	}

	public function testConstantPptx(): void
	{
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			TMediaType::PPTX
		);
	}

	public function testConstantDoc(): void
	{
		$this->assertSame('application/msword', TMediaType::DOC);
	}

	public function testConstantXls(): void
	{
		$this->assertSame('application/vnd.ms-excel', TMediaType::XLS);
	}

	public function testConstantPpt(): void
	{
		$this->assertSame('application/vnd.ms-powerpoint', TMediaType::PPT);
	}

	// ---- CSP / Reporting API ----

	public function testConstantCspReport(): void
	{
		$this->assertSame('application/csp-report', TMediaType::CSP_REPORT);
	}

	public function testConstantReportsJson(): void
	{
		$this->assertSame('application/reports+json', TMediaType::REPORTS_JSON);
	}

	// ---- Image ----

	public function testConstantPng(): void
	{
		$this->assertSame('image/png', TMediaType::PNG);
	}

	public function testConstantJpeg(): void
	{
		$this->assertSame('image/jpeg', TMediaType::JPEG);
	}

	public function testConstantGif(): void
	{
		$this->assertSame('image/gif', TMediaType::GIF);
	}

	public function testConstantWebp(): void
	{
		$this->assertSame('image/webp', TMediaType::WEBP);
	}

	public function testConstantSvg(): void
	{
		$this->assertSame('image/svg+xml', TMediaType::SVG);
	}

	public function testConstantIcon(): void
	{
		$this->assertSame('image/x-icon', TMediaType::ICON);
	}

	public function testConstantAvif(): void
	{
		$this->assertSame('image/avif', TMediaType::AVIF);
	}

	public function testConstantBmp(): void
	{
		$this->assertSame('image/bmp', TMediaType::BMP);
	}

	public function testConstantTiff(): void
	{
		$this->assertSame('image/tiff', TMediaType::TIFF);
	}

	// ---- Audio ----

	public function testConstantAudioMpeg(): void
	{
		$this->assertSame('audio/mpeg', TMediaType::AUDIO_MPEG);
	}

	public function testConstantAudioOgg(): void
	{
		$this->assertSame('audio/ogg', TMediaType::AUDIO_OGG);
	}

	public function testConstantAudioWav(): void
	{
		$this->assertSame('audio/wav', TMediaType::AUDIO_WAV);
	}

	public function testConstantAudioWebm(): void
	{
		$this->assertSame('audio/webm', TMediaType::AUDIO_WEBM);
	}

	public function testConstantAudioAac(): void
	{
		$this->assertSame('audio/aac', TMediaType::AUDIO_AAC);
	}

	// ---- Video ----

	public function testConstantVideoMp4(): void
	{
		$this->assertSame('video/mp4', TMediaType::VIDEO_MP4);
	}

	public function testConstantVideoWebm(): void
	{
		$this->assertSame('video/webm', TMediaType::VIDEO_WEBM);
	}

	public function testConstantVideoOgg(): void
	{
		$this->assertSame('video/ogg', TMediaType::VIDEO_OGG);
	}

	// ---- Defaults ----

	public function testConstantDefaultType(): void
	{
		$this->assertSame('text', TMediaType::DEFAULT_TYPE);
	}

	public function testConstantDefaultSubtype(): void
	{
		$this->assertSame('html', TMediaType::DEFAULT_SUBTYPE);
	}

	// ---- Font ----

	public function testConstantWoff(): void
	{
		$this->assertSame('font/woff', TMediaType::WOFF);
	}

	public function testConstantWoff2(): void
	{
		$this->assertSame('font/woff2', TMediaType::WOFF2);
	}

	public function testConstantTtf(): void
	{
		$this->assertSame('font/ttf', TMediaType::TTF);
	}

	public function testConstantOtf(): void
	{
		$this->assertSame('font/otf', TMediaType::OTF);
	}

	// -----------------------------------------------------------------------
	// Constructor — default
	// -----------------------------------------------------------------------

	public function testConstructorDefaultIsTextHtml(): void
	{
		$mt = new TMediaType();
		$this->assertSame('text', $mt->getType());
		$this->assertSame('html', $mt->getSubtype());
		$this->assertSame('text/html', $mt->getMimeType());
		$this->assertSame([], $mt->getParameters());
	}

	public function testDefaultConstantsMatchNoArgConstructor(): void
	{
		// DEFAULT_TYPE and DEFAULT_SUBTYPE must agree with the no-arg constructor result.
		$mt = new TMediaType();
		$this->assertSame(TMediaType::DEFAULT_TYPE, $mt->getType());
		$this->assertSame(TMediaType::DEFAULT_SUBTYPE, $mt->getSubtype());
	}

	// -----------------------------------------------------------------------
	// Subclass DEFAULT_TYPE / DEFAULT_SUBTYPE — late static binding
	// -----------------------------------------------------------------------

	public function testSubclassDefaultsUsedWhenNoArgConstructed(): void
	{
		// A subclass that overrides both DEFAULT_TYPE and DEFAULT_SUBTYPE must have
		// its constants picked up via static:: in the constructor.
		$sub = new class extends TMediaType {
			public const DEFAULT_TYPE = 'application';
			public const DEFAULT_SUBTYPE = 'json';
		};
		$this->assertSame('application', $sub->getType());
		$this->assertSame('json', $sub->getSubtype());
		$this->assertSame('application/json', $sub->getMimeType());
	}

	public function testSubclassDefaultTypeOverrideAlone(): void
	{
		// Only DEFAULT_TYPE overridden — DEFAULT_SUBTYPE falls back to the base value.
		$sub = new class extends TMediaType {
			public const DEFAULT_TYPE = 'image';
		};
		$this->assertSame('image', $sub->getType());
		$this->assertSame(TMediaType::DEFAULT_SUBTYPE, $sub->getSubtype());
	}

	public function testSubclassExplicitArgOverridesDefaults(): void
	{
		// When an explicit media type is passed, it must win over the subclass defaults.
		$sub = new class('text/plain') extends TMediaType {
			public const DEFAULT_TYPE = 'application';
			public const DEFAULT_SUBTYPE = 'json';
		};
		$this->assertSame('text', $sub->getType());
		$this->assertSame('plain', $sub->getSubtype());
	}

	public function testSubclassNoArgConstructorHasNoParameters(): void
	{
		$sub = new class extends TMediaType {
			public const DEFAULT_TYPE = 'application';
			public const DEFAULT_SUBTYPE = 'xml';
		};
		$this->assertSame([], $sub->getParameters());
	}

	// -----------------------------------------------------------------------
	// Constructor — simple mime types
	// -----------------------------------------------------------------------

	public function testConstructorSimpleMimeType(): void
	{
		$mt = new TMediaType('application/json');
		$this->assertSame('application', $mt->getType());
		$this->assertSame('json', $mt->getSubtype());
		$this->assertSame('application/json', $mt->getMimeType());
	}

	public function testConstructorWithConstant(): void
	{
		$mt = new TMediaType(TMediaType::JSON);
		$this->assertSame('application/json', $mt->getMimeType());
	}

	public function testConstructorNormalizesToLowercase(): void
	{
		$mt = new TMediaType('Text/HTML');
		$this->assertSame('text', $mt->getType());
		$this->assertSame('html', $mt->getSubtype());
	}

	public function testConstructorTrimsWhitespace(): void
	{
		$mt = new TMediaType('  application/json  ');
		$this->assertSame('application/json', $mt->getMimeType());
	}

	// -----------------------------------------------------------------------
	// Constructor — with parameters
	// -----------------------------------------------------------------------

	public function testConstructorParsesCharsetParameter(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('text/html', $mt->getMimeType());
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorParsesQuotedParameterValue(): void
	{
		$mt = new TMediaType('text/html; charset="UTF-8"');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorParsesSingleQuotedParameterValue(): void
	{
		$mt = new TMediaType("text/html; charset='UTF-8'");
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorParsesMultipleParameters(): void
	{
		$mt = new TMediaType('multipart/form-data; boundary=----abc123; charset=UTF-8');
		$this->assertSame('----abc123', $mt->getParameter('boundary'));
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorNormalizesParameterNamesToLowercase(): void
	{
		$mt = new TMediaType('text/html; Charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getCharset());
		$this->assertSame('UTF-8', $mt->getParameter('charset'));
	}

	public function testConstructorHandlesExtraWhitespaceAroundSemicolon(): void
	{
		$mt = new TMediaType('text/html ;  charset = UTF-8');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorIgnoresMalformedParameters(): void
	{
		// A parameter without '=' is malformed and must be silently skipped.
		$mt = new TMediaType('text/html; garbage; charset=UTF-8');
		$this->assertNull($mt->getParameter('garbage'));
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	// -----------------------------------------------------------------------
	// Constructor — edge cases
	// -----------------------------------------------------------------------

	public function testConstructorEmptyStringYieldsEmptyTypeAndSubtype(): void
	{
		// An empty string produces an empty type and empty subtype.
		$mt = new TMediaType('');
		$this->assertSame('', $mt->getType());
		$this->assertSame('', $mt->getSubtype());
		$this->assertSame('', $mt->getMimeType());
		$this->assertSame([], $mt->getParameters());
	}

	public function testConstructorTypeOnlyNoSlash(): void
	{
		// When there is no '/', setMimeType() treats the whole string as the type.
		$mt = new TMediaType('text');
		$this->assertSame('text', $mt->getType());
		$this->assertSame('', $mt->getSubtype());
		$this->assertSame('text', $mt->getMimeType());
	}

	public function testConstructorTypeOnlyWithParameters(): void
	{
		// No '/' in the type portion, but parameters are still parsed.
		$mt = new TMediaType('text; charset=UTF-8');
		$this->assertSame('text', $mt->getType());
		$this->assertSame('', $mt->getSubtype());
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testConstructorEmptyStringToString(): void
	{
		// (string) of an empty-string construction must return ''.
		$mt = new TMediaType('');
		$this->assertSame('', (string) $mt);
	}

	public function testConstructorSubtypeWithPlus(): void
	{
		$mt = new TMediaType('image/svg+xml');
		$this->assertSame('image', $mt->getType());
		$this->assertSame('svg+xml', $mt->getSubtype());
	}

	public function testConstructorSubtypeWithDash(): void
	{
		$mt = new TMediaType('application/x-www-form-urlencoded');
		$this->assertSame('application', $mt->getType());
		$this->assertSame('x-www-form-urlencoded', $mt->getSubtype());
	}

	// -----------------------------------------------------------------------
	// Type getter / setter
	// -----------------------------------------------------------------------

	public function testGetSetType(): void
	{
		$mt = new TMediaType();
		$mt->setType('application');
		$this->assertSame('application', $mt->getType());
	}

	public function testSetTypeNormalizesToLowercase(): void
	{
		$mt = new TMediaType();
		$mt->setType('APPLICATION');
		$this->assertSame('application', $mt->getType());
	}

	public function testSetTypeTrimsWhitespace(): void
	{
		$mt = new TMediaType();
		$mt->setType('  image  ');
		$this->assertSame('image', $mt->getType());
	}

	// -----------------------------------------------------------------------
	// Subtype getter / setter
	// -----------------------------------------------------------------------

	public function testGetSetSubtype(): void
	{
		$mt = new TMediaType();
		$mt->setSubtype('json');
		$this->assertSame('json', $mt->getSubtype());
	}

	public function testSetSubtypeNormalizesToLowercase(): void
	{
		$mt = new TMediaType();
		$mt->setSubtype('JSON');
		$this->assertSame('json', $mt->getSubtype());
	}

	public function testSetSubtypeEmpty(): void
	{
		$mt = new TMediaType();
		$mt->setSubtype('');
		$this->assertSame('', $mt->getSubtype());
	}

	public function testSetSubtypeTrimsWhitespace(): void
	{
		$mt = new TMediaType();
		$mt->setSubtype('  json  ');
		$this->assertSame('json', $mt->getSubtype());
	}

	// -----------------------------------------------------------------------
	// MimeType getter / setter
	// -----------------------------------------------------------------------

	public function testGetMimeTypeCombinesTypeAndSubtype(): void
	{
		$mt = new TMediaType();
		$mt->setType('application');
		$mt->setSubtype('json');
		$this->assertSame('application/json', $mt->getMimeType());
	}

	public function testGetMimeTypeWithEmptySubtype(): void
	{
		$mt = new TMediaType();
		$mt->setType('text');
		$mt->setSubtype('');
		$this->assertSame('text', $mt->getMimeType());
	}

	public function testSetMimeTypeParsesTypeAndSubtype(): void
	{
		$mt = new TMediaType();
		$mt->setMimeType('application/json');
		$this->assertSame('application', $mt->getType());
		$this->assertSame('json', $mt->getSubtype());
	}

	public function testSetMimeTypeNormalizesToLowercase(): void
	{
		$mt = new TMediaType();
		$mt->setMimeType('TEXT/HTML');
		$this->assertSame('text', $mt->getType());
		$this->assertSame('html', $mt->getSubtype());
	}

	public function testSetMimeTypeDoesNotClearParameters(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setMimeType('application/json');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testSetMimeTypeNoSlashSetsTypeOnly(): void
	{
		$mt = new TMediaType();
		$mt->setMimeType('text');
		$this->assertSame('text', $mt->getType());
		$this->assertSame('', $mt->getSubtype());
	}

	public function testSetMimeTypeTrimsComponentWhitespace(): void
	{
		// Each component is trimmed individually via setType/setSubtype.
		$mt = new TMediaType();
		$mt->setMimeType(' application / json ');
		$this->assertSame('application', $mt->getType());
		$this->assertSame('json', $mt->getSubtype());
	}

	public function testSetMimeTypeMultipleSlashesSubtypeContainsRemainder(): void
	{
		// explode limit=2 — everything after the first slash becomes the subtype.
		$mt = new TMediaType();
		$mt->setMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$this->assertSame('application', $mt->getType());
		$this->assertSame(
			'vnd.openxmlformats-officedocument.wordprocessingml.document',
			$mt->getSubtype()
		);
	}

	// -----------------------------------------------------------------------
	// Parameters — full map
	// -----------------------------------------------------------------------

	public function testGetParametersDefaultEmpty(): void
	{
		$mt = new TMediaType();
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersReplacesMap(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameters(['boundary' => '----abc']);
		$this->assertSame(['boundary' => '----abc'], $mt->getParameters());
		$this->assertNull($mt->getCharset());
	}

	public function testSetParametersNormalizesNames(): void
	{
		$mt = new TMediaType();
		$mt->setParameters(['Charset' => 'UTF-8', 'BOUNDARY' => 'abc']);
		$this->assertArrayHasKey('charset', $mt->getParameters());
		$this->assertArrayHasKey('boundary', $mt->getParameters());
	}

	public function testClearParametersEmptiesMap(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameter('boundary', '----abc');
		$mt->clearParameters();
		$this->assertSame([], $mt->getParameters());
	}

	public function testClearParametersIsNoOpWhenAlreadyEmpty(): void
	{
		$mt = new TMediaType();
		$mt->clearParameters();
		$this->assertSame([], $mt->getParameters());
	}

	public function testClearParametersAllowsSubsequentAdd(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->clearParameters();
		$mt->setParameter('boundary', '----xyz');
		$this->assertSame(['boundary' => '----xyz'], $mt->getParameters());
		$this->assertNull($mt->getCharset());
	}

	public function testToStringAfterClearParameters(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->clearParameters();
		$this->assertSame('text/html', (string) $mt);
	}

	// -----------------------------------------------------------------------
	// Parameters — single accessor
	// -----------------------------------------------------------------------

	public function testGetParameterReturnsNullWhenAbsent(): void
	{
		$mt = new TMediaType();
		$this->assertNull($mt->getParameter('charset'));
	}

	public function testGetParameterCaseInsensitive(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getParameter('CHARSET'));
		$this->assertSame('UTF-8', $mt->getParameter('Charset'));
	}

	public function testSetParameterAddsNew(): void
	{
		$mt = new TMediaType();
		$mt->setParameter('boundary', '----xyz');
		$this->assertSame('----xyz', $mt->getParameter('boundary'));
	}

	public function testSetParameterReplacesExisting(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameter('charset', 'ISO-8859-1');
		$this->assertSame('ISO-8859-1', $mt->getCharset());
	}

	public function testSetParameterNormalizesName(): void
	{
		$mt = new TMediaType();
		$mt->setParameter('CHARSET', 'UTF-8');
		$this->assertSame('UTF-8', $mt->getParameter('charset'));
	}

	public function testSetParameterNullRemovesEntry(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameter('charset', null);
		$this->assertNull($mt->getCharset());
		$this->assertFalse($mt->hasParameter('charset'));
	}

	public function testSetParameterEmptyStringRemovesEntry(): void
	{
		// An empty string is treated as absent — equivalent to removeParameter().
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameter('charset', '');
		$this->assertNull($mt->getCharset());
		$this->assertFalse($mt->hasParameter('charset'));
	}

	// -----------------------------------------------------------------------
	// Parameters — remove / has
	// -----------------------------------------------------------------------

	public function testRemoveParameterDeletesEntry(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->removeParameter('charset');
		$this->assertNull($mt->getCharset());
		$this->assertSame([], $mt->getParameters());
	}

	public function testRemoveParameterCaseInsensitive(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->removeParameter('CHARSET');
		$this->assertNull($mt->getCharset());
	}

	public function testRemoveParameterNoOpWhenAbsent(): void
	{
		$mt = new TMediaType();
		// Must not throw.
		$mt->removeParameter('nonexistent');
		$this->assertSame([], $mt->getParameters());
	}

	public function testHasParameterTrueWhenPresent(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertTrue($mt->hasParameter('charset'));
	}

	public function testHasParameterFalseWhenAbsent(): void
	{
		$mt = new TMediaType();
		$this->assertFalse($mt->hasParameter('charset'));
	}

	public function testHasParameterCaseInsensitive(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertTrue($mt->hasParameter('CHARSET'));
		$this->assertTrue($mt->hasParameter('Charset'));
	}

	public function testGetParameterTrimsName(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getParameter(' charset '));
	}

	public function testSetParameterTrimsName(): void
	{
		$mt = new TMediaType();
		$mt->setParameter(' charset ', 'UTF-8');
		$this->assertSame('UTF-8', $mt->getParameter('charset'));
		$this->assertTrue($mt->hasParameter('charset'));
	}

	public function testHasParameterTrimsName(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertTrue($mt->hasParameter(' charset '));
	}

	public function testRemoveParameterTrimsName(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->removeParameter(' charset ');
		$this->assertFalse($mt->hasParameter('charset'));
	}

	public function testSetParametersArrayPathTrimsName(): void
	{
		$mt = new TMediaType();
		$mt->setParameters([' charset ' => 'UTF-8']);
		$this->assertSame('UTF-8', $mt->getParameter('charset'));
		$this->assertTrue($mt->hasParameter('charset'));
	}

	// -----------------------------------------------------------------------
	// Charset convenience
	// -----------------------------------------------------------------------

	public function testGetCharsetNullWhenAbsent(): void
	{
		$mt = new TMediaType();
		$this->assertNull($mt->getCharset());
	}

	public function testGetCharsetReturnsValue(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testSetCharsetAdds(): void
	{
		$mt = new TMediaType();
		$mt->setCharset('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $mt->getCharset());
	}

	public function testSetCharsetReplaces(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setCharset('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $mt->getCharset());
	}

	public function testSetCharsetNullRemovesParameter(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setCharset(null);
		$this->assertNull($mt->getCharset());
		$this->assertFalse($mt->hasParameter('charset'));
	}

	public function testSetCharsetEmptyStringRemovesParameter(): void
	{
		// setCharset('') delegates to setParameter('charset', ''), which the trait
		// treats as a removal — HTTP parameters have no meaningful empty value.
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setCharset('');
		$this->assertNull($mt->getCharset());
		$this->assertFalse($mt->hasParameter('charset'));
	}

	// -----------------------------------------------------------------------
	// Boundary convenience
	// -----------------------------------------------------------------------

	public function testGetBoundaryNullWhenAbsent(): void
	{
		$mt = new TMediaType();
		$this->assertNull($mt->getBoundary());
	}

	public function testGetBoundaryReturnsValue(): void
	{
		$mt = new TMediaType('multipart/form-data; boundary=----abc');
		$this->assertSame('----abc', $mt->getBoundary());
	}

	public function testSetBoundaryAdds(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setBoundary('----WebKitFormBoundaryXYZ');
		$this->assertSame('----WebKitFormBoundaryXYZ', $mt->getBoundary());
	}

	public function testSetBoundaryReplaces(): void
	{
		$mt = new TMediaType('multipart/form-data; boundary=----old');
		$mt->setBoundary('----new');
		$this->assertSame('----new', $mt->getBoundary());
	}

	public function testSetBoundaryNullRemoves(): void
	{
		$mt = new TMediaType('multipart/form-data; boundary=----abc');
		$mt->setBoundary(null);
		$this->assertNull($mt->getBoundary());
		$this->assertFalse($mt->hasParameter('boundary'));
	}

	public function testSetBoundaryEmptyStringRemoves(): void
	{
		// Same removal semantics as setBoundary(null) — empty string has no meaning.
		$mt = new TMediaType('multipart/form-data; boundary=----abc');
		$mt->setBoundary('');
		$this->assertNull($mt->getBoundary());
		$this->assertFalse($mt->hasParameter('boundary'));
	}

	public function testBoundaryAppearsInToString(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setBoundary('----abc');
		$this->assertSame('multipart/form-data; boundary=----abc', (string) $mt);
	}

	public function testBoundaryAndCharsetTogetherInToString(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setCharset('UTF-8');
		$mt->setBoundary('----abc');
		$str = (string) $mt;
		$this->assertStringContainsString('charset=UTF-8', $str);
		$this->assertStringContainsString('boundary=----abc', $str);
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringNoParameters(): void
	{
		$mt = new TMediaType('application/json');
		$this->assertSame('application/json', (string) $mt);
	}

	public function testToStringWithCharset(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('text/html; charset=UTF-8', (string) $mt);
	}

	public function testToStringMultipleParameters(): void
	{
		$mt = new TMediaType('multipart/form-data; boundary=----abc; charset=UTF-8');
		$str = (string) $mt;
		$this->assertStringContainsString('multipart/form-data', $str);
		$this->assertStringContainsString('boundary=----abc', $str);
		$this->assertStringContainsString('charset=UTF-8', $str);
	}

	public function testToStringAfterMutation(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setCharset('ISO-8859-1');
		$this->assertSame('text/html; charset=ISO-8859-1', (string) $mt);
	}

	public function testToStringAfterRemovingCharset(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setCharset(null);
		$this->assertSame('text/html', (string) $mt);
	}

	public function testToStringTypeOnlyNoSubtype(): void
	{
		$mt = new TMediaType('text');
		$this->assertSame('text', (string) $mt);
	}

	public function testToStringNormalizesQuotedParameterToUnquoted(): void
	{
		// Quoted values are stripped on parse and emitted without quotes when the
		// value is a valid token — a round-trip normalizes unnecessary quoting.
		$mt = new TMediaType('text/html; charset="UTF-8"');
		$this->assertSame('text/html; charset=UTF-8', (string) $mt);
	}

	public function testToStringPreservesParameterInsertionOrder(): void
	{
		$mt = new TMediaType();
		$mt->setMimeType('multipart/form-data');
		$mt->setParameter('boundary', '----abc');
		$mt->setParameter('charset', 'UTF-8');
		// boundary was inserted first, so it must come first.
		$this->assertSame('multipart/form-data; boundary=----abc; charset=UTF-8', (string) $mt);
	}

	// -----------------------------------------------------------------------
	// Round-trip fidelity
	// -----------------------------------------------------------------------

	public function testRoundTripHtmlWithCharset(): void
	{
		$original = 'text/html; charset=UTF-8';
		$mt = new TMediaType($original);
		$this->assertSame($original, (string) $mt);
	}

	public function testRoundTripJson(): void
	{
		$mt = new TMediaType(TMediaType::JSON);
		$this->assertSame('application/json', (string) $mt);
	}

	public function testRoundTripSvg(): void
	{
		$mt = new TMediaType(TMediaType::SVG);
		$this->assertSame('image/svg+xml', (string) $mt);
	}

	public function testRoundTripForm(): void
	{
		$mt = new TMediaType(TMediaType::FORM);
		$this->assertSame('application/x-www-form-urlencoded', (string) $mt);
	}

	public function testRoundTripMultipart(): void
	{
		$mt = new TMediaType(TMediaType::MULTIPART);
		$this->assertSame('multipart/form-data', (string) $mt);
	}

	public function testRoundTripMarkdown(): void
	{
		$mt = new TMediaType(TMediaType::MARKDOWN);
		$this->assertSame('text/markdown', (string) $mt);
	}

	public function testRoundTripCalendar(): void
	{
		$mt = new TMediaType(TMediaType::CALENDAR);
		$this->assertSame('text/calendar', (string) $mt);
	}

	public function testRoundTripJsonLd(): void
	{
		$mt = new TMediaType(TMediaType::JSON_LD);
		$this->assertSame('application/ld+json', (string) $mt);
	}

	public function testRoundTripDocx(): void
	{
		$mt = new TMediaType(TMediaType::DOCX);
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			(string) $mt
		);
	}

	public function testRoundTripXlsx(): void
	{
		$mt = new TMediaType(TMediaType::XLSX);
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			(string) $mt
		);
	}

	public function testRoundTripPptx(): void
	{
		$mt = new TMediaType(TMediaType::PPTX);
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			(string) $mt
		);
	}

	public function testRoundTripAudioMpeg(): void
	{
		$mt = new TMediaType(TMediaType::AUDIO_MPEG);
		$this->assertSame('audio/mpeg', (string) $mt);
	}

	public function testRoundTripVideoMp4(): void
	{
		$mt = new TMediaType(TMediaType::VIDEO_MP4);
		$this->assertSame('video/mp4', (string) $mt);
	}

	public function testRoundTripAvif(): void
	{
		$mt = new TMediaType(TMediaType::AVIF);
		$this->assertSame('image/avif', (string) $mt);
	}

	public function testRoundTripWoff2(): void
	{
		$mt = new TMediaType(TMediaType::WOFF2);
		$this->assertSame('font/woff2', (string) $mt);
	}

	public function testRoundTripGzip(): void
	{
		$mt = new TMediaType(TMediaType::GZIP);
		$this->assertSame('application/gzip', (string) $mt);
	}

	public function testRoundTripTar(): void
	{
		$mt = new TMediaType(TMediaType::TAR);
		$this->assertSame('application/x-tar', (string) $mt);
	}

	public function testRoundTripBzip2(): void
	{
		$mt = new TMediaType(TMediaType::BZIP2);
		$this->assertSame('application/x-bzip2', (string) $mt);
	}

	public function testRoundTripXz(): void
	{
		$mt = new TMediaType(TMediaType::XZ);
		$this->assertSame('application/x-xz', (string) $mt);
	}

	public function testRoundTripRss(): void
	{
		// '+xml' suffix in subtype must survive intact.
		$mt = new TMediaType(TMediaType::RSS);
		$this->assertSame('application/rss+xml', (string) $mt);
	}

	public function testRoundTripAtom(): void
	{
		$mt = new TMediaType(TMediaType::ATOM);
		$this->assertSame('application/atom+xml', (string) $mt);
	}

	public function testRoundTripRdf(): void
	{
		$mt = new TMediaType(TMediaType::RDF);
		$this->assertSame('application/rdf+xml', (string) $mt);
	}

	public function testRoundTripCspReport(): void
	{
		$mt = new TMediaType(TMediaType::CSP_REPORT);
		$this->assertSame('application/csp-report', (string) $mt);
	}

	public function testRoundTripReportsJson(): void
	{
		$mt = new TMediaType(TMediaType::REPORTS_JSON);
		$this->assertSame('application/reports+json', (string) $mt);
	}

	public function testRoundTripRtf(): void
	{
		$mt = new TMediaType(TMediaType::RTF);
		$this->assertSame('application/rtf', (string) $mt);
	}

	public function testRoundTripWasm(): void
	{
		$mt = new TMediaType(TMediaType::WASM);
		$this->assertSame('application/wasm', (string) $mt);
	}

	// -----------------------------------------------------------------------
	// Mutation after construction
	// -----------------------------------------------------------------------

	public function testMutateTypeAndSubtype(): void
	{
		$mt = new TMediaType();
		$mt->setType('application');
		$mt->setSubtype('json');
		$this->assertSame('application/json', $mt->getMimeType());
	}

	public function testMutatePreservesParameters(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setMimeType('application/json');
		// Parameters must survive a MimeType change.
		$this->assertSame('UTF-8', $mt->getCharset());
		$this->assertSame('application/json; charset=UTF-8', (string) $mt);
	}

	// -----------------------------------------------------------------------
	// ArrayAccess — parameter pipe
	// -----------------------------------------------------------------------

	public function testOffsetGetReturnsParameterValue(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertSame('UTF-8', $mt['charset']);
	}

	public function testOffsetGetReturnsNullWhenAbsent(): void
	{
		$mt = new TMediaType('text/html');
		$this->assertNull($mt['charset']);
	}

	public function testOffsetExistsReturnsTrueWhenPresent(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$this->assertTrue(isset($mt['charset']));
	}

	public function testOffsetExistsReturnsFalseWhenAbsent(): void
	{
		$mt = new TMediaType('text/html');
		$this->assertFalse(isset($mt['charset']));
	}

	public function testOffsetSetAddsParameter(): void
	{
		$mt = new TMediaType('text/html');
		$mt['charset'] = 'UTF-8';
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testOffsetSetOverwritesExistingParameter(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt['charset'] = 'ISO-8859-1';
		$this->assertSame('ISO-8859-1', $mt['charset']);
	}

	public function testOffsetUnsetRemovesParameter(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		unset($mt['charset']);
		$this->assertFalse(isset($mt['charset']));
		$this->assertNull($mt->getCharset());
	}

	public function testOffsetUnsetNonExistentIsNoop(): void
	{
		$mt = new TMediaType('text/html');
		unset($mt['charset']); // must not throw
		$this->assertFalse(isset($mt['charset']));
	}

	public function testArrayAccessNamesNormalizedToLowercase(): void
	{
		$mt = new TMediaType('text/html');
		$mt['Charset'] = 'UTF-8';
		// Both spellings address the same entry.
		$this->assertSame('UTF-8', $mt['charset']);
		$this->assertTrue(isset($mt['CHARSET']));
		unset($mt['CharSet']);
		$this->assertFalse(isset($mt['charset']));
	}

	public function testArrayAccessRoundTripsWithToString(): void
	{
		$mt = new TMediaType('application/json');
		$mt['charset'] = 'UTF-8';
		$this->assertSame('application/json; charset=UTF-8', (string) $mt);
		unset($mt['charset']);
		$this->assertSame('application/json', (string) $mt);
	}

	public function testArrayAccessImplementsArrayAccessInterface(): void
	{
		$mt = new TMediaType();
		$this->assertInstanceOf(\ArrayAccess::class, $mt);
	}

	// -----------------------------------------------------------------------
	// setParameters — string form
	// -----------------------------------------------------------------------

	public function testSetParametersStringParsesSimpleParam(): void
	{
		$mt = new TMediaType('text/html');
		$mt->setParameters('charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testSetParametersStringParsesMultipleParams(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setParameters('charset=UTF-8; boundary=----foo');
		$this->assertSame('UTF-8', $mt->getCharset());
		$this->assertSame('----foo', $mt->getParameter('boundary'));
	}

	public function testSetParametersStringWithLeadingSemicolon(): void
	{
		$mt = new TMediaType('text/html');
		$mt->setParameters('; charset=UTF-8');
		$this->assertSame('UTF-8', $mt->getCharset());
	}

	public function testSetParametersStringStripsQuotedValues(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setParameters('boundary="----WebKitFormBoundary"');
		$this->assertSame('----WebKitFormBoundary', $mt->getParameter('boundary'));
	}

	public function testSetParametersStringStripsSingleQuotedValues(): void
	{
		$mt = new TMediaType('multipart/form-data');
		$mt->setParameters("boundary='----abc'");
		$this->assertSame('----abc', $mt->getParameter('boundary'));
	}

	public function testSetParametersStringNormalizesNamesToLowercase(): void
	{
		$mt = new TMediaType('text/html');
		$mt->setParameters('Charset=UTF-8');
		$params = $mt->getParameters();
		$this->assertArrayHasKey('charset', $params);
		$this->assertSame('UTF-8', $params['charset']);
	}

	public function testSetParametersEmptyStringClearsParameters(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameters('');
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersEmptyArrayClearsAll(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameters([]);
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersStringReplacesExistingParams(): void
	{
		$mt = new TMediaType('text/html; charset=UTF-8; boundary=abc');
		$mt->setParameters('charset=ISO-8859-1');
		$this->assertSame('ISO-8859-1', $mt->getCharset());
		$this->assertNull($mt->getParameter('boundary'));
	}

	public function testSetParametersStringRoundTripsWithToString(): void
	{
		$mt = new TMediaType('text/html');
		$mt->setParameters('charset=UTF-8; boundary=foo');
		$this->assertSame('text/html; charset=UTF-8; boundary=foo', (string) $mt);
	}

	public function testSetParametersWhitespaceOnlyStringClearsParameters(): void
	{
		// Whitespace-only input is trimmed to '' before the split; PREG_SPLIT_NO_EMPTY
		// then yields no tokens, so the result must be an empty map.
		$mt = new TMediaType('text/html; charset=UTF-8');
		$mt->setParameters('   ');
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersStringWithOnlyMalformedPairsProducesNoParams(): void
	{
		// Pairs without a name (e.g. '=value') or without a value (e.g. 'name=')
		// are silently skipped because the regex requires both a non-empty name
		// and a non-empty value.
		$mt = new TMediaType('text/html');
		$mt->setParameters('=value; name=');
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersStringSkipsBareTokenWithoutEqualsSign(): void
	{
		// A bare token with no '=' does not match the regex
		// /^([a-zA-Z0-9_\-\*]+)\s*=\s*(.+)$/ and is silently skipped.
		$mt = new TMediaType('text/html');
		$mt->setParameters('onlythis');
		$this->assertSame([], $mt->getParameters());
	}

	public function testSetParametersStringValueWithEmbeddedEqualsSign(): void
	{
		// The regex uses a greedy (.+) for the value half, so everything after
		// the first '=' is captured — embedded '=' characters are included.
		$mt = new TMediaType('text/html');
		$mt->setParameters('param=key=value');
		$this->assertSame('key=value', $mt->getParameter('param'));
	}

	// -----------------------------------------------------------------------
	// ArrayAccess — offsetSet value coercion
	// -----------------------------------------------------------------------

	public function testOffsetSetCoercesIntegerValueToString(): void
	{
		// offsetSet() calls setParameter((string) $offset, (string) $value), so
		// an integer value must be stored as its string equivalent.
		$mt = new TMediaType('text/html');
		$mt['charset'] = 42;
		$this->assertSame('42', $mt->getCharset());
	}
}
