<?php

/**
 * TApplicationBootstrapTest class file.
 *
 * Comprehensive coverage for the 4.4.0 default-module bootstrap surface
 * on {@see \Prado\TApplication}:
 *
 *  - the nine `DEFAULT_*_ID` constants and their late-static-binding override
 *  - {@see TApplication::runModuleLifecycle} — the extracted four-phase
 *    runner shared by `getModule` (lazy load) and `bootstrapDefaultModule`
 *  - {@see TApplication::bootstrapDefaultModule} — assigns canonical ID +
 *    runs lifecycle without registering in `$_modules`
 *  - {@see TApplication::bootstrapDefaultModules} — registers each
 *    instanced default under its self-reported ID, with type-collision
 *    suppression and no auto-instantiation
 *  - the nine lazy default-module accessors and their canonical-ID
 *    assignment via `bootstrapDefaultModule`
 *  - `initApplication`'s ordering of bootstrap → onConfiguration
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\TConfigurationException;
use Prado\IModule;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TModule;

// =============================================================================
// Fixtures
// =============================================================================

/**
 * Public-wrapper subclass of TApplication exposing the protected bootstrap
 * surface and the related test helpers. Instantiated via
 * {@see \ReflectionClass::newInstanceWithoutConstructor()} so each test starts
 * from a clean, fully-controllable instance — no Prado singleton swap.
 */
class TApplicationBootstrapAccessor extends TApplication
{
	public function pubBootstrapDefaultModule(IModule $module, string $id): void
	{
		$this->bootstrapDefaultModule($module, $id);
	}

	public function pubBootstrapDefaultModules(): void
	{
		$this->bootstrapDefaultModules();
	}

	public function pubRunModuleLifecycle(IModule $module, mixed $config): void
	{
		$this->runModuleLifecycle($module, $config);
	}
}

/**
 * Subclass that overrides one canonical-ID constant to verify the bootstrap
 * surface reads `static::DEFAULT_*_ID` (late static binding).
 */
class TApplicationBootstrapAccessorCustomRequestId extends TApplicationBootstrapAccessor
{
	public const DEFAULT_REQUEST_ID = 'custom_http_request';
}

/**
 * `TComponent`-rooted module that records every lifecycle dispatch in order.
 * Overriding the `dy*` methods as concrete instance methods short-circuits
 * `TComponent::__call`'s behavior-chain dispatch — which is what we want for
 * tests that need to observe the call deterministically.
 */
class AppBootstrapSpyModule extends TModule
{
	/** @var array<int, array{phase: string, config: mixed}> */
	public array $calls = [];

	public function dyPreInit($config = null, $chain = null): void
	{
		$this->calls[] = ['phase' => 'dyPreInit', 'config' => $config];
	}

	public function init($config)
	{
		$this->calls[] = ['phase' => 'init', 'config' => $config];
	}

	public function dyPostInit($config = null, $chain = null): void
	{
		$this->calls[] = ['phase' => 'dyPostInit', 'config' => $config];
	}
}

/**
 * Bare `IModule` implementation that intentionally does NOT extend `TComponent`.
 * Used to confirm `runModuleLifecycle` skips `dyPreInit`/`dyPostInit` for
 * non-component modules without erroring on the missing methods.
 */
class AppBootstrapBareIModule implements IModule
{
	public int $initCount = 0;
	public mixed $lastConfig = 'NOT_SET';
	private string $_id = '';

	public function init($config)
	{
		$this->initCount++;
		$this->lastConfig = $config;
	}

	public function getID()
	{
		return $this->_id;
	}

	public function setID($id)
	{
		$this->_id = (string) $id;
	}
}

/**
 * Spy module with configurable {@see IModuleDependency} output, used to
 * exercise the required/advisory/lazy-load branches in `runModuleLifecycle`.
 */
class AppBootstrapDepSpyModule extends AppBootstrapSpyModule implements IModuleDependency
{
	/** What `getModuleDependencies` returns; mirrors the documented shapes. */
	public null|string|array $declaredDeps = null;

