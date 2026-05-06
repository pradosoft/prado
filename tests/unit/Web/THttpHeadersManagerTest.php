<?php

use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\Web\THttpHeader;
use Prado\Web\THttpHeaderCSP;
use Prado\Web\THttpHeadersManager;
use Prado\Web\THttpResponse;
use Prado\Web\Javascripts\TJavaScript;

/**
 * THttpHeadersManager subclass that intercepts sendHeaders() so tests
 * can assert which headers were emitted without needing a live HTTP response.
 */
class TTestableHttpHeadersManager extends THttpHeadersManager
{
	/** @var string[] headers captured by the overridden sendHeaders() */
	public array $sentHeaders = [];

	/** @var int number of times sendHeaders() was called */
	public int $sendCount = 0;

	protected function sendHeaders(): void
	{
		$this->sendCount++;
		foreach ($this->_headers as $header) {
			$this->sentHeaders[] = (string) $header;
		}
		// Mirror what the real sendHeaders() does: mark as sent so
		// ensureHeadersSent() won't call us again.
		$ref = new ReflectionProperty(THttpHeadersManager::class, '_headersSent');
		$ref->setAccessible(true);
		$ref->setValue($this, true);
	}

	/** Expose loadHeaders() so tests can configure headers without a full init(). */
	public function publicLoadHeaders(mixed $config): void
	{
		$this->loadHeaders($config);
	}
}

class THttpHeadersManagerTest extends PHPUnit\Framework\TestCase
{
	public static ?TApplication $app = null;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../Security/app');
		}
		TJavaScript::setScriptNonce(null);
	}

	protected function tearDown(): void
	{
		// THttpHeaderCSP::init() with a NONCE policy calls TJavaScript::setScriptNonce().
		// Reset after every test to prevent nonce pollution across unrelated tests.
		TJavaScript::setScriptNonce(null);
	}

	// -----------------------------------------------------------------------
	// getDefaultMappingClass
	// -----------------------------------------------------------------------

	public function testGetDefaultMappingClass()
	{
		$manager = new TTestableHttpHeadersManager();
		self::assertEquals(THttpHeader::class, $manager->getDefaultMappingClass());
	}

	// -----------------------------------------------------------------------
	// loadHeaders — array config
	// -----------------------------------------------------------------------

	public function testLoadHeadersFromArrayConfig()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'X-Frame-Options', 'Value' => 'DENY']],
				['properties' => ['Name' => 'X-Content-Type-Options', 'Value' => 'nosniff']],
			],
		]);

		$manager->ensureHeadersSent();
		self::assertCount(2, $manager->sentHeaders);
		self::assertContains('X-Frame-Options: DENY', $manager->sentHeaders);
		self::assertContains('X-Content-Type-Options: nosniff', $manager->sentHeaders);
	}

	public function testLoadHeadersFromArrayConfigWithCustomClass()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'    => THttpHeaderCSP::class,
					'policies' => [
						['name' => 'default-src', 'value' => "'self'"],
					],
				],
			],
		]);

		$manager->ensureHeadersSent();
		self::assertCount(1, $manager->sentHeaders);
		self::assertStringContainsString('Content-Security-Policy', $manager->sentHeaders[0]);
	}

	public function testLoadHeadersEmptyArrayConfigProducesNoHeaders()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([]);

		$manager->ensureHeadersSent();
		self::assertCount(0, $manager->sentHeaders);
	}

	public function testLoadHeadersInvalidClassThrows()
	{
		$manager = new TTestableHttpHeadersManager();
		$this->expectException(TConfigurationException::class);
		$manager->publicLoadHeaders([
			'headers' => [
				['class' => \stdClass::class],
			],
		]);
	}

	// -----------------------------------------------------------------------
	// ensureHeadersSent — idempotency
	// -----------------------------------------------------------------------

	public function testEnsureHeadersSentOnlyCallsSendOnce()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'X-Test', 'Value' => '1']],
			],
		]);

		$manager->ensureHeadersSent();
		$manager->ensureHeadersSent(); // second call must be a no-op
		$manager->ensureHeadersSent(); // third call must be a no-op

		self::assertEquals(1, $manager->sendCount);
		self::assertCount(1, $manager->sentHeaders);
	}

	public function testEnsureHeadersSentWithNoHeaders()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->ensureHeadersSent();
		self::assertEquals(1, $manager->sendCount);
		self::assertCount(0, $manager->sentHeaders);
	}

	// -----------------------------------------------------------------------
	// sendHeaders — __toString delegation
	// -----------------------------------------------------------------------

	public function testSendHeadersUsesHeaderToString()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'Strict-Transport-Security', 'Value' => 'max-age=31536000']],
			],
		]);

		$manager->ensureHeadersSent();
		self::assertEquals(['Strict-Transport-Security: max-age=31536000'], $manager->sentHeaders);
	}

	public function testSendHeadersPreservesOrder()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'A', 'Value' => '1']],
				['properties' => ['Name' => 'B', 'Value' => '2']],
				['properties' => ['Name' => 'C', 'Value' => '3']],
			],
		]);

		$manager->ensureHeadersSent();
		self::assertEquals(['A: 1', 'B: 2', 'C: 3'], $manager->sentHeaders);
	}

	// -----------------------------------------------------------------------
	// getNonce
	// -----------------------------------------------------------------------

	public function testGetNonceReturnsNullWhenNoCspHeader()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'X-Frame-Options', 'Value' => 'DENY']],
			],
		]);

		self::assertNull($manager->getNonce());
	}

	public function testGetNonceReturnsNullWhenCspHasNoNoncePlaceholder()
	{
		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'    => THttpHeaderCSP::class,
					'policies' => [
						['name' => 'default-src', 'value' => "'self'"],
					],
				],
			],
		]);

		self::assertNull($manager->getNonce());
	}

	public function testGetNonceReturnsNonceWhenCspHasNoncePlaceholder()
	{
		// Requires a live security manager to generate the nonce
		$sm = new \Prado\Security\TSecurityManager();
		$sm->init(null);

		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				[
					'class'    => THttpHeaderCSP::class,
					'policies' => [
						['name' => 'default-src', 'value' => "'self' NONCE"],
					],
				],
			],
		]);

		$nonce = $manager->getNonce();
		self::assertNotNull($nonce);
		self::assertIsString($nonce);
		self::assertNotEmpty($nonce);
	}

	public function testGetNonceReturnsFirstCspNonce()
	{
		$sm = new \Prado\Security\TSecurityManager();
		$sm->init(null);

		$manager = new TTestableHttpHeadersManager();
		$manager->publicLoadHeaders([
			'headers' => [
				['properties' => ['Name' => 'X-Frame-Options', 'Value' => 'DENY']],
				[
					'class'    => THttpHeaderCSP::class,
					'policies' => [
						['name' => 'script-src', 'value' => "'self' NONCE"],
					],
				],
			],
		]);

		// The first header is a plain THttpHeader (no nonce); getNonce() must
		// look past it and find the CSP header's nonce.
		$nonce = $manager->getNonce();
		self::assertNotNull($nonce);
	}

	public function testGetNonceWithNoCspHeadersAtAllReturnsNull()
	{
		$manager = new TTestableHttpHeadersManager();
		self::assertNull($manager->getNonce());
	}
}
