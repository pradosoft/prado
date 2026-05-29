<?php

/**
 * TCspViolationParameterTest
 *
 * Unit tests for {@see \Prado\Web\Services\TCspViolationParameter}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\Services\TCspViolationParameter;

class TCspViolationParameterTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Constants — shared field names (same key in both formats)
	// -----------------------------------------------------------------------

	public function testConstantDisposition(): void
	{
		self::assertSame('disposition', TCspViolationParameter::DISPOSITION);
	}

	public function testConstantReferrer(): void
	{
		self::assertSame('referrer', TCspViolationParameter::REFERRER);
	}

	// -----------------------------------------------------------------------
	// Constants — modern (application/reports+json) field names
	// -----------------------------------------------------------------------

	public function testConstantBlockedUrl(): void
	{
		self::assertSame('blockedURL', TCspViolationParameter::BLOCKED_URL);
	}

	public function testConstantColumnNumber(): void
	{
		self::assertSame('columnNumber', TCspViolationParameter::COLUMN_NUMBER);
	}

	public function testConstantDocumentUrl(): void
	{
		self::assertSame('documentURL', TCspViolationParameter::DOCUMENT_URL);
	}

	public function testConstantEffectiveDirective(): void
	{
		self::assertSame('effectiveDirective', TCspViolationParameter::EFFECTIVE_DIRECTIVE);
	}

	public function testConstantLineNumber(): void
	{
		self::assertSame('lineNumber', TCspViolationParameter::LINE_NUMBER);
	}

	public function testConstantOriginalPolicy(): void
	{
		self::assertSame('originalPolicy', TCspViolationParameter::ORIGINAL_POLICY);
	}

	public function testConstantSample(): void
	{
		self::assertSame('sample', TCspViolationParameter::SAMPLE);
	}

	public function testConstantSourceFile(): void
	{
		self::assertSame('sourceFile', TCspViolationParameter::SOURCE_FILE);
	}

	public function testConstantStatusCode(): void
	{
		self::assertSame('statusCode', TCspViolationParameter::STATUS_CODE);
	}

	// -----------------------------------------------------------------------
	// Constants — legacy (application/csp-report) field names
	// -----------------------------------------------------------------------

	public function testConstantBlockedUriLegacy(): void
	{
		self::assertSame('blocked-uri', TCspViolationParameter::BLOCKED_URI_LEGACY);
	}

	public function testConstantColumnNumberLegacy(): void
	{
		self::assertSame('column-number', TCspViolationParameter::COLUMN_NUMBER_LEGACY);
	}

	public function testConstantDocumentUriLegacy(): void
	{
		self::assertSame('document-uri', TCspViolationParameter::DOCUMENT_URI_LEGACY);
	}

	public function testConstantEffectiveDirectiveLegacy(): void
	{
		self::assertSame('effective-directive', TCspViolationParameter::EFFECTIVE_DIRECTIVE_LEGACY);
	}

	public function testConstantLineNumberLegacy(): void
	{
		self::assertSame('line-number', TCspViolationParameter::LINE_NUMBER_LEGACY);
	}

	public function testConstantOriginalPolicyLegacy(): void
	{
		self::assertSame('original-policy', TCspViolationParameter::ORIGINAL_POLICY_LEGACY);
	}

	public function testConstantScriptSampleLegacy(): void
	{
		self::assertSame('script-sample', TCspViolationParameter::SCRIPT_SAMPLE_LEGACY);
	}

	public function testConstantSourceFileLegacy(): void
	{
		self::assertSame('source-file', TCspViolationParameter::SOURCE_FILE_LEGACY);
	}

	public function testConstantStatusCodeLegacy(): void
	{
		self::assertSame('status-code', TCspViolationParameter::STATUS_CODE_LEGACY);
	}

	public function testConstantViolatedDirectiveLegacy(): void
	{
		self::assertSame('violated-directive', TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY);
	}

	// -----------------------------------------------------------------------
	// Constructor / getReport / getParameter
	// -----------------------------------------------------------------------

	public function testConstructorStoresReport(): void
	{
		$report = ['document-uri' => 'https://example.com'];
		$param = new TCspViolationParameter($report);
		self::assertSame($report, $param->getReport());
	}

	public function testGetReportEquivalentToGetParameter(): void
	{
		$report = ['blocked-uri' => 'https://evil.example.com/script.js'];
		$param = new TCspViolationParameter($report);
		self::assertSame($param->getReport(), (array) $param->getParameter());
	}

	public function testGetReportEmptyArray(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame([], $param->getReport());
	}

	// -----------------------------------------------------------------------
	// ArrayAccess
	// -----------------------------------------------------------------------

	public function testArrayAccessLegacyKey(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::BLOCKED_URI_LEGACY => 'https://blocked.example.com',
		]);
		self::assertSame('https://blocked.example.com', $param[TCspViolationParameter::BLOCKED_URI_LEGACY]);
	}

	public function testArrayAccessModernKey(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::BLOCKED_URL => 'https://blocked.example.com',
		]);
		self::assertSame('https://blocked.example.com', $param[TCspViolationParameter::BLOCKED_URL]);
	}

	public function testArrayAccessMissingKeyReturnsNull(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertNull($param[TCspViolationParameter::BLOCKED_URI_LEGACY]);
	}

	public function testArrayAccessIssetReturnsTrueForExistingKey(): void
	{
		$param = new TCspViolationParameter(['referrer' => 'https://ref.example.com']);
		self::assertTrue(isset($param[TCspViolationParameter::REFERRER]));
	}

	public function testArrayAccessIssetReturnsFalseForMissingKey(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertFalse(isset($param[TCspViolationParameter::REFERRER]));
	}

	// -----------------------------------------------------------------------
	// getDocumentUrl
	// -----------------------------------------------------------------------

	public function testGetDocumentUrlModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DOCUMENT_URL => 'https://doc.example.com']);
		self::assertSame('https://doc.example.com', $param->getDocumentUrl());
	}

	public function testGetDocumentUrlFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DOCUMENT_URI_LEGACY => 'https://doc.example.com']);
		self::assertSame('https://doc.example.com', $param->getDocumentUrl());
	}

	public function testGetDocumentUrlModernTakesPrecedenceOverLegacy(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::DOCUMENT_URI_LEGACY => 'https://legacy.example.com',
			TCspViolationParameter::DOCUMENT_URL        => 'https://modern.example.com',
		]);
		self::assertSame('https://modern.example.com', $param->getDocumentUrl());
	}

	public function testGetDocumentUrlAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getDocumentUrl());
	}

	// -----------------------------------------------------------------------
	// getReferrer
	// -----------------------------------------------------------------------

	public function testGetReferrerReturnsValue(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::REFERRER => 'https://ref.example.com']);
		self::assertSame('https://ref.example.com', $param->getReferrer());
	}

	public function testGetReferrerAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getReferrer());
	}

	// -----------------------------------------------------------------------
	// getBlockedUrl
	// -----------------------------------------------------------------------

	public function testGetBlockedUrlModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::BLOCKED_URL => 'https://blocked.example.com']);
		self::assertSame('https://blocked.example.com', $param->getBlockedUrl());
	}

	public function testGetBlockedUrlFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::BLOCKED_URI_LEGACY => 'https://blocked.example.com']);
		self::assertSame('https://blocked.example.com', $param->getBlockedUrl());
	}

	public function testGetBlockedUrlModernTakesPrecedenceOverLegacy(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::BLOCKED_URI_LEGACY => 'https://legacy-blocked.example.com',
			TCspViolationParameter::BLOCKED_URL        => 'https://modern-blocked.example.com',
		]);
		self::assertSame('https://modern-blocked.example.com', $param->getBlockedUrl());
	}

	public function testGetBlockedUrlAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getBlockedUrl());
	}

	// -----------------------------------------------------------------------
	// isModernFormat / isLegacyFormat
	// -----------------------------------------------------------------------

	public function testIsModernFormatTrueWhenDocumentUrlPresent(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DOCUMENT_URL => 'https://example.com']);
		self::assertTrue($param->isModernFormat());
		self::assertFalse($param->isLegacyFormat());
	}

	public function testIsModernFormatTrueWhenEffectiveDirectivePresent(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::EFFECTIVE_DIRECTIVE => 'script-src']);
		self::assertTrue($param->isModernFormat());
	}

	public function testIsLegacyFormatTrueWhenDocumentUriLegacyPresent(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DOCUMENT_URI_LEGACY => 'https://example.com']);
		self::assertTrue($param->isLegacyFormat());
		self::assertFalse($param->isModernFormat());
	}

	public function testIsLegacyFormatTrueWhenViolatedDirectiveLegacyPresent(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY => 'script-src']);
		self::assertTrue($param->isLegacyFormat());
	}

	public function testFormatUnknownWhenReportIsEmpty(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertFalse($param->isModernFormat());
		self::assertFalse($param->isLegacyFormat());
	}

	public function testBothFormatsDetectedWhenMixedKeysPresent(): void
	{
		// A report containing keys from both formats (e.g. a merged or
		// hand-crafted payload) can be simultaneously modern and legacy.
		$param = new TCspViolationParameter([
			TCspViolationParameter::DOCUMENT_URL       => 'https://example.com',
			TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY => 'script-src',
		]);
		self::assertTrue($param->isModernFormat());
		self::assertTrue($param->isLegacyFormat());
	}

	// -----------------------------------------------------------------------
	// getViolatedDirective
	// -----------------------------------------------------------------------

	public function testGetViolatedDirectiveLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY => 'script-src']);
		self::assertSame('script-src', $param->getViolatedDirective());
	}

	public function testGetViolatedDirectiveAbsentReturnsEmptyString(): void
	{
		// No format indicator present — treated as indeterminate (not modern),
		// so returns '' rather than null.
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getViolatedDirective());
	}

	public function testGetViolatedDirectiveReturnsNullForModernFormat(): void
	{
		// Modern reports+json format does not carry violated-directive at all.
		$param = new TCspViolationParameter([
			TCspViolationParameter::EFFECTIVE_DIRECTIVE => 'script-src',
		]);
		self::assertNull($param->getViolatedDirective());
	}

	// -----------------------------------------------------------------------
	// getEffectiveDirective
	// -----------------------------------------------------------------------

	public function testGetEffectiveDirectiveModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::EFFECTIVE_DIRECTIVE => 'script-src']);
		self::assertSame('script-src', $param->getEffectiveDirective());
	}

	public function testGetEffectiveDirectiveFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::EFFECTIVE_DIRECTIVE_LEGACY => 'script-src']);
		self::assertSame('script-src', $param->getEffectiveDirective());
	}

	public function testGetEffectiveDirectiveModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::EFFECTIVE_DIRECTIVE_LEGACY => 'script-src legacy',
			TCspViolationParameter::EFFECTIVE_DIRECTIVE        => 'script-src modern',
		]);
		self::assertSame('script-src modern', $param->getEffectiveDirective());
	}

	public function testGetEffectiveDirectiveAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getEffectiveDirective());
	}

	// -----------------------------------------------------------------------
	// getOriginalPolicy
	// -----------------------------------------------------------------------

	public function testGetOriginalPolicyModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::ORIGINAL_POLICY => "default-src 'none'"]);
		self::assertSame("default-src 'none'", $param->getOriginalPolicy());
	}

	public function testGetOriginalPolicyFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::ORIGINAL_POLICY_LEGACY => "default-src 'none'"]);
		self::assertSame("default-src 'none'", $param->getOriginalPolicy());
	}

	public function testGetOriginalPolicyModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::ORIGINAL_POLICY_LEGACY => "default-src 'none' legacy",
			TCspViolationParameter::ORIGINAL_POLICY        => "default-src 'none' modern",
		]);
		self::assertSame("default-src 'none' modern", $param->getOriginalPolicy());
	}

	public function testGetOriginalPolicyAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getOriginalPolicy());
	}

	// -----------------------------------------------------------------------
	// getDisposition
	// -----------------------------------------------------------------------

	public function testGetDispositionEnforce(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DISPOSITION => 'enforce']);
		self::assertSame('enforce', $param->getDisposition());
	}

	public function testGetDispositionReport(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::DISPOSITION => 'report']);
		self::assertSame('report', $param->getDisposition());
	}

	public function testGetDispositionAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getDisposition());
	}

	// -----------------------------------------------------------------------
	// getStatusCode
	// -----------------------------------------------------------------------

	public function testGetStatusCodeModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::STATUS_CODE => 404]);
		self::assertSame(404, $param->getStatusCode());
	}

	public function testGetStatusCodeFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::STATUS_CODE_LEGACY => 200]);
		self::assertSame(200, $param->getStatusCode());
	}

	public function testGetStatusCodeModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::STATUS_CODE_LEGACY => 200,
			TCspViolationParameter::STATUS_CODE        => 404,
		]);
		self::assertSame(404, $param->getStatusCode());
	}

	public function testGetStatusCodeAbsentReturnsZero(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame(0, $param->getStatusCode());
	}

	public function testGetStatusCodeCoercesStringToInt(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::STATUS_CODE_LEGACY => '200']);
		self::assertSame(200, $param->getStatusCode());
	}

	public function testGetStatusCodeModernKeyCoercesStringToInt(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::STATUS_CODE => '404']);
		self::assertSame(404, $param->getStatusCode());
	}

	// -----------------------------------------------------------------------
	// getLineNumber
	// -----------------------------------------------------------------------

	public function testGetLineNumberModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::LINE_NUMBER => 99]);
		self::assertSame(99, $param->getLineNumber());
	}

	public function testGetLineNumberFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::LINE_NUMBER_LEGACY => 42]);
		self::assertSame(42, $param->getLineNumber());
	}

	public function testGetLineNumberModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::LINE_NUMBER_LEGACY => 10,
			TCspViolationParameter::LINE_NUMBER        => 20,
		]);
		self::assertSame(20, $param->getLineNumber());
	}

	public function testGetLineNumberAbsentReturnsZero(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame(0, $param->getLineNumber());
	}

	public function testGetLineNumberCoercesStringToInt(): void
	{
		// Browsers may send numeric fields as JSON strings; (int) coercion
		// must convert them to a proper integer.
		$param = new TCspViolationParameter([TCspViolationParameter::LINE_NUMBER_LEGACY => '42']);
		self::assertSame(42, $param->getLineNumber());
	}

	public function testGetLineNumberModernKeyCoercesStringToInt(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::LINE_NUMBER => '99']);
		self::assertSame(99, $param->getLineNumber());
	}

	// -----------------------------------------------------------------------
	// getColumnNumber
	// -----------------------------------------------------------------------

	public function testGetColumnNumberModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::COLUMN_NUMBER => 15]);
		self::assertSame(15, $param->getColumnNumber());
	}

	public function testGetColumnNumberFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::COLUMN_NUMBER_LEGACY => 7]);
		self::assertSame(7, $param->getColumnNumber());
	}

	public function testGetColumnNumberModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::COLUMN_NUMBER_LEGACY => 5,
			TCspViolationParameter::COLUMN_NUMBER        => 10,
		]);
		self::assertSame(10, $param->getColumnNumber());
	}

	public function testGetColumnNumberAbsentReturnsZero(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame(0, $param->getColumnNumber());
	}

	public function testGetColumnNumberCoercesStringToInt(): void
	{
		// Browsers may send numeric fields as JSON strings; (int) coercion
		// must convert them to a proper integer.
		$param = new TCspViolationParameter([TCspViolationParameter::COLUMN_NUMBER_LEGACY => '7']);
		self::assertSame(7, $param->getColumnNumber());
	}

	public function testGetColumnNumberModernKeyCoercesStringToInt(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::COLUMN_NUMBER => '15']);
		self::assertSame(15, $param->getColumnNumber());
	}

	// -----------------------------------------------------------------------
	// getSourceFile
	// -----------------------------------------------------------------------

	public function testGetSourceFileModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::SOURCE_FILE => 'https://example.com/app.js']);
		self::assertSame('https://example.com/app.js', $param->getSourceFile());
	}

	public function testGetSourceFileFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::SOURCE_FILE_LEGACY => 'https://example.com/app.js']);
		self::assertSame('https://example.com/app.js', $param->getSourceFile());
	}

	public function testGetSourceFileModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::SOURCE_FILE_LEGACY => 'https://legacy.example.com/app.js',
			TCspViolationParameter::SOURCE_FILE        => 'https://modern.example.com/app.js',
		]);
		self::assertSame('https://modern.example.com/app.js', $param->getSourceFile());
	}

	public function testGetSourceFileAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getSourceFile());
	}

	// -----------------------------------------------------------------------
	// getSample
	// -----------------------------------------------------------------------

	public function testGetSampleModernKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::SAMPLE => 'alert(1)']);
		self::assertSame('alert(1)', $param->getSample());
	}

	public function testGetSampleFallsBackToLegacyKey(): void
	{
		$param = new TCspViolationParameter([TCspViolationParameter::SCRIPT_SAMPLE_LEGACY => 'alert(1)']);
		self::assertSame('alert(1)', $param->getSample());
	}

	public function testGetSampleModernTakesPrecedence(): void
	{
		$param = new TCspViolationParameter([
			TCspViolationParameter::SCRIPT_SAMPLE_LEGACY => 'legacy sample',
			TCspViolationParameter::SAMPLE               => 'modern sample',
		]);
		self::assertSame('modern sample', $param->getSample());
	}

	public function testGetSampleAbsentReturnsEmptyString(): void
	{
		$param = new TCspViolationParameter([]);
		self::assertSame('', $param->getSample());
	}

	// -----------------------------------------------------------------------
	// Full legacy report round-trip
	// -----------------------------------------------------------------------

	public function testAllLegacyGettersOnFullReport(): void
	{
		$report = [
			TCspViolationParameter::DOCUMENT_URI_LEGACY        => 'https://example.com/page',
			TCspViolationParameter::REFERRER                   => 'https://referrer.example.com',
			TCspViolationParameter::BLOCKED_URI_LEGACY         => 'https://blocked.example.com/evil.js',
			TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY  => 'script-src',
			TCspViolationParameter::EFFECTIVE_DIRECTIVE_LEGACY => 'script-src-elem',
			TCspViolationParameter::ORIGINAL_POLICY_LEGACY     => "default-src 'none'; script-src 'self'",
			TCspViolationParameter::DISPOSITION                => 'enforce',
			TCspViolationParameter::STATUS_CODE_LEGACY         => 200,
			TCspViolationParameter::LINE_NUMBER_LEGACY         => 42,
			TCspViolationParameter::COLUMN_NUMBER_LEGACY       => 7,
			TCspViolationParameter::SOURCE_FILE_LEGACY         => 'https://example.com/app.js',
			TCspViolationParameter::SCRIPT_SAMPLE_LEGACY       => 'alert(1)',
		];

		$param = new TCspViolationParameter($report);

		self::assertTrue($param->isLegacyFormat());
		self::assertFalse($param->isModernFormat());
		self::assertSame('https://example.com/page', $param->getDocumentUrl());
		self::assertSame('https://referrer.example.com', $param->getReferrer());
		self::assertSame('https://blocked.example.com/evil.js', $param->getBlockedUrl());
		self::assertSame('script-src', $param->getViolatedDirective());
		self::assertSame('script-src-elem', $param->getEffectiveDirective());
		self::assertSame("default-src 'none'; script-src 'self'", $param->getOriginalPolicy());
		self::assertSame('enforce', $param->getDisposition());
		self::assertSame(200, $param->getStatusCode());
		self::assertSame(42, $param->getLineNumber());
		self::assertSame(7, $param->getColumnNumber());
		self::assertSame('https://example.com/app.js', $param->getSourceFile());
		self::assertSame('alert(1)', $param->getSample());
		self::assertSame($report, $param->getReport());
	}

	// -----------------------------------------------------------------------
	// Full modern report round-trip
	// -----------------------------------------------------------------------

	public function testAllModernGettersOnFullReport(): void
	{
		$report = [
			TCspViolationParameter::DOCUMENT_URL        => 'https://example.com/page',
			TCspViolationParameter::REFERRER            => 'https://referrer.example.com',
			TCspViolationParameter::BLOCKED_URL         => 'https://blocked.example.com/evil.js',
			TCspViolationParameter::EFFECTIVE_DIRECTIVE => 'script-src-elem',
			TCspViolationParameter::ORIGINAL_POLICY     => "default-src 'none'; script-src 'self'",
			TCspViolationParameter::DISPOSITION         => 'report',
			TCspViolationParameter::STATUS_CODE         => 0,
			TCspViolationParameter::LINE_NUMBER         => 12,
			TCspViolationParameter::COLUMN_NUMBER       => 3,
			TCspViolationParameter::SOURCE_FILE         => 'https://example.com/bundle.js',
			TCspViolationParameter::SAMPLE              => 'eval(',
		];

		$param = new TCspViolationParameter($report);

		self::assertTrue($param->isModernFormat());
		self::assertFalse($param->isLegacyFormat());
		self::assertSame('https://example.com/page', $param->getDocumentUrl());
		self::assertSame('https://referrer.example.com', $param->getReferrer());
		self::assertSame('https://blocked.example.com/evil.js', $param->getBlockedUrl());
		// Modern format has no violated-directive field — returns null.
		self::assertNull($param->getViolatedDirective());
		self::assertSame('script-src-elem', $param->getEffectiveDirective());
		self::assertSame("default-src 'none'; script-src 'self'", $param->getOriginalPolicy());
		self::assertSame('report', $param->getDisposition());
		self::assertSame(0, $param->getStatusCode());
		self::assertSame(12, $param->getLineNumber());
		self::assertSame(3, $param->getColumnNumber());
		self::assertSame('https://example.com/bundle.js', $param->getSourceFile());
		self::assertSame('eval(', $param->getSample());
		self::assertSame($report, $param->getReport());
	}

	// -----------------------------------------------------------------------
	// isModernFormat / isLegacyFormat — null PHP value vs. absent key
	// -----------------------------------------------------------------------

	public function testIsModernFormatReturnsFalseWhenEffectiveDirectiveIsPhpNull(): void
	{
		// offsetGet(EFFECTIVE_DIRECTIVE) returns null for both an absent key and a
		// key whose stored value IS null.  isModernFormat() must return false in
		// either case — a null value is semantically the same as absent.
		$param = new TCspViolationParameter(
			[TCspViolationParameter::EFFECTIVE_DIRECTIVE => null]
		);
		self::assertFalse($param->isModernFormat(),
			'A stored null value for effectiveDirective must not satisfy isModernFormat()');
	}

	public function testIsLegacyFormatReturnsFalseWhenViolatedDirectiveLegacyIsPhpNull(): void
	{
		// Same reasoning for the legacy detection key.
		$param = new TCspViolationParameter(
			[TCspViolationParameter::VIOLATED_DIRECTIVE_LEGACY => null]
		);
		self::assertFalse($param->isLegacyFormat(),
			'A stored null value for violated-directive must not satisfy isLegacyFormat()');
	}
}
