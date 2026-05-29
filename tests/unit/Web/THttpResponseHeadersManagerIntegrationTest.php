<?php

/**
 * THttpResponseHeadersManagerIntegrationTest
 *
 * Integration tests verifying that {@see \Prado\Web\THttpResponse} correctly
 * delegates header sending to a registered {@see \Prado\Web\HttpHeaders\THttpHeadersManager}
 * when one is wired up via {@see THttpResponse::setHeadersManager()}.
 */

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\HttpHeaders\THttpHeader;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\THttpResponse;

// Load the shared test double if it has not been loaded yet.
if (!class_exists('TTestableHttpHeadersManager', false)) {
	require_once __DIR__ . '/HttpHeaders/TTestableHttpHeadersManager.php';
}

/**
 * Integration tests for the THttpResponse ↔ THttpHeadersManager wiring.
 *
 * Each test is self-contained: any state written to THttpResponse or TApplication
 * is restored in a `finally` block so that test-ordering does not matter.
 */
class THttpResponseHeadersManagerIntegrationTest extends PHPUnit\Framework\TestCase
{
	private static TApplication $app;

	// =========================================================================
	// Bootstrap
	// =========================================================================

	public static function setUpBeforeClass(): void
	{
		self::$app = Prado::getApplication();
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Registers a {@see TTestableHttpHeadersManager} instance with the
	 * application under the supplied module ID so that
	 * {@see TApplication::getModule()} can resolve it.
	 * @param TTestableHttpHeadersManager $manager manager instance to register.
	 * @param string $id module ID to register under.
	 */
	private function registerManager(TTestableHttpHeadersManager $manager, string $id): void
	{
		self::$app->setModule($id, $manager);
	}

	/**
	 * Removes a previously registered module from the application by setting
	 * its slot to `null` (the lazy-load sentinel).
	 * @param string $id module ID to unregister.
	 */
	private function unregisterModule(string $id): void
	{
		// Reflection is required because setModule() accepts a nullable IModule,
		// but internally we want to remove the key entirely to avoid a stale
		// null entry that getModule() would treat as "load lazily".
		$ref = new \ReflectionProperty(TApplication::class, '_modules');
		$ref->setAccessible(true);
		$modules = $ref->getValue(self::$app);
		unset($modules[$id]);
		$ref->setValue(self::$app, $modules);
	}

	/**
	 * Returns the live {@see THttpResponse} from the application.
	 * @return THttpResponse
	 */
	private function freshResponse(): THttpResponse
	{
		return self::$app->getResponse();
	}

	/**
	 * Clears the cached `_headersManager` reference on a THttpResponse so that
	 * the next call to {@see THttpResponse::getHeadersManagerModule()} performs
	 * a fresh lookup from the application's module registry.
	 * @param THttpResponse $response response object to clear the cache on.
	 */
	private function clearManagerCache(THttpResponse $response): void
	{
		$ref = new \ReflectionProperty(THttpResponse::class, '_headersManager');
		$ref->setAccessible(true);
		$ref->setValue($response, null);
	}

	// =========================================================================
	// Tests — property getters / setters
	// =========================================================================

	/**
	 * The HeadersManager property defaults to an empty string when no manager
	 * has been configured.
	 */
	public function testGetHeadersManagerDefaultIsEmptyString(): void
	{
		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			// Ensure we start from the default (empty) state.
			$response->setHeadersManager('');
			$this->assertSame('', $response->getHeadersManager());
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
		}
	}