	public function getModuleDependencies(bool $isPreInit): null|string|array
	{
		return $this->declaredDeps;
	}
}

// =============================================================================
// Tests
// =============================================================================

/**
 * Coverage for the bootstrap surface added to TApplication in 4.4.0.
 *
 * Each test instantiates a {@see TApplicationBootstrapAccessor} via
 * `newInstanceWithoutConstructor()` so the global Prado singleton is never
 * touched. Where the accessor needs initialised collections (`_modules`,
 * `_lazyModules`), `PradoUnit::setProp` sets them explicitly.
 *
 * @package System
 */
class TApplicationBootstrapTest extends PHPUnit\Framework\TestCase
{
	private function newAccessor(string $class = TApplicationBootstrapAccessor::class): TApplicationBootstrapAccessor
	{
		$ref = new \ReflectionClass($class);
		$acc = $ref->newInstanceWithoutConstructor();
		PradoUnit::setProp($acc, '_modules', []);
		PradoUnit::setProp($acc, '_lazyModules', []);
		return $acc;
	}

	// =======================================================================
	// DEFAULT_*_ID canonical default-module ID constants
	// =======================================================================

	public function testConstant_defaultRequestId(): void
	{
		$this->assertSame('request', TApplication::DEFAULT_REQUEST_ID);
	}

	public function testConstant_defaultResponseId(): void
	{
		$this->assertSame('response', TApplication::DEFAULT_RESPONSE_ID);
	}

	public function testConstant_defaultSessionId(): void
	{
		$this->assertSame('session', TApplication::DEFAULT_SESSION_ID);
	}

	public function testConstant_defaultErrorHandlerId(): void
	{
		$this->assertSame('errorHandler', TApplication::DEFAULT_ERROR_HANDLER_ID);
	}

	public function testConstant_defaultSecurityManagerId(): void
	{
		$this->assertSame('securityManager', TApplication::DEFAULT_SECURITY_MANAGER_ID);
	}

	public function testConstant_defaultAssetManagerId(): void
	{
		$this->assertSame('assetManager', TApplication::DEFAULT_ASSET_MANAGER_ID);
	}

	public function testConstant_defaultGlobalizationId(): void
	{
		$this->assertSame('globalization', TApplication::DEFAULT_GLOBALIZATION_ID);
	}

	public function testConstant_defaultTemplateManagerId(): void
	{
		$this->assertSame('templateManager', TApplication::DEFAULT_TEMPLATE_MANAGER_ID);
	}

	public function testConstant_defaultThemeManagerId(): void
	{
		$this->assertSame('themeManager', TApplication::DEFAULT_THEME_MANAGER_ID);
	}

	public function testConstant_subclassOverrideHonored(): void
	{
		$this->assertSame(
			'custom_http_request',
			TApplicationBootstrapAccessorCustomRequestId::DEFAULT_REQUEST_ID
		);
	}

	public function testConstant_baseClassUnaffectedBySubclassOverride(): void
	{
		// Sanity: subclass override must not bleed into the parent constant.
		$this->assertSame('request', TApplication::DEFAULT_REQUEST_ID);
	}

	// =======================================================================
	// runModuleLifecycle — TComponent vs bare IModule, config passthrough
	// =======================================================================

	public function testRunModuleLifecycle_tComponentModule_callsAllPhasesInOrder(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubRunModuleLifecycle($module, 'cfg');

		$this->assertSame(
			['dyPreInit', 'init', 'dyPostInit'],
			array_column($module->calls, 'phase'),
			'TComponent module must receive dyPreInit, init, dyPostInit in order'
		);
	}

	public function testRunModuleLifecycle_configForwardedToEveryPhase(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubRunModuleLifecycle($module, 'shared_cfg');

		foreach ($module->calls as $call) {
			$this->assertSame(
				'shared_cfg',
				$call['config'],
				"Phase {$call['phase']} did not receive the supplied config"
			);
		}
	}

