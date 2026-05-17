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
 * Tests for TApplication's service registry API:
 *   - getPageServiceID() / setPageServiceID()
 *   - getService() / setService()
 *   - registerService()
 *   - hasRegisteredService()
 *   - getRegisteredService()
 *   - getRegisteredServices()
 *   - getRegisteredServiceByClass()
 *   - getRegisteredServicesByClass()
 *   - startService()
 *   - onConfiguration()
 *   - onInitComplete()
 *
 * @package System
 */
class TApplicationServiceTest extends PHPUnit\Framework\TestCase
{
	private TApplication $_app;
	private array $_snap = [];

	protected function setUp(): void
	{
		$this->_app  = Prado::getApplication();
		$this->_snap = TTestApplication::snapshotApp($this->_app);
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_snap, $this->_app);
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
		PradoUnit::setProp($this->_app, '_services', $services);
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

	public function testPageServiceID_setterAcceptsArbitraryString(): void
	{
		$this->_app->setPageServiceID('custom-service-id');
		$this->assertSame('custom-service-id', $this->_app->getPageServiceID());
	}

	public function testPageServiceID_setterMovesRegistryEntryToNewKey(): void
	{
		// old exists, new does not → rename fires.
		$oldId = $this->_app->getPageServiceID();
		$this->setServices([$oldId => [InitTrackingService::class, [], null]]);

		$this->_app->setPageServiceID('newpage');

		$this->assertSame('newpage', $this->_app->getPageServiceID());
		$this->assertFalse($this->_app->hasRegisteredService($oldId));
		$this->assertTrue($this->_app->hasRegisteredService('newpage'));
	}

	public function testPageServiceID_registryEntryPreservedAfterRename(): void
	{
		$oldId = $this->_app->getPageServiceID();
		$entry = [InitTrackingService::class, [], null];
		$this->setServices([$oldId => $entry]);

		$this->_app->setPageServiceID('newpage');

		$this->assertSame($entry, $this->_app->getRegisteredService('newpage'));
	}

	public function testPageServiceID_setterAlwaysUpdatesPageServiceID(): void
	{
		// _pageServiceID is updated even when the registry rename is suppressed.
		$this->setServices([]);
		$this->_app->setPageServiceID('explicit');
		$this->assertSame('explicit', $this->_app->getPageServiceID());
	}

	public function testPageServiceID_setterDoesNotOverwriteExistingTarget(): void
	{
		// New ID already occupied → rename suppressed; both registry entries intact.
		$oldId = $this->_app->getPageServiceID();
		$this->setServices([
			$oldId    => [InitTrackingService::class, [], null],
			'newpage' => [TestBaseService::class, [], null],
		]);

		$this->_app->setPageServiceID('newpage');

		$this->assertSame('newpage', $this->_app->getPageServiceID());
		$this->assertTrue($this->_app->hasRegisteredService($oldId),   'old key must survive');
		$this->assertSame(TestBaseService::class, $this->_app->getRegisteredService('newpage')[0], 'target must not be overwritten');
	}

	public function testPageServiceID_setterNoOpRegistryWhenSameValue(): void
	{
		$id     = $this->_app->getPageServiceID();
		$before = $this->_app->getRegisteredServices();

		$this->_app->setPageServiceID($id);

		$this->assertSame($before, $this->_app->getRegisteredServices());
	}

	public function testPageServiceID_setterWithNoRegistryEntry(): void
	{
		// Old ID not in registry → no spurious entry created under new ID.
		$this->setServices([]);

		$this->_app->setPageServiceID('other');

		$this->assertSame('other', $this->_app->getPageServiceID());
		$this->assertFalse($this->_app->hasRegisteredService('other'));
	}

	// -----------------------------------------------------------------------
	// getService / setService
	// -----------------------------------------------------------------------

	public function testGetService_initiallyNull(): void
	{
		// The bootstrap constructs TApplication but never runs it,
		// so no service is active at the start of the suite.
		PradoUnit::setProp($this->_app, '_service', null);
		$this->assertNull($this->_app->getService());
	}

	public function testSetGetService_roundTrip(): void
	{
		$stub = new TestBaseService();
		$this->_app->setService($stub);
		$this->assertSame($stub, $this->_app->getService());
	}

