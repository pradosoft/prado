<?php

namespace Prado\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\THttpException;
use Prado\IService;
use Prado\Prado;
use Prado\TService;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Web\TTestCookieResponse;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpRequest;

/**
 * Minimal concrete service used as the primary subject under test.
 */
class TServiceTest_ServiceA extends TService
{
	public bool $ranInit = false;
	public bool $ranRun  = false;

	public function init($config): void
	{
		parent::init($config);
		$this->ranInit = true;
	}

	public function run(): void
	{
		$this->ranRun = true;
	}
}

/**
 * A second, sibling service — used to verify that getInstance() does not
 * return the wrong class.
 */
class TServiceTest_ServiceB extends TService
{
	public function run(): void {}
}

/**
 * Subclass of ServiceA — used to verify the `static` return-type behaviour.
 */
class TServiceTest_ServiceA_Sub extends TServiceTest_ServiceA {}

/**
 * Tests for {@see \Prado\TService}.
 */
class TServiceTest extends TestCase
{
	private ?IService $_savedService = null;
	private TServiceTest_ServiceA $service;
	private ?TTestApplication $_otherApp = null;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	protected function setUp(): void
	{
		$this->_savedService = Prado::getApplication()->getService();
		$this->service = new TServiceTest_ServiceA();
	}

	protected function tearDown(): void
	{
		// If a test constructed a TTestApplication, restore global state first so
		// that the service restore below operates on the correct singleton.
		$this->_otherApp?->restoreApplication();
		$this->_otherApp = null;
		Prado::getApplication()->setService($this->_savedService);
	}

	// =========================================================================
	// ID property
	// =========================================================================

	public function testGetIDIsNullByDefault(): void
	{
		$this->assertNull($this->service->getID());
	}

	public function testSetGetID(): void
	{
		$this->service->setID('myService');
		$this->assertEquals('myService', $this->service->getID());
	}

	public function testSetIDOverwrites(): void
	{
		$this->service->setID('first');
		$this->service->setID('second');
		$this->assertEquals('second', $this->service->getID());
	}

	// =========================================================================
	// Enabled property
	// =========================================================================

	public function testEnabledDefaultsToTrue(): void
	{
		$this->assertTrue($this->service->getEnabled());
	}

	public function testSetEnabledBool(): void
	{
		$this->service->setEnabled(false);
		$this->assertFalse($this->service->getEnabled());

		$this->service->setEnabled(true);
		$this->assertTrue($this->service->getEnabled());
	}

	public function testSetEnabledStringTrue(): void
	{
		$this->service->setEnabled('true');
		$this->assertTrue($this->service->getEnabled());

		$this->service->setEnabled('True');
		$this->assertTrue($this->service->getEnabled());
	}

	public function testSetEnabledStringFalse(): void
	{
		$this->service->setEnabled('false');
		$this->assertFalse($this->service->getEnabled());

		$this->service->setEnabled('False');
		$this->assertFalse($this->service->getEnabled());
	}

	public function testSetEnabledInt(): void
	{
		$this->service->setEnabled(0);
		$this->assertFalse($this->service->getEnabled());

		$this->service->setEnabled(1);
		$this->assertTrue($this->service->getEnabled());
	}

	// =========================================================================
	// init() and run()
	// =========================================================================

	public function testInitCallsDyInit(): void
	{
		$this->assertFalse($this->service->ranInit);
		$this->service->init(null);
		$this->assertTrue($this->service->ranInit);
	}

	public function testRunExecutes(): void
	{
		$this->assertFalse($this->service->ranRun);
		$this->service->run();
		$this->assertTrue($this->service->ranRun);
	}

	public function testBaseRunIsNoOp(): void
	{
		$service = new class () extends TService {};
		$this->assertNull($service->run());
	}

	// =========================================================================
	// getInstance() — core behaviour
	// =========================================================================

	public function testGetInstanceReturnsSelfWhenActive(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->service);