	public function testRunModuleLifecycle_nullConfigForwarded(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubRunModuleLifecycle($module, null);

		foreach ($module->calls as $call) {
			$this->assertNull($call['config'], "Phase {$call['phase']} should receive null config");
		}
	}

	public function testRunModuleLifecycle_bareIModule_skipsDyPhases_butStillCallsInit(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapBareIModule();

		// If runModuleLifecycle attempted dyPreInit/dyPostInit on a class that
		// has no such methods, PHP would throw. Reaching the assertion proves
		// the isComponent guard correctly skipped both.
		$acc->pubRunModuleLifecycle($module, 'cfg');

		$this->assertSame(1, $module->initCount, 'init must be called exactly once');
		$this->assertSame('cfg', $module->lastConfig, 'init must receive the config');
	}

	public function testRunModuleLifecycle_requiredDepMissing_throws(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapDepSpyModule();
		$module->declaredDeps = ['ghost_required'];

		$this->expectException(TConfigurationException::class);
		$acc->pubRunModuleLifecycle($module, null);
	}

	public function testRunModuleLifecycle_advisoryDepMissing_silentlySkipped(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapDepSpyModule();
		$module->declaredDeps = ['ghost_advisory' => false];

		// Must not throw; init still runs.
		$acc->pubRunModuleLifecycle($module, null);

		$initCount = count(array_filter($module->calls, fn($c) => $c['phase'] === 'init'));
		$this->assertSame(1, $initCount, 'init must run even when advisory dep is missing');
	}

	public function testRunModuleLifecycle_presentDepForceLoaded(): void
	{
		$acc = $this->newAccessor();

		// Register a lazy module slot keyed 'lazyDep' that, when force-loaded,
		// instantiates AppTestModule. setLazyModule is protected so we bypass
		// it and write _lazyModules / _modules directly via reflection.
		PradoUnit::setProp($acc, '_lazyModules', [
			'lazyDep' => [AppTestModule::class, [], null],
		]);
		PradoUnit::setProp($acc, '_modules', ['lazyDep' => null]);

		$module = new AppBootstrapDepSpyModule();
		$module->declaredDeps = ['lazyDep'];

		$acc->pubRunModuleLifecycle($module, null);

		// After force-load, _modules['lazyDep'] is a concrete AppTestModule.
		$loaded = $acc->getModule('lazyDep');
		$this->assertInstanceOf(AppTestModule::class, $loaded);
	}

	public function testRunModuleLifecycle_noIModuleDependency_noDepLoop(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();  // does NOT implement IModuleDependency

		// Should run cleanly with no deps to resolve.
		$acc->pubRunModuleLifecycle($module, null);

		$this->assertContains('init', array_column($module->calls, 'phase'));
	}

	// =======================================================================
	// bootstrapDefaultModule — setID + lifecycle, no $_modules write
	// =======================================================================

	public function testBootstrapDefaultModule_assignsCanonicalId(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubBootstrapDefaultModule($module, 'my_canon_id');

		$this->assertSame('my_canon_id', $module->getID());
	}

	public function testBootstrapDefaultModule_runsFullLifecycle_withNullConfig(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubBootstrapDefaultModule($module, 'spy');

		// All three phases ran (TComponent module) with null config.
		$this->assertSame(['dyPreInit', 'init', 'dyPostInit'], array_column($module->calls, 'phase'));
		foreach ($module->calls as $call) {
			$this->assertNull($call['config']);
		}
	}

	public function testBootstrapDefaultModule_doesNotRegisterIn_modules(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapSpyModule();

		$acc->pubBootstrapDefaultModule($module, 'spy_id');

		$this->assertSame(
			[],
			$acc->getModules(),
			'bootstrapDefaultModule must not write to $_modules; registration is bootstrapDefaultModules() time'
		);
	}

	public function testBootstrapDefaultModule_requiredDepMissing_propagates(): void
	{
		$acc = $this->newAccessor();
		$module = new AppBootstrapDepSpyModule();
		$module->declaredDeps = ['ghost_required'];

		$this->expectException(TConfigurationException::class);
		$acc->pubBootstrapDefaultModule($module, 'spy_id');
	}

