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
 *  - {@see TApplication::bootstrapDefaultModules} — sweep that enrols
 *    each instanced default in `$_modules` (type-collision suppression
 *    keeps configured same-type modules in place) and flips the
 *    STATE_DEFAULT_MODULES_BOOTSTRAPPED flag so subsequent late-arrival defaults
 *    are registered immediately by `bootstrapDefaultModule`
 *  - the nine lazy default-module accessors and their canonical-ID
 *    assignment via `bootstrapDefaultModule`
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
 * Subclass exposing {@see TApplication::initApplication} as public and
 * stubbing {@see TApplication::initService} to a no-op so the full
 * initialization sequence can be driven from a test without HTTP setup
 * or registered services.
 */
class TApplicationBootstrapInitAccessor extends TApplicationBootstrapAccessor
{
	public function pubInitApplication(): void
	{
		$this->initApplication();
	}

	protected function initService(): void
	{
		// No-op: bypass service resolution so initApplication() can
		// reach setStateFlag(STATE_INITIALIZED) without a configured service.
	}
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
	// STATE_* bit constants
	// =======================================================================

	public function testConstant_stateDefaultModulesBootstrapped(): void
	{
		$this->assertSame(1 << 0, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED);
	}

	public function testConstant_stateInitialized(): void
	{
		$this->assertSame(1 << 1, TApplication::STATE_INITIALIZED);
	}

	public function testConstant_stateBitsAreDistinct(): void
	{
		// No two STATE_* bits may share a position.
		$this->assertSame(
			0,
			TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED & TApplication::STATE_INITIALIZED,
			'STATE_* bits must occupy distinct positions in $_stateFlags'
		);
	}

	// =======================================================================
	// State-flag helpers — getStateFlags / hasStateFlag / setStateFlag
	// =======================================================================

	public function testStateFlags_initialValueIsZero(): void
	{
		$acc = $this->newAccessor();
		$this->assertSame(0, $acc->getStateFlags());
	}

