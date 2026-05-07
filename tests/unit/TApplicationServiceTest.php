<?php

use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TService;

/**
 * Stub service classes used only within this test file.
 * They extend TService so is_a() checks work correctly.
 */
class TestBaseService extends TService {}
class TestChildService extends TestBaseService {}
class TestSiblingService extends TestBaseService {}
class TestUnrelatedService extends TService {}

/** Not a TService — used to exercise the invalid-class guard in startService(). */
class NotAService {}

/** A TService stub whose getEnabled() always returns false. */
class DisabledTestService extends TestBaseService
{
	public function getEnabled()
	{
		return false;
	}
}

/**
 * A TService stub that records init() calls and exposes a settable property
 * so startService()'s init-property application can be verified.
 */
class InitTrackingService extends TestBaseService
{
	public bool $initCalled = false;
	private string $_trackedProp = '';

	public function getTrackedProp(): string
	{
		return $this->_trackedProp;
	}

	public function setTrackedProp(string $value): void
	{
		$this->_trackedProp = $value;
	}

	public function init($config): void
	{
		parent::init($config);
		$this->initCalled = true;
	}
}

/**
 * Tests for TApplication's service API:
 *   - getPageServiceID() / setPageServiceID()
 *   - getService() / setService()
 *   - getServiceIdByClass()
 *   - getServiceIdsByClass()
 *   - startService()
 */
class TApplicationServiceTest extends PHPUnit\Framework\TestCase
{
	private TApplication $_app;
	private \ReflectionProperty $_servicesProp;
	private \ReflectionProperty $_serviceProp;
	private array $_originalServices;
	private mixed $_originalService;
	private string $_originalPageServiceID;

	protected function setUp(): void
	{
		$this->_app = Prado::getApplication();

		$this->_servicesProp = new \ReflectionProperty(TApplication::class, '_services');
		$this->_servicesProp->setAccessible(true);

		$this->_serviceProp = new \ReflectionProperty(TApplication::class, '_service');
		$this->_serviceProp->setAccessible(true);

		// Snapshot state so tearDown can restore it cleanly.
		$this->_originalServices     = $this->_servicesProp->getValue($this->_app) ?? [];
		$this->_originalService      = $this->_serviceProp->getValue($this->_app);
		$this->_originalPageServiceID = $this->_app->getPageServiceID();
	}

