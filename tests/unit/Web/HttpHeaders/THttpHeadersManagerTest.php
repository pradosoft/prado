<?php

/**
 * THttpHeadersManagerTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeadersManager}.
 *
 * The {@see TTestableHttpHeadersManager} subclass defined below intercepts
 * {@see sendHeaders()} so tests can inspect emitted header strings without
 * touching the live HTTP stack.  It also exposes protected helpers as public
 * methods so the manager can be exercised without a full {@see TApplication}
 * lifecycle.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Util\TLogger;
use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeader;
use Prado\Web\HttpHeaders\THttpHeaderBase;
use Prado\Web\HttpHeaders\THttpHeaderContentType;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\HttpHeaders\THttpHeaderHsts;
use Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints;
use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\Services\TCspReportingService;
use Prado\Web\THttpHeaderName;

// ---------------------------------------------------------------------------
// Shared test double
// ---------------------------------------------------------------------------

require_once __DIR__ . '/TTestableHttpHeadersManager.php';

// ---------------------------------------------------------------------------
// Minimal header stub — no app needed
// ---------------------------------------------------------------------------

class TStubHeader extends THttpHeaderBase
{
	public string $name = 'X-Stub';
	public string $value = 'stub-value';
	public int $initCallCount = 0;
	public int $initCompleteCallCount = 0;
	public int $finalizeCallCount = 0;

	public function getHeaderName(): string
	{
		return $this->name;
	}

	public function getHeaderValue(): string
	{
		return $this->value;
	}

	public function setHeaderValue($value): void
	{
		$this->value = (string) $value;
	}

	public function init($config): void
	{
		$this->initCallCount++;
	}

	public function initComplete(): void
	{
		$this->initCompleteCallCount++;
	}

	public function finalizeHeader(): void
	{
		$this->finalizeCallCount++;
	}
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

class THttpHeadersManagerTest extends PHPUnit\Framework\TestCase
{
	public static ?TApplication $app = null;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../../Security/app');
		}
	}

	// -----------------------------------------------------------------------
	// Constructor / defaults
	// -----------------------------------------------------------------------

	public function testDefaultReportingEndpointName()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame(
			THttpHeadersManager::DEFAULT_REPORTING_SERVICE_NAME,
			$m->getReportingEndpointName()
		);
	}

	public function testDefaultReportingServiceId()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame(
			THttpHeadersManager::DEFAULT_REPORTING_SERVICE_ID,
			$m->getReportingServiceId()
		);
	}

	public function testDefaultReportOnlyIsNull()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertNull($m->getReportOnly());
	}

	public function testDefaultReportingServiceModeIsAuto()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame('Auto', $m->getReportingServiceMode());
	}

	public function testDefaultMappingClass()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame(THttpHeader::class, $m->getDefaultMappingClass());
	}

	public function testDefaultHeaderClass()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame(THttpHeader::class, $m->getDefaultHeaderClass());
	}

	public function testDefaultMappingClassAliasesDefaultHeaderClass()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame($m->getDefaultHeaderClass(), $m->getDefaultMappingClass());
	}

	public function testSetDefaultHeaderClassPersists()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setDefaultHeaderClass(THttpHeaderContentType::class);
		self::assertSame(THttpHeaderContentType::class, $m->getDefaultHeaderClass());
		self::assertSame(THttpHeaderContentType::class, $m->getDefaultMappingClass());
	}

	// -----------------------------------------------------------------------
	// Name→class registry
	// -----------------------------------------------------------------------

	public function testDefaultNameClassMapContainsExpectedEntries()
	{
		$m = new TTestableHttpHeadersManager();
		$map = $m->publicGetDefaultNameClassMap();

		self::assertArrayHasKey(THttpHeaderName::ContentType, $map);
		self::assertSame(THttpHeaderContentType::class, $map[THttpHeaderName::ContentType]);

		self::assertArrayHasKey(THttpHeaderName::ContentSecurityPolicy, $map);
		self::assertSame(THttpHeaderCsp::class, $map[THttpHeaderName::ContentSecurityPolicy]);

		self::assertArrayHasKey(THttpHeaderName::ContentSecurityPolicyReportOnly, $map);
		self::assertSame(THttpHeaderCsp::class, $map[THttpHeaderName::ContentSecurityPolicyReportOnly]);

		self::assertArrayHasKey(THttpHeaderName::StrictTransportSecurity, $map);
		self::assertSame(THttpHeaderHsts::class, $map[THttpHeaderName::StrictTransportSecurity]);

		self::assertArrayHasKey(THttpHeaderName::ReportingEndpoints, $map);
		self::assertSame(THttpHeaderReportingEndpoints::class, $map[THttpHeaderName::ReportingEndpoints]);
	}

	public function testGetNameClassMapLazilyPopulatesFromDefaults()
	{
		$m = new TTestableHttpHeadersManager();
		// Before any call, internal map is empty.
		self::assertEmpty($m->publicGetNameClassMapDirect());
		// After getNameClassMap(), defaults are populated — keys are lowercase.
		$map = $m->getNameClassMap();
		self::assertNotEmpty($map);
		self::assertArrayHasKey(strtolower(THttpHeaderName::ContentType), $map);
	}

	public function testEnsureNameClassMapIsIdempotent()
	{
		$m = new TTestableHttpHeadersManager();
		$m->ensureNameClassMap();
		$first = $m->getNameClassMap();
		$m->ensureNameClassMap();
		$second = $m->getNameClassMap();
		self::assertSame($first, $second);
	}

	public function testEnsureNameClassMapIsIdempotentWhenDefaultMapIsEmpty()
	{
		// A subclass whose getDefaultNameClassMap() returns [] must not trigger
		// re-initialization on every ensureNameClassMap() call. The bool flag
		// (not empty()) must guard the initialization.
		$m = new class extends TTestableHttpHeadersManager {
			protected function getDefaultNameClassMap(): array { return []; }
		};
		$m->ensureNameClassMap();
		// Mutate the map after initialization.
		$m->registerHeaderClass('X-Custom', THttpHeader::class);
		// A second ensureNameClassMap() call must NOT wipe the registered entry.
		$m->ensureNameClassMap();
		self::assertArrayHasKey('x-custom', $m->getNameClassMap());
	}

	public function testAddNameClassMapMergesEntries()
	{
		$m = new TTestableHttpHeadersManager();
		$m->addNameClassMap(['X-Custom' => THttpHeader::class]);
		$map = $m->getNameClassMap();
		// Keys are stored lowercase.
		self::assertArrayHasKey('x-custom', $map);
		self::assertSame(THttpHeader::class, $map['x-custom']);
		// Originals still present (also lowercase).
		self::assertArrayHasKey(strtolower(THttpHeaderName::ContentType), $map);
	}

	public function testAddNameClassMapOverridesExistingEntry()
	{
		$m = new TTestableHttpHeadersManager();
		$m->addNameClassMap([THttpHeaderName::StrictTransportSecurity => THttpHeader::class]);
		$map = $m->getNameClassMap();
		self::assertSame(THttpHeader::class, $map[strtolower(THttpHeaderName::StrictTransportSecurity)]);
	}

	public function testRegisterHeaderClassAddsEntry()
	{
		$m = new TTestableHttpHeadersManager();
		$m->registerHeaderClass('X-Test', THttpHeader::class);
		$map = $m->getNameClassMap();
		// Keys are stored lowercase.
		self::assertArrayHasKey('x-test', $map);
		self::assertSame(THttpHeader::class, $map['x-test']);
	}

	public function testNameClassMapKeysAreNormalizedToLowercase()
	{
		$m = new TTestableHttpHeadersManager();
		// Default map entries (from THttpHeaderName constants) are lowercased on storage.
		$map = $m->getNameClassMap();
		foreach (array_keys($map) as $key) {
			self::assertSame(strtolower($key), $key, "Map key '$key' is not lowercase");
		}
	}

	public function testRegisterHeaderClassWithDifferentCasingDoesNotCreateDuplicates()
	{
		$m = new TTestableHttpHeadersManager();
		$m->registerHeaderClass('X-Dupe', THttpHeader::class);
		$m->registerHeaderClass('x-dupe', TStubHeader::class);  // different casing, same header
		$map = $m->getNameClassMap();
		// Second registration must overwrite, not add a second entry.
		self::assertCount(
			1,
			array_filter(array_keys($map), fn ($k) => strtolower($k) === 'x-dupe')
		);
		self::assertSame(TStubHeader::class, $map['x-dupe']);
	}

	// -----------------------------------------------------------------------
	// CRUD — addHeader / getHeaders / hasHeader / removeHeader
	// -----------------------------------------------------------------------

	public function testAddHeaderAppendsToList()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertCount(1, $m->getHeaders());
		self::assertSame($h, $m->getHeaders()[0]);
	}

	public function testAddHeaderSetsManagerOnHeader()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertSame($m, $h->getManager());
	}

	public function testAddHeaderAcceptsHeaderAlreadyOwnedBySameManager()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$h->setManager($m);
		// Must not throw.
		$m->addHeader($h);
		self::assertCount(1, $m->getHeaders());
	}

	public function testAddHeaderDuplicateInstanceIsNoop()
	{
		// Adding the same instance twice must not create a duplicate entry;
		// duplicate lines would be sent for replacing headers.
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		$m->addHeader($h);
		self::assertCount(1, $m->getHeaders());
	}

	public function testAddHeaderThrowsWhenHeaderBelongsToDifferentManager()
	{
		$m1 = new TTestableHttpHeadersManager();
		$m2 = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$h->setManager($m1);

		$this->expectException(TInvalidOperationException::class);
		$m2->addHeader($h);
	}

	public function testHasHeaderReturnsTrueForAddedHeader()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertTrue($m->hasHeader('X-Stub'));
	}

	public function testHasHeaderIsCaseInsensitive()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertTrue($m->hasHeader('x-stub'));
		self::assertTrue($m->hasHeader('X-STUB'));
	}

	public function testHasHeaderReturnsFalseWhenNotPresent()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertFalse($m->hasHeader('X-Stub'));
	}

	public function testHasHeaderByClass()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertTrue($m->hasHeader(TStubHeader::class));
		self::assertFalse($m->hasHeader(THttpHeaderContentType::class));
	}

	public function testRemoveHeaderByInstanceReturnsTrueAndRemovesIt()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		$result = $m->removeHeader($h);
		self::assertTrue($result);
		self::assertCount(0, $m->getHeaders());
	}

	public function testRemoveHeaderByNameRemovesAllMatchingHeaders()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		$m->addHeader($h2);
		$result = $m->removeHeader('X-Stub');
		self::assertTrue($result);
		self::assertCount(0, $m->getHeaders());
	}

	public function testRemoveHeaderByNameLeavesOtherHeadersIntact()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$other = new TStubHeader();
		$other->name = 'X-Other';
		$m->addHeader($h1);
		$m->addHeader($other);
		$m->addHeader($h2);
		$result = $m->removeHeader('X-Stub');
		self::assertTrue($result);
		$remaining = $m->getHeaders();
		self::assertCount(1, $remaining);
		self::assertSame($other, $remaining[0]);
	}

	public function testRemoveHeaderReturnsFalseWhenNotFound()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertFalse($m->removeHeader('X-Missing'));
	}

	public function testRemoveHeaderByNameIsCaseInsensitive()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertTrue($m->removeHeader('x-stub'));
		self::assertCount(0, $m->getHeaders());
	}

	// -----------------------------------------------------------------------
	// getHeaderByName / getHeadersByName / getHeadersByClass
	// -----------------------------------------------------------------------

	public function testGetHeaderByNameReturnsFirstMatch()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		$m->addHeader($h2);
		self::assertSame($h1, $m->getHeaderByName('X-Stub'));
	}

	public function testGetHeaderByNameReturnsNullWhenMissing()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertNull($m->getHeaderByName('X-Missing'));
	}

	public function testGetHeaderByNameIsCaseInsensitive()
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertSame($h, $m->getHeaderByName('x-stub'));
	}

	public function testGetHeadersByNameReturnsAllMatches()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$other = new TStubHeader();
		$other->name = 'X-Other';
		$m->addHeader($h1);
		$m->addHeader($other);
		$m->addHeader($h2);
		$result = $m->getHeadersByName('X-Stub');
		self::assertCount(2, $result);
		self::assertSame($h1, $result[0]);
		self::assertSame($h2, $result[1]);
	}

	public function testGetHeadersByNameReturnsEmptyArrayWhenMissing()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame([], $m->getHeadersByName('X-Missing'));
	}

	public function testGetHeadersByClassReturnsMatchingInstances()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$ct = new THttpHeaderContentType();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		$m->addHeader($ct);
		$m->addHeader($h2);
		$result = $m->getHeadersByClass(TStubHeader::class);
		self::assertCount(2, $result);
		self::assertSame($h1, $result[0]);
		self::assertSame($h2, $result[1]);
	}

	public function testGetHeadersByClassReturnsEmptyWhenNoneMatch()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame([], $m->getHeadersByClass(THttpHeaderHsts::class));
	}

	// -----------------------------------------------------------------------
	// Reporter properties
	// -----------------------------------------------------------------------

	public function testSetReportingServiceModeAcceptsFalse()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		self::assertFalse($m->getReportingServiceMode());
	}

	public function testSetReportingServiceModeAcceptsTrue()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		self::assertTrue($m->getReportingServiceMode());
	}

	public function testSetReportingServiceModeAcceptsAutoString()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('auto');
		self::assertSame('Auto', $m->getReportingServiceMode());
	}

	public function testSetReportingServiceModeAutoIsCaseInsensitive()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('AUTO');
		self::assertSame('Auto', $m->getReportingServiceMode());
	}

	public function testSetReportingServiceModeStringTrueCoercedToTrue()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('true');
		self::assertTrue($m->getReportingServiceMode());
	}

	public function testSetReportingServiceModeStringFalseCoercedToFalse()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportingServiceMode('false');
		self::assertFalse($m->getReportingServiceMode());
	}

	public function testSetReportOnlyAcceptsTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(false);
		$m->setReportOnly(true);
		self::assertTrue($m->getReportOnly());
	}

	public function testSetReportOnlyAcceptsFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(false);
		self::assertFalse($m->getReportOnly());
	}

	public function testSetReportOnlyStringTrueCoercedToTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly('true');
		self::assertTrue($m->getReportOnly());
	}

	public function testSetReportOnlyStringFalseCoercedToFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly('false');
		self::assertFalse($m->getReportOnly());
	}

	public function testSetReportOnlyNullRestoresAutoState(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(true);
		$m->setReportOnly(null);
		self::assertNull($m->getReportOnly());
	}

	public function testSetReportOnlyAutoStringRestoresAutoState(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(true);
		$m->setReportOnly('auto');
		self::assertNull($m->getReportOnly());
	}

	public function testSetReportOnlyAutoStringCaseInsensitive(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(true);
		$m->setReportOnly('AUTO');
		self::assertNull($m->getReportOnly());
	}

	// -----------------------------------------------------------------------
	// resolveReportOnly — mode-dependent resolution
	// -----------------------------------------------------------------------

	public function testResolveReportOnlyReturnsTrueWhenExplicitlyTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(true);
		self::assertTrue($m->publicResolveReportOnly());
	}

	public function testResolveReportOnlyReturnsFalseWhenExplicitlyFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(false);
		self::assertFalse($m->publicResolveReportOnly());
	}

	public function testResolveReportOnlyReturnsTrueInDebugMode(): void
	{
		// Auto (null) + Debug mode → true.
		$m = new TTestableHttpHeadersManager();
		self::assertNull($m->getReportOnly(), 'pre-condition: default is Auto');
		$original = self::$app->getMode();
		self::$app->setMode(TApplicationMode::Debug);
		try {
			$m->setId('headers');
			self::assertTrue($m->publicResolveReportOnly(),
				'Auto mode in Debug must resolve to true');
		} finally {
			self::$app->setMode($original);
		}
	}

	public function testResolveReportOnlyReturnsFalseInNormalMode(): void
	{
		$m = new TTestableHttpHeadersManager();
		$original = self::$app->getMode();
		self::$app->setMode(TApplicationMode::Normal);
		try {
			$m->setId('headers');
			self::assertFalse($m->publicResolveReportOnly(),
				'Auto mode in Normal must resolve to false');
		} finally {
			self::$app->setMode($original);
		}
	}

	public function testResolveReportOnlyReturnsFalseInPerformanceMode(): void
	{
		$m = new TTestableHttpHeadersManager();
		$original = self::$app->getMode();
		self::$app->setMode(TApplicationMode::Performance);
		try {
			$m->setId('headers');
			self::assertFalse($m->publicResolveReportOnly(),
				'Auto mode in Performance must resolve to false');
		} finally {
			self::$app->setMode($original);
		}
	}

	public function testSetReportingServiceIdPersists()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceId('my-service');
		self::assertSame('my-service', $m->getReportingServiceId());
	}

	public function testSetReportingServiceIdAutoIsCaseInsensitive()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceId('AUTO');
		self::assertSame('Auto', $m->getReportingServiceId());

		$m->setReportingServiceId('auto');
		self::assertSame('Auto', $m->getReportingServiceId());
	}

	public function testSetReportingEndpointNamePersists()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingEndpointName('my-endpoint');
		self::assertSame('my-endpoint', $m->getReportingEndpointName());
	}

	// -----------------------------------------------------------------------
	// loadDefaultHeaders
	// -----------------------------------------------------------------------

	public function testLoadDefaultHeadersAddsContentTypeWhenAbsent()
	{
		$m = new TTestableHttpHeadersManager();
		self::assertFalse($m->hasHeader(THttpHeaderName::ContentType));
		$m->publicLoadDefaultHeaders();
		self::assertTrue($m->hasHeader(THttpHeaderName::ContentType));
	}

	public function testLoadDefaultHeadersDoesNotAddContentTypeWhenAlreadyPresent()
	{
		$m = new TTestableHttpHeadersManager();
		$ct = new THttpHeaderContentType();
		$m->addHeader($ct);
		$m->publicLoadDefaultHeaders();
		// Still just one Content-Type header.
		self::assertCount(1, $m->getHeadersByName(THttpHeaderName::ContentType));
	}

	// -----------------------------------------------------------------------
	// init(null) — full pipeline smoke test
	// -----------------------------------------------------------------------

	public function testInitNullRunsFullPipelineAndAddsDefaultContentTypeHeader(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setId('test-init-null-manager');
		// Must not throw; null → normalizeConfig → [] → loadHeaderClasses(noop) →
		// loadHeaders(noop) → loadDefaultHeaders (adds Content-Type) →
		// initComplete → attachEventHandler('onConfiguration', …).
		$m->init(null);
		self::assertTrue(
			$m->hasHeader(THttpHeaderName::ContentType),
			'init(null) must trigger loadDefaultHeaders(), which adds Content-Type'
		);
		self::assertCount(1, $m->getHeaders(),
			'null config must not add extra headers beyond the Content-Type default'
		);
	}

	// -----------------------------------------------------------------------
	// normalizeConfig / configToArray
	// -----------------------------------------------------------------------

	public function testNormalizeConfigReturnsArrayAsIs(): void
	{
		$m = new TTestableHttpHeadersManager();
		$input = ['headers' => [['properties' => ['HeaderName' => 'X-Foo']]]];
		self::assertSame($input, $m->publicNormalizeConfig($input));
	}

	public function testNormalizeConfigReturnsEmptyArrayForNull(): void
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame([], $m->publicNormalizeConfig(null));
	}

	public function testNormalizeConfigReturnsEmptyArrayForScalar(): void
	{
		$m = new TTestableHttpHeadersManager();
		self::assertSame([], $m->publicNormalizeConfig('raw-string'));
	}

	public function testNormalizeConfigDispatchesToConfigToArrayForXml(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<headerclass name="X-Custom" class="' . TStubHeader::class . '" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$result = $m->publicNormalizeConfig($doc);
		self::assertArrayHasKey('headerclasses', $result);
		self::assertArrayHasKey('headers', $result);
		self::assertCount(1, $result['headerclasses']);
		self::assertSame('X-Custom', $result['headerclasses'][0]['name']);
		self::assertSame(TStubHeader::class, $result['headerclasses'][0]['class']);
	}

	public function testConfigToArrayExtractsHeaderclassElements(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<headerclass name="X-Alpha" class="' . TStubHeader::class . '" />'
			. '<headerclass name="X-Beta"  class="' . THttpHeader::class . '" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$result = $m->publicConfigToArray($doc);

		self::assertCount(2, $result['headerclasses']);
		self::assertSame('X-Alpha', $result['headerclasses'][0]['name']);
		self::assertSame(TStubHeader::class, $result['headerclasses'][0]['class']);
		self::assertSame('X-Beta', $result['headerclasses'][1]['name']);
		self::assertSame(THttpHeader::class, $result['headerclasses'][1]['class']);
	}

	public function testConfigToArrayExtractsHeaderElements(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header HeaderName="X-Custom" HeaderValue="val" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$result = $m->publicConfigToArray($doc);

		self::assertCount(1, $result['headers']);
		$entry = $result['headers'][0];
		self::assertArrayHasKey('properties', $entry);
		self::assertArrayHasKey('config', $entry);
		self::assertInstanceOf(\Prado\Xml\TXmlElement::class, $entry['config']);
		self::assertSame('X-Custom', $entry['properties']['HeaderName']);
		self::assertSame('val', $entry['properties']['HeaderValue']);
		self::assertArrayNotHasKey('class', $entry);
	}

	public function testConfigToArrayExtractsClassAttributeFromHeader(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header class="' . TStubHeader::class . '" HeaderValue="max-age=31536000" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$result = $m->publicConfigToArray($doc);

		$entry = $result['headers'][0];
		self::assertSame(TStubHeader::class, $entry['class']);
		self::assertArrayNotHasKey('class', $entry['properties']);
	}

	public function testConfigToArrayEmptyModuleHasEmptyLists(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<module></module>');
		$m = new TTestableHttpHeadersManager();
		$result = $m->publicConfigToArray($doc);

		self::assertSame([], $result['headerclasses']);
		self::assertSame([], $result['headers']);
	}

	// -----------------------------------------------------------------------
	// loadHeaders — XML configuration path (via normalizeConfig)
	// -----------------------------------------------------------------------

	public function testLoadHeadersXmlPlainHeader(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header HeaderName="X-Custom" HeaderValue="val" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders($doc);

		self::assertCount(1, $m->getHeaders());
		$h = $m->getHeaders()[0];
		self::assertSame('X-Custom', $h->getHeaderName());
		self::assertSame('val', $h->getHeaderValue());
	}

	public function testLoadHeadersXmlPromotesHsts(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header HeaderName="' . THttpHeaderName::StrictTransportSecurity . '" HeaderValue="max-age=31536000" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders($doc);

		self::assertInstanceOf(THttpHeaderHsts::class, $m->getHeaders()[0]);
	}

	public function testLoadHeadersXmlTypedClassWithChildElements(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header class="' . THttpHeaderCsp::class . '">'
			. "<policy Name=\"default-src\">'self'</policy>"
			. '</header>'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders($doc);

		self::assertCount(1, $m->getHeaders());
		$csp = $m->getHeaders()[0];
		self::assertInstanceOf(THttpHeaderCsp::class, $csp);
		/** @var THttpHeaderCsp $csp */
		self::assertTrue($csp->hasPolicy(TCspDirective::DefaultSrc));
	}

	public function testLoadHeadersXmlMultipleHeaders(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>'
			. '<header HeaderName="X-Frame-Options" HeaderValue="DENY" />'
			. '<header HeaderName="X-Content-Type-Options" HeaderValue="nosniff" />'
			. '</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders($doc);

		self::assertCount(2, $m->getHeaders());
		self::assertSame('X-Frame-Options', $m->getHeaders()[0]->getHeaderName());
		self::assertSame('X-Content-Type-Options', $m->getHeaders()[1]->getHeaderName());
	}

	// -----------------------------------------------------------------------
	// loadHeaderClasses — array configuration
	// -----------------------------------------------------------------------

	public function testLoadHeaderClassesWithNullIsNoop(): void
	{
		$m = new TTestableHttpHeadersManager();
		$before = $m->getNameClassMap();
		$m->publicLoadHeaderClasses(null);
		self::assertSame($before, $m->getNameClassMap());
	}

	public function testLoadHeaderClassesWithEmptyArrayIsNoop(): void
	{
		$m = new TTestableHttpHeadersManager();
		$before = $m->getNameClassMap();
		$m->publicLoadHeaderClasses([]);
		self::assertSame($before, $m->getNameClassMap());
	}

	public function testLoadHeaderClassesSingleEntryRegistered(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['name' => 'X-Custom', 'class' => TStubHeader::class],
			],
		]);
		self::assertSame(TStubHeader::class, $m->getNameClassMap()['x-custom']);
	}

	public function testLoadHeaderClassesMultipleEntriesRegistered(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['name' => 'X-Alpha', 'class' => TStubHeader::class],
				['name' => 'X-Beta',  'class' => THttpHeader::class],
			],
		]);
		$map = $m->getNameClassMap();
		self::assertSame(TStubHeader::class, $map['x-alpha']);
		self::assertSame(THttpHeader::class, $map['x-beta']);
	}

	public function testLoadHeaderClassesOverridesExistingEntry(): void
	{
		$m = new TTestableHttpHeadersManager();
		// HSTS is in the default map; override it with the stub.
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['name' => THttpHeaderName::StrictTransportSecurity, 'class' => TStubHeader::class],
			],
		]);
		self::assertSame(TStubHeader::class, $m->getNameClassMap()[strtolower(THttpHeaderName::StrictTransportSecurity)]);
	}

	public function testLoadHeaderClassesThrowsWhenNameMissing(): void
	{
		$m = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['class' => TStubHeader::class],   // 'name' key absent
			],
		]);
	}

	public function testLoadHeaderClassesThrowsWhenClassMissing(): void
	{
		$m = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['name' => 'X-Custom'],            // 'class' key absent
			],
		]);
	}

	public function testLoadHeaderClassesRegisteredBeforeHeadersAreBuilt(): void
	{
		// Verifies the init() call order: loadHeaderClasses() runs before loadHeaders(),
		// so a class registered via 'headerclasses' is available for auto-promotion
		// when the 'headers' list is processed.
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaderClasses([
			'headerclasses' => [
				['name' => 'X-Stub', 'class' => TStubHeader::class],
			],
		]);
		$m->publicLoadHeaders([
			'headers' => [
				['properties' => ['HeaderName' => 'X-Stub', 'HeaderValue' => 'val']],
			],
		]);
		// The header must have been promoted to TStubHeader via the registered mapping.
		self::assertInstanceOf(TStubHeader::class, $m->getHeaders()[0]);
	}

	// -----------------------------------------------------------------------
	// loadHeaderClasses — XML configuration
	// -----------------------------------------------------------------------

	public function testLoadHeaderClassesXmlNullIsNoop(): void
	{
		$m = new TTestableHttpHeadersManager();
		$before = $m->getNameClassMap();
		$m->publicLoadHeaderClasses(null);
		self::assertSame($before, $m->getNameClassMap());
	}

	public function testLoadHeaderClassesXmlSingleEntry(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<module><headerclass name="X-Custom" class="' . TStubHeader::class . '" /></module>');
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaderClasses($doc);
		self::assertSame(TStubHeader::class, $m->getNameClassMap()['x-custom']);
	}

	public function testLoadHeaderClassesXmlMultipleEntries(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString(
			'<module>' .
			'<headerclass name="X-Alpha" class="' . TStubHeader::class . '" />' .
			'<headerclass name="X-Beta"  class="' . THttpHeader::class . '" />' .
			'</module>'
		);
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaderClasses($doc);
		$map = $m->getNameClassMap();
		self::assertSame(TStubHeader::class, $map['x-alpha']);
		self::assertSame(THttpHeader::class, $map['x-beta']);
	}

	public function testLoadHeaderClassesXmlThrowsWhenNameMissing(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<module><headerclass class="' . TStubHeader::class . '" /></module>');
		$m = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$m->publicLoadHeaderClasses($doc);
	}

	public function testLoadHeaderClassesXmlThrowsWhenClassMissing(): void
	{
		$doc = new \Prado\Xml\TXmlDocument();
		$doc->loadFromString('<module><headerclass name="X-Custom" /></module>');
		$m = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$m->publicLoadHeaderClasses($doc);
	}

	// -----------------------------------------------------------------------
	// loadHeaders — array configuration
	// -----------------------------------------------------------------------

	public function testLoadHeadersWithNullIsNoop()
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders(null);
		self::assertCount(0, $m->getHeaders());
	}

	public function testLoadHeadersWithEmptyArrayIsNoop()
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([]);
		self::assertCount(0, $m->getHeaders());
	}

	public function testLoadHeadersCreatesPlainTHttpHeader()
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [
				['properties' => ['HeaderName' => 'X-Custom', 'HeaderValue' => 'val']],
			],
		]);
		self::assertCount(1, $m->getHeaders());
		$h = $m->getHeaders()[0];
		self::assertInstanceOf(THttpHeader::class, $h);
		self::assertSame('X-Custom', $h->getHeaderName());
		self::assertSame('val', $h->getHeaderValue());
	}

	public function testLoadHeadersPromotesHstsViaNameClassMap()
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [
				[
					'properties' => [
						'HeaderName'  => THttpHeaderName::StrictTransportSecurity,
						'HeaderValue' => 'max-age=31536000',
					],
				],
			],
		]);
		self::assertCount(1, $m->getHeaders());
		self::assertInstanceOf(THttpHeaderHsts::class, $m->getHeaders()[0]);
	}

	public function testLoadHeadersPromotesContentTypeViaNameClassMap()
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [
				[
					'properties' => [
						'HeaderName' => THttpHeaderName::ContentType,
					],
				],
			],
		]);
		self::assertInstanceOf(THttpHeaderContentType::class, $m->getHeaders()[0]);
	}

	public function testLoadHeadersPromotionIsCaseInsensitive()
	{
		// The name→class map lookup must be case-insensitive, consistent with all
		// other header-name comparisons in the manager.
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [
				[
					'properties' => [
						'HeaderName'  => strtolower(THttpHeaderName::StrictTransportSecurity),
						'HeaderValue' => 'max-age=31536000',
					],
				],
			],
		]);
		self::assertCount(1, $m->getHeaders());
		self::assertInstanceOf(THttpHeaderHsts::class, $m->getHeaders()[0]);
	}

	public function testLoadHeadersWithExplicitClassDoesNotPromote()
	{
		$m = new TTestableHttpHeadersManager();
		// TStubHeader is not the default mapping class, so specifying it explicitly
		// must bypass the name→class promotion logic and leave the instance as-is.
		// TStubHeader has no setHeaderName(), so only HeaderValue is passed.
		$m->publicLoadHeaders([
			'headers' => [
				[
					'class'      => TStubHeader::class,
					'properties' => [
						'HeaderValue' => 'max-age=31536000',
					],
				],
			],
		]);
		// Explicit non-default class bypasses promotion → stays TStubHeader.
		self::assertInstanceOf(TStubHeader::class, $m->getHeaders()[0]);
		self::assertNotInstanceOf(THttpHeaderHsts::class, $m->getHeaders()[0]);
	}

	public function testLoadHeadersThrowsOnNonHeaderClass()
	{
		$m = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$m->publicLoadHeaders([
			'headers' => [
				['class' => \stdClass::class],
			],
		]);
	}

	public function testLoadHeadersCallsInitOnEachHeader()
	{
		$m = new TTestableHttpHeadersManager();
		// Register TStubHeader in the name→class map so it can be promoted.
		$m->registerHeaderClass('X-Stub', TStubHeader::class);
		$m->publicLoadHeaders([
			'headers' => [
				['class' => TStubHeader::class],
				['class' => TStubHeader::class],
			],
		]);
		foreach ($m->getHeadersByClass(TStubHeader::class) as $stub) {
			self::assertGreaterThanOrEqual(1, $stub->initCallCount,
				'init() must be called during loadHeaders()');
		}
	}

	// -----------------------------------------------------------------------
	// initComplete
	// -----------------------------------------------------------------------

	public function testInitCompleteCallsInitCompleteOnAllHeaders()
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		$m->addHeader($h2);
		$m->publicInitComplete();
		self::assertSame(1, $h1->initCompleteCallCount);
		self::assertSame(1, $h2->initCompleteCallCount);
	}

	// -----------------------------------------------------------------------
	// finalizeHeaders — calls finalizeHeader on all headers
	// -----------------------------------------------------------------------

	public function testFinalizeHeadersCallsFinalizeOnAllHeaders()
	{
		$m = new TTestableHttpHeadersManager();
		// ReportingServiceMode=false so finalizeReporterService is a no-op.
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		$m->addHeader($h2);
		$m->publicFinalizeHeaders();
		self::assertSame(1, $h1->finalizeCallCount);
		self::assertSame(1, $h2->finalizeCallCount);
	}

	// -----------------------------------------------------------------------
	// getIsHandled / setIsHandled
	// -----------------------------------------------------------------------

	public function testGetIsHandledDefaultsFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		self::assertFalse($m->getIsHandled());
	}

	public function testSetIsHandledTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled(true);
		self::assertTrue($m->getIsHandled());
	}

	public function testSetIsHandledFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled(true);
		$m->setIsHandled(false);
		self::assertFalse($m->getIsHandled());
	}

	public function testSetIsHandledTrueString(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled('true');
		self::assertTrue($m->getIsHandled());
	}

	public function testSetIsHandledFalseString(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled('true');
		$m->setIsHandled('false');
		self::assertFalse($m->getIsHandled());
	}

	public function testSetIsHandledOneString(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled('1');
		self::assertTrue($m->getIsHandled());
	}

	// -----------------------------------------------------------------------
	// ensureHeadersSent — idempotency
	// -----------------------------------------------------------------------

	public function testEnsureHeadersSentSendsOnce(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		$m->ensureHeadersSent();
		self::assertSame(1, $m->sendCount);
	}

	public function testEnsureHeadersSentIsIdempotent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		$m->ensureHeadersSent();
		$m->ensureHeadersSent();
		$m->ensureHeadersSent();
		self::assertSame(1, $m->sendCount, 'sendHeaders() must be called exactly once');
	}

	public function testEnsureHeadersSentEmitsHeaderStrings(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		$m->ensureHeadersSent();
		self::assertContains('X-Stub: stub-value', $m->sentHeaders);
	}

	public function testEnsureHeadersSentIsNoopWhenIsHandledTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->addHeader(new TStubHeader());
		$m->setIsHandled(true);
		$m->ensureHeadersSent();
		self::assertSame(0, $m->sendCount,
			'ensureHeadersSent() must not call sendHeaders() when IsHandled is true');
		self::assertFalse($m->getHeadersSent(),
			'HeadersSent must remain false when IsHandled suppressed the send');
	}

	public function testEnsureHeadersSentSendsAfterIsHandledResetToFalse(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->addHeader(new TStubHeader());
		$m->setIsHandled(true);
		$m->ensureHeadersSent();
		self::assertSame(0, $m->sendCount);

		$m->setIsHandled(false);
		$m->ensureHeadersSent();
		self::assertSame(1, $m->sendCount,
			'ensureHeadersSent() must send after IsHandled is reset to false');
	}

	public function testIsHandledDoesNotAffectHeadersSentFlag(): void
	{
		// IsHandled suppresses the pipeline; HeadersSent tracks internal pipeline completion.
		// The two flags are independent — setting IsHandled must not touch HeadersSent.
		$m = new TTestableHttpHeadersManager();
		self::assertFalse($m->getHeadersSent());
		$m->setIsHandled(true);
		self::assertFalse($m->getHeadersSent());
	}

	// -----------------------------------------------------------------------
	// ensureReportingServiceRegistered — mode=false no-op
	// -----------------------------------------------------------------------

	public function testensureReportingServiceRegisteredIsNoopWhenModeFalse()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		// Must not throw even without a real TApplication.
		$m->ensureReportingServiceRegistered();
		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------
	// Reporter service wiring — Auto mode with existing service (no-op)
	// -----------------------------------------------------------------------

	public function testensureReportingServiceRegisteredIsNoopWhenAutoAndServiceAlreadyExists()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setId('headers');

		// Inject the service directly into the app registry.
		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$ref->setValue(self::$app, array_merge($before, [
			TCspReportingService::SERVICE_ID => [TCspReportingService::class, [], null],
		]));

		try {
			// Should not register another one.
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			// Service count must not have grown beyond one entry for this class.
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertCount(1, $byClass, 'Existing service must not be duplicated');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredAdoptsFoundServiceIdInAutoMode()
	{
		// When Auto mode finds an existing TCspReportingService, it must update
		// ReportingServiceId to the found service's actual registered ID so that
		// finalizeReporterService() can use it directly without a second class scan.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		// Remove any pre-existing TCspReportingService so 'custom-csp-reporter' is the only match.
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, array_merge($without, [
			'custom-csp-reporter' => [TCspReportingService::class, [], null],
		]));

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			self::assertSame('custom-csp-reporter', $m->getReportingServiceId(),
				'Auto mode must adopt the existing service ID, not keep "Auto"');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredRegistersServiceWhenAutoAndNoneExists()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		// Ensure no TCspReportingService exists.
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertNotEmpty($byClass, 'A TCspReportingService entry must be registered');
			// ID must have been adopted so it is no longer the 'Auto' sentinel.
			self::assertSame(TCspReportingService::SERVICE_ID, $m->getReportingServiceId(),
				'Auto mode must adopt the default service ID after creating a new service');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredUsesLiteralServiceId()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-csp-service');
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		// Ensure no entry with that ID exists.
		$without = $before;
		unset($without['my-csp-service']);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			self::assertArrayHasKey('my-csp-service', $services,
				'Service must be registered under the literal ID');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredIsNoopWhenLiteralIdAlreadyRegistered()
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-csp-service');
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$withService = array_merge($before, [
			'my-csp-service' => [TCspReportingService::class, [], null],
		]);
		$ref->setValue(self::$app, $withService);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			// Count of 'my-csp-service' entries — must be exactly 1.
			self::assertArrayHasKey('my-csp-service', $services);
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredLogsWarningWhenLiteralIdHasWrongClass(): void
	{
		// When the literal ReportingServiceId is already registered but points to a
		// class that is NOT a TCspReportingService subclass, ensureReportingServiceRegistered()
		// must emit a WARNING-level log entry and still return without throwing.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('wrong-class-service');
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		// Register something that is definitely NOT a TCspReportingService subclass.
		$withService = array_merge($before, [
			'wrong-class-service' => [THttpHeadersManager::class, [], null],
		]);
		$ref->setValue(self::$app, $withService);

		$logger = Prado::getLogger();
		$logger->deleteLogs();

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);

			$logs = $logger->getLogs(TLogger::WARNING);
			$warningMessages = array_column($logs, 0);
			$found = false;
			foreach ($warningMessages as $msg) {
				if (str_contains((string) $msg, 'wrong-class-service')) {
					$found = true;
					break;
				}
			}
			self::assertTrue($found,
				'A WARNING log entry mentioning the mismatched service ID must be emitted');
		} finally {
			$ref->setValue(self::$app, $before);
			$logger->deleteLogs();
		}
	}

	public function testensureReportingServiceRegisteredRegistersServiceWhenAutoModeAndNoneExists()
	{
		// ReportingServiceMode='Auto' must register the service when none exists and
		// ReportOnly resolves to true (Debug mode makes the app auto-report-only).
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('Auto');
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);

		$originalMode = self::$app->getMode();
		self::$app->setMode(TApplicationMode::Debug);
		$ref->setValue(self::$app, $without);

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertNotEmpty($byClass,
				'ReportingServiceMode=Auto must register a TCspReportingService when none exists in Debug mode');
		} finally {
			$ref->setValue(self::$app, $before);
			self::$app->setMode($originalMode);
		}
	}

	public function testensureReportingServiceRegisteredSetsAutoRegisteredFlag()
	{
		// The entry stored in the service registry must carry AutoRegistered=true
		// as an init-property so the service knows it was auto-created.
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
			$entry = $services[TCspReportingService::SERVICE_ID] ?? null;
			self::assertNotNull($entry, 'Service entry must exist');
			// Registry entry is [$class, $initProperties, $configElement].
			self::assertTrue($entry[1]['AutoRegistered'] ?? false,
				'Auto-registered service must have AutoRegistered=true in its init-properties');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testensureReportingServiceRegisteredAutoModeStringIsNoopWhenServiceAlreadyExists()
	{
		// ReportingServiceMode='Auto' string — if the service already exists, no second
		// entry must be added.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode('Auto');
		$m->setId('headers');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$ref->setValue(self::$app, array_merge($before, [
			TCspReportingService::SERVICE_ID => [TCspReportingService::class, [], null],
		]));

		try {
			$m->ensureReportingServiceRegistered(self::$app, null);
			$services = $ref->getValue(self::$app);
			$byClass = array_filter($services, fn ($s) => ($s[0] ?? null) === TCspReportingService::class);
			self::assertCount(1, $byClass,
				'ReportingServiceMode=Auto must not register a duplicate service');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — mode=false / no-app early return
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceIsNoopWhenModeFalseAndNoServiceRegistered(): void
	{
		// mode=false with no TCspReportingService in the registry — no sentinel
		// replacement and no Reporting-Endpoints header must be added.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		// Ensure no TCspReportingService is registered for this test.
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, $without);

		try {
			$m->publicFinalizeReporterService();
			self::assertCount(0, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class));
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testFinalizeReporterServiceModeFalseReplacesCspReportUriSentinelWhenServicePresent(): void
	{
		// mode=false + TCspReportingService already registered → REPORT_URI sentinel
		// in a CSP header must still be replaced with the real reporter URL.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		$csp->setPolicy(TCspDirective::ReportUri, ''); // blank → REPORT_URI sentinel

		self::assertTrue($csp->hasReportUriPlaceholder(), 'pre-condition: sentinel must be stored');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, array_merge($without, [
			'my-reporter' => [TCspReportingService::class, [], null],
		]));

		try {
			$m->publicFinalizeReporterService();

			self::assertFalse($csp->hasReportUriPlaceholder(),
				'REPORT_URI sentinel must be replaced even when ReportingServiceMode=false');
			self::assertStringContainsString(
				'https://example.com/csp-report',
				$csp->getPolicy(TCspDirective::ReportUri) ?? '',
				'report-uri value must contain the reporter URL'
			);
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testFinalizeReporterServiceModeFalseReplacesReportingEndpointsSentinelWhenServicePresent(): void
	{
		// mode=false + TCspReportingService already registered → REPORT_URI sentinel
		// in a Reporting-Endpoints header must still be replaced with the real URL.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$re = new THttpHeaderReportingEndpoints();
		$m->addHeader($re);
		$re->init([]);
		$re->addEndpoint('my-ep', ''); // blank → REPORT_URI sentinel

		self::assertTrue($re->hasReportUriPlaceholder(), 'pre-condition: sentinel must be stored');

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, array_merge($without, [
			'my-reporter' => [TCspReportingService::class, [], null],
		]));

		try {
			$m->publicFinalizeReporterService();

			self::assertFalse($re->hasReportUriPlaceholder(),
				'Reporting-Endpoints sentinel must be replaced even when ReportingServiceMode=false');
			self::assertStringContainsString(
				'https://example.com/csp-report',
				$re->getEndpointUrl('my-ep') ?? '',
				'Endpoint URL must contain the reporter URL after replacement'
			);
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	public function testFinalizeReporterServiceModeFalseDoesNotInjectReportToOrCreateEndpointHeader(): void
	{
		// mode=false must never inject report-to, convert report-only, or create a
		// Reporting-Endpoints header — even when a TCspReportingService is registered.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		// No report-uri sentinel, no report-to.

		$ref = new ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		$ref->setValue(self::$app, array_merge($without, [
			'my-reporter' => [TCspReportingService::class, [], null],
		]));

		try {
			$m->publicFinalizeReporterService();

			self::assertCount(0, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class),
				'mode=false must not auto-create a Reporting-Endpoints header');
			self::assertFalse($csp->hasPolicy(TCspDirective::ReportTo),
				'mode=false must not inject a report-to directive');
			self::assertFalse($csp->getReportOnly(),
				'mode=false must not convert an enforcing CSP to report-only');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — creates Reporting-Endpoints when absent
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceAddsReportingEndpointsHeaderWhenNonePresent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		self::assertCount(0, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class),
			'pre-condition: no RE header before finalize');

		$m->publicFinalizeReporterService();

		$reHeaders = $m->getHeadersByClass(THttpHeaderReportingEndpoints::class);
		self::assertCount(1, $reHeaders, 'A RE header must be created automatically');
	}

	public function testFinalizeReporterServiceDeclaresEndpointWithCorrectUrl(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$m->publicFinalizeReporterService();

		/** @var THttpHeaderReportingEndpoints $re */
		$re = $m->getHeadersByClass(THttpHeaderReportingEndpoints::class)[0];
		$name = $m->getReportingEndpointName();
		self::assertTrue($re->hasEndpoint($name), 'Endpoint must be declared under the configured name');
		// buildReporterUrl appends the service ID as a path segment.
		self::assertStringContainsString(
			'https://example.com/csp-report/',
			$re->getEndpointUrl($name) ?? ''
		);
	}

	public function testFinalizeReporterServiceUsesExistingReportingEndpointsHeader(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		// Pre-add a RE header with a different endpoint.
		$existing = new THttpHeaderReportingEndpoints();
		$m->addHeader($existing);
		$existing->init([]);
		$existing->addEndpoint('other-ep', 'https://example.com/other');

		$m->publicFinalizeReporterService();

		// Must still be exactly one RE header — no duplicate created.
		self::assertCount(1, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class));
		// Both the pre-existing and the new endpoint must be present.
		self::assertTrue($existing->hasEndpoint('other-ep'));
		self::assertTrue($existing->hasEndpoint($m->getReportingEndpointName()));
	}

	public function testFinalizeReporterServiceDoesNotAddEndpointWhenAlreadyDeclared(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';
		$endpointName = $m->getReportingEndpointName();

		// Pre-add a RE header that already declares the endpoint.
		$re = new THttpHeaderReportingEndpoints();
		$m->addHeader($re);
		$re->init([]);
		$re->addEndpoint($endpointName, 'https://pre-existing.example.com/csp');

		$m->publicFinalizeReporterService();

		// The URL must not be overwritten.
		self::assertSame(
			'https://pre-existing.example.com/csp',
			$re->getEndpointUrl($endpointName)
		);
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — report-to injection into CSP headers
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceInjectsReportToIntoCspHeader(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertFalse($csp->hasPolicy(TCspDirective::ReportTo), 'pre-condition: no report-to yet');

		$m->publicFinalizeReporterService();

		self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
			'finalizeReporterService must inject report-to into CSP headers that lack one');
		$policies = $csp->getPolicies();
		self::assertSame($m->getReportingEndpointName(), $policies[TCspDirective::ReportTo]);
	}

	public function testFinalizeReporterServiceDoesNotOverwriteExistingReportTo(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportTo, 'my-custom-endpoint');

		$m->publicFinalizeReporterService();

		// The pre-existing report-to value must not be changed.
		$policies = $csp->getPolicies();
		self::assertSame('my-custom-endpoint', $policies[TCspDirective::ReportTo]);
	}

	public function testFinalizeReporterServiceInjectsReportToIntoAllCspHeaders(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp1 = new THttpHeaderCsp();
		$csp2 = new THttpHeaderCsp();
		$m->addHeader($csp1);
		$m->addHeader($csp2);
		$csp1->init([]);
		$csp2->init([]);

		$m->publicFinalizeReporterService();

		self::assertTrue($csp1->hasPolicy(TCspDirective::ReportTo), 'First CSP must get report-to');
		self::assertTrue($csp2->hasPolicy(TCspDirective::ReportTo), 'Second CSP must get report-to');
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — Auto mode converts enforcing CSP to report-only
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceAutoModeConvertsEnforcingCspToReportOnly(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(true);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		self::assertFalse($csp->getReportOnly(), 'pre-condition: enforcing mode');

		$m->publicFinalizeReporterService();

		self::assertTrue($csp->getReportOnly(),
			'Auto mode must flip enforcing CSP headers to report-only');
	}

	public function testFinalizeReporterServiceAutoModeDoesNotChangeAlreadyReportOnlyCsp(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(true);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->setReportOnly(true);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");

		$m->publicFinalizeReporterService();

		// Already report-only — must remain report-only (no double-flip).
		self::assertTrue($csp->getReportOnly());
	}

	public function testFinalizeReporterServiceTrueModeDoesNotConvertEnforcingCsp(): void
	{
		// ReportingServiceMode=true + ReportOnly=false must inject report-to but must NOT
		// convert enforcing headers to report-only.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");

		$m->publicFinalizeReporterService();

		self::assertFalse($csp->getReportOnly(),
			'ReportingServiceMode=true must not convert enforcing CSP to report-only');
		self::assertTrue($csp->hasPolicy(TCspDirective::ReportTo),
			'ReportingServiceMode=true must still inject report-to');
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — report-uri placeholder fill
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceFillsInReportUriSentinel(): void
	{
		// When a CSP has the REPORT_URI sentinel, finalize must replace it with
		// the actual reporter URL. Use a literal service ID to control the exact URL
		// (TTestableHttpHeadersManager::buildReporterUrl appends serviceId as a path segment).
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-reporter');
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportUri, THttpHeaderCsp::REPORT_URI);

		$m->publicFinalizeReporterService();

		self::assertSame(
			'https://example.com/csp-report/my-reporter',
			$csp->getPolicy(TCspDirective::ReportUri),
			'REPORT_URI sentinel must be replaced with the reporter URL'
		);
	}

	public function testFinalizeReporterServiceDoesNotOverwriteDeveloperReportUri(): void
	{
		// A developer-supplied report-uri (not the sentinel) must not be touched.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::ReportUri, 'https://my-own-collector.example.com/report');

		$m->publicFinalizeReporterService();

		self::assertSame(
			'https://my-own-collector.example.com/report',
			$csp->getPolicy(TCspDirective::ReportUri),
			'Developer-supplied report-uri must not be overwritten by the manager'
		);
	}

	public function testFinalizeReporterServiceReportUriPlaceholderTriggersAutoMode(): void
	{
		// In Auto/Auto mode, the presence of the REPORT_URI sentinel must be enough
		// to trigger wiring even when there is no report-to directive.
		// ServiceMode and ServiceId are both 'Auto' by default.
		$m = new TTestableHttpHeadersManager();
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$csp->addPolicy(TCspDirective::ReportUri, THttpHeaderCsp::REPORT_URI);

		$m->publicFinalizeReporterService();

		// The sentinel must have been replaced — any non-sentinel value proves finalize ran.
		self::assertNotSame(
			THttpHeaderCsp::REPORT_URI,
			$csp->getPolicy(TCspDirective::ReportUri),
			'REPORT_URI sentinel must trigger Auto mode and be replaced with the reporter URL'
		);
		self::assertNotNull(
			$csp->getPolicy(TCspDirective::ReportUri),
			'report-uri must be set to a URL, not null'
		);
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — literal ReportingServiceId
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceLiteralServiceIdUsedForEndpointUrl(): void
	{
		// When ReportingServiceId is set to a literal (not 'Auto'), that literal ID
		// must be forwarded to buildReporterUrl — and must therefore appear in the
		// resulting endpoint URL (the fake doubles-up serviceId as a path segment).
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-custom-reporter');
		$m->fakeReporterUrl = 'https://custom.example.com/csp';

		$m->publicFinalizeReporterService();

		$reHeaders = $m->getHeadersByClass(THttpHeaderReportingEndpoints::class);
		self::assertCount(1, $reHeaders);
		// URL will be 'https://custom.example.com/csp/my-custom-reporter'.
		self::assertStringContainsString('my-custom-reporter', $reHeaders[0]->getHeaderValue(),
			'Literal service ID must flow through buildReporterUrl into the endpoint URL');
	}

	// -----------------------------------------------------------------------
	// validateHeaders — COEP/COOP pair
	// -----------------------------------------------------------------------

	public function testValidateCoepCoopPairIsNoOpWhenNeitherPresent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$m->publicValidateCoepCoopPair();
		$logs = $logger->getLogs(TLogger::WARNING);
		self::assertEmpty($logs, 'No WARNING must be logged when neither COEP nor COOP is present');
	}

	public function testValidateCoepCoopPairIsNoOpWhenBothPresent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$coep = new TStubHeader();
		$coep->name = THttpHeaderName::CrossOriginEmbedderPolicy;
		$coop = new TStubHeader();
		$coop->name = THttpHeaderName::CrossOriginOpenerPolicy;
		$m->addHeader($coep);
		$m->addHeader($coop);
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$m->publicValidateCoepCoopPair();
		$logs = $logger->getLogs(TLogger::WARNING);
		self::assertEmpty($logs, 'No WARNING must be logged when both COEP and COOP are present');
	}

	public function testValidateCoepCoopPairWarnsWhenCoepPresentWithoutCoop(): void
	{
		$m = new TTestableHttpHeadersManager();
		$coep = new TStubHeader();
		$coep->name = THttpHeaderName::CrossOriginEmbedderPolicy;
		$m->addHeader($coep);
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$m->publicValidateCoepCoopPair();
		$logs = $logger->getLogs(TLogger::WARNING);
		self::assertNotEmpty($logs, 'A WARNING must be logged when COEP is present without COOP');
		self::assertStringContainsString('Cross-Origin', $logs[0][0]);
	}

	public function testValidateCoepCoopPairWarnsWhenCoopPresentWithoutCoep(): void
	{
		$m = new TTestableHttpHeadersManager();
		$coop = new TStubHeader();
		$coop->name = THttpHeaderName::CrossOriginOpenerPolicy;
		$m->addHeader($coop);
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$m->publicValidateCoepCoopPair();
		$logs = $logger->getLogs(TLogger::WARNING);
		self::assertNotEmpty($logs, 'A WARNING must be logged when COOP is present without COEP');
		self::assertStringContainsString('Cross-Origin', $logs[0][0]);
	}

	// -----------------------------------------------------------------------
	// validateHeaders — frame-ancestors vs X-Frame-Options
	// -----------------------------------------------------------------------

	public function testValidateFrameAncestorsXFrameOptionsIsNoOpWhenNoCsp(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicValidateFrameAncestorsXFrameOptions();
		$this->addToAssertionCount(1);
	}

	public function testValidateFrameAncestorsXFrameOptionsIsNoOpWhenXFrameOptionsPresent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$xfo = new TStubHeader();
		$xfo->name = THttpHeaderName::XFrameOptions;
		$m->addHeader($xfo);
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::FrameAncestors, "'none'");
		$m->addHeader($csp);
		$csp->init([]);
		// Both present — no warning.
		$m->publicValidateFrameAncestorsXFrameOptions();
		$this->addToAssertionCount(1);
	}

	public function testValidateFrameAncestorsXFrameOptionsWarnsWhenFrameAncestorsWithoutXfo(): void
	{
		$m = new TTestableHttpHeadersManager();
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::FrameAncestors, "'none'");
		$m->addHeader($csp);
		$csp->init([]);
		// frame-ancestors present, X-Frame-Options absent — logs DEBUG, must not throw.
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$m->publicValidateFrameAncestorsXFrameOptions();
		$logs = $logger->getLogs(TLogger::DEBUG);
		self::assertNotEmpty($logs, 'A DEBUG log must be emitted when frame-ancestors lacks X-Frame-Options');
		self::assertStringContainsString('frame-ancestors', $logs[0][0]);
	}

	public function testValidateFrameAncestorsXFrameOptionsIsNoOpWhenCspHasNoFrameAncestors(): void
	{
		$m = new TTestableHttpHeadersManager();
		$csp = new THttpHeaderCsp();
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");
		$m->addHeader($csp);
		$csp->init([]);
		// No frame-ancestors in CSP — no warning even without X-Frame-Options.
		$m->publicValidateFrameAncestorsXFrameOptions();
		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------
	// validateHeaders — called from finalizeHeaders()
	// -----------------------------------------------------------------------

	public function testValidateHeadersIsCalledByFinalizeHeaders(): void
	{
		$m = new class extends TTestableHttpHeadersManager {
			public bool $validated = false;
			protected function validateHeaders(): void
			{
				$this->validated = true;
			}
		};
		$m->publicFinalizeHeaders();
		self::assertTrue($m->validated, 'validateHeaders() must be called by finalizeHeaders()');
	}

	public function testValidateHeadersRunsAfterPerHeaderFinalizeHeader(): void
	{
		// Validation runs after per-header finalizeHeader() so it sees the final
		// header state (e.g. sandbox already stripped, report-to already injected).
		$order = [];
		$m = new class ($order) extends TTestableHttpHeadersManager {
			public function __construct(public array &$log) { parent::__construct(); }
			protected function validateHeaders(): void { $this->log[] = 'validate'; }
		};
		$h = new class ($order) extends TStubHeader {
			public function __construct(public array &$log) {}
			public function finalizeHeader(): void { $this->log[] = 'finalize'; }
		};
		$m->addHeader($h);
		$m->setReportingServiceMode(false);
		$m->publicFinalizeHeaders();
		self::assertSame(['finalize', 'validate'], $order,
			'per-header finalizeHeader() must run before validateHeaders()');
	}

	// -----------------------------------------------------------------------
	// setReportOnly — integer coercion through union type
	// -----------------------------------------------------------------------

	public function testSetReportOnlyIntegerCoercion(): void
	{
		// The union type bool|string|null coerces integer to string in PHP 8+
		// (string is preferred over bool in the coercion order), so 0 → "0" and
		// 1 → "1". TPropertyValue::ensureBoolean("0") → false, ensureBoolean("1") → true.
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(0);
		self::assertFalse($m->getReportOnly());

		$m->setReportOnly(1);
		self::assertTrue($m->getReportOnly());
	}

	// -----------------------------------------------------------------------
	// resolveReportOnly — no application returns false
	// -----------------------------------------------------------------------

	public function testResolveReportOnlyReturnsFalseWhenNoApplication(): void
	{
		// When getApplication() returns null (no TApplication in context),
		// resolveReportOnly() must short-circuit and return false.
		$m = new class extends TTestableHttpHeadersManager {
			public function getApplication()
			{
				return null;
			}
		};
		self::assertNull($m->getReportOnly(), 'pre-condition: default is Auto (null)');
		self::assertFalse($m->publicResolveReportOnly());
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — Auto mode with no triggers is a no-op
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceAutoModeWithNoTriggersIsNoop(): void
	{
		// Mode=Auto (default), serviceId=Auto (default), no CSP headers and
		// resolveReportOnly()=false (Normal app mode) → early return with no
		// Reporting-Endpoints header added.
		$m = new TTestableHttpHeadersManager();
		$original = self::$app->getMode();
		self::$app->setMode(\Prado\TApplicationMode::Normal);
		try {
			self::assertCount(0, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class),
				'pre-condition: no RE header before finalize');
			$m->publicFinalizeReporterService();
			self::assertCount(0, $m->getHeadersByClass(THttpHeaderReportingEndpoints::class),
				'No RE header must be added when no triggers are present in Auto mode');
		} finally {
			self::$app->setMode($original);
		}
	}

	// -----------------------------------------------------------------------
	// ensureReportingServiceRegistered — Auto mode + no service + no reportOnly is noop
	// -----------------------------------------------------------------------

	public function testEnsureReportingServiceRegisteredAutoModeNoServiceAndNoReportOnlyIsNoOp(): void
	{
		// When mode='Auto', reportOnly=false, and no TCspReportingService is registered,
		// ensureReportingServiceRegistered() must neither register a new service nor
		// change ReportingServiceId from 'Auto'.
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(false);

		$ref = new \ReflectionProperty(TApplication::class, '_services');
		$ref->setAccessible(true);
		$before = $ref->getValue(self::$app);
		$without = array_filter($before, fn ($s) => ($s[0] ?? null) !== TCspReportingService::class);
		try {
			$ref->setValue(self::$app, $without);
			$m->ensureReportingServiceRegistered();
			self::assertNull(
				self::$app->getRegisteredServiceByClass(TCspReportingService::class),
				'ensureReportingServiceRegistered must not force-register a new service when reportOnly=false in Auto mode'
			);
			self::assertSame('Auto', $m->getReportingServiceId(),
				'ReportingServiceId must remain "Auto" when no service was found and none was registered');
		} finally {
			$ref->setValue(self::$app, $before);
		}
	}

	// -----------------------------------------------------------------------
	// loadDefaultHeaders — calling it twice must not duplicate default headers
	// -----------------------------------------------------------------------

	public function testLoadDefaultHeadersCalledTwiceDoesNotDuplicateDefaultHeaders(): void
	{
		// loadDefaultHeaders() guards with hasHeader(); a second call must not add
		// a second Content-Type header. Tested via the public wrapper to avoid
		// triggering the event-handler attachment that init() performs.
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadDefaultHeaders();
		$m->publicLoadDefaultHeaders();
		self::assertCount(1, $m->getHeadersByClass(THttpHeaderContentType::class),
			'loadDefaultHeaders() must not add a duplicate Content-Type on repeated calls');
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — Reporting-Endpoints REPORT_URI sentinel fill
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceFillsReportingEndpointsSentinelUrl(): void
	{
		// An endpoint registered with a blank URL stores the REPORT_URI sentinel;
		// finalizeReporterService() must replace it with the live reporter URL.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-reporter');
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$re = new THttpHeaderReportingEndpoints();
		$m->addHeader($re);
		$re->init([]);
		$re->addEndpoint('my-ep', ''); // blank → REPORT_URI sentinel

		self::assertTrue($re->hasReportUriPlaceholder(), 'pre-condition: sentinel stored');

		$m->publicFinalizeReporterService();

		self::assertFalse($re->hasReportUriPlaceholder(),
			'Sentinel must be replaced after finalizeReporterService()');
		self::assertStringContainsString(
			'https://example.com/csp-report',
			$re->getEndpointUrl('my-ep') ?? '',
			'Endpoint URL must be the live reporter URL after replacement'
		);
	}

	public function testFinalizeReporterServiceDoesNotOverwriteRealEndpointUrl(): void
	{
		// An endpoint with a real URL must be left untouched.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-reporter');
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$re = new THttpHeaderReportingEndpoints();
		$m->addHeader($re);
		$re->init([]);
		$re->addEndpoint('my-ep', 'https://my-own-collector.example.com/report');

		$m->publicFinalizeReporterService();

		self::assertSame(
			'https://my-own-collector.example.com/report',
			$re->getEndpointUrl('my-ep'),
			'Developer-supplied endpoint URL must not be overwritten'
		);
	}

	public function testFinalizeReporterServiceReportingEndpointsSentinelTriggersAutoMode(): void
	{
		// In Auto/Auto mode (no CSP headers, no reportOnly), a REPORT_URI sentinel
		// in a Reporting-Endpoints header must itself trigger wiring.
		$m = new TTestableHttpHeadersManager();
		$m->fakeReporterUrl = 'https://example.com/csp-report';
		$original = self::$app->getMode();
		self::$app->setMode(\Prado\TApplicationMode::Normal);
		try {
			$re = new THttpHeaderReportingEndpoints();
			$m->addHeader($re);
			$re->init([]);
			$re->addEndpoint('auto-ep', ''); // sentinel → triggers condition 3

			$m->publicFinalizeReporterService();

			self::assertFalse($re->hasReportUriPlaceholder(),
				'Sentinel must be replaced when Reporting-Endpoints sentinel triggers Auto mode');
		} finally {
			self::$app->setMode($original);
		}
	}

	public function testFinalizeReporterServiceMixedEndpointsSentinelReplacedRealPreserved(): void
	{
		// One real URL endpoint and one sentinel endpoint in the same header;
		// only the sentinel must be replaced.
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(true);
		$m->setReportOnly(false);
		$m->setReportingServiceId('my-reporter');
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$re = new THttpHeaderReportingEndpoints();
		$m->addHeader($re);
		$re->init([]);
		$re->addEndpoint('real-ep', 'https://my-own-collector.example.com/report');
		$re->addEndpoint('auto-ep', ''); // sentinel

		$m->publicFinalizeReporterService();

		self::assertSame(
			'https://my-own-collector.example.com/report',
			$re->getEndpointUrl('real-ep'),
			'Real endpoint URL must be preserved'
		);
		self::assertStringContainsString(
			'https://example.com/csp-report',
			$re->getEndpointUrl('auto-ep') ?? '',
			'Sentinel endpoint URL must be replaced'
		);
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testDefaultReporterServiceNameConstant()
	{
		self::assertSame('prado-csp-reporter', THttpHeadersManager::DEFAULT_REPORTING_SERVICE_NAME);
	}

	public function testDefaultReporterServicePropIdConstant()
	{
		self::assertSame('Auto', THttpHeadersManager::DEFAULT_REPORTING_SERVICE_ID);
	}

	public function testDefaultReportOnlyConstant()
	{
		self::assertNull(THttpHeadersManager::DEFAULT_REPORT_ONLY);
	}

	public function testDefaultReportingServiceModeConstant()
	{
		self::assertSame('Auto', THttpHeadersManager::DEFAULT_REPORTING_SERVICE_MODE);
	}

	// -----------------------------------------------------------------------
	// setReportOnlyDirect / getReportOnlyDirect — MT-1, MT-2
	// -----------------------------------------------------------------------

	public function testSetReportOnlyDirectStoresTrueValue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicSetReportOnlyDirect(true);
		self::assertTrue($m->publicGetReportOnlyDirect());
	}

	public function testSetReportOnlyDirectStoresFalseValue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicSetReportOnlyDirect(true);
		$m->publicSetReportOnlyDirect(false);
		self::assertFalse($m->publicGetReportOnlyDirect());
	}

	public function testSetReportOnlyDirectStoresNullRestoringAutoState(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicSetReportOnlyDirect(true);
		$m->publicSetReportOnlyDirect(null);
		self::assertNull($m->publicGetReportOnlyDirect(),
			'setReportOnlyDirect(null) must restore the Auto state');
	}

	public function testGetReportOnlyDirectMatchesGetReportOnly(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportOnly(true);
		self::assertSame($m->getReportOnly(), $m->publicGetReportOnlyDirect(),
			'getReportOnlyDirect() must return the same value as getReportOnly()');
	}

	// -----------------------------------------------------------------------
	// removeHeader by instance — not in list (MT-5)
	// -----------------------------------------------------------------------

	public function testRemoveHeaderByInstanceReturnsFalseWhenNotInList(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		// NOT added to $m.
		self::assertFalse($m->removeHeader($h),
			'removeHeader() must return false when the instance is not in the list');
	}

	// -----------------------------------------------------------------------
	// hasHeader / getHeadersByClass with parent class — MT-7, MT-8
	// -----------------------------------------------------------------------

	public function testHasHeaderByParentClassReturnsTrueForSubclassInstance(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$m->addHeader($h);
		self::assertTrue($m->hasHeader(THttpHeaderBase::class),
			'hasHeader(THttpHeaderBase::class) must return true when a subclass instance is present');
	}

	public function testGetHeadersByClassReturnsSubclassInstancesForParentClass(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new THttpHeader();
		$m->addHeader($h1);
		$m->addHeader($h2);
		$result = $m->getHeadersByClass(THttpHeaderBase::class);
		self::assertCount(2, $result,
			'getHeadersByClass(THttpHeaderBase::class) must return all headers (both are subclasses)');
	}

	// -----------------------------------------------------------------------
	// getHeadersByName — case-insensitive (MT-9)
	// -----------------------------------------------------------------------

	public function testGetHeadersByNameIsCaseInsensitive(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h = new TStubHeader();
		$h->name = 'X-Stub';
		$m->addHeader($h);
		$lower = $m->getHeadersByName('x-stub');
		$upper = $m->getHeadersByName('X-STUB');
		$mixed = $m->getHeadersByName('X-Stub');
		self::assertCount(1, $lower);
		self::assertCount(1, $upper);
		self::assertCount(1, $mixed);
		self::assertSame($h, $lower[0]);
	}

	// -----------------------------------------------------------------------
	// setHeadersDirect — MT-10
	// -----------------------------------------------------------------------

	public function testSetHeadersDirectReplacesBackingList(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h2 = new TStubHeader();
		$m->addHeader($h1);
		self::assertCount(1, $m->getHeaders(), 'pre-condition: one header added');
		$m->publicSetHeadersDirect([$h2]);
		$headers = $m->getHeaders();
		self::assertCount(1, $headers);
		self::assertSame($h2, $headers[0],
			'setHeadersDirect() must replace the entire backing list');
	}

	// -----------------------------------------------------------------------
	// ensureNameClassMap — idempotency, no double-merge (MT-11)
	// -----------------------------------------------------------------------

	public function testEnsureNameClassMapDoesNotDoubleMergeDefaultsOnRepeatedCalls(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->ensureNameClassMap();
		$mapAfterFirst = $m->getNameClassMap();
		$m->ensureNameClassMap();
		$mapAfterSecond = $m->getNameClassMap();
		self::assertSame($mapAfterFirst, $mapAfterSecond,
			'ensureNameClassMap() must be idempotent — calling it twice must not double the map');
	}

	// -----------------------------------------------------------------------
	// buildHeader — name not in map stays default class (MT-3, MT-4)
	// -----------------------------------------------------------------------

	public function testLoadHeadersDoesNotPromoteWhenNameNotInMap(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [['properties' => ['HeaderName' => 'X-Unknown-Header', 'HeaderValue' => 'v']]],
		]);
		$headers = $m->getHeaders();
		self::assertCount(1, $headers);
		self::assertInstanceOf(THttpHeader::class, $headers[0],
			'A header name absent from the map must stay as the default THttpHeader class');
	}

	public function testLoadHeadersWithNoHeaderNamePropertyDoesNotCrash(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->publicLoadHeaders([
			'headers' => [['class' => TStubHeader::class, 'properties' => []]],
		]);
		$headers = $m->getHeaders();
		self::assertCount(1, $headers);
		self::assertInstanceOf(TStubHeader::class, $headers[0]);
	}

	// -----------------------------------------------------------------------
	// removeHeader reindexes backing array (MT-23)
	// -----------------------------------------------------------------------

	public function testRemoveHeaderByNameReindexesBackingArray(): void
	{
		$m = new TTestableHttpHeadersManager();
		$h1 = new TStubHeader();
		$h1->name = 'X-One';
		$h2 = new TStubHeader();
		$h2->name = 'X-Two';
		$h3 = new TStubHeader();
		$h3->name = 'X-Three';
		$m->addHeader($h1);
		$m->addHeader($h2);
		$m->addHeader($h3);
		$m->removeHeader('X-Two');
		$headers = $m->getHeaders();
		self::assertCount(2, $headers);
		self::assertSame($h1, $headers[0]);
		self::assertSame($h3, $headers[1],
			'After removing the middle header the remaining items must have sequential zero-based keys');
		self::assertArrayNotHasKey(2, $headers);
	}

	// -----------------------------------------------------------------------
	// ensureHeadersSent sets HeadersSent to true (MT-15)
	// -----------------------------------------------------------------------

	public function testEnsureHeadersSentSetsHeadersSentToTrue(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		self::assertFalse($m->getHeadersSent(), 'pre-condition: not sent yet');
		$m->ensureHeadersSent();
		self::assertTrue($m->getHeadersSent(),
			'ensureHeadersSent() must set HeadersSent to true after the first call');
	}

	// -----------------------------------------------------------------------
	// finalizeHeaders ordering: reporter → per-header → validate (MT-14)
	// -----------------------------------------------------------------------

	public function testFinalizeHeadersCallsReporterServiceBeforePerHeaderFinalize(): void
	{
		$order = [];
		$m = new class ($order) extends TTestableHttpHeadersManager {
			public function __construct(public array &$log) { parent::__construct(); }
			protected function finalizeReporterService(): void { $this->log[] = 'reporter'; }
		};
		$h = new class ($order) extends TStubHeader {
			public function __construct(public array &$log) {}
			public function finalizeHeader(): void { $this->log[] = 'header'; }
		};
		$m->addHeader($h);
		$m->publicFinalizeHeaders();
		self::assertSame(['reporter', 'header'], array_slice($order, 0, 2),
			'finalizeReporterService() must run before per-header finalizeHeader()');
	}

	// -----------------------------------------------------------------------
	// validateHeaders calls both sub-validators (MT-16)
	// -----------------------------------------------------------------------

	public function testValidateHeadersCallsBothSubValidators(): void
	{
		$called = [];
		$m = new class ($called) extends TTestableHttpHeadersManager {
			public function __construct(public array &$log) { parent::__construct(); }
			protected function validateCoepCoopPair(): void { $this->log[] = 'coep'; }
			protected function validateFrameAncestorsXFrameOptions(): void { $this->log[] = 'frame'; }
		};
		$m->publicValidateHeaders();
		self::assertContains('coep', $called, 'validateCoepCoopPair() must be called by validateHeaders()');
		self::assertContains('frame', $called, 'validateFrameAncestorsXFrameOptions() must be called by validateHeaders()');
	}

	// -----------------------------------------------------------------------
	// IsHandled / HeadersSent independence (TQ-8)
	// -----------------------------------------------------------------------

	public function testHeadersSentDoesNotAffectIsHandledFlag(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setReportingServiceMode(false);
		$m->ensureHeadersSent();
		self::assertTrue($m->getHeadersSent(), 'pre-condition: headers sent');
		self::assertFalse($m->getIsHandled(),
			'getHeadersSent() being true must not affect getIsHandled()');
	}

	public function testIsHandledSuppressesHeadersSent(): void
	{
		$m = new TTestableHttpHeadersManager();
		$m->setIsHandled(true);
		$m->ensureHeadersSent();
		self::assertFalse($m->getHeadersSent(),
			'When IsHandled is true, ensureHeadersSent() must not send (HeadersSent stays false)');
		self::assertTrue($m->getIsHandled());
	}

	// -----------------------------------------------------------------------
	// finalizeReporterService — Auto mode with literal service ID (MT-20)
	// -----------------------------------------------------------------------

	public function testFinalizeReporterServiceAutoModeWithLiteralServiceIdUsesItDirectly(): void
	{
		// serviceId is already set to a literal (not 'Auto') — the safety-net
		// class-scan branch is skipped and the literal is forwarded to buildReporterUrl.
		$m = new TTestableHttpHeadersManager();
		// Leave mode as 'Auto' but set a concrete service ID.
		$m->setReportingServiceId('explicit-reporter');
		$m->fakeReporterUrl = 'https://example.com/csp-report';

		$csp = new THttpHeaderCsp();
		$m->addHeader($csp);
		$csp->init([]);
		$csp->addPolicy(TCspDirective::DefaultSrc, "'self'");

		$m->publicFinalizeReporterService();

		$reHeaders = $m->getHeadersByClass(THttpHeaderReportingEndpoints::class);
		self::assertCount(1, $reHeaders);
		self::assertStringContainsString('explicit-reporter', $reHeaders[0]->getHeaderValue(),
			'Literal service ID must flow through buildReporterUrl into the endpoint URL');
	}
}