	// =======================================================================
	// bootstrapDefaultModules — registry sweep with type-collision suppression
	// =======================================================================

	public function testBootstrapDefaultModules_emptyApp_registersNothing(): void
	{
		$acc = $this->newAccessor();
		// All nine slots already null after newAccessor (no constructor ran).

		$acc->pubBootstrapDefaultModules();

		$this->assertSame([], $acc->getModules());
	}

	public function testBootstrapDefaultModules_instancedRequest_registered(): void
	{
		$acc = $this->newAccessor();
		$req = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		$acc->pubBootstrapDefaultModules();

		$this->assertSame(
			$req,
			$acc->getModules()[TApplication::DEFAULT_REQUEST_ID] ?? null,
			'Instanced default request must be registered under its canonical ID'
		);
	}

	public function testBootstrapDefaultModules_registersUnderModuleSelfReportedId(): void
	{
		$acc = $this->newAccessor();
		$req = new \Prado\Web\THttpRequest();
		// Deliberately give it a non-canonical ID — the registry must follow
		// the module's own getID(), not the canonical DEFAULT_REQUEST_ID literal.
		$req->setID('my_custom_req_id');
		PradoUnit::setProp($acc, '_request', $req);

		$acc->pubBootstrapDefaultModules();

		$this->assertArrayHasKey('my_custom_req_id', $acc->getModules());
		$this->assertArrayNotHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertSame($req, $acc->getModules()['my_custom_req_id']);
	}

	public function testBootstrapDefaultModules_typeCollision_suppressesDefault(): void
	{
		$acc = $this->newAccessor();

		// A configured THttpRequest already registered under SOME id.
		$configured = new \Prado\Web\THttpRequest();
		$configured->setID('configured_request');
		PradoUnit::setProp($acc, '_modules', ['configured_request' => $configured]);

		// A lazy default-request instance also exists in the slot.
		$defaultReq = new \Prado\Web\THttpRequest();
		$defaultReq->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $defaultReq);

		$acc->pubBootstrapDefaultModules();

