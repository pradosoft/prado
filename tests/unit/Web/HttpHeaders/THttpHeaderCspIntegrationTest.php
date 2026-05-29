<?php

/**
 * THttpHeaderCspIntegrationTest
 *
 * End-to-end integration tests for the CSP pipeline:
 *   THttpHeaderCsp → THttpHeaderReportingEndpoints → THttpHeadersManager
 *   ↔ TCspReportingService (wired via ReportingMode / ReportingServiceId).
 *
 * Tests cover:
 * - report-to ↔ Reporting-Endpoints cross-validation (finalizeHeader warning)
 * - ReportingMode=true  injects Reporting-Endpoints + report-to directive
 * - ReportingMode='Auto' additionally converts enforcing CSP to report-only
 * - Already-report-only CSP is left unchanged by Auto mode
 * - No double injection of report-to or endpoint
 * - Custom endpoint name propagates into emitted headers
 * - Custom (literal) ReportingServiceId is used in the endpoint URL
 * - Emitted header strings are syntactically correct
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\TApplication;
use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints;
use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Services\TCspReportingService;
use Prado\Web\Services\TCspViolationParameter;

require_once __DIR__ . '/TTestableHttpHeadersManager.php';

class THttpHeaderCspIntegrationTest extends PHPUnit\Framework\TestCase
{
	public static ?TApplication $app = null;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../../Security/app');
		}
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** Inject a TCspReportingService entry into the app's service registry and
	 * return a callable that restores the original state. */
	private function injectService(string $id = TCspReportingService::SERVICE_ID): callable
	{
		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$ref->setValue(self::$app, array_merge($before, [
			$id => [TCspReportingService::class, [], null],
		]));
		return function () use ($ref, $before) {
			$ref->setValue(self::$app, $before);
		};
	}

	/** Build a manager loaded with a single enforcing CSP header. */
	private function makeManagerWithCsp(
		string $defaultSrcValue = "'self'",
		bool $reportOnly = false
	): TTestableHttpHeadersManager {
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'      => THttpHeaderCsp::class,
					'properties' => ['ReportOnly' => $reportOnly ? 'true' : 'false'],
					'policies'   => [
						['name' => TCspDirective::DefaultSrc, 'value' => $defaultSrcValue],
					],
				],
			],
		]);
		return $manager;
	}

	// -----------------------------------------------------------------------
	// CSP → Reporting-Endpoints cross-validation
	// -----------------------------------------------------------------------

	public function testCspWithReportToValidatesAgainstReportingEndpoints()
	{
		// CSP declares report-to "csp-endpoint" but no Reporting-Endpoints header
		// is present → finalizeHeader() must not throw, but must complete normally.
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportTo,   'value' => 'csp-endpoint'],
					],
				],
			],
		]);

		// Should complete without exception (a warning is logged but not thrown).
		$manager->ensureHeadersSent();

		$found = false;
		foreach ($manager->sentHeaders as $h) {
			if (str_contains($h, 'Content-Security-Policy')) {
				$found = true;
				self::assertStringContainsString('report-to', $h);
			}
		}
		self::assertTrue($found, 'CSP header must appear in sent headers');
	}

	public function testCspReportToWithMatchingReportingEndpointsPassesValidation()
	{
		// When a matching Reporting-Endpoints header is also configured, the
		// cross-validation must be satisfied and both headers must be emitted.
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'     => THttpHeaderReportingEndpoints::class,
					'endpoints' => [
						['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
					],
				],
				[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportTo,   'value' => 'csp-endpoint'],
					],
				],
			],
		]);
		// This test verifies manual report-to ↔ Reporting-Endpoints configuration,
		// not ReportingServiceMode auto-wiring. Disable auto-wiring so ensureHeadersSent()
		// does not attempt to construct a reporter URL (which requires a live request).
		$manager->setReportingServiceMode(false);

		$manager->ensureHeadersSent();

		$hasCsp = false;
		$hasRe = false;
		foreach ($manager->sentHeaders as $h) {
			if (str_starts_with($h, 'Content-Security-Policy:')) {
				$hasCsp = true;
				self::assertStringContainsString('report-to csp-endpoint', $h);
			}
			if (str_starts_with($h, 'Reporting-Endpoints:')) {
				$hasRe = true;
				self::assertStringContainsString('csp-endpoint=', $h);
				self::assertStringContainsString('https://example.com/csp-reports', $h);
			}
		}
		self::assertTrue($hasCsp, 'CSP header must be emitted');
		self::assertTrue($hasRe, 'Reporting-Endpoints header must be emitted');
	}

	// -----------------------------------------------------------------------
	// ReportingServiceMode = true + ReportOnly = false — automatic injection
	// -----------------------------------------------------------------------

	public function testReportingModeTrueInjectsReportingEndpointsHeader()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$reHeaders = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class);
			self::assertNotEmpty($reHeaders, 'A Reporting-Endpoints header must be injected when ReportingServiceMode=true');
		} finally {
			$restore();
		}
	}

	public function testReportingModeTrueInjectsReportToIntoCsp()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
				'report-to directive must be injected into the CSP header');
		} finally {
			$restore();
		}
	}

	public function testReportingModeTrueReportToNameMatchesReportingEndpointsName()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			$reportToValue = trim($csp->getPolicies()[TCspDirective::ReportTo]);

			/** @var THttpHeaderReportingEndpoints $re */
			$re = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class)[0];
			self::assertTrue($re->hasEndpoint($reportToValue),
				'The report-to directive name must match an endpoint declared in Reporting-Endpoints');
		} finally {
			$restore();
		}
	}

	public function testReportingModeTrueDoesNotConvertCspToReportOnly()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertFalse($csp->getReportOnly(),
				'ReportingServiceMode=true + ReportOnly=false must NOT convert an enforcing CSP to report-only');
		} finally {
			$restore();
		}
	}

	public function testReportingModeTrueEmitsCorrectHeaderStrings()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);

			$manager->ensureHeadersSent();

			$hasCsp = false;
			$hasRe = false;
			$endpointName = $manager->getReportingEndpointName();
			foreach ($manager->sentHeaders as $h) {
				if (str_starts_with($h, 'Content-Security-Policy:')) {
					$hasCsp = true;
					self::assertStringContainsString('default-src', $h);
					self::assertStringContainsString('report-to ' . $endpointName, $h);
				}
				if (str_starts_with($h, 'Reporting-Endpoints:')) {
					$hasRe = true;
					// Must declare the endpoint name followed by a quoted URL.
					self::assertMatchesRegularExpression('/' . preg_quote($endpointName, '/') . '="[^"]+"/', $h);
				}
			}
			self::assertTrue($hasCsp, 'Enforcing CSP header must be present in sent headers');
			self::assertTrue($hasRe, 'Reporting-Endpoints header must be present in sent headers');
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// ReportingServiceMode = 'Auto' + ReportOnly = true — additionally converts CSP to report-only
	// -----------------------------------------------------------------------

	public function testReportingModeAutoConvertsEnforcingCspToReportOnly()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode('Auto');
			// ReportOnly=true is the default; set explicitly for clarity.
			$manager->setReportOnly(true);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertTrue($csp->getReportOnly(),
				'ReportOnly=true must convert an enforcing CSP to report-only');
		} finally {
			$restore();
		}
	}

	public function testReportingModeAutoLeavesAlreadyReportOnlyCspUnchanged()
	{
		$restore = $this->injectService();
		try {
			// CSP is already report-only — ReportOnly must not change it (it stays report-only).
			$manager = $this->makeManagerWithCsp("'self'", reportOnly: true);
			$manager->setReportingServiceMode('Auto');
			$manager->setReportOnly(true);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertTrue($csp->getReportOnly(),
				'An already-report-only CSP must remain report-only');
		} finally {
			$restore();
		}
	}

	public function testReportingModeAutoEmitsReportOnlyHeaderName()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode('Auto');
			// ReportOnly=true is the default — enforcing CSP will be downgraded.

			$manager->ensureHeadersSent();

			$hasReportOnly = false;
			foreach ($manager->sentHeaders as $h) {
				if (str_starts_with($h, 'Content-Security-Policy-Report-Only:')) {
					$hasReportOnly = true;
					self::assertStringContainsString('default-src', $h);
				}
				// Enforcing CSP must NOT appear.
				if (str_starts_with($h, 'Content-Security-Policy:')) {
					self::fail('Enforcing Content-Security-Policy header must not be emitted when ReportOnly=true');
				}
			}
			self::assertTrue($hasReportOnly, 'Content-Security-Policy-Report-Only header must be emitted when ReportOnly=true');
		} finally {
			$restore();
		}
	}

	public function testReportingModeAutoStillInjectsReportingEndpointsAndReportTo()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode('Auto');
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
				'Auto mode must still inject report-to into the (now report-only) CSP');

			$reHeaders = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class);
			self::assertNotEmpty($reHeaders,
				'Auto mode must still inject a Reporting-Endpoints header');
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// No double injection
	// -----------------------------------------------------------------------

	public function testNoDoubleInjectionOfReportToWhenAlreadyPresent()
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [
					[
						'class'    => THttpHeaderCsp::class,
						'policies' => [
							['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
							// report-to already set explicitly by the developer.
							['name' => TCspDirective::ReportTo,   'value' => 'my-endpoint'],
						],
					],
				],
			]);
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			// The pre-existing 'my-endpoint' value must be preserved.
			self::assertSame('my-endpoint', trim($csp->getPolicies()[TCspDirective::ReportTo]),
				'Pre-existing report-to directive must not be overwritten');
		} finally {
			$restore();
		}
	}

	public function testNoDoubleInjectionOfEndpointWhenAlreadyDeclared()
	{
		$restore = $this->injectService();
		try {
			$endpointName = 'prado-csp-reporter'; // default ReportingEndpointName

			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [
					[
						'class'     => THttpHeaderReportingEndpoints::class,
						'endpoints' => [
							// Endpoint already declared before finalizeReporterService() runs.
							['name' => $endpointName, 'url' => 'https://custom.example.com/csp'],
						],
					],
					[
						'class'    => THttpHeaderCsp::class,
						'policies' => [
							['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						],
					],
				],
			]);
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$reHeaders = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class);
			self::assertCount(1, $reHeaders,
				'Only one Reporting-Endpoints header must exist');

			/** @var THttpHeaderReportingEndpoints $re */
			$re = $reHeaders[0];
			// The pre-existing custom URL must be preserved, not overwritten.
			$value = $re->getHeaderValue();
			self::assertStringContainsString('https://custom.example.com/csp', $value,
				'Pre-existing endpoint URL must not be overwritten');
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// Multiple CSP headers
	// -----------------------------------------------------------------------

	public function testReportingModeTrueInjectsReportToIntoAllCspHeaders()
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [
					[
						'class'    => THttpHeaderCsp::class,
						'policies' => [
							['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						],
					],
					[
						// Second CSP — report-only variant, no report-to yet.
						'class'      => THttpHeaderCsp::class,
						'properties' => ['ReportOnly' => 'true'],
						'policies'   => [
							['name' => TCspDirective::ScriptSrc, 'value' => "'self' 'unsafe-inline'"],
						],
					],
				],
			]);
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$cspHeaders = $manager->getHeadersByClass(THttpHeaderCsp::class);
			self::assertCount(2, $cspHeaders);
			foreach ($cspHeaders as $csp) {
				self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
					'report-to must be injected into every CSP header');
			}
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// Custom endpoint name
	// -----------------------------------------------------------------------

	public function testCustomReportingEndpointNameAppearsInHeaders()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->setReportingEndpointName('my-custom-endpoint');
			$manager->publicFinalizeReporterService();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertSame('my-custom-endpoint', trim($csp->getPolicies()[TCspDirective::ReportTo]),
				'Custom endpoint name must appear in the report-to directive value');

			/** @var THttpHeaderReportingEndpoints $re */
			$re = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class)[0];
			self::assertTrue($re->hasEndpoint('my-custom-endpoint'),
				'Custom endpoint name must be registered in the Reporting-Endpoints header');
		} finally {
			$restore();
		}
	}

	public function testCustomReportingEndpointNameAppearsInEmittedHeaderStrings()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->setReportingEndpointName('my-custom-endpoint');

			$manager->ensureHeadersSent();

			$hasCspWithEndpoint = false;
			$hasReWithEndpoint = false;
			foreach ($manager->sentHeaders as $h) {
				if (str_contains($h, 'Content-Security-Policy')) {
					$hasCspWithEndpoint = str_contains($h, 'report-to my-custom-endpoint');
				}
				if (str_starts_with($h, 'Reporting-Endpoints:')) {
					$hasReWithEndpoint = str_contains($h, 'my-custom-endpoint=');
				}
			}
			self::assertTrue($hasCspWithEndpoint, 'Custom endpoint name must appear in the emitted CSP header');
			self::assertTrue($hasReWithEndpoint, 'Custom endpoint name must appear in the emitted Reporting-Endpoints header');
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// Custom ReportingServiceId (literal)
	// -----------------------------------------------------------------------

	public function testLiteralReportingServiceIdAppearsInEndpointUrl()
	{
		$manager = $this->makeManagerWithCsp();
		$manager->setReportingServiceMode(true);
		$manager->setReportOnly(false);
		$manager->setReportingServiceId('my-csp-service');
		$manager->publicFinalizeReporterService();

		/** @var THttpHeaderReportingEndpoints $re */
		$re = $manager->getHeadersByClass(THttpHeaderReportingEndpoints::class)[0];
		self::assertStringContainsString('my-csp-service', $re->getHeaderValue(),
			'The literal ReportingServiceId must appear in the endpoint URL');
	}

	// -----------------------------------------------------------------------
	// CSP sandbox stripping — happens in finalizeHeader(), not initComplete()
	// -----------------------------------------------------------------------

	public function testSandboxDirectiveIsStrippedWhenCspConvertedToReportOnly()
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [
					[
						'class'    => THttpHeaderCsp::class,
						'policies' => [
							['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
							['name' => TCspDirective::Sandbox,    'value' => ''],
						],
					],
				],
			]);
			// Sandbox is still present after loading — init() does not strip it.
			// ReportOnly=true (default) converts the enforcing CSP to report-only in
			// finalizeReporterService(), then finalizeHeader() strips sandbox.
			// Both happen inside finalizeHeaders(), so we call that.
			$manager->setReportingServiceMode('Auto');
			$manager->publicFinalizeHeaders();

			/** @var THttpHeaderCsp $csp */
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertFalse($csp->hasPolicy(TCspDirective::Sandbox),
				'sandbox directive must be removed when the CSP is converted to report-only');
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// report-uri placeholder — sentinel and blank trigger auto-fill
	// -----------------------------------------------------------------------

	public function testReportUriSentinelIsFilledByManager(): void
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportUri,  'value' => THttpHeaderCsp::REPORT_URI],
					],
				]],
			]);
			$manager->setReportingServiceMode('Auto');
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			$reportUri = $csp->getPolicy(TCspDirective::ReportUri);
			self::assertNotNull($reportUri, 'report-uri must be present after finalize');
			self::assertNotSame(THttpHeaderCsp::REPORT_URI, $reportUri,
				'Sentinel must be replaced with the actual URL');
			self::assertStringStartsWith('https://', $reportUri,
				'Filled-in report-uri must be a URL');
		} finally {
			$restore();
		}
	}

	public function testReportUriBlankValueIsFilledByManager(): void
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportUri,  'value' => ''],
					],
				]],
			]);
			$manager->setReportingServiceMode('Auto');
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			$reportUri = $csp->getPolicy(TCspDirective::ReportUri);
			self::assertNotEmpty($reportUri,
				'Blank report-uri placeholder must be filled with the reporter URL');
		} finally {
			$restore();
		}
	}

	public function testReportUriPlaceholderTriggersAutoMode(): void
	{
		// A report-uri placeholder (with no report-to) must be enough to activate
		// Auto ReportingServiceMode wiring even when resolveReportOnly() is false.
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportUri,  'value' => THttpHeaderCsp::REPORT_URI],
					],
				]],
			]);
			$manager->setReportingServiceMode('Auto');
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			// Wiring must have proceeded: a report-to must also have been injected.
			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
				'report-uri placeholder must trigger Auto wiring, injecting report-to as well');
		} finally {
			$restore();
		}
	}

	public function testDeveloperSuppliedReportUriIsNotOverwritten(): void
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->publicLoadHeaders([
				'headers' => [[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ReportUri,  'value' => 'https://my-own-endpoint.example.com/csp'],
					],
				]],
			]);
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->publicFinalizeReporterService();

			$csp = $manager->getHeadersByClass(THttpHeaderCsp::class)[0];
			self::assertSame(
				'https://my-own-endpoint.example.com/csp',
				$csp->getPolicy(TCspDirective::ReportUri),
				'A developer-supplied report-uri URL must not be overwritten'
			);
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// Reporting-Endpoints header — emitted value format
	// -----------------------------------------------------------------------

	public function testReportingEndpointsHeaderValueFormat()
	{
		// Manually configured Reporting-Endpoints must emit the RFC 8941 format.
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'     => THttpHeaderReportingEndpoints::class,
					'endpoints' => [
						['name' => 'csp-ep',  'url' => 'https://example.com/csp'],
						['name' => 'default', 'url' => 'https://example.com/default'],
					],
				],
			],
		]);

		$manager->ensureHeadersSent();

		$reHeader = null;
		foreach ($manager->sentHeaders as $h) {
			if (str_starts_with($h, 'Reporting-Endpoints:')) {
				$reHeader = $h;
			}
		}
		self::assertNotNull($reHeader, 'Reporting-Endpoints header must be emitted');
		self::assertStringContainsString('csp-ep="https://example.com/csp"', $reHeader);
		self::assertStringContainsString('default="https://example.com/default"', $reHeader);
	}

	// -----------------------------------------------------------------------
	// CSP nonce replacement
	// -----------------------------------------------------------------------

	public function testCspNonceIsReplacedInEmittedHeader()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'    => THttpHeaderCsp::class,
					'policies' => [
						['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
						['name' => TCspDirective::ScriptSrc,  'value' => "'self' " . THttpHeaderCsp::NONCE],
					],
				],
			],
		]);
		// init() has already run and called setScriptNonce() with the real CSP
		// nonce from the security manager. Override with a known value so the
		// assertion is deterministic.
		TJavaScript::setScriptNonce('test-nonce-abc');
		// Disable auto-wiring so ensureHeadersSent() does not attempt to construct
		// a reporter URL (which requires a live request) and emits an enforcing
		// Content-Security-Policy: header that the assertions below can match.
		$manager->setReportingServiceMode(false);

		$manager->ensureHeadersSent();

		$cspLine = null;
		foreach ($manager->sentHeaders as $h) {
			if (str_starts_with($h, 'Content-Security-Policy:')) {
				$cspLine = $h;
			}
		}
		self::assertNotNull($cspLine, 'CSP header must be emitted');
		self::assertStringContainsString("'nonce-test-nonce-abc'", $cspLine,
			'NONCE placeholder must be replaced with the actual nonce in the emitted CSP header');
		self::assertStringNotContainsString(THttpHeaderCsp::NONCE, $cspLine,
			'The NONCE placeholder must not appear verbatim in the emitted CSP header');
	}

	// -----------------------------------------------------------------------
	// Full pipeline smoke test — configure, send, verify all header strings
	// -----------------------------------------------------------------------

	public function testFullPipelineEmitsAllRequiredHeaders()
	{
		$restore = $this->injectService();
		try {
			$manager = new TTestableHttpHeadersManager();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->setReportingEndpointName('prado-csp');
			$manager->publicLoadHeaders([
				'headers' => [
					[
						'class'    => THttpHeaderCsp::class,
						'policies' => [
							['name' => TCspDirective::DefaultSrc, 'value' => "'self'"],
							['name' => TCspDirective::ScriptSrc,  'value' => "'self'"],
						],
					],
				],
			]);
			$manager->publicLoadDefaultHeaders();

			$manager->ensureHeadersSent();

			$headerNames = array_map(
				fn ($h) => explode(':', $h, 2)[0],
				$manager->sentHeaders
			);
			self::assertContains('Content-Security-Policy', $headerNames,
				'Enforcing CSP header must be emitted');
			self::assertContains('Reporting-Endpoints', $headerNames,
				'Reporting-Endpoints header must be emitted');
			self::assertContains('Content-Type', $headerNames,
				'Default Content-Type header must be emitted');

			// CSP must contain both directives and the injected report-to.
			$cspLine = null;
			foreach ($manager->sentHeaders as $h) {
				if (str_starts_with($h, 'Content-Security-Policy:')) {
					$cspLine = $h;
				}
			}
			self::assertNotNull($cspLine);
			self::assertStringContainsString("default-src 'self'", $cspLine);
			self::assertStringContainsString("script-src 'self'", $cspLine);
			self::assertStringContainsString('report-to prado-csp', $cspLine);
		} finally {
			$restore();
		}
	}

	// -----------------------------------------------------------------------
	// TCspReportingService auto-registration via ensureReportingServiceRegistered
	// -----------------------------------------------------------------------

	public function testReportingModeTrueAutoRegistersServiceInAppRegistry()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertNotEmpty($byClass,
				'ReportingServiceMode=true must register TCspReportingService in the app registry');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testReportingModeAutoAutoRegistersServiceInAppRegistry()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('Auto');
		// ReportOnly=true (default) triggers registration in Auto mode.
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertNotEmpty($byClass,
				'ReportingServiceMode=Auto + ReportOnly=true must register TCspReportingService in the app registry');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testAutoRegisteredServiceInitPropertiesContainAutoRegisteredTrue()
	{
		// The registered entry's init-properties must include AutoRegistered=true
		// so the service knows it was created by the manager, not the developer.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			// Registry format: [$class, $initProperties, $configElement]
			$entry = $services[TCspReportingService::SERVICE_ID] ?? null;
			self::assertNotNull($entry, 'Service entry must be registered');
			self::assertTrue($entry[1]['AutoRegistered'] ?? false,
				'Auto-registered service entry must carry AutoRegistered=true as an init-property');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	// -----------------------------------------------------------------------
	// Violation pipeline — manager configures URL, service handles POST
	// -----------------------------------------------------------------------

	/**
	 * Extracts the first quoted URL from a Reporting-Endpoints header value.
	 * e.g. `Reporting-Endpoints: prado-csp-reporter="https://host/csp"` → `https://host/csp`
	 */
	private function extractReporterUrl(string $headerLine): ?string
	{
		if (preg_match('/"([^"]+)"/', $headerLine, $m)) {
			return $m[1];
		}
		return null;
	}

	public function testReporterUrlInReportingEndpointsContainsServiceId()
	{
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->ensureHeadersSent();

			$reporterUrl = null;
			foreach ($manager->sentHeaders as $h) {
				if (str_starts_with($h, 'Reporting-Endpoints:')) {
					$reporterUrl = $this->extractReporterUrl($h);
				}
			}
			self::assertNotNull($reporterUrl, 'Reporting-Endpoints header must contain a URL');
			self::assertStringContainsString(TCspReportingService::SERVICE_ID, $reporterUrl,
				'The reporter URL must contain the service ID so browsers POST to the right endpoint');
		} finally {
			$restore();
		}
	}

	public function testViolationLegacyFormatIsProcessedByService()
	{
		// Full pipeline: manager emits Reporting-Endpoints → browser POSTs a
		// legacy-format violation body → service fires onViolation with correct data.
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->ensureHeadersSent();

			// Simulate the browser POST that lands at the reporter URL.
			$violationReport = [
				'document-uri'       => 'https://example.com/page',
				'blocked-uri'        => 'https://evil.example/x.js',
				'effective-directive' => 'script-src-elem',
				'violated-directive' => 'script-src-elem',
				'original-policy'    => "default-src 'self'",
				'disposition'        => 'enforce',
				'status-code'        => 200,
			];
			$postBody = json_encode(['csp-report' => $violationReport]);

			$svc = $this->makeTestableReporterService('POST', $postBody);
			$capturedParam = null;
			$svc->onViolation[] = function ($sender, $param) use (&$capturedParam) {
				$capturedParam = $param;
			};

			$svc->run();

			self::assertSame(204, $svc->getStatusCode(),
				'Service must respond 204 No Content on a valid violation report');
			self::assertInstanceOf(TCspViolationParameter::class, $capturedParam,
				'onViolation must fire with a TCspViolationParameter');
			self::assertSame('https://evil.example/x.js', $capturedParam->getBlockedUrl());
			self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
			self::assertSame('https://example.com/page', $capturedParam->getDocumentUrl());
			self::assertSame('enforce', $capturedParam->getDisposition());
		} finally {
			$restore();
		}
	}

	public function testViolationModernFormatIsProcessedByService()
	{
		// Modern application/reports+json format from the same pipeline.
		$restore = $this->injectService();
		try {
			$manager = $this->makeManagerWithCsp();
			$manager->setReportingServiceMode(true);
			$manager->setReportOnly(false);
			$manager->ensureHeadersSent();

			$violationBody = [
				'documentURL'        => 'https://example.com/page',
				'blockedURL'         => 'https://evil.example/track.js',
				'effectiveDirective' => 'script-src-elem',
				'originalPolicy'     => "default-src 'self'",
				'disposition'        => 'report',
				'statusCode'         => 0,
				'lineNumber'         => 42,
				'columnNumber'       => 7,
			];
			$postBody = json_encode([[
				'type' => 'csp-violation',
				'age'  => 0,
				'body' => $violationBody,
			]]);

			$svc = $this->makeTestableReporterService('POST', $postBody);
			$capturedParam = null;
			$svc->onViolation[] = function ($sender, $param) use (&$capturedParam) {
				$capturedParam = $param;
			};

			$svc->run();

			self::assertSame(204, $svc->getStatusCode());
			self::assertInstanceOf(TCspViolationParameter::class, $capturedParam);
			self::assertSame('https://evil.example/track.js', $capturedParam->getBlockedUrl());
			self::assertSame('script-src-elem', $capturedParam->getEffectiveDirective());
			self::assertSame('https://example.com/page', $capturedParam->getDocumentUrl());
			self::assertSame(42, $capturedParam->getLineNumber());
			self::assertSame(7, $capturedParam->getColumnNumber());
		} finally {
			$restore();
		}
	}

	public function testServiceReturns405ForNonPostRequests()
	{
		$svc = $this->makeTestableReporterService('GET', '');
		$svc->run();
		self::assertSame(405, $svc->getStatusCode(),
			'Reporter service must reject non-POST requests with 405');
	}

	public function testServiceReturns400ForMalformedJson()
	{
		$svc = $this->makeTestableReporterService('POST', '{not valid json}');
		$svc->run();
		self::assertSame(400, $svc->getStatusCode(),
			'Reporter service must return 400 for malformed JSON bodies');
	}

	public function testServiceReturns204ForEmptyBody()
	{
		// Browsers may send an empty body in some edge cases; service must
		// handle it gracefully with 204.
		$svc = $this->makeTestableReporterService('POST', '');
		$svc->run();
		self::assertSame(204, $svc->getStatusCode());
	}

	public function testMultipleViolationsInOnePostFireMultipleEvents()
	{
		$postBody = json_encode([
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/a.js']],
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/b.js']],
			['type' => 'csp-violation', 'body' => ['blockedURL' => 'https://evil.example/c.js']],
		]);

		$svc = $this->makeTestableReporterService('POST', $postBody);
		$count = 0;
		$blocked = [];
		$svc->onViolation[] = function ($sender, $param) use (&$count, &$blocked) {
			$count++;
			$blocked[] = $param->getBlockedUrl();
		};

		$svc->run();

		self::assertSame(3, $count, 'onViolation must fire once per csp-violation entry');
		self::assertContains('https://evil.example/a.js', $blocked);
		self::assertContains('https://evil.example/b.js', $blocked);
		self::assertContains('https://evil.example/c.js', $blocked);
	}

	// -----------------------------------------------------------------------
	// Helper: anonymous TCspReportingService subclass for violation tests
	// -----------------------------------------------------------------------

	/**
	 * Returns a minimal anonymous subclass of TCspReportingService that
	 * intercepts readBody(), getRequest(), and getResponse() so no live HTTP
	 * stack or TApplication wiring is required.
	 */
	private function makeTestableReporterService(string $requestMethod, string|false $body): TCspReportingService
	{
		return new class ($requestMethod, $body) extends TCspReportingService {
			/** Captured by the inner response stub via a direct object reference. */
			public int $statusCode = 200;

			public function __construct(
				private string $requestType,
				private string|false $bodyContent
			) {}

			public function getStatusCode(): int { return $this->statusCode; }

			protected function readBody(): false|string { return $this->bodyContent; }

			public function getRequest()
			{
				return new class ($this->requestType) {
					public function __construct(private string $type) {}
					public function getRequestType(): string { return $this->type; }
				};
			}

			public function getResponse()
			{
				// Capture $this so the stub can write back to $statusCode.
				$svc = $this;
				return new class ($svc) {
					public function __construct(private object $svc) {}
					public function setStatusCode(int $code, ?string $reason = null): void
					{
						$this->svc->statusCode = $code;
					}
					public function appendHeader(string $header, bool $replace = true, int $code = 0): void {}
				};
			}
		};
	}
}