	/**
	 * Setting a module ID via {@see THttpResponse::setHeadersManager()} is
	 * reflected by {@see THttpResponse::getHeadersManager()}.
	 */
	public function testSetGetHeadersManagerId(): void
	{
		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager('my-id');
			$this->assertSame('my-id', $response->getHeadersManager());
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
		}
	}

	// =========================================================================
	// Tests — getHeadersManagerModule()
	// =========================================================================

	/**
	 * When the HeadersManager ID is empty, {@see getHeadersManagerModule()}
	 * returns null without throwing.
	 */
	public function testGetHeadersManagerModuleReturnsNullForEmptyId(): void
	{
		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager('');
			$this->clearManagerCache($response);
			$this->assertNull($response->getHeadersManagerModule());
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
		}
	}

	/**
	 * When the HeadersManager ID references a module that does not exist in the
	 * application registry, {@see getHeadersManagerModule()} throws a
	 * {@see TConfigurationException}.
	 */
	public function testGetHeadersManagerModuleThrowsForMissingModule(): void
	{
		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager('nonexistent-xxxxxx');
			$this->clearManagerCache($response);
			$this->expectException(TConfigurationException::class);
			$response->getHeadersManagerModule();
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
		}
	}

	/**
	 * When the registered module is not a {@see THttpHeadersManager} instance,
	 * {@see getHeadersManagerModule()} throws a {@see TConfigurationException}.
	 */
	public function testGetHeadersManagerModuleThrowsForWrongType(): void
	{
		// Register a plain THttpResponse (not a THttpHeadersManager) under a test ID.
		$wrongModule = new THttpResponse();
		$moduleId = 'wrong-type-module-xxxx';
		self::$app->setModule($moduleId, $wrongModule);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$this->expectException(TConfigurationException::class);
			$response->getHeadersManagerModule();
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	/**
	 * When a {@see TTestableHttpHeadersManager} is registered under the
	 * configured ID, {@see getHeadersManagerModule()} returns that exact instance.
	 */
	public function testGetHeadersManagerModuleReturnsRegisteredManager(): void
	{
		$moduleId = 'test-hm-001';
		$manager = new TTestableHttpHeadersManager();
		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$resolved = $response->getHeadersManagerModule();
			$this->assertSame($manager, $resolved);
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	// =========================================================================
	// Tests — ensureHeadersSent() delegation
	// =========================================================================

	/**
	 * {@see THttpResponse::ensureHeadersSent()} calls the manager's
	 * {@see THttpHeadersManager::ensureHeadersSent()} exactly once.
	 */
	public function testEnsureHeadersSentCallsManagerEnsureHeadersSent(): void
	{
		$moduleId = 'test-hm-send-001';
		$manager = new TTestableHttpHeadersManager();
		$manager->setReportingServiceMode(false);
		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();
			$this->assertSame(1, $manager->sendCount);
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	/**
	 * A second call to {@see THttpResponse::ensureHeadersSent()} is a no-op —
	 * the manager records a send count of 1, not 2.
	 */
	public function testManagerSendIsIdempotent(): void
	{
		$moduleId = 'test-hm-idempotent-001';
		$manager = new TTestableHttpHeadersManager();
		$manager->setReportingServiceMode(false);
		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();
			// The manager guards internally against double-send.
			$this->assertSame(1, $manager->sendCount);
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	/**
	 * Headers added to the manager before {@see ensureHeadersSent()} are captured
	 * in the manager's {@see TTestableHttpHeadersManager::$sentHeaders} array.
	 */
	public function testManagerHeadersAreCapturedAfterEnsureHeadersSent(): void
	{
		$moduleId = 'test-hm-capture-001';
		$manager = new TTestableHttpHeadersManager();
		$manager->setReportingServiceMode(false);

		$header = new THttpHeader();
		$header->setHeaderName('X-Test');
		$header->setHeaderValue('integration');
		$manager->addHeader($header);

		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();

			$found = false;
			foreach ($manager->sentHeaders as $sent) {
				if (str_contains($sent, 'X-Test') && str_contains($sent, 'integration')) {
					$found = true;
					break;
				}
			}
			$this->assertTrue($found, 'X-Test: integration header was not captured in sentHeaders.');
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	/**
	 * When the HeadersManager ID is empty, {@see ensureHeadersSent()} skips the
	 * manager entirely — a separately registered manager's send count remains 0.
	 */
	public function testEmptyHeadersManagerIdSkipsManagerCall(): void
	{
		$moduleId = 'test-hm-skip-001';
		$manager = new TTestableHttpHeadersManager();
		$manager->setReportingServiceMode(false);
		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			// Explicitly clear the manager ID so no manager is invoked.
			$response->setHeadersManager('');
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();
			$this->assertSame(0, $manager->sendCount);
			$this->assertNull($response->getHeadersManagerModule());
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}

	/**
	 * When {@see THttpHeadersManager::setIsHandled()} is `true`, the manager
	 * treats {@see ensureHeadersSent()} as a no-op and the send count stays 0.
	 */
	public function testManagerIsHandledPreventsManagerSendViaResponse(): void
	{
		$moduleId = 'test-hm-handled-001';
		$manager = new TTestableHttpHeadersManager();
		$manager->setReportingServiceMode(false);
		$manager->setIsHandled(true);
		$this->registerManager($manager, $moduleId);

		$response = $this->freshResponse();
		$original = $response->getHeadersManager();
		try {
			$response->setHeadersManager($moduleId);
			$this->clearManagerCache($response);
			$response->ensureHeadersSent();
			$this->assertSame(0, $manager->sendCount);
		} finally {
			$response->setHeadersManager($original);
			$this->clearManagerCache($response);
			$this->unregisterModule($moduleId);
		}
	}
}