	public function testSetService_replacesExistingService(): void
	{
		$first  = new TestBaseService();
		$second = new TestBaseService();
		$this->_app->setService($first);
		$this->_app->setService($second);
		$this->assertSame($second, $this->_app->getService());
	}

	// -----------------------------------------------------------------------
	// registerService
	// -----------------------------------------------------------------------

	public function testRegisterService_byClassRegistersService(): void
	{
		$this->_app->registerService('reg_svc', InitTrackingService::class);
		$this->assertTrue($this->_app->hasRegisteredService('reg_svc'));
	}

	// -----------------------------------------------------------------------
	// registerService — Prado3 dot-notation class name resolution
	//
	// Legacy application.xml files use dot-notation class names such as
	// "System.Web.Services.TSoapService". Since PR #1105 introduced
	// registerService(), passing such a name directly causes a
	// TConfigurationException because class_exists('System...', true) returns
	// false even after Prado::using() loads the underlying PHP class — no
	// alias is created for the dot-notation form.
	//
	// Short classMap names (e.g. 'TJsonService') already work because
	// Prado::using() creates a class_alias for them; that behaviour is
	// documented here as a regression guard.
	// -----------------------------------------------------------------------

	public function testRegisterService_withPrado3SystemDotNotationSucceeds(): void
	{
		// 'System.Web.Services.TJsonService' is the Prado3 form of
		// Prado\Web\Services\TJsonService. registerService() must resolve it
		// via Prado::using() rather than calling class_exists() on the raw string.
		$this->_app->registerService('prado3_sys', 'System.Web.Services.TJsonService');
		$this->assertTrue($this->_app->hasRegisteredService('prado3_sys'));
	}

	public function testRegisterService_withPrado3PradoDotNotationSucceeds(): void
	{
		// 'Prado.Web.Services.TJsonService' uses dots in place of backslashes —
		// another valid Prado3 form that must be resolved before class_exists().
		$this->_app->registerService('prado3_dot', 'Prado.Web.Services.TJsonService');
		$this->assertTrue($this->_app->hasRegisteredService('prado3_dot'));
	}

	public function testRegisterService_withClassMapShortNameSucceeds(): void
	{
		// Short classMap names (e.g. 'TJsonService') are already handled by the
		// PHP autoloader via Prado::using() → class_alias(). This test documents
		// that the current behaviour survives the fix.
		$this->_app->registerService('prado3_short', 'TJsonService');
		$this->assertTrue($this->_app->hasRegisteredService('prado3_short'));
	}

	public function testRegisterService_prado3SystemDotNotation_storesResolvableClass(): void
	{
		// The class stored in the registry must be recognisable as an IService
		// implementor so that getRegisteredServiceByClass() / startService() work.
		$this->_app->registerService('prado3_sys', 'System.Web.Services.TJsonService');
		$entry = $this->_app->getRegisteredService('prado3_sys');
		$storedClass = $entry[0];
		$this->assertTrue(
			is_a($storedClass, \Prado\IService::class, true),
			"Stored class '{$storedClass}' must implement IService"
		);
	}

	public function testRegisterService_prado3PradoDotNotation_storesResolvableClass(): void
	{
		$this->_app->registerService('prado3_dot', 'Prado.Web.Services.TJsonService');
		$entry = $this->_app->getRegisteredService('prado3_dot');
		$storedClass = $entry[0];
		$this->assertTrue(
			is_a($storedClass, \Prado\IService::class, true),
			"Stored class '{$storedClass}' must implement IService"
		);
	}

	public function testRegisterService_prado3SystemDotNotation_storesResolvableClassProperties(): void
	{
		// Properties and config element must be preserved alongside the resolved class.
		$props = ['SomeProperty' => 'value'];
		$this->_app->registerService('prado3_props', 'System.Web.Services.TJsonService', $props);
		$entry = $this->_app->getRegisteredService('prado3_props');
		$this->assertSame($props, $entry[1]);
		$this->assertNull($entry[2]);
	}

	public function testGetRegisteredServiceByClass_withPrado3SystemDotNotationRegistration(): void
	{
		// A service registered with a Prado3 name must be discoverable by its
		// canonical PHP class name via getRegisteredServiceByClass().
		$this->_app->registerService('prado3_lookup', 'System.Web.Services.TJsonService');
		$id = $this->_app->getRegisteredServiceByClass(\Prado\Web\Services\TJsonService::class);
		$this->assertSame('prado3_lookup', $id);
	}

