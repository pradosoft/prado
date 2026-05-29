<?php

/**
 * TCspReportingServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TCspReportingService}.
 *
 * Covers {@see TCspReportingService::run()} for all HTTP path branches, the
 * AutoRegistered flag, {@see TCspReportingService::getInstance()}, and the
 * {@see TCspReportingService::onViolation} event wiring for both legacy and
 * modern CSP violation report formats.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\TApplication;
use Prado\Web\Services\TCspReportingService;
use Prado\Web\Services\TCspViolationParameter;

// ---------------------------------------------------------------------------
// Minimal fake objects — avoid touching the real HTTP stack.
// ---------------------------------------------------------------------------

/**
 * Fake HTTP request: only surfaces the request-type getter used by run().
 */
class TTestCspRequest
{
	public string $requestType = 'POST';

	public function getRequestType(): string
	{
		return $this->requestType;
	}
}

/**
 * Fake HTTP response: captures setStatusCode() / appendHeader() calls
 * without invoking PHP's header() function.
 */
class TTestCspResponse
{
	public int $statusCode = 200;
	/** @var string[] */
	public array $appendedHeaders = [];

	public function setStatusCode(int $code, ?string $reason = null): void
	{
		$this->statusCode = $code;
	}

	public function appendHeader(string $header, bool $replace = true, int $response_code = 0): void
	{
		$this->appendedHeaders[] = $header;
	}
}

/**
 * Testable subclass of TCspReportingService.
 *
 * Overrides:
 * - readBody()    — returns $body instead of php://input
 * - getRequest()  — returns a TTestCspRequest with settable requestType
 * - getResponse() — returns a TTestCspResponse that captures status / headers
 *
 * Also exposes logViolation() publicly so tests can call it directly.
 */
class TestableCspReportingService extends TCspReportingService
{
	/** @var string|false synthetic request body returned by readBody() */
	public string|false $body = '';

	private TTestCspRequest $_fakeRequest;
	private TTestCspResponse $_fakeResponse;

	public function __construct()
	{
		$this->_fakeRequest = new TTestCspRequest();
		$this->_fakeResponse = new TTestCspResponse();
	}

	public function setMockRequestType(string $type): void
	{
		$this->_fakeRequest->requestType = $type;
	}

	/** Returns the status code set on the fake response. */
	public function getStatusCode(): int
	{
		return $this->_fakeResponse->statusCode;
	}

	/** Returns headers appended to the fake response. */
	public function getAppendedHeaders(): array
	{
		return $this->_fakeResponse->appendedHeaders;
	}

	protected function readBody(): false|string
	{
		return $this->body;
	}

	public function getRequest()
	{
		return $this->_fakeRequest;
	}

	public function getResponse()
	{
		return $this->_fakeResponse;
	}