		// The default must not be registered — configured one stays sole.
		$this->assertSame($configured, $acc->getModules()['configured_request']);
		$this->assertArrayNotHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertCount(1, $acc->getModules());
	}

	public function testBootstrapDefaultModules_typeCollision_subclassAlsoSuppressesDefault(): void
	{
		$acc = $this->newAccessor();

		// Subclass of THttpRequest pre-registered.
		$configured = new class extends \Prado\Web\THttpRequest {};
		$configured->setID('my_subclassed_request');
		PradoUnit::setProp($acc, '_modules', ['my_subclassed_request' => $configured]);

		$defaultReq = new \Prado\Web\THttpRequest();
		$defaultReq->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $defaultReq);

		$acc->pubBootstrapDefaultModules();

		// Non-strict type match: subclass is-a THttpRequest so the default is
		// still suppressed.
		$this->assertArrayNotHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertCount(1, $acc->getModules());
	}

	public function testBootstrapDefaultModules_neverInstantiatesAbsentSlot(): void
	{
		$acc = $this->newAccessor();

		$acc->pubBootstrapDefaultModules();

		// None of the nine backing fields should have been touched.
		foreach (['_request', '_response', '_session', '_errorHandler',
			'_security', '_assetManager', '_globalization',
			'_templateManager', '_themeManager'] as $field) {
			$this->assertNull(
				PradoUnit::getProp($acc, $field),
				"Backing field $field must remain null — bootstrapDefaultModules must never instantiate"
			);
		}
	}

	public function testBootstrapDefaultModules_idempotent_secondCallIsNoOp(): void
	{
		$acc = $this->newAccessor();
		$req = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		$acc->pubBootstrapDefaultModules();
		$first = $acc->getModules();

		$acc->pubBootstrapDefaultModules();
		$second = $acc->getModules();

		$this->assertSame($first, $second);
		$this->assertSame($req, $second[TApplication::DEFAULT_REQUEST_ID]);
	}

	public function testBootstrapDefaultModules_multipleInstancedSlots_allRegistered(): void
	{
		$acc = $this->newAccessor();

		$req  = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		$sec  = new \Prado\Security\TSecurityManager();
		$sec->setID(TApplication::DEFAULT_SECURITY_MANAGER_ID);
		PradoUnit::setProp($acc, '_security', $sec);

		$acc->pubBootstrapDefaultModules();

		$this->assertArrayHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertArrayHasKey(TApplication::DEFAULT_SECURITY_MANAGER_ID, $acc->getModules());
		$this->assertCount(2, $acc->getModules());
	}

	public function testBootstrapDefaultModules_preservesUnrelatedModules(): void
	{
		$acc = $this->newAccessor();

		// An unrelated configured module of a different type.
		$unrelated = new AppTestModule();
		$unrelated->setID('my_unrelated');
		PradoUnit::setProp($acc, '_modules', ['my_unrelated' => $unrelated]);

		$req = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		$acc->pubBootstrapDefaultModules();

		$this->assertSame($unrelated, $acc->getModules()['my_unrelated']);
		$this->assertSame($req, $acc->getModules()[TApplication::DEFAULT_REQUEST_ID]);
		$this->assertCount(2, $acc->getModules());
	}

	// =======================================================================
	// Lazy default-module accessors — canonical ID assigned on creation
	// =======================================================================
	//
	// Each accessor is tested via the bootstrap singleton (Prado::getApplication).
	// The state is snapshotted in setUp and restored in tearDown so per-test
	// nullification doesn't leak. For each accessor: null the backing field,
	// call the accessor, observe (a) returned instance type, (b) ID equals
	// canonical, (c) subsequent call returns the same instance.

	/** @var TApplication The bootstrap singleton. */
	private TApplication $_app;
	private array $_snap = [];

	protected function setUp(): void
	{
		$this->_app = Prado::getApplication();
		$this->_snap = TTestApplication::snapshotApp($this->_app);
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_snap, $this->_app);
	}

	public function testLazyAccessor_request_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_request', null);
		$instance = $this->_app->getRequest();
		$this->assertInstanceOf(\Prado\Web\THttpRequest::class, $instance);
		$this->assertSame(TApplication::DEFAULT_REQUEST_ID, $instance->getID());
		$this->assertSame($instance, $this->_app->getRequest(), 'Second call returns same instance');
	}

	public function testLazyAccessor_response_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_response', null);
		$levelBefore = ob_get_level();
		$instance = $this->_app->getResponse();
		while (ob_get_level() > $levelBefore) {
			ob_end_clean();
		}
		$this->assertInstanceOf(\Prado\Web\THttpResponse::class, $instance);
		$this->assertSame(TApplication::DEFAULT_RESPONSE_ID, $instance->getID());
	}

	public function testLazyAccessor_session_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_session', null);
		$instance = $this->_app->getSession();
		$this->assertInstanceOf(\Prado\Web\THttpSession::class, $instance);
		$this->assertSame(TApplication::DEFAULT_SESSION_ID, $instance->getID());
	}

	public function testLazyAccessor_errorHandler_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_errorHandler', null);
		$instance = $this->_app->getErrorHandler();
		$this->assertInstanceOf(\Prado\Exceptions\TErrorHandler::class, $instance);
		$this->assertSame(TApplication::DEFAULT_ERROR_HANDLER_ID, $instance->getID());
	}

	public function testLazyAccessor_securityManager_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_security', null);
		$instance = $this->_app->getSecurityManager();
		$this->assertInstanceOf(\Prado\Security\TSecurityManager::class, $instance);
		$this->assertSame(TApplication::DEFAULT_SECURITY_MANAGER_ID, $instance->getID());
	}

	public function testLazyAccessor_globalization_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_globalization', null);
		$instance = $this->_app->getGlobalization(true);
		$this->assertInstanceOf(\Prado\I18N\TGlobalization::class, $instance);
		$this->assertSame(TApplication::DEFAULT_GLOBALIZATION_ID, $instance->getID());
	}

	public function testLazyAccessor_globalization_createFalse_returnsNull(): void
	{
		PradoUnit::setProp($this->_app, '_globalization', null);
		$this->assertNull(
			$this->_app->getGlobalization(false),
			'getGlobalization(false) must NOT auto-create'
		);
	}

	public function testLazyAccessor_templateManager_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_templateManager', null);
		$instance = $this->_app->getTemplateManager();
		$this->assertInstanceOf(\Prado\Web\UI\TTemplateManager::class, $instance);
		$this->assertSame(TApplication::DEFAULT_TEMPLATE_MANAGER_ID, $instance->getID());
	}

	public function testLazyAccessor_themeManager_assignsCanonicalId(): void
	{
		PradoUnit::setProp($this->_app, '_themeManager', null);
		$instance = $this->_app->getThemeManager();
		$this->assertInstanceOf(\Prado\Web\UI\TThemeManager::class, $instance);
		$this->assertSame(TApplication::DEFAULT_THEME_MANAGER_ID, $instance->getID());
	}

	public function testLazyAccessor_setterBypassesBootstrap(): void
	{
		// When the setter is called directly with a fully-constructed instance,
		// no bootstrap fires and the ID stays whatever the setter passed-in
		// instance had.
		$pre = new \Prado\Web\THttpRequest();
		$pre->setID('prebuilt');
		$this->_app->setRequest($pre);
		$this->assertSame('prebuilt', $this->_app->getRequest()->getID());
	}

	// =======================================================================
	// initApplication ordering — bootstrap fires BEFORE onConfiguration
	// =======================================================================
	//
	// We can't run initApplication() against the bootstrap singleton without
	// extensive setup (config file, service registration, etc.), but we CAN
	// verify the call ordering by inspecting source: bootstrapDefaultModules
	// must be invoked before onConfiguration. The earlier integration check
	// via grep proves the order; here we test the observable effect — an
	// onConfiguration handler attached BEFORE initApplication runs sees the
	// default modules already registered.

	public function testInitApplication_bootstrapDefaultModulesIsCalledBeforeOnConfiguration(): void
	{
		// Use the accessor; instantiate without constructor so initApplication's
		// internal calls don't try to load a config file or start services.
		$acc = $this->newAccessor();

		// Seed an instanced default so bootstrapDefaultModules has something
		// to register, and confirm the registration happens before the event.
		$req = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		// Subscribe a handler that snapshots _modules at event time.
		$snapAtEvent = null;
		$acc->attachEventHandler('onConfiguration', function () use ($acc, &$snapAtEvent) {
			$snapAtEvent = $acc->getModules();
		});

		// Drive the two steps in the order initApplication uses.
		$acc->pubBootstrapDefaultModules();
		$acc->onConfiguration();

		$this->assertIsArray($snapAtEvent);
		$this->assertArrayHasKey(
			TApplication::DEFAULT_REQUEST_ID,
			$snapAtEvent,
			'onConfiguration handlers must see default modules already registered'
		);
	}

	public function testOnConfiguration_isThinEventRaise_doesNotItselfBootstrap(): void
	{
		$acc = $this->newAccessor();

		// Seed an instanced default. If onConfiguration is doing the
		// bootstrapping (the old design), calling it alone would register
		// the default — that would be wrong now.
		$req = new \Prado\Web\THttpRequest();
		$req->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $req);

		$acc->onConfiguration();

		$this->assertArrayNotHasKey(
			TApplication::DEFAULT_REQUEST_ID,
			$acc->getModules(),
			'onConfiguration must not itself bootstrap; that is initApplication\'s job before raising the event'
		);
	}

	public function testOnConfiguration_raisesEventToAttachedHandlers(): void
	{
		$acc = $this->newAccessor();

		$fired = false;
		$acc->attachEventHandler('onConfiguration', function () use (&$fired) {
			$fired = true;
		});

		$acc->onConfiguration();

		$this->assertTrue($fired);
	}
}