	public function testGetRegisteredServicesByClass_withPrado3SystemDotNotationRegistration(): void
	{
		// Same discoverability requirement for the plural variant.
		$this->_app->registerService('prado3_lookup', 'System.Web.Services.TJsonService');
		$ids = $this->_app->getRegisteredServicesByClass(\Prado\Web\Services\TJsonService::class);
		$this->assertContains('prado3_lookup', $ids);
	}

	public function testStartService_withPrado3SystemDotNotationRegistration(): void
	{
		// Full round-trip: register with Prado3 name → startService → running instance.
		// TJsonService::init(null) is safe: it skips JSON-endpoint parsing when
		// $config is null (as it is when no config element was supplied).
		$this->setServices([]);
		$this->_app->registerService('prado3_start', 'System.Web.Services.TJsonService');
		$this->_app->startService('prado3_start');
		$this->assertInstanceOf(\Prado\Web\Services\TJsonService::class, $this->_app->getService());
		$this->assertSame('prado3_start', $this->_app->getService()->getID());
	}

	public function testRegisterService_byClassStoresCorrectFormat(): void
	{
		$this->_app->registerService('reg_fmt', InitTrackingService::class);
		$entry = $this->_app->getRegisteredService('reg_fmt');
		$this->assertIsArray($entry);
		$this->assertSame(InitTrackingService::class, $entry[0]);
		$this->assertSame([], $entry[1]);
		$this->assertNull($entry[2]);
	}

	public function testRegisterService_withProperties(): void
	{
		$props = ['TrackedProp' => 'testValue'];
		$this->_app->registerService('reg_props', InitTrackingService::class, $props);
		$entry = $this->_app->getRegisteredService('reg_props');
		$this->assertSame($props, $entry[1]);
	}

	public function testRegisterService_withConfigElement(): void
	{
		$config = ['some' => 'config'];
		$this->_app->registerService('reg_cfg', InitTrackingService::class, [], $config);
		$entry = $this->_app->getRegisteredService('reg_cfg');
		$this->assertSame($config, $entry[2]);
	}

	public function testRegisterService_viaSpread(): void
	{
		// applyConfiguration calls registerService($id, ...$serviceConfig) where
		// $serviceConfig is the [$class, $properties, $config] tuple from the XML
		// parser. Verify that positional spreading maps correctly.
		$tuple = [InitTrackingService::class, ['TrackedProp' => 'spread'], ['extra' => true]];
		$this->_app->registerService('reg_spread', ...$tuple);
		$entry = $this->_app->getRegisteredService('reg_spread');
		$this->assertSame(InitTrackingService::class, $entry[0]);
		$this->assertSame(['TrackedProp' => 'spread'], $entry[1]);
		$this->assertSame(['extra' => true], $entry[2]);
	}