		$this->assertSame($this->service, TServiceTest_ServiceA::getInstance($app));
	}

	public function testGetInstanceReturnsNullWhenNoServiceSet(): void
	{
		$app = Prado::getApplication();
		$app->setService(null);

		$this->assertNull(TServiceTest_ServiceA::getInstance($app));
	}

	public function testGetInstanceReturnsNullForSiblingClass(): void
	{
		$app = Prado::getApplication();
		$app->setService(new TServiceTest_ServiceB());

		$this->assertNull(TServiceTest_ServiceA::getInstance($app));
	}

	public function testGetInstanceReturnsNullWhenCalledOnSiblingClass(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->service);   // ServiceA active

		$this->assertNull(TServiceTest_ServiceB::getInstance($app));
	}

	// =========================================================================
	// getInstance() — static scoping (the `static` return-type rule)
	// =========================================================================

	/**
	 * Calling TService::getInstance() matches ANY TService subclass because
	 * `instanceof static` evaluates to `instanceof TService` in that context.
	 */
	public function testGetInstanceOnBaseClassMatchesAnySubclass(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->service);

		$this->assertSame($this->service, TService::getInstance($app));
	}

	/**
	 * Calling the parent class method with a sub-subclass active: should match,
	 * because the sub-subclass is still `instanceof TServiceTest_ServiceA`.
	 */
	public function testGetInstanceMatchesSubclassInstance(): void
	{
		$sub = new TServiceTest_ServiceA_Sub();
		$app = Prado::getApplication();
		$app->setService($sub);

		$this->assertSame($sub, TServiceTest_ServiceA::getInstance($app));
		$this->assertSame($sub, TServiceTest_ServiceA_Sub::getInstance($app));
		// Sibling still returns null
		$this->assertNull(TServiceTest_ServiceB::getInstance($app));
	}

	/**
	 * Calling a sub-subclass method when only the parent is active: should NOT
	 * match because the active service is not an instance of the sub-subclass.
	 */
	public function testGetInstanceReturnsNullForParentWhenSubclassRequired(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->service);   // plain ServiceA, not Sub

		$this->assertNull(TServiceTest_ServiceA_Sub::getInstance($app));
	}

	// =========================================================================
	// getInstance() — default $app parameter
	// =========================================================================

	public function testGetInstanceUsesGlobalAppWhenArgIsNull(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->service);

		// explicit null is equivalent to omitting the argument
		$this->assertSame($this->service, TServiceTest_ServiceA::getInstance(null));
		$this->assertSame($this->service, TServiceTest_ServiceA::getInstance());
	}

	/**
	 * An explicit $app is used as-is; the global Prado::getApplication() is only
	 * consulted when $app is null/omitted.
	 */
	public function testGetInstanceExplicitAppDiffersFromGlobal(): void
	{
		$globalApp = Prado::getApplication();
		$globalApp->setService($this->service);         // ServiceA on the global app

		// TTestApplication snapshots global state, then becomes Prado::getApplication().
		$this->_otherApp = new TTestApplication();
		$serviceB = new TServiceTest_ServiceB();
		$this->_otherApp->setService($serviceB);        // ServiceB on the other app

		// getInstance() with no arg now uses the other app (current global).
		$this->assertNull(TServiceTest_ServiceA::getInstance());
		$this->assertSame($serviceB, TServiceTest_ServiceB::getInstance());

		// getInstance($globalApp) targets globalApp regardless of the current global.
		$this->assertSame($this->service, TServiceTest_ServiceA::getInstance($globalApp));
		$this->assertNull(TServiceTest_ServiceB::getInstance($globalApp));
	}

	// =========================================================================
	// CSRF validation
	// =========================================================================

	public function testEnableCsrfValidationDefaultsToFalse(): void
	{
		$this->assertFalse($this->service->getEnableCsrfValidation());
		$this->service->setEnableCsrfValidation('true');
		$this->assertTrue($this->service->getEnableCsrfValidation());
	}

	/**
	 * ensureCsrfToken() is a no-op while validation is off and for GET, HEAD, and
	 * OPTIONS; otherwise a request without the token is a 400 naming the service.
	 */
	public function testEnsureCsrfToken(): void
	{
		$app = Prado::getApplication();
		$obLevel = ob_get_level();
		$savedResponse = $app->getResponse();
		$savedMethod = $_SERVER['REQUEST_METHOD'] ?? null;
		$savedPost = $_POST;
		$app->setResponse(new TTestCookieResponse());
		unset($_POST[THttpRequest::CSRF_TOKEN_FIELD]);
		try {
			$service = $this->service;
			$service->setID('api');
			$ensure = fn () => PradoUnit::invoke($service, 'ensureCsrfToken');

			$_SERVER['REQUEST_METHOD'] = 'POST';
			$ensure();

			$service->setEnableCsrfValidation(true);
			foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
				$_SERVER['REQUEST_METHOD'] = $method;
				$ensure();
			}

			$_SERVER['REQUEST_METHOD'] = 'POST';
			try {
				$ensure();
				$this->fail('a POST without a token was accepted');
			} catch (THttpException $e) {
				$this->assertSame(400, $e->getStatusCode());
				$this->assertSame('service_csrf_token_invalid', $e->getErrorCode());
				$this->assertStringContainsString("'api'", $e->getMessage());
			}

			$_POST[THttpRequest::CSRF_TOKEN_FIELD] = $app->getRequest()->getCsrfToken();
			$ensure();
			$this->assertTrue(true);
		} finally {
			$_POST = $savedPost;
			$_SERVER['REQUEST_METHOD'] = $savedMethod;
			$app->setResponse($savedResponse);
			// the first getResponse() of the run bootstraps the response, which starts a buffer
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}
}