	/** Exposes protected logViolation() for targeted testing. */
	public function publicLogViolation(array $report): void
	{
		$this->logViolation($report);
	}
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

class TCspReportingServiceTest extends PHPUnit\Framework\TestCase
{
	public static ?TApplication $app = null;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../../Security/app');
		}
	}

	/** Helper: create a testable service with the given method and body. */
	private function makeService(string $method = 'POST', string|false $body = ''): TestableCspReportingService
	{
		$svc = new TestableCspReportingService();
		$svc->setMockRequestType($method);
		$svc->body = $body;
		return $svc;
	}

	// -----------------------------------------------------------------------
	// SERVICE_ID constant
	// -----------------------------------------------------------------------

	public function testServiceIdConstant(): void
	{
		self::assertSame('csp-reporting', TCspReportingService::SERVICE_ID);
	}

	// -----------------------------------------------------------------------
	// AutoRegistered flag
	// -----------------------------------------------------------------------

	public function testAutoRegisteredDefaultIsFalse(): void
	{
		$svc = new TestableCspReportingService();
		self::assertFalse($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredBoolTrue(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered(true);
		self::assertTrue($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredBoolFalse(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered(true);
		$svc->setAutoRegistered(false);
		self::assertFalse($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredStringTrue(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered('true');
		self::assertTrue($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredStringFalse(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered('false');
		self::assertFalse($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredStringOne(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered('1');
		self::assertTrue($svc->getAutoRegistered());
	}

	public function testSetAutoRegisteredStringZero(): void
	{
		$svc = new TestableCspReportingService();
		$svc->setAutoRegistered(true);
		$svc->setAutoRegistered('0');
		self::assertFalse($svc->getAutoRegistered());
	}

	// -----------------------------------------------------------------------
	// getInstance()
	// -----------------------------------------------------------------------

	public function testGetInstanceReturnsNullWhenNotActiveService(): void
	{
		// The bootstrap app's active service is TPageService, not
		// TCspReportingService, so getInstance() must return null.
		self::assertNull(TCspReportingService::getInstance(self::$app));
	}

	public function testGetInstanceWithNullApplicationReturnsNull(): void
	{
		// Passing null explicitly (or when Prado::getApplication() returns null)
		// must return null gracefully — no TypeError or fatal error.
		self::assertNull(TCspReportingService::getInstance(null));
	}

	// -----------------------------------------------------------------------
	// run() — non-POST method returns 405
	// -----------------------------------------------------------------------

	public function testRunGetReturns405(): void
	{
		$svc = $this->makeService('GET');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode());
	}

	public function testRunGetAppendsAllowHeader(): void
	{
		$svc = $this->makeService('GET');
		$svc->run();
		self::assertContains('Allow: POST', $svc->getAppendedHeaders());
	}

	public function testRunDeleteReturns405(): void
	{
		$svc = $this->makeService('DELETE');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode());
	}

	public function testRunPutReturns405(): void
	{
		$svc = $this->makeService('PUT');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode());
	}

	public function testRunHeadReturns405(): void
	{
		$svc = $this->makeService('HEAD');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode());
	}

	public function testRunPatchReturns405(): void
	{
		$svc = $this->makeService('PATCH');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode());
	}

	// -----------------------------------------------------------------------
	// run() — empty body returns 204
	// -----------------------------------------------------------------------

	public function testRunEmptyStringBodyReturns204(): void
	{
		$svc = $this->makeService('POST', '');
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunFalseBodyReturns204(): void
	{
		// readBody() may return false when php://input cannot be opened.
		$svc = $this->makeService('POST', false);
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunBodyOfZeroStringReturns204(): void
	{
		// PHP's empty('0') returns true, so the string '0' is treated as an
		// empty body and produces 204 without attempting JSON parsing.
		$svc = $this->makeService('POST', '0');
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	// -----------------------------------------------------------------------
	// run() — malformed JSON returns 400
	// -----------------------------------------------------------------------

	public function testRunMalformedJsonReturns400(): void
	{
		$svc = $this->makeService('POST', '{not valid json}');
		$svc->run();
		self::assertSame(400, $svc->getStatusCode());
	}

	public function testRunJsonNullLiteralReturns400(): void
	{
		// json_decode('null') === null, which run() treats as a parse error.
		$svc = $this->makeService('POST', 'null');
		$svc->run();
		self::assertSame(400, $svc->getStatusCode());
	}

	public function testRunJsonStringLiteralProduces204WithoutEvent(): void
	{
		// json_decode('"text"') returns a string (non-null, non-array).
		// Neither the legacy nor the modern branch triggers, so run() falls
		// through to the final 204. Documents current intended behaviour.
		$svc = $this->makeService('POST', '"just a string"');
		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
		self::assertFalse($fired);
	}

	public function testRunJsonIntegerLiteralProduces204WithoutEvent(): void
	{
		// json_decode('42', true) returns int 42 (non-null, non-array).
		// Neither branch triggers; run() falls through to the final 204.
		$svc = $this->makeService('POST', '42');
		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
		self::assertFalse($fired);
	}

	// -----------------------------------------------------------------------
	// run() — legacy format (application/csp-report)
	// -----------------------------------------------------------------------

	public function testRunLegacyFormatReturns204(): void
	{
		$body = json_encode(['csp-report' => [
			'blocked-uri'        => 'https://evil.example/x.js',
			'violated-directive' => 'script-src-elem',
		]]);
		$svc = $this->makeService('POST', $body);
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunLegacyFormatFiresOnViolation(): void
	{
		$report = [
			'document-uri'        => 'https://example.com/',
			'blocked-uri'         => 'https://evil.example/x.js',
			'effective-directive' => 'script-src-elem',
		];
		$svc = $this->makeService('POST', json_encode(['csp-report' => $report]));

		$fired = false;
		$capturedParam = null;
		$svc->onViolation[] = function ($sender, $param) use (&$fired, &$capturedParam) {
			$fired = true;
			$capturedParam = $param;
		};

		$svc->run();

		self::assertTrue($fired, 'onViolation must fire for a legacy-format CSP report');
		self::assertInstanceOf(TCspViolationParameter::class, $capturedParam);
		self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
		self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
	}

	public function testRunLegacyFormatViolationParameterContainsFullReport(): void
	{
		$report = [
			'document-uri'        => 'https://example.com/',
			'referrer'            => 'https://ref.example/',
			'blocked-uri'         => 'https://evil.example/x.js',
			'violated-directive'  => 'script-src-elem',
			'effective-directive' => 'script-src-elem',
			'original-policy'     => "script-src 'self'",
			'disposition'         => 'enforce',
			'status-code'         => 200,
			'line-number'         => 12,
			'column-number'       => 4,
			'source-file'         => 'https://example.com/page.html',
			'script-sample'       => 'eval("x")',
		];
		$svc = $this->makeService('POST', json_encode(['csp-report' => $report]));

		$capturedParam = null;
		$svc->onViolation[] = function ($sender, $param) use (&$capturedParam) {
			$capturedParam = $param;
		};

		$svc->run();

		self::assertNotNull($capturedParam);
		self::assertTrue($capturedParam->isLegacyFormat());
		self::assertFalse($capturedParam->isModernFormat());
		self::assertSame($report, $capturedParam->getReport());
		self::assertSame('https://example.com/', $capturedParam->getDocumentUrl());
		self::assertSame('https://ref.example/', $capturedParam->getReferrer());
		self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
		self::assertSame('script-src-elem', $capturedParam->getViolatedDirective());
		self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
		self::assertSame("script-src 'self'", $capturedParam->getOriginalPolicy());
		self::assertSame('enforce', $capturedParam->getDisposition());
		self::assertSame(200, $capturedParam->getStatusCode());
		self::assertSame(12, $capturedParam->getLineNumber());
		self::assertSame(4, $capturedParam->getColumnNumber());
		self::assertSame('https://example.com/page.html', $capturedParam->getSourceFile());
		self::assertSame('eval("x")', $capturedParam->getSample());
	}

	public function testRunLegacyFormatNonArrayCspReportIsIgnored(): void
	{
		// csp-report value must be an array; a string must be skipped.
		$body = json_encode(['csp-report' => 'not-an-array']);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'Non-array csp-report value must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	// -----------------------------------------------------------------------
	// run() — modern format (application/reports+json)
	// -----------------------------------------------------------------------

	public function testRunModernFormatReturns204(): void
	{
		$body = json_encode([[
			'type' => 'csp-violation',
			'body' => ['blockedURL' => 'https://evil.example/x.js'],
		]]);
		$svc = $this->makeService('POST', $body);
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatFiresOnViolation(): void
	{
		$report = [
			'documentURL'        => 'https://example.com/',
			'blockedURL'         => 'https://evil.example/x.js',
			'effectiveDirective' => 'script-src-elem',
		];
		$body = json_encode([[
			'type' => 'csp-violation',
			'body' => $report,
		]]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$capturedParam = null;
		$svc->onViolation[] = function ($sender, $param) use (&$fired, &$capturedParam) {
			$fired = true;
			$capturedParam = $param;
		};

		$svc->run();

		self::assertTrue($fired, 'onViolation must fire for a modern-format CSP report');
		self::assertInstanceOf(TCspViolationParameter::class, $capturedParam);
		self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
		self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
	}

	public function testRunModernFormatViolationParameterContainsFullReport(): void
	{
		$report = [
			'documentURL'        => 'https://example.com/',
			'referrer'           => 'https://ref.example/',
			'blockedURL'         => 'https://evil.example/x.js',
			'effectiveDirective' => 'script-src-elem',
			'originalPolicy'     => "script-src 'self'",
			'disposition'        => 'report',
			'statusCode'         => 0,
			'lineNumber'         => 5,
			'columnNumber'       => 10,
			'sourceFile'         => 'https://example.com/app.js',
			'sample'             => 'bad()',
		];
		$body = json_encode([[
			'type' => 'csp-violation',
			'body' => $report,
		]]);
		$svc = $this->makeService('POST', $body);

		$capturedParam = null;
		$svc->onViolation[] = function ($sender, $param) use (&$capturedParam) {
			$capturedParam = $param;
		};

		$svc->run();

		self::assertNotNull($capturedParam);
		self::assertTrue($capturedParam->isModernFormat());
		self::assertFalse($capturedParam->isLegacyFormat());
		self::assertSame($report, $capturedParam->getReport());
		self::assertSame('https://example.com/', $capturedParam->getDocumentUrl());
		self::assertSame('https://ref.example/', $capturedParam->getReferrer());
		self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
		// Modern format has no violated-directive field — must return null.
		self::assertNull($capturedParam->getViolatedDirective());
		self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
		self::assertSame("script-src 'self'", $capturedParam->getOriginalPolicy());
		self::assertSame('report', $capturedParam->getDisposition());
		self::assertSame(0, $capturedParam->getStatusCode());
		self::assertSame(5, $capturedParam->getLineNumber());
		self::assertSame(10, $capturedParam->getColumnNumber());
		self::assertSame('https://example.com/app.js', $capturedParam->getSourceFile());
		self::assertSame('bad()', $capturedParam->getSample());
	}

	public function testRunModernFormatMultipleViolationsFireMultipleEvents(): void
	{
		$body = json_encode([
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/a.js']],
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/b.js']],
		]);
		$svc = $this->makeService('POST', $body);

		$fireCount = 0;
		$blocked = [];
		$svc->onViolation[] = function ($sender, $param) use (&$fireCount, &$blocked) {
			$fireCount++;
			$blocked[] = $param->getBlockedUrl();
		};

		$svc->run();

		self::assertSame(2, $fireCount, 'onViolation must fire once per csp-violation entry');
		self::assertContains('https://evil.example/a.js', $blocked);
		self::assertContains('https://evil.example/b.js', $blocked);
	}

	public function testRunModernFormatNonCspViolationTypeIsIgnored(): void
	{
		$body = json_encode([[
			'type' => 'deprecation',
			'body' => ['message' => 'some-deprecated-feature'],
		]]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'Non-csp-violation report types must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatMissingBodyFieldIsIgnored(): void
	{
		$body = json_encode([[
			'type' => 'csp-violation',
			// no 'body' key
		]]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'Report entry without a body field must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatNonArrayBodyIsIgnored(): void
	{
		// body must be an array; a scalar must be silently skipped.
		$body = json_encode([[
			'type' => 'csp-violation',
			'body' => 'not-an-array',
		]]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'Report entry with a non-array body must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatNonArrayEntryIsIgnored(): void
	{
		// A scalar at the top level of the reports array (e.g. 42) must be
		// silently skipped because is_array($report) is false.
		$body = json_encode([42]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'A scalar report entry must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatEntryWithoutTypeIsIgnored(): void
	{
		// An entry that has no 'type' key: ($report['type'] ?? '') resolves to
		// '' which does not match 'csp-violation', so it must be skipped.
		$body = json_encode([[
			'body' => ['blockedURL' => 'https://evil.example/x.js'],
		]]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertFalse($fired, 'Report entry without a type field must not fire onViolation');
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testRunModernFormatMixedTypesOnlyProcessesCspViolations(): void
	{
		$body = json_encode([
			['type' => 'deprecation',   'body' => ['message' => 'x']],
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/c.js']],
			['type' => 'network-error', 'body' => ['message' => 'y']],
		]);
		$svc = $this->makeService('POST', $body);

		$fireCount = 0;
		$svc->onViolation[] = function () use (&$fireCount) { $fireCount++; };

		$svc->run();

		self::assertSame(1, $fireCount, 'Only csp-violation entries must trigger onViolation');
	}

	// -----------------------------------------------------------------------
	// run() — JSON that is neither object-with-csp-report nor array
	// -----------------------------------------------------------------------

	public function testRunJsonObjectWithoutCspReportKeyProduces204WithoutFiringEvent(): void
	{
		$body = json_encode(['some-other-key' => ['data' => 'value']]);
		$svc = $this->makeService('POST', $body);

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertSame(204, $svc->getStatusCode());
		self::assertFalse($fired, 'An unrelated JSON object must not fire onViolation');
	}

	public function testRunEmptyJsonArrayProduces204WithoutFiringEvent(): void
	{
		$svc = $this->makeService('POST', '[]');

		$fired = false;
		$svc->onViolation[] = function () use (&$fired) { $fired = true; };

		$svc->run();

		self::assertSame(204, $svc->getStatusCode());
		self::assertFalse($fired, 'An empty JSON array must not fire onViolation');
	}

	// -----------------------------------------------------------------------
	// onViolation — event sender is the service instance
	// -----------------------------------------------------------------------

	public function testOnViolationSenderIsTheServiceInstance(): void
	{
		$body = json_encode(['csp-report' => [
			'blocked-uri' => 'https://evil.example/x.js',
		]]);
		$svc = $this->makeService('POST', $body);

		$capturedSender = null;
		$svc->onViolation[] = function ($sender, $param) use (&$capturedSender) {
			$capturedSender = $sender;
		};

		$svc->run();

		self::assertSame($svc, $capturedSender, 'The event sender must be the TCspReportingService instance');
	}

	// -----------------------------------------------------------------------
	// logViolation() — directly tests TCspViolationParameter construction
	// -----------------------------------------------------------------------

	public function testLogViolationCreatesParameterWithCorrectFields(): void
	{
		$report = [
			'document-uri'        => 'https://example.com/',
			'blocked-uri'         => 'https://evil.example/x.js',
			'effective-directive' => 'img-src',
		];
		$svc = $this->makeService();

		$capturedParam = null;
		$svc->onViolation[] = function ($sender, $param) use (&$capturedParam) {
			$capturedParam = $param;
		};

		$svc->publicLogViolation($report);

		self::assertInstanceOf(TCspViolationParameter::class, $capturedParam);
		self::assertSame('https://example.com/', $capturedParam->getDocumentUrl());
		self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
		self::assertSame('img-src', $capturedParam->getEffectiveDirective());
		self::assertSame($report, $capturedParam->getReport());
	}

	public function testLogViolationCanBeCalledMultipleTimesOnTheSameInstance(): void
	{
		$svc = $this->makeService();

		$params = [];
		$svc->onViolation[] = function ($sender, $param) use (&$params) {
			$params[] = $param;
		};

		$svc->publicLogViolation(['blocked-uri' => 'https://evil.example/a.js']);
		$svc->publicLogViolation(['blockedURL'  => 'https://evil.example/b.js']);

		self::assertCount(2, $params);
		self::assertSame('https://evil.example/a.js', $params[0]->getBlockedUrl());
		self::assertSame('https://evil.example/b.js', $params[1]->getBlockedUrl());
	}

	public function testOnViolationMultipleHandlersAllFire(): void
	{
		// Attaching more than one handler to onViolation must fire all of them
		// in registration order for every violation logged.
		$svc = $this->makeService();

		$log1 = [];
		$log2 = [];
		$svc->onViolation[] = function ($sender, $param) use (&$log1) {
			$log1[] = $param->getDocumentUrl();
		};
		$svc->onViolation[] = function ($sender, $param) use (&$log2) {
			$log2[] = $param->getDocumentUrl();
		};

		$svc->publicLogViolation([TCspViolationParameter::DOCUMENT_URL => 'https://example.com/']);

		self::assertCount(1, $log1, 'First handler must fire once');
		self::assertCount(1, $log2, 'Second handler must fire once');
		self::assertSame($log1[0], $log2[0], 'Both handlers must receive the same document URL');
	}
}