	protected function tearDown(): void
	{
		$this->_servicesProp->setValue($this->_app, $this->_originalServices);
		$this->_serviceProp->setValue($this->_app, $this->_originalService);
		$this->_app->setPageServiceID($this->_originalPageServiceID);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Replace $_services entirely with a controlled map for the duration of
	 * one test.  Each entry must be a three-element tuple matching the real
	 * storage format: [$class, $initProperties, $configElement].
	 */
	private function setServices(array $services): void
	{
		$this->_servicesProp->setValue($this->_app, $services);
	}

	// -----------------------------------------------------------------------
	// getPageServiceID / setPageServiceID
	// -----------------------------------------------------------------------

	public function testPageServiceID_defaultIsPageConstant(): void
	{
		$this->assertSame(TApplication::PAGE_SERVICE_ID, $this->_app->getPageServiceID());
		$this->assertSame('page', $this->_app->getPageServiceID());
	}

	public function testPageServiceID_setterChangesGetter(): void
	{
		$this->_app->setPageServiceID('mypage');
		$this->assertSame('mypage', $this->_app->getPageServiceID());
	}

	// -----------------------------------------------------------------------
	// getService / setService
	// -----------------------------------------------------------------------

	public function testGetService_initiallyNull(): void
	{
		// The bootstrap constructs TApplication but never runs it,
		// so no service is active at the start of the suite.
		$this->_serviceProp->setValue($this->_app, null);
		$this->assertNull($this->_app->getService());
	}

	public function testSetGetService_roundTrip(): void
	{
		$stub = new TestBaseService();
		$this->_app->setService($stub);
		$this->assertSame($stub, $this->_app->getService());
	}

	// -----------------------------------------------------------------------
	// getServiceIdByClass — singular
	// -----------------------------------------------------------------------

	public function testGetServiceIdByClass_returnsNullWhenNoServices(): void
	{
		$this->setServices([]);
		$this->assertNull($this->_app->getServiceIdByClass(TestBaseService::class));
	}

	public function testGetServiceIdByClass_returnsNullWhenNoMatch(): void
	{
		$this->setServices([
			'unrelated' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertNull($this->_app->getServiceIdByClass(TestBaseService::class));
	}

	public function testGetServiceIdByClass_exactMatch(): void
	{
		$this->setServices([
			'base' => [TestBaseService::class, [], null],
		]);
		$this->assertSame('base', $this->_app->getServiceIdByClass(TestBaseService::class));
	}

	public function testGetServiceIdByClass_subclassMatch(): void
	{
		$this->setServices([
			'child' => [TestChildService::class, [], null],
		]);
		// TestChildService extends TestBaseService — should match by inheritance.
		$this->assertSame('child', $this->_app->getServiceIdByClass(TestBaseService::class));
	}

	public function testGetServiceIdByClass_returnsFirstMatch(): void
	{
		$this->setServices([
			'first'  => [TestBaseService::class, [], null],
			'second' => [TestBaseService::class, [], null],
		]);
		$this->assertSame('first', $this->_app->getServiceIdByClass(TestBaseService::class));
	}

	public function testGetServiceIdByClass_pageServiceRegisteredByDefault(): void
	{
		$id = $this->_app->getServiceIdByClass(\Prado\Web\Services\TPageService::class);
		$this->assertNotNull($id);
	}

	// -----------------------------------------------------------------------
	// getServiceIdsByClass — plural
	// -----------------------------------------------------------------------

	public function testGetServiceIdsByClass_returnsEmptyArrayWhenNoServices(): void
	{
		$this->setServices([]);
		$this->assertSame([], $this->_app->getServiceIdsByClass(TestBaseService::class));
	}

	public function testGetServiceIdsByClass_returnsEmptyArrayWhenNoMatch(): void
	{
		$this->setServices([
			'unrelated' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertSame([], $this->_app->getServiceIdsByClass(TestBaseService::class));
	}

	public function testGetServiceIdsByClass_singleExactMatch(): void
	{
		$this->setServices([
			'base' => [TestBaseService::class, [], null],
		]);
		$this->assertSame(['base'], $this->_app->getServiceIdsByClass(TestBaseService::class));
	}

	public function testGetServiceIdsByClass_multipleExactMatches(): void
	{
		$this->setServices([
			'svc1'  => [TestBaseService::class, [], null],
			'svc2'  => [TestBaseService::class, [], null],
			'other' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertSame(['svc1', 'svc2'], $this->_app->getServiceIdsByClass(TestBaseService::class));
	}

	public function testGetServiceIdsByClass_subclassesIncludedByDefault(): void
	{
		$this->setServices([
			'base'    => [TestBaseService::class, [], null],
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
			'other'   => [TestUnrelatedService::class, [], null],
		]);
		$result = $this->_app->getServiceIdsByClass(TestBaseService::class);
		$this->assertSame(['base', 'child', 'sibling'], $result);
	}

	public function testGetServiceIdsByClass_strictExcludesSubclasses(): void
	{
		$this->setServices([
			'base'    => [TestBaseService::class, [], null],
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
		]);
		$result = $this->_app->getServiceIdsByClass(TestBaseService::class, strict: true);
		$this->assertSame(['base'], $result);
	}

	public function testGetServiceIdsByClass_strictMatchesExactClassOnly(): void
	{
		$this->setServices([
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
		]);
		// No entry is exactly TestBaseService, so strict returns empty.
		$result = $this->_app->getServiceIdsByClass(TestBaseService::class, strict: true);
		$this->assertSame([], $result);
	}

	public function testGetServiceIdsByClass_strictFalseMatchesSubclasses(): void
	{
		$this->setServices([
			'child' => [TestChildService::class, [], null],
		]);
		$result = $this->_app->getServiceIdsByClass(TestBaseService::class, strict: false);
		$this->assertSame(['child'], $result);
	}

	public function testGetServiceIdsByClass_preservesRegistrationOrder(): void
	{
		$this->setServices([
			'z_svc' => [TestBaseService::class, [], null],
			'a_svc' => [TestBaseService::class, [], null],
			'm_svc' => [TestBaseService::class, [], null],
		]);
		// IDs must appear in the same order they were registered.
		$this->assertSame(['z_svc', 'a_svc', 'm_svc'], $this->_app->getServiceIdsByClass(TestBaseService::class));
	}

	public function testGetServiceIdsByClass_pageServiceRegisteredByDefault(): void
	{
		$ids = $this->_app->getServiceIdsByClass(\Prado\Web\Services\TPageService::class);
		$this->assertNotEmpty($ids);
		$this->assertContains($this->_app->getPageServiceID(), $ids);
	}

	public function testGetServiceIdsByClass_returnsStringIds(): void
	{
		// PHP array keys that look like integers are stored as ints;
		// the method must still cast them to strings.
		$this->setServices([
			0 => [TestBaseService::class, [], null],
			1 => [TestBaseService::class, [], null],
		]);
		$result = $this->_app->getServiceIdsByClass(TestBaseService::class);
		$this->assertSame(['0', '1'], $result);
		foreach ($result as $id) {
			$this->assertIsString($id);
		}
	}

	// -----------------------------------------------------------------------
	// onConfigurationComplete
	// -----------------------------------------------------------------------

	public function testOnConfigurationComplete_eventIsRaiseable(): void
	{
		$called = false;
		$handler = function () use (&$called) {
			$called = true;
		};
		$this->_app->attachEventHandler('onConfigurationComplete', $handler);

		$this->_app->onConfigurationComplete();

		$this->_app->detachEventHandler('onConfigurationComplete', $handler);
		$this->assertTrue($called);
	}

	public function testOnConfigurationComplete_handlerReceivesApplication(): void
	{
		$receivedSender = null;
		$receivedParam = 'not-null';
		$handler = function ($sender, $param) use (&$receivedSender, &$receivedParam) {
			$receivedSender = $sender;
			$receivedParam = $param;
		};
		$this->_app->attachEventHandler('onConfigurationComplete', $handler);

		$this->_app->onConfigurationComplete();

		$this->_app->detachEventHandler('onConfigurationComplete', $handler);
		$this->assertSame($this->_app, $receivedSender);
		$this->assertNull($receivedParam);
	}

	public function testOnConfigurationComplete_serviceRegisteredInHandlerIsVisibleToGetServiceIds(): void
	{
		// Simulate what a module would do: register a new service inside the
		// onConfigurationComplete handler, then verify it is discoverable via
		// getServiceIdsByClass() after the event fires.
		$app = $this->_app;
		$handler = function () use ($app) {
			$prop = new \ReflectionProperty(TApplication::class, '_services');
			$prop->setAccessible(true);
			$services = $prop->getValue($app);
			$services['late_svc'] = [InitTrackingService::class, [], null];
			$prop->setValue($app, $services);
		};
		$app->attachEventHandler('onConfigurationComplete', $handler);

		$app->onConfigurationComplete();

		$app->detachEventHandler('onConfigurationComplete', $handler);
		$this->assertContains('late_svc', $app->getServiceIdsByClass(InitTrackingService::class));
	}

	// -----------------------------------------------------------------------
	// startService
	// -----------------------------------------------------------------------

	public function testStartService_unknownIdThrows(): void
	{
		$this->setServices([]);
		$this->expectException(THttpException::class);
		$this->_app->startService('nonexistent');
	}

	public function testStartService_nonServiceClassThrows(): void
	{
		// NotAService does not extend TService — startService must reject it.
		$this->setServices([
			'bad' => [NotAService::class, [], null],
		]);
		$this->expectException(THttpException::class);
		$this->_app->startService('bad');
	}

	public function testStartService_disabledServiceThrows(): void
	{
		$this->setServices([
			'disabled' => [DisabledTestService::class, [], null],
		]);
		$this->expectException(THttpException::class);
		$this->_app->startService('disabled');
	}

	public function testStartService_setsCurrentService(): void
	{
		$this->setServices([
			'tracker' => [InitTrackingService::class, [], null],
		]);
		$this->_app->startService('tracker');
		$this->assertInstanceOf(InitTrackingService::class, $this->_app->getService());
	}

	public function testStartService_setsServiceId(): void
	{
		$this->setServices([
			'tracker' => [InitTrackingService::class, [], null],
		]);
		$this->_app->startService('tracker');
		$this->assertSame('tracker', $this->_app->getService()->getID());
	}

	public function testStartService_appliesInitProperties(): void
	{
		$this->setServices([
			'tracker' => [InitTrackingService::class, ['TrackedProp' => 'hello'], null],
		]);
		$this->_app->startService('tracker');
		/** @var InitTrackingService $service */
		$service = $this->_app->getService();
		$this->assertSame('hello', $service->getTrackedProp());
	}

	public function testStartService_callsInit(): void
	{
		$this->setServices([
			'tracker' => [InitTrackingService::class, [], null],
		]);
		$this->_app->startService('tracker');
		/** @var InitTrackingService $service */
		$service = $this->_app->getService();
		$this->assertTrue($service->initCalled);
	}
}