	public function testRegisterService_nullClassThrows(): void
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage('no_class');
		$this->_app->registerService('no_class', null);
	}

	public function testRegisterService_emptyClassThrows(): void
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage('empty_class');
		$this->_app->registerService('empty_class', '');
	}

	public function testRegisterService_overwritesExistingId(): void
	{
		$this->_app->registerService('overwrite', TestBaseService::class);
		$this->_app->registerService('overwrite', InitTrackingService::class);
		$entry = $this->_app->getRegisteredService('overwrite');
		$this->assertSame(InitTrackingService::class, $entry[0]);
	}

	public function testRegisterService_nonExistentClassThrows(): void
	{
		// usingClass() returns null for an unknown name → !is_string() guard fires.
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage('ThisClassDoesNotExistAnywhere_XYZ');
		$this->_app->registerService('bad', 'ThisClassDoesNotExistAnywhere_XYZ');
	}

	public function testRegisterService_withDirectoryNamespace_throws(): void
	{
		// usingClass() returns false for a directory namespace → !is_string() guard fires.
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->_app->registerService('dirservice', 'Prado\\Web\\Services\\*');
	}

	public function testRegisterService_withPrado3DirectoryNotation_throws(): void
	{
		// Prado3 dot-notation directory resolves to false → !is_string() guard fires.
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->_app->registerService('dirservice', 'System.Web.Services.*');
	}

	public function testRegisterService_nonServiceClassThrows(): void
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage(NotAService::class);
		$this->_app->registerService('bad', NotAService::class);
	}

	public function testRegisterService_validServiceClassSucceeds(): void
	{
		$this->_app->registerService('valid', InitTrackingService::class);
		$this->assertTrue($this->_app->hasRegisteredService('valid'));
		$entry = $this->_app->getRegisteredService('valid');
		$this->assertSame(InitTrackingService::class, $entry[0]);
	}

	// -----------------------------------------------------------------------
	// hasRegisteredService
	// -----------------------------------------------------------------------

	public function testHasRegisteredService_falseWhenNotRegistered(): void
	{
		$this->setServices([]);
		$this->assertFalse($this->_app->hasRegisteredService('nonexistent'));
	}

	public function testHasRegisteredService_trueAfterRegister(): void
	{
		$this->setServices([]);
		$this->_app->registerService('present', InitTrackingService::class);
		$this->assertTrue($this->_app->hasRegisteredService('present'));
	}

	public function testHasRegisteredService_trueForPageServiceByDefault(): void
	{
		$this->assertTrue($this->_app->hasRegisteredService($this->_app->getPageServiceID()));
	}

	public function testHasRegisteredService_nullReturnsFalseWhenEmpty(): void
	{
		$this->setServices([]);
		$this->assertFalse($this->_app->hasRegisteredService());
	}

	public function testHasRegisteredService_nullReturnsTrueWhenServicesExist(): void
	{
		$this->setServices([
			'a' => [InitTrackingService::class, [], null],
		]);
		$this->assertTrue($this->_app->hasRegisteredService());
	}

	public function testHasRegisteredService_nullReturnsTrueForDefaultRegistry(): void
	{
		// Default app has at least the page service registered.
		$this->assertTrue($this->_app->hasRegisteredService(null));
	}

	// -----------------------------------------------------------------------
	// getRegisteredService
	// -----------------------------------------------------------------------

	public function testGetRegisteredService_nullWhenNotRegistered(): void
	{
		$this->setServices([]);
		$this->assertNull($this->_app->getRegisteredService('ghost'));
	}

	public function testGetRegisteredService_returnsThreeElementArray(): void
	{
		$this->setServices([
			'svc' => [TestBaseService::class, ['k' => 'v'], null],
		]);
		$entry = $this->_app->getRegisteredService('svc');
		$this->assertIsArray($entry);
		$this->assertCount(3, $entry);
		$this->assertSame(TestBaseService::class, $entry[0]);
		$this->assertSame(['k' => 'v'], $entry[1]);
		$this->assertNull($entry[2]);
	}

	// -----------------------------------------------------------------------
	// getRegisteredServices
	// -----------------------------------------------------------------------

	public function testGetRegisteredServices_emptyArrayWhenNoServices(): void
	{
		$this->setServices([]);
		$this->assertSame([], $this->_app->getRegisteredServices());
	}

	public function testGetRegisteredServices_returnsAllRegisteredServices(): void
	{
		$map = [
			'a' => [TestBaseService::class, [], null],
			'b' => [TestChildService::class, [], null],
		];
		$this->setServices($map);
		$this->assertSame($map, $this->_app->getRegisteredServices());
	}

	public function testGetRegisteredServices_pageServicePresentByDefault(): void
	{
		$ids = $this->_app->getRegisteredServices();
		$this->assertArrayHasKey($this->_app->getPageServiceID(), $ids);
	}

	public function testGetRegisteredServices_returnsMap(): void
	{
		$this->setServices([
			'x' => [TestBaseService::class, [], null],
		]);
		$ids = $this->_app->getRegisteredServices();
		$this->assertIsArray($ids);
		$this->assertArrayHasKey('x', $ids);
	}

	// -----------------------------------------------------------------------
	// getRegisteredServiceByClass — singular
	// -----------------------------------------------------------------------

	/**
	 * A directory namespace (usingClass returns false) is not a valid class
	 * to look up; the method returns null immediately.
	 */
	public function testGetRegisteredServiceByClass_withDirectoryNamespace_returnsNull(): void
	{
		$result = $this->_app->getRegisteredServiceByClass('Prado\\Web\\Services\\*');
		$this->assertNull($result);
	}

	/**
	 * An unknown class name (usingClass returns null) also returns null immediately.
	 */
	public function testGetRegisteredServiceByClass_withUnknownClass_returnsNull(): void
	{
		$result = $this->_app->getRegisteredServiceByClass('TFakeServiceClassXYZ99999');
		$this->assertNull($result);
	}

	public function testGetRegisteredServiceByClass_returnsNullWhenNoServices(): void
	{
		$this->setServices([]);
		$this->assertNull($this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	public function testGetRegisteredServiceByClass_returnsNullWhenNoMatch(): void
	{
		$this->setServices([
			'unrelated' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertNull($this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	public function testGetRegisteredServiceByClass_exactMatch(): void
	{
		$this->setServices([
			'base' => [TestBaseService::class, [], null],
		]);
		$this->assertSame('base', $this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	public function testGetRegisteredServiceByClass_subclassMatch(): void
	{
		$this->setServices([
			'child' => [TestChildService::class, [], null],
		]);
		// TestChildService extends TestBaseService — should match by inheritance.
		$this->assertSame('child', $this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	public function testGetRegisteredServiceByClass_returnsFirstMatch(): void
	{
		$this->setServices([
			'first'  => [TestBaseService::class, [], null],
			'second' => [TestBaseService::class, [], null],
		]);
		$this->assertSame('first', $this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	public function testGetRegisteredServiceByClass_pageServiceRegisteredByDefault(): void
	{
		$id = $this->_app->getRegisteredServiceByClass(\Prado\Web\Services\TPageService::class);
		$this->assertNotNull($id);
	}

	public function testGetRegisteredServiceByClass_skipsNonMatchingBeforeMatch(): void
	{
		$this->setServices([
			'skip1'  => [TestUnrelatedService::class, [], null],
			'skip2'  => [TestUnrelatedService::class, [], null],
			'target' => [TestBaseService::class, [], null],
		]);
		$this->assertSame('target', $this->_app->getRegisteredServiceByClass(TestBaseService::class));
	}

	// -----------------------------------------------------------------------
	// getRegisteredServicesByClass — plural
	// -----------------------------------------------------------------------

	/**
	 * A directory namespace (usingClass returns false) → empty array immediately.
	 */
	public function testGetRegisteredServicesByClass_withDirectoryNamespace_returnsEmptyArray(): void
	{
		$result = $this->_app->getRegisteredServicesByClass('Prado\\Web\\Services\\*');
		$this->assertSame([], $result);
	}

	/**
	 * An unknown class name (usingClass returns null) → empty array immediately.
	 */
	public function testGetRegisteredServicesByClass_withUnknownClass_returnsEmptyArray(): void
	{
		$result = $this->_app->getRegisteredServicesByClass('TFakeServiceClassXYZ99999');
		$this->assertSame([], $result);
	}

	public function testGetRegisteredServicesByClass_returnsEmptyArrayWhenNoServices(): void
	{
		$this->setServices([]);
		$this->assertSame([], $this->_app->getRegisteredServicesByClass(TestBaseService::class));
	}

	public function testGetRegisteredServicesByClass_returnsEmptyArrayWhenNoMatch(): void
	{
		$this->setServices([
			'unrelated' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertSame([], $this->_app->getRegisteredServicesByClass(TestBaseService::class));
	}

	public function testGetRegisteredServicesByClass_singleExactMatch(): void
	{
		$this->setServices([
			'base' => [TestBaseService::class, [], null],
		]);
		$this->assertSame(['base'], $this->_app->getRegisteredServicesByClass(TestBaseService::class));
	}

	public function testGetRegisteredServicesByClass_multipleExactMatches(): void
	{
		$this->setServices([
			'svc1'  => [TestBaseService::class, [], null],
			'svc2'  => [TestBaseService::class, [], null],
			'other' => [TestUnrelatedService::class, [], null],
		]);
		$this->assertSame(['svc1', 'svc2'], $this->_app->getRegisteredServicesByClass(TestBaseService::class));
	}

	public function testGetRegisteredServicesByClass_subclassesIncludedByDefault(): void
	{
		$this->setServices([
			'base'    => [TestBaseService::class, [], null],
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
			'other'   => [TestUnrelatedService::class, [], null],
		]);
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class);
		$this->assertSame(['base', 'child', 'sibling'], $result);
	}

	public function testGetRegisteredServicesByClass_strictExcludesSubclasses(): void
	{
		$this->setServices([
			'base'    => [TestBaseService::class, [], null],
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
		]);
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class, strict: true);
		$this->assertSame(['base'], $result);
	}

	public function testGetRegisteredServicesByClass_strictMatchesExactClassOnly(): void
	{
		$this->setServices([
			'child'   => [TestChildService::class, [], null],
			'sibling' => [TestSiblingService::class, [], null],
		]);
		// No entry is exactly TestBaseService, so strict returns empty.
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class, strict: true);
		$this->assertSame([], $result);
	}

	public function testGetRegisteredServicesByClass_strictFalseMatchesSubclasses(): void
	{
		$this->setServices([
			'child' => [TestChildService::class, [], null],
		]);
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class, strict: false);
		$this->assertSame(['child'], $result);
	}

	public function testGetRegisteredServicesByClass_preservesRegistrationOrder(): void
	{
		$this->setServices([
			'z_svc' => [TestBaseService::class, [], null],
			'a_svc' => [TestBaseService::class, [], null],
			'm_svc' => [TestBaseService::class, [], null],
		]);
		// IDs must appear in the same order they were registered.
		$this->assertSame(['z_svc', 'a_svc', 'm_svc'], $this->_app->getRegisteredServicesByClass(TestBaseService::class));
	}

	public function testGetRegisteredServicesByClass_pageServiceRegisteredByDefault(): void
	{
		$ids = $this->_app->getRegisteredServicesByClass(\Prado\Web\Services\TPageService::class);
		$this->assertNotEmpty($ids);
		$this->assertContains($this->_app->getPageServiceID(), $ids);
	}

	public function testGetRegisteredServicesByClass_returnsStringIds(): void
	{
		// PHP array keys that look like integers are stored as ints;
		// the method must still cast them to strings.
		$this->setServices([
			0 => [TestBaseService::class, [], null],
			1 => [TestBaseService::class, [], null],
		]);
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class);
		$this->assertSame(['0', '1'], $result);
		foreach ($result as $id) {
			$this->assertIsString($id);
		}
	}

	public function testGetRegisteredServicesByClass_strictReturnStringIds(): void
	{
		$this->setServices([
			0 => [TestBaseService::class, [], null],
		]);
		$result = $this->_app->getRegisteredServicesByClass(TestBaseService::class, strict: true);
		$this->assertSame(['0'], $result);
		$this->assertIsString($result[0]);
	}

	// -----------------------------------------------------------------------
	// unregisterService
	// -----------------------------------------------------------------------

	public function testUnregisterService_removesRegisteredService(): void
	{
		$this->_app->registerService('to_remove', InitTrackingService::class);
		$this->assertTrue($this->_app->hasRegisteredService('to_remove'));
		$this->_app->unregisterService('to_remove');
		$this->assertFalse($this->_app->hasRegisteredService('to_remove'));
	}

	public function testUnregisterService_isNoOpForUnknownId(): void
	{
		$before = $this->_app->getRegisteredServices();
		$this->_app->unregisterService('does_not_exist');
		$this->assertSame($before, $this->_app->getRegisteredServices());
	}

	public function testUnregisterService_doesNotAffectOtherServices(): void
	{
		$this->setServices([
			'keep'   => [InitTrackingService::class, [], null],
			'remove' => [TestBaseService::class, [], null],
		]);
		$this->_app->unregisterService('remove');
		$this->assertTrue($this->_app->hasRegisteredService('keep'));
		$this->assertFalse($this->_app->hasRegisteredService('remove'));
	}

	public function testUnregisterService_canReregisterAfterRemoval(): void
	{
		$this->_app->registerService('cycle', TestBaseService::class);
		$this->_app->unregisterService('cycle');
		$this->_app->registerService('cycle', InitTrackingService::class);
		$entry = $this->_app->getRegisteredService('cycle');
		$this->assertSame(InitTrackingService::class, $entry[0]);
	}

	public function testUnregisterService_throwsForDefaultPageServiceId(): void
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->_app->unregisterService($this->_app->getPageServiceID());
	}

	// -----------------------------------------------------------------------
	// onConfiguration
	// -----------------------------------------------------------------------

	public function testOnConfigurationComplete_eventIsRaiseable(): void
	{
		$called = false;
		$handler = function () use (&$called) {
			$called = true;
		};
		$this->_app->attachEventHandler('onConfiguration', $handler);

		$this->_app->onConfiguration();

		$this->_app->detachEventHandler('onConfiguration', $handler);
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
		$this->_app->attachEventHandler('onConfiguration', $handler);

		$this->_app->onConfiguration();

		$this->_app->detachEventHandler('onConfiguration', $handler);
		$this->assertSame($this->_app, $receivedSender);
		$this->assertNull($receivedParam);
	}

	public function testOnConfigurationComplete_multipleHandlersCalled(): void
	{
		$count = 0;
		$handler1 = function () use (&$count) { $count++; };
		$handler2 = function () use (&$count) { $count++; };
		$this->_app->attachEventHandler('onConfiguration', $handler1);
		$this->_app->attachEventHandler('onConfiguration', $handler2);

		$this->_app->onConfiguration();

		$this->_app->detachEventHandler('onConfiguration', $handler1);
		$this->_app->detachEventHandler('onConfiguration', $handler2);
		$this->assertSame(2, $count);
	}

	public function testOnConfigurationComplete_serviceRegisteredInHandlerIsVisibleToGetRegisteredServices(): void
	{
		// Simulate what a module would do: register a new service inside the
		// onConfiguration handler, then verify it is discoverable via
		// getRegisteredServicesByClass() after the event fires.
		$app = $this->_app;
		$handler = function () use ($app) {
			$app->registerService('late_svc', InitTrackingService::class);
		};
		$app->attachEventHandler('onConfiguration', $handler);

		$app->onConfiguration();

		$app->detachEventHandler('onConfiguration', $handler);
		$this->assertContains('late_svc', $app->getRegisteredServicesByClass(InitTrackingService::class));
	}

	// -----------------------------------------------------------------------
	// onInitComplete
	// -----------------------------------------------------------------------

	public function testOnInitComplete_eventIsRaiseable(): void
	{
		$called = false;
		$handler = function () use (&$called) {
			$called = true;
		};
		$this->_app->attachEventHandler('onInitComplete', $handler);

		$this->_app->onInitComplete();

		$this->_app->detachEventHandler('onInitComplete', $handler);
		$this->assertTrue($called);
	}

	public function testOnInitComplete_handlerReceivesApplicationAndNullParam(): void
	{
		$receivedSender = null;
		$receivedParam = 'not-null';
		$handler = function ($sender, $param) use (&$receivedSender, &$receivedParam) {
			$receivedSender = $sender;
			$receivedParam = $param;
		};
		$this->_app->attachEventHandler('onInitComplete', $handler);

		$this->_app->onInitComplete();

		$this->_app->detachEventHandler('onInitComplete', $handler);
		$this->assertSame($this->_app, $receivedSender);
		$this->assertNull($receivedParam);
	}

	public function testOnInitComplete_multipleHandlersCalled(): void
	{
		$count = 0;
		$h1 = function () use (&$count) { $count++; };
		$h2 = function () use (&$count) { $count++; };
		$this->_app->attachEventHandler('onInitComplete', $h1);
		$this->_app->attachEventHandler('onInitComplete', $h2);

		$this->_app->onInitComplete();

		$this->_app->detachEventHandler('onInitComplete', $h1);
		$this->_app->detachEventHandler('onInitComplete', $h2);
		$this->assertSame(2, $count);
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

	public function testStartService_registeredViaRegisterService(): void
	{
		// Verify that a service registered via registerService() is correctly
		// started by startService().
		$this->_app->registerService('dyn_svc', InitTrackingService::class, ['TrackedProp' => 'dynamic']);
		$this->_app->startService('dyn_svc');
		/** @var InitTrackingService $service */
		$service = $this->_app->getService();
		$this->assertInstanceOf(InitTrackingService::class, $service);
		$this->assertSame('dyn_svc', $service->getID());
		$this->assertSame('dynamic', $service->getTrackedProp());
		$this->assertTrue($service->initCalled);
	}

	public function testStartService_multipleSequentialStarts(): void
	{
		// Each call to startService() must replace the active service completely.
		$this->setServices([
			'first'  => [InitTrackingService::class, [], null],
			'second' => [TestChildService::class, [], null],
		]);
		$this->_app->startService('first');
		$this->assertInstanceOf(InitTrackingService::class, $this->_app->getService());

		$this->_app->startService('second');
		$this->assertInstanceOf(TestChildService::class, $this->_app->getService());
		$this->assertSame('second', $this->_app->getService()->getID());
	}
}