	public function testHasStateFlag_falseWhenNotSet(): void
	{
		$acc = $this->newAccessor();
		$this->assertFalse($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));
		$this->assertFalse($acc->hasStateFlag(TApplication::STATE_INITIALIZED));
	}

	public function testSetStateFlag_setsBit_thenHasStateFlagReportsTrue(): void
	{
		$acc = $this->newAccessor();

		// setStateFlag is protected; drive it via reflection so we test the
		// helper itself, not through bootstrapDefaultModules.
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);
		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED);

		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));
		$this->assertSame(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED, $acc->getStateFlags());
	}

	public function testSetStateFlag_clearsBitWhenOnIsFalse(): void
	{
		$acc = $this->newAccessor();
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);

		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED, true);
		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));

		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED, false);
		$this->assertFalse($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));
		$this->assertSame(0, $acc->getStateFlags());
	}

	public function testSetStateFlag_idempotentOnAlreadySetBit(): void
	{
		$acc = $this->newAccessor();
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);

		$rm->invoke($acc, TApplication::STATE_INITIALIZED);
		$rm->invoke($acc, TApplication::STATE_INITIALIZED);

		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_INITIALIZED));
		$this->assertSame(TApplication::STATE_INITIALIZED, $acc->getStateFlags());
	}

	public function testSetStateFlag_multipleBitsCoexist(): void
	{
		$acc = $this->newAccessor();
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);

		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED);
		$rm->invoke($acc, TApplication::STATE_INITIALIZED);

		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));
		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_INITIALIZED));
		$this->assertSame(
			TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED | TApplication::STATE_INITIALIZED,
			$acc->getStateFlags()
		);
	}

	public function testHasStateFlag_multiBitRequiresAllBitsSet(): void
	{
		$acc = $this->newAccessor();
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);

		// Only set one of two bits.
		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED);

		// Combined check must report false when any requested bit is missing.
		$combined = TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED | TApplication::STATE_INITIALIZED;
		$this->assertFalse($acc->hasStateFlag($combined));

		// After setting the second bit, the combined check passes.
		$rm->invoke($acc, TApplication::STATE_INITIALIZED);
		$this->assertTrue($acc->hasStateFlag($combined));
	}

	public function testSetStateFlag_clearOnlyAffectsRequestedBits(): void
	{
		$acc = $this->newAccessor();
		$rm = new \ReflectionMethod(TApplication::class, 'setStateFlag');
		$rm->setAccessible(true);

		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED);
		$rm->invoke($acc, TApplication::STATE_INITIALIZED);

		// Clear only one bit.
		$rm->invoke($acc, TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED, false);

		$this->assertFalse($acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED));
		$this->assertTrue($acc->hasStateFlag(TApplication::STATE_INITIALIZED));
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
			'bootstrapDefaultModule must not write to $_modules — the module\'s own init() is expected to self-register if needed'
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
	// bootstrapDefaultModules — registry sweep with type-collision suppression
	// =======================================================================

	public function testBootstrapDefaultModules_emptyApp_registersNothing(): void
	{
		$acc = $this->newAccessor();
		$acc->pubBootstrapDefaultModules();
		$this->assertSame([], $acc->getModules());
	}

	public function testBootstrapDefaultModules_registersInstancedDefaultUnderModuleSelfReportedId(): void
	{
		$acc = $this->newAccessor();
		$req = new \Prado\Web\THttpRequest();
		$req->setID('my_custom_req_id');  // NOT the canonical literal
		PradoUnit::setProp($acc, '_request', $req);

		$acc->pubBootstrapDefaultModules();

		// Registry keys on the module's own getID(), not on the canonical
		// DEFAULT_REQUEST_ID literal — so a module that's been renamed via
		// setID after bootstrapDefaultModule still lands under its own name.
		$this->assertArrayHasKey('my_custom_req_id', $acc->getModules());
		$this->assertSame($req, $acc->getModules()['my_custom_req_id']);
	}

	public function testBootstrapDefaultModules_typeCollision_suppressesDefault(): void
	{
		$acc = $this->newAccessor();
		// A configured THttpRequest already registered under SOME id.
		$configured = new \Prado\Web\THttpRequest();
		$configured->setID('configured_request');
		PradoUnit::setProp($acc, '_modules', ['configured_request' => $configured]);

		// A lazy default-request instance also exists.
		$defaultReq = new \Prado\Web\THttpRequest();
		$defaultReq->setID(TApplication::DEFAULT_REQUEST_ID);
		PradoUnit::setProp($acc, '_request', $defaultReq);

		$acc->pubBootstrapDefaultModules();

		$this->assertSame($configured, $acc->getModules()['configured_request']);
		$this->assertArrayNotHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertCount(1, $acc->getModules());
	}

	public function testBootstrapDefaultModules_neverInstantiatesAbsentSlot(): void
	{
		$acc = $this->newAccessor();
		$acc->pubBootstrapDefaultModules();

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
	}

	public function testBootstrapDefaultModules_setsStateDefaultModulesBootstrappedFlag(): void
	{
		$acc = $this->newAccessor();
		$this->assertFalse(
			$acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED),
			'Sanity: flag should start clear'
		);

		$acc->pubBootstrapDefaultModules();

		$this->assertTrue(
			$acc->hasStateFlag(TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED),
			'bootstrapDefaultModules() must flip STATE_DEFAULT_MODULES_BOOTSTRAPPED on completion'
		);
	}

	// =======================================================================
	// Late-bootstrap flag — bootstrapDefaultModule registers if the sweep ran
	// =======================================================================

	public function testBootstrapDefaultModule_beforeSweep_doesNotRegister(): void
	{
		$acc = $this->newAccessor();
		$module = new \Prado\Web\THttpRequest();

		// Sweep has NOT been called → bootstrapDefaultModule must leave
		// $_modules pristine.
		$acc->pubBootstrapDefaultModule($module, 'pre_sweep_id');

		$this->assertArrayNotHasKey('pre_sweep_id', $acc->getModules());
	}

	public function testBootstrapDefaultModule_afterSweep_registersLateArrival(): void
	{
		$acc = $this->newAccessor();
		// Run the sweep with nothing instanced — the flag flips even on an
		// empty registry.
		$acc->pubBootstrapDefaultModules();

		// A new default is bootstrapped late (e.g. someone calls getSession()
		// for the first time after onConfiguration). It MUST be registered.
		$module = new \Prado\Web\THttpSession();
		$acc->pubBootstrapDefaultModule($module, TApplication::DEFAULT_SESSION_ID);

		$this->assertSame(
			$module,
			$acc->getModules()[TApplication::DEFAULT_SESSION_ID] ?? null,
			'Late-bootstrap default must be registered when the sweep has already completed'
		);
	}

	public function testBootstrapDefaultModule_afterSweep_typeCollisionStillSuppresses(): void
	{
		$acc = $this->newAccessor();

		// A configured same-type module is already in the registry before
		// the sweep runs.
		$configured = new \Prado\Web\THttpRequest();
		$configured->setID('configured_request');
		PradoUnit::setProp($acc, '_modules', ['configured_request' => $configured]);

		$acc->pubBootstrapDefaultModules();

		// Now a late default-request bootstrap arrives. It must NOT be
		// registered — the configured same-type module takes precedence.
		$lateDefault = new \Prado\Web\THttpRequest();
		$acc->pubBootstrapDefaultModule($lateDefault, TApplication::DEFAULT_REQUEST_ID);

		$this->assertArrayNotHasKey(TApplication::DEFAULT_REQUEST_ID, $acc->getModules());
		$this->assertSame($configured, $acc->getModules()['configured_request']);
		$this->assertCount(1, $acc->getModules());
	}

	// =======================================================================
	// initApplication — STATE_INITIALIZED set after onInitComplete
	// =======================================================================

	private function newInitAccessor(): TApplicationBootstrapInitAccessor
	{
		$ref = new \ReflectionClass(TApplicationBootstrapInitAccessor::class);
		$acc = $ref->newInstanceWithoutConstructor();
		PradoUnit::setProp($acc, '_modules', []);
		PradoUnit::setProp($acc, '_lazyModules', []);
		return $acc;
	}

	public function testInitApplication_setsStateInitializedFlagOnCompletion(): void
	{
		$acc = $this->newInitAccessor();
		$this->assertFalse(
			$acc->hasStateFlag(TApplication::STATE_INITIALIZED),
			'Sanity: STATE_INITIALIZED should start clear'
		);

		$acc->pubInitApplication();

		$this->assertTrue(
			$acc->hasStateFlag(TApplication::STATE_INITIALIZED),
			'initApplication() must set STATE_INITIALIZED after onInitComplete returns'
		);
	}

	public function testInitApplication_setsBothBootstrapAndInitializedFlags(): void
	{
		$acc = $this->newInitAccessor();

		$acc->pubInitApplication();

		// bootstrapDefaultModules is part of the initApplication sequence,
		// so both bits should be set when initApplication returns.
		$combined = TApplication::STATE_DEFAULT_MODULES_BOOTSTRAPPED
			| TApplication::STATE_INITIALIZED;
		$this->assertTrue($acc->hasStateFlag($combined));
		$this->assertSame($combined, $acc->getStateFlags());
	}

	public function testInitApplication_initializedFlagSetAfterOnInitComplete(): void
	{
		$acc = $this->newInitAccessor();

		// Capture the flag value INSIDE the onInitComplete handler: must be
		// false there (the flag is set AFTER onInitComplete returns).
		$flagDuringOnInitComplete = null;
		$acc->attachEventHandler('onInitComplete', function () use ($acc, &$flagDuringOnInitComplete) {
			$flagDuringOnInitComplete = $acc->hasStateFlag(TApplication::STATE_INITIALIZED);
		});

		$acc->pubInitApplication();

		$this->assertFalse(
			$flagDuringOnInitComplete,
			'STATE_INITIALIZED must NOT be set yet while onInitComplete handlers are running'
		);
		$this->assertTrue(
			$acc->hasStateFlag(TApplication::STATE_INITIALIZED),
			'STATE_INITIALIZED must be set once initApplication returns'
		);
	}

	// =======================================================================
	// onConfiguration — thin event raise
	// =======================================================================

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
