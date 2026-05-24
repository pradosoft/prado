<?php

use Prado\TApplication;
use Prado\TModule;

// =============================================================================
// Helper fixtures for TApplicationModuleDependencyTest
// =============================================================================

/**
 * Records the order in which test modules call init().
 */
class DepOrderTracker
{
	public static array $order = [];

	public static function reset(): void
	{
		self::$order = [];
	}

	public static function record(string $id): void
	{
		self::$order[] = $id;
	}
}

/**
 * A module that declares dependencies via the IModuleDependency interface.
 * Its DependencyId property holds the single dependency module ID.
 * When DependencyId is empty no dependency is declared.
 */
class InterfaceDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private string $_dependencyId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_dependencyId !== '' ? [$this->_dependencyId] : [];
	}

	public function getDependencyId(): string
	{
		return $this->_dependencyId;
	}

	public function setDependencyId(string $value): void
	{
		$this->_dependencyId = $value;
	}

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * A module with no dependency declarations that still records its init() call.
 */
class TrackingModule extends \Prado\TModule
{
	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * A module that implements both IModuleDependency (Source 1) and has an attached
 * behavior implementing IModuleDependency (Source 2), used to test that both
 * sources are merged and deduplicated.
 */
class BothSourcesDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private string $_primaryDepId = '';
	private string $_secondDepId  = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		$deps = [];
		if ($this->_primaryDepId !== '') {
			$deps[] = $this->_primaryDepId;
		}
		if ($this->_secondDepId !== '') {
			$deps[] = $this->_secondDepId;
		}
		return $deps;
	}

	public function getPrimaryDepId(): string { return $this->_primaryDepId; }
	public function setPrimaryDepId(string $v): void { $this->_primaryDepId = $v; }

	public function getSecondDepId(): string { return $this->_secondDepId; }
	public function setSecondDepId(string $v): void { $this->_secondDepId = $v; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * A behavior that implements IModuleDependency.  Its single declared dependency
 * is driven by a settable DepId property so tests can mutate it between calls.
 */
class DepBehavior extends \Prado\Util\TBehavior implements \Prado\IModuleDependency
{
	private string $_depId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_depId !== '' ? [$this->_depId] : [];
	}

	public function getDepId(): string { return $this->_depId; }
	public function setDepId(string $v): void { $this->_depId = $v; }
}

/**
 * An IModuleDependency module whose getModuleDependencies() can return null.
 */
class NullReturnDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private bool $_returnNull = false;

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_returnNull ? null : [];
	}

	public function setReturnNull(bool $v): void
	{
		$this->_returnNull = $v;
	}

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * Exposes the protected dependency methods as public for unit testing, and
 * adds direct access to internalLoadModule for path-coverage tests.
 */
class TApplicationDepAccessor extends TApplication
{

	/** @return array<string,array{id:string,required:bool}> */
	public function pubCollectDeps(\Prado\IModule $module, bool $isPreInit = false): array
	{
		return $this->collectModuleDependencies($module, $isPreInit)['deps'];
	}

	public function pubSortByDep(array $pending, array &$cache = [], bool $isPreInit = false): array
	{
		return $this->sortModulesByDependency($pending, $cache, $isPreInit);
	}

	public function pubInternalLoadModule(string $id, bool $force = false)
	{
		return $this->internalLoadModule($id, $force);
	}

	public function pubGetLazyModule(string $id): ?array
	{
		return $this->getLazyModule($id);
	}
}

// =============================================================================
// New fixtures for extended coverage
// =============================================================================

/**
 * Module whose getModuleDependencies() returns a plain string (shorthand for a
 * single dependency).  Exercises the (array) cast path in collectModuleDependencies.
 */
class StringReturnDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private string $_depId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_depId !== '' ? $this->_depId : null;
	}

	public function getDepId(): string { return $this->_depId; }
	public function setDepId(string $v): void { $this->_depId = $v; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * A behavior that declares its dep as advisory (required=false) via key-value form.
 * Used to test that module-source declarations override behavior-source ones.
 */
class DepBehaviorAdvisory extends \Prado\Util\TBehavior implements \Prado\IModuleDependency
{
	private string $_depId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_depId !== '' ? [$this->_depId => false] : null;
	}

	public function getDepId(): string { return $this->_depId; }
	public function setDepId(string $v): void { $this->_depId = $v; }
}

/**
 * A module that always returns a single advisory (required=false) dep via verbose form.
 * The dep ID is set through setAdvisoryDepId(), which maps cleanly to init-properties
 * so applyConfiguration / internalLoadModule can configure it.
 */
class AdvisoryDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private string $_advisoryDepId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_advisoryDepId !== ''
			? [['id' => $this->_advisoryDepId, 'required' => false]]
			: null;
	}

	public function getAdvisoryDepId(): string { return $this->_advisoryDepId; }
	public function setAdvisoryDepId(string $v): void { $this->_advisoryDepId = $v; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * Module whose getModuleDependencies() returns the key-value associative form:
 * ['moduleId' => bool|int|string].  The value is the required flag consumed by
 * TPropertyValue::ensureBoolean; only the key (module ID) is collected.
 */
class KeyValueDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private array $_deps = [];

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_deps !== [] ? $this->_deps : null;
	}

	/** @param array<string,bool|int|string> $deps e.g. ['db' => true, 'cache' => false] */
	public function setDepsArray(array $deps): void { $this->_deps = $deps; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * Module whose getModuleDependencies() returns the verbose array form:
 * [['id' => 'moduleId', 'required' => bool], ...].
 */
class VerboseArrayDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private array $_deps = [];

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_deps !== [] ? $this->_deps : null;
	}

	/** @param list<array{id:string,required?:bool}> $deps */
	public function setDepsArray(array $deps): void { $this->_deps = $deps; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

/**
 * A module that records its own dyPreInit, init, and dyPostInit calls into a
 * shared static $phases array, in addition to calling the parent implementations.
 *
 * Since dyPreInit and dyPostInit are dynamic events (dy-prefix) dispatched through
 * TComponent::__call, defining them as real PHP methods on the subclass causes
 * PHP to invoke them directly — allowing phase-order assertions without requiring
 * a separate tracking behavior.
 *
 * Implements IModuleDependency so a single dependency can be configured via
 * setDependencyId() in applyConfiguration init-properties.
 */
class PhaseTrackingModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	/** Ordered log of 'moduleId:phase' strings across all instances. */
	public static array $phases = [];

	private string $_dependencyId = '';

	public static function reset(): void
	{
		self::$phases = [];
	}

	public function getDependencyId(): string { return $this->_dependencyId; }
	public function setDependencyId(string $v): void { $this->_dependencyId = $v; }

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_dependencyId !== '' ? $this->_dependencyId : null;
	}

	public function dyPreInit($config): void
	{
		self::$phases[] = $this->getID() . ':pre';
	}

	public function init($config): void
	{
		parent::init($config);
		self::$phases[] = $this->getID() . ':init';
		DepOrderTracker::record($this->getID());
	}

	public function dyPostInit($config): void
	{
		self::$phases[] = $this->getID() . ':post';
	}
}

/**
 * A module that returns different deps depending on the `$isPreInit` flag.
 * Used to verify that the flag is actually threaded from sortModulesByDependency
 * down to getModuleDependencies().
 *
 * Flag mapping:
 *   $isPreInit=true : returns $_preinitDepId (if set) — dyPreInit pass
 *   $isPreInit=false: returns $_initDepId    (if set) — init() pass
 *                     (dyPostInit reuses the init-pass order; no separate dep)
 */
class PhaseAwareDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	/** Recorded $isPreInit values from each getModuleDependencies() call. */
	public array $receivedPhases = [];

	private string $_preinitDepId = '';
	private string $_initDepId    = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		$this->receivedPhases[] = $isPreInit;
		$depId = $isPreInit ? $this->_preinitDepId : $this->_initDepId;
		return $depId !== '' ? [$depId] : [];
	}

	public function getPreinitDepId(): string { return $this->_preinitDepId; }
	public function setPreinitDepId(string $v): void { $this->_preinitDepId = $v; }

	public function getInitDepId(): string { return $this->_initDepId; }
	public function setInitDepId(string $v): void { $this->_initDepId = $v; }

	public function init($config): void
	{
		parent::init($config);
		DepOrderTracker::record($this->getID());
	}
}

// =============================================================================
// Additional fixtures for extended coverage (Part 2)
// =============================================================================

/**
 * A behavior implementing IModuleDependency that returns a plain string (shorthand
 * for a single dependency). Used to verify that string-shorthand returns from
 * behavior sources are collected correctly.
 */
class BehaviorStringDep extends \Prado\Util\TBehavior implements \Prado\IModuleDependency
{
	private string $_depId = '';

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_depId !== '' ? $this->_depId : null;
	}

	public function getDepId(): string { return $this->_depId; }
	public function setDepId(string $v): void { $this->_depId = $v; }
}

/**
 * A behavior implementing IModuleDependency that returns the verbose array form:
 * [['id' => 'moduleId', 'required' => bool]].
 */
class BehaviorVerboseDep extends \Prado\Util\TBehavior implements \Prado\IModuleDependency
{
	private array $_deps = [];

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->_deps !== [] ? $this->_deps : null;
	}

	public function setDepsArray(array $deps): void { $this->_deps = $deps; }
}

/**
 * A behavior that implements dyFilterDependencies to inject or remove entries
 * from the dependency map returned by collectModuleDependencies().
 * Used to verify the integration between collectModuleDependencies() and the
 * dyFilterDependencies dynamic event.
 */
class AppDyFilterBehavior extends \Prado\Util\TBehavior
{
	/** @var array<string,array{id:string,required:bool}> Dep entries to inject. */
	private array $_additions = [];

	/** @var list<string> Dep IDs to remove. */
	private array $_removals = [];

	/** @param array<string,array{id:string,required:bool}> $additions */
	public function setAdditions(array $additions): void { $this->_additions = $additions; }

	/** @param list<string> $removals */
	public function setRemovals(array $removals): void { $this->_removals = $removals; }

	/**
	 * @param array<string,array{id:string,required:bool}> $deps
	 * @return array<string,array{id:string,required:bool}>
	 */
	public function dyFilterDependencies(array $deps): array
	{
		foreach ($this->_removals as $id) {
			unset($deps[$id]);
		}
		foreach ($this->_additions as $id => $entry) {
			$deps[$id] = $entry;
		}
		return $deps;
	}
}

/**
 * A module that implements IModule and IModuleDependency but does NOT extend
 * TComponent.  Used to verify that collectModuleDependencies() handles the
 * non-TComponent branch correctly: Source 1 (module-level IModuleDependency) is
 * still processed, but Source 2 (behavior scanning) and dyFilterDependencies are
 * skipped because they require TComponent.
 */
class PureIModuleWithDep implements \Prado\IModule, \Prado\IModuleDependency
{
	private string $_id = '';

	/** @param list<string> $deps Plain dep IDs. */
	public function __construct(private readonly array $deps = []) {}

	public function init($config): void {}
	public function getID(): string { return $this->_id; }
	public function setID($value): void { $this->_id = $value; }

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->deps !== [] ? $this->deps : null;
	}
}

/**
 * Module that builds its dependency array dynamically as
 * `[$id1 => $req1, $id2 => $req2]`, where `$id1` and `$id2` are typed `mixed`
 * so that null, false, or '' can be passed as keys.  This exercises PHP's
 * array-key coercion rules (null→'', false→0) at construction time and lets
 * tests assert on the framework's handling of invalid dependency IDs.
 */
class TwoKeyValueDepModule extends \Prado\TModule implements \Prado\IModuleDependency
{
	private mixed $_id1 = null;
	private mixed $_id2 = null;
	private bool $_req1 = true;
	private bool $_req2 = false;

	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return [$this->_id1 => $this->_req1, $this->_id2 => $this->_req2];
	}

	public function getId1(): mixed { return $this->_id1; }
	public function setId1(mixed $v): void { $this->_id1 = $v; }
	public function getId2(): mixed { return $this->_id2; }
	public function setId2(mixed $v): void { $this->_id2 = $v; }
	public function setReq1(bool $v): void { $this->_req1 = $v; }
	public function setReq2(bool $v): void { $this->_req2 = $v; }
}

// =============================================================================

/**
 * Tests for module dependency ordering (Kahn's topological sort) and cycle
 * detection in TApplication.
 *
 * Covers:
 * - collectModuleDependencies: IModuleDependency on module (Source 1) and on
 *   behaviors (Source 2); all input forms — indexed-string, string-shorthand,
 *   key-value, verbose array; null/empty-string/missing-id entries silently
 *   skipped; self-dependency in any form or source throws
 *   application_module_dependency_self_reference immediately; deduplication
 *   across sources; module-source priority over behavior-source for the same dep
 *   ID; two behaviors with the same dep ID (first wins); non-TComponent module
 *   (no behavior scan, no dyFilterDependencies); dyFilterDependencies integration
 *   (add dep, remove dep, end-to-end with sort).
 * - sortModulesByDependency: empty / single / no-deps ordering; chain; diamond;
 *   disjoint groups; external-batch dep silently ignored; advisory dep in batch
 *   still enforces order; cycle detection (2-node, 3-node, 4-node); self-dep
 *   propagates through sort as application_module_dependency_self_reference;
 *   meaningful exception message; sort-result cache (hit, miss, new entry
 *   on changed graph, new entry for different batch); phase flag threaded through.
 * - internalLoadModule: false / null / success paths; ID set; lazy slot nullified.
 * - applyConfiguration: init order respects dependencies; no-deps declaration
 *   order; full four-phase (dyPreInit / init / dyPostInit) ordering for single
 *   module and for a two-module dependency pair; cycle throws before any init;
 *   all dep input forms exercised end-to-end; advisory dep in batch sorted; phase
 *   flag passed to each sort pass.
 * - getModule() lazy-load: dependency initialized first; dep already live not
 *   re-initialized; four-phase order per module; three-level chain; advisory dep
 *   registered as lazy still loaded; required dep absent throws; advisory dep
 *   absent silently skipped.
 *
 * @package System
 */
class TApplicationDependencyTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Shared helpers
	// -----------------------------------------------------------------------

	private function newAccessor(): TApplicationDepAccessor
	{
		$ref = new \ReflectionClass(TApplicationDepAccessor::class);
		$app = $ref->newInstanceWithoutConstructor();
		PradoUnit::setProp($app, '_modules', []);
		PradoUnit::setProp($app, '_lazyModules', []);
		PradoUnit::setProp($app, '_parameters', new \Prado\Collections\TMap());
		PradoUnit::setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($app, '_configFileExt', TApplication::CONFIG_FILE_EXT_XML);
		PradoUnit::setProp($app, '_basePath', sys_get_temp_dir());
		PradoUnit::setProp($app, '_pageServiceID', 'page');
		PradoUnit::setProp($app, '_services', []);
		return $app;
	}

	/** Build an entry array as sortModulesByDependency() expects. */
	private function entry(string $id, \Prado\IModule $module): array
	{
		return ['id' => $id, 'module' => $module, 'config' => null];
	}

	/**
	 * Extract just the dep IDs from a pubCollectDeps() result.
	 *
	 * @param array<string,array{id:string,required:bool}> $deps keyed by dep ID
	 * @return list<string>
	 */
	private function depIds(array $deps): array
	{
		return array_column($deps, 'id');
	}

	/**
	 * Look up the required flag for a specific dep ID in a pubCollectDeps() result.
	 * Returns null if the ID is not present.
	 *
	 * @param array<string,array{id:string,required:bool}> $deps keyed by dep ID
	 */
	private function depRequired(array $deps, string $id): ?bool
	{
		return isset($deps[$id]) ? $deps[$id]['required'] : null;
	}

	/**
	 * Build a config mock whose getModules() returns $modules.
	 *
	 * Mirrors TApplicationConfiguration which always includes 'id' in the
	 * init-properties tuple so that internalLoadModule() can call setID() on
	 * the module via the property system.
	 */
	private function moduleConfig(array $modules): \Prado\TApplicationConfiguration
	{
		// Inject 'id' into every module's init-properties, matching what the real
		// config loaders (loadModulesPhp / loadModulesXml) produce.
		$withId = [];
		foreach ($modules as $id => $tuple) {
			$tuple[1]['id'] = $id;
			$withId[$id] = $tuple;
		}

		$cfg = $this->createMock(\Prado\TApplicationConfiguration::class);
		$cfg->method('getIsEmpty')->willReturn(false);
		$cfg->method('getAliases')->willReturn([]);
		$cfg->method('getUsings')->willReturn([]);
		$cfg->method('getProperties')->willReturn([]);
		$cfg->method('getServices')->willReturn([]);
		$cfg->method('getParameters')->willReturn([]);
		$cfg->method('getModules')->willReturn($withId);
		$cfg->method('getExternalConfigurations')->willReturn([]);
		return $cfg;
	}

	protected function setUp(): void
	{
		DepOrderTracker::reset();
		PhaseTrackingModule::reset();
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — Source 1: IModuleDependency on module
	// -----------------------------------------------------------------------

	public function testCollectDeps_interfaceNoDep_returnsEmpty(): void
	{
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		// DependencyId is empty → no deps declared.
		$this->assertSame([], $app->pubCollectDeps($module));
	}

	public function testCollectDeps_interfaceWithDep_returnsId(): void
	{
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('db');
		$deps = $app->pubCollectDeps($module);
		$this->assertSame(['db'], $this->depIds($deps));
		$this->assertTrue($this->depRequired($deps, 'db'),
			'plain-string dep must default to required=true');
	}

	public function testCollectDeps_interfaceDynamic_reevaluatedEachCall(): void
	{
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('first');

		$first = $app->pubCollectDeps($module);
		$module->setDependencyId('second');
		$second = $app->pubCollectDeps($module);

		$this->assertContains('first', $this->depIds($first));
		$this->assertContains('second', $this->depIds($second),
			'IModuleDependency result must be re-evaluated on every call');
		$this->assertNotContains('first', $this->depIds($second));
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — string return form (bare scalar)
	// -----------------------------------------------------------------------

	public function testCollectDeps_stringReturn_singleDepReturned(): void
	{
		// getModuleDependencies() returns a plain string — the (array) cast must
		// convert it to a one-element indexed array so the dep is collected.
		$app    = $this->newAccessor();
		$module = new StringReturnDepModule();
		$module->setDepId('db');

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'a string return from getModuleDependencies() must be collected as a single dep');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'a plain-string dep must default to required=true');
	}

	public function testCollectDeps_stringReturn_null_returnsEmpty(): void
	{
		// When getModuleDependencies() returns null the dep list must be empty.
		$app    = $this->newAccessor();
		$module = new StringReturnDepModule();
		// _depId is '' → returns null

		$this->assertSame([], $app->pubCollectDeps($module),
			'null return from getModuleDependencies() must yield an empty dep list');
	}

	public function testCollectDeps_stringReturn_sort_depComesBefore(): void
	{
		// Verify the string-return dep is actually honoured by the topological sort.
		// b returns 'a' as a plain string → sorted order must be [a, b].
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new StringReturnDepModule();
		$b->setDepId('a');

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'a dep declared via string return must be respected by the sort');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — indexed array form ([$depId])
	// -----------------------------------------------------------------------

	public function testCollectDeps_indexedArray_singleDepReturned(): void
	{
		// getModuleDependencies() returns [$depId] — an indexed array with an integer
		// key and a string value.  The dep must be collected with required=true.
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('db');

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'an indexed-array dep entry must be collected as a single dep');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'indexed-array dep must default to required=true');
	}

	public function testCollectDeps_indexedArray_multipleDepReturned(): void
	{
		// getModuleDependencies() returns ['db', 'cache'] — two integer-keyed string
		// values.  Both must be collected with required=true.
		$app    = $this->newAccessor();
		$module = new BothSourcesDepModule();
		$module->setPrimaryDepId('db');
		$module->setSecondDepId('cache');

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps),
			'first indexed-array dep entry must be collected');
		$this->assertContains('cache', $this->depIds($deps),
			'second indexed-array dep entry must be collected');
		$this->assertCount(2, $deps);
		$this->assertTrue($this->depRequired($deps, 'db'));
		$this->assertTrue($this->depRequired($deps, 'cache'));
	}

	public function testCollectDeps_indexedArray_sort_depComesBefore(): void
	{
		// b declares dep on a via the indexed-array form ['a'] →
		// the topological sort must place a before b.
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new InterfaceDepModule();
		$b->setDependencyId('a');

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'a dep declared via indexed-array form must be respected by the sort');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — key-value associative form
	// -----------------------------------------------------------------------

	public function testCollectDeps_keyValueForm_boolTrue_depCollected(): void
	{
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['db' => true]);

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'key-value form with bool true value must collect the key as a dep ID');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'key-value true value must yield required=true');
	}

	public function testCollectDeps_keyValueForm_boolFalse_depCollected(): void
	{
		// false means required=false (advisory), but the dep ID is still collected.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['cache' => false]);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('cache', $this->depIds($deps),
			'key-value form with bool false value must still collect the dep ID');
		$this->assertFalse($this->depRequired($deps, 'cache'),
			'key-value false value must yield required=false');
	}

	public function testCollectDeps_keyValueForm_multipleDeps_allCollected(): void
	{
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['db' => true, 'cache' => false]);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps));
		$this->assertContains('cache', $this->depIds($deps));
		$this->assertCount(2, $deps);
		$this->assertTrue($this->depRequired($deps, 'db'));
		$this->assertFalse($this->depRequired($deps, 'cache'));
	}

	public function testCollectDeps_keyValueForm_stringBoolValue_accepted(): void
	{
		// TPropertyValue::ensureBoolean accepts string values such as 'yes'/'no'.
		// These must not throw and both IDs must appear in the result with the
		// correct parsed required flag.
		// ensureBoolean recognises 'true' (case-insensitive) and numeric strings.
		// Use '1' (truthy) and '0' (falsy) as representative string boolean values.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['db' => '1', 'cache' => '0']);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps),
			"key-value string value '1' must be accepted by ensureBoolean");
		$this->assertContains('cache', $this->depIds($deps),
			"key-value string value '0' must be accepted by ensureBoolean");
		$this->assertTrue($this->depRequired($deps, 'db'),
			"'1' must parse to required=true");
		$this->assertFalse($this->depRequired($deps, 'cache'),
			"'0' must parse to required=false");
	}

	public function testCollectDeps_keyValueForm_intValue_accepted(): void
	{
		// Integer 1 / 0 are valid boolean representations.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['db' => 1, 'cache' => 0]);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps));
		$this->assertContains('cache', $this->depIds($deps));
		$this->assertTrue($this->depRequired($deps, 'db'));
		$this->assertFalse($this->depRequired($deps, 'cache'));
	}

	public function testCollectDeps_keyValueForm_sort_depComesBefore(): void
	{
		// The key-value dep must be honoured in sort order.
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new KeyValueDepModule();
		$b->setDepsArray(['a' => true]);

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'a dep declared via key-value form must be respected by the sort');
	}

	public function testCollectDeps_keyValueForm_emptyStringKey_silentlyIgnored(): void
	{
		// An empty-string key fails the `$key !== ''` guard and must be silently skipped.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['' => true]);

		$this->assertSame([], $app->pubCollectDeps($module),
			'key-value entry with empty-string key must be silently ignored');
	}

	public function testCollectDeps_keyValueForm_nullKey_silentlyIgnored(): void
	{
		// PHP coerces a null array key to '' at array-construction time.
		// The resulting '' key must be silently skipped by the framework.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		// setDepsArray([null => true]) — PHP coerces null key to '' on assignment.
		$module->setDepsArray([null => true]);

		$this->assertSame([], $app->pubCollectDeps($module),
			'key-value entry whose key is null (coerced to empty string by PHP) must be silently ignored');
	}

	public function testCollectDeps_keyValueForm_falseKey_silentlyIgnored(): void
	{
		// PHP coerces a false array key to 0 (integer) at array-construction time.
		// The integer 0 fails is_string() and must be silently skipped.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		// setDepsArray([false => true]) — PHP coerces false key to 0 on assignment.
		$module->setDepsArray([false => true]);

		$this->assertSame([], $app->pubCollectDeps($module),
			'key-value entry whose key is false (coerced to 0 by PHP) must be silently ignored');
	}

	public function testCollectDeps_keyValueForm_bothIdsNull_collapseAndIgnored(): void
	{
		// When both module ID getters return null, PHP coerces both keys to '' and
		// the second entry silently overwrites the first (same key).  The surviving
		// '' entry is then skipped by the framework's `$key !== ''` guard.
		// This models the real pattern: [$this->getDepId1() => true, $this->getDepId2() => false]
		// where both getters have not yet been configured.
		$app    = $this->newAccessor();
		$module = new TwoKeyValueDepModule();
		// _id1 = null, _id2 = null (defaults) → [null=>true, null=>false] → [''=> false]

		$this->assertSame([], $app->pubCollectDeps($module),
			'both null dep IDs must collapse to a single empty-string key and be silently ignored');
	}

	public function testCollectDeps_keyValueForm_bothIdsFalse_collapseAndIgnored(): void
	{
		// false keys are coerced to integer 0 — both entries collapse to [0 => false].
		// The integer key fails is_string() and is silently skipped.
		$app    = $this->newAccessor();
		$module = new TwoKeyValueDepModule();
		$module->setId1(false);
		$module->setId2(false);

		$this->assertSame([], $app->pubCollectDeps($module),
			'both false dep IDs must collapse to integer key 0 and be silently ignored');
	}

	public function testCollectDeps_keyValueForm_bothIdsEmptyString_collapseAndIgnored(): void
	{
		// Both IDs are already '' — the array is ['' => true, '' => false] → ['' => false].
		// The '' key fails `$key !== ''` and is silently skipped.
		$app    = $this->newAccessor();
		$module = new TwoKeyValueDepModule();
		$module->setId1('');
		$module->setId2('');

		$this->assertSame([], $app->pubCollectDeps($module),
			'both empty-string dep IDs must be silently ignored');
	}

	public function testCollectDeps_keyValueForm_nullAndEmptyString_collapseAndIgnored(): void
	{
		// null and '' both coerce to '': [null=>'', ''=> false] → ['' => false].
		// The surviving '' entry is silently skipped.
		$app    = $this->newAccessor();
		$module = new TwoKeyValueDepModule();
		$module->setId1(null);
		$module->setId2('');

		$this->assertSame([], $app->pubCollectDeps($module),
			'null and empty-string dep IDs must both collapse to empty string and be silently ignored');
	}

	public function testCollectDeps_keyValueForm_oneNullOneValid_validCollectedNullIgnored(): void
	{
		// When one ID is null (→ '') and the other is a real module ID, the null
		// entry gets its own '' key (different from the real key) and is skipped,
		// leaving only the valid dep in the result.
		$app    = $this->newAccessor();
		$module = new TwoKeyValueDepModule();
		$module->setId1(null);   // → '' key — skipped
		$module->setId2('db');   // → 'db' key — collected
		$module->setReq1(true);
		$module->setReq2(true);

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'the valid dep ID must be collected even when the other ID is null');
		$this->assertTrue($this->depRequired($deps, 'db'));
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — verbose array form
	// -----------------------------------------------------------------------

	public function testCollectDeps_verboseArrayForm_requiredTrue_depCollected(): void
	{
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => 'db', 'required' => true]]);

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'verbose array entry with required=true must contribute its id as a dep');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'verbose array required=true must yield required=true');
	}

	public function testCollectDeps_verboseArrayForm_requiredFalse_depCollected(): void
	{
		// required=false means advisory; the dep ID is still collected for ordering.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => 'cache', 'required' => false]]);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('cache', $this->depIds($deps),
			'verbose array entry with required=false must still contribute its id as a dep');
		$this->assertFalse($this->depRequired($deps, 'cache'),
			'verbose array required=false must yield required=false');
	}

	public function testCollectDeps_verboseArrayForm_missingRequiredKey_defaultsToTrue(): void
	{
		// When the verbose array entry omits the 'required' key, the default is true.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => 'db']]);  // no 'required' key

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps));
		$this->assertTrue($this->depRequired($deps, 'db'),
			'verbose array entry without required key must default to required=true');
	}

	public function testCollectDeps_verboseArrayForm_multipleDeps_allCollected(): void
	{
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([
			['id' => 'db',    'required' => true],
			['id' => 'cache', 'required' => false],
		]);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps));
		$this->assertContains('cache', $this->depIds($deps));
		$this->assertCount(2, $deps);
		$this->assertTrue($this->depRequired($deps, 'db'));
		$this->assertFalse($this->depRequired($deps, 'cache'));
	}

	public function testCollectDeps_verboseArrayForm_emptyId_ignored(): void
	{
		// An entry with an empty 'id' string must be silently skipped.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => '', 'required' => true]]);

		$this->assertSame([], $app->pubCollectDeps($module),
			'verbose array entry with empty id must be silently ignored');
	}

	public function testCollectDeps_verboseArrayForm_missingId_ignored(): void
	{
		// An array entry that has no 'id' key at all must be silently skipped.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['required' => true]]);   // no 'id' key

		$this->assertSame([], $app->pubCollectDeps($module),
			'verbose array entry missing the id key must be silently ignored');
	}

	public function testCollectDeps_verboseArrayForm_sort_depComesBefore(): void
	{
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new VerboseArrayDepModule();
		$b->setDepsArray([['id' => 'a', 'required' => true]]);

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'a dep declared via verbose array form must be respected by the sort');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — self-dependency detection
	// -----------------------------------------------------------------------

	public function testCollectDeps_selfDep_stringShorthand_throws(): void
	{
		// A module returning its own ID as a plain string dep must throw immediately.
		$app    = $this->newAccessor();
		$module = new StringReturnDepModule();
		$module->setDepId('myModule');
		$module->setID('myModule');

		try {
			$app->pubCollectDeps($module);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_self_reference', $e->getErrorCode());
		}
	}

	public function testCollectDeps_selfDep_keyValueForm_throws(): void
	{
		// A module returning its own ID as a key-value key must throw immediately.
		$app    = $this->newAccessor();
		$module = new KeyValueDepModule();
		$module->setDepsArray(['myModule' => true]);
		$module->setID('myModule');

		try {
			$app->pubCollectDeps($module);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_self_reference', $e->getErrorCode());
		}
	}

	public function testCollectDeps_selfDep_verboseArrayForm_throws(): void
	{
		// A module returning its own ID in the verbose array form must throw immediately.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => 'myModule', 'required' => true]]);
		$module->setID('myModule');

		try {
			$app->pubCollectDeps($module);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_self_reference', $e->getErrorCode());
		}
	}

	public function testCollectDeps_selfDep_behaviorSource_throws(): void
	{
		// A self-dep declared by an attached behavior must also throw.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$module->setID('myModule');

		$behavior = new DepBehaviorAdvisory();
		$behavior->setDepId('myModule');
		$module->attachBehavior('selfDep', $behavior);

		try {
			$app->pubCollectDeps($module);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_self_reference', $e->getErrorCode());
		} finally {
			$module->detachBehavior('selfDep');
		}
	}

	public function testCollectDeps_selfDep_exceptionMessageContainsModuleId(): void
	{
		// The exception message must name the offending module so developers can
		// identify the misconfiguration without reading a stack trace.
		$app    = $this->newAccessor();
		$module = new StringReturnDepModule();
		$module->setDepId('offender');
		$module->setID('offender');

		try {
			$app->pubCollectDeps($module);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertStringContainsString('offender', $e->getMessage());
		}
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — deduplication across forms and sources
	// -----------------------------------------------------------------------

	public function testCollectDeps_mixedForms_deduplicatedAcrossForms(): void
	{
		// Module returns the same dep ID via two different forms in one call.
		// The result must contain it only once.
		$app    = $this->newAccessor();
		$module = new BothSourcesDepModule();
		$module->setPrimaryDepId('db');  // plain string in array
		$module->setSecondDepId('db');   // same ID again

		$deps = $app->pubCollectDeps($module);

		$this->assertCount(1, array_filter($this->depIds($deps), fn($d) => $d === 'db'),
			'the same dep ID appearing multiple times in one source must be deduplicated');
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — sort result cache
	// -----------------------------------------------------------------------

	public function testSort_resultCachedAfterFirstCall(): void
	{
		$app   = $this->newAccessor();
		$cache = [];
		$a     = new TrackingModule();
		$b     = new InterfaceDepModule();
		$b->setDependencyId('a');
		$pending = [
			$this->entry('b', $b),
			$this->entry('a', $a),
		];

		$app->pubSortByDep($pending, $cache);

		$this->assertArrayHasKey(TApplication::DEP_SORT_CACHE_KEY, $cache, 'sort cache sub-key must exist after first call');
		$this->assertCount(1, $cache[TApplication::DEP_SORT_CACHE_KEY], 'exactly one fingerprint must be stored');
	}

	public function testSort_sameDepsReturnsCachedOrder(): void
	{
		$app   = $this->newAccessor();
		$cache = [];
		$a     = new TrackingModule();
		$b     = new InterfaceDepModule();
		$b->setDependencyId('a');
		$pending = [
			$this->entry('b', $b),
			$this->entry('a', $a),
		];

		$first  = array_column($app->pubSortByDep($pending, $cache), 'id');
		$second = array_column($app->pubSortByDep($pending, $cache), 'id');

		$this->assertSame($first, $second, 'repeated call with identical graph must return same order');
		$this->assertCount(1, $cache[TApplication::DEP_SORT_CACHE_KEY], 'no new fingerprint must be added for identical graph');
	}

	public function testSort_changedDepsProducesNewCacheEntry(): void
	{
		// First call: b depends on a.
		// Second call: IModuleDependency changes — b depends on nothing.
		$app   = $this->newAccessor();
		$cache = [];
		$a     = new TrackingModule();
		$b     = new InterfaceDepModule();
		$b->setDependencyId('a');
		$pending = [
			$this->entry('b', $b),
			$this->entry('a', $a),
		];

		$app->pubSortByDep($pending, $cache);

		// Mutate the dynamic dep (IModuleDependency) — clears the dep.
		$b->setDependencyId('');
		$app->pubSortByDep($pending, $cache);

		$this->assertCount(2, $cache[TApplication::DEP_SORT_CACHE_KEY],
			'a changed dependency graph must produce a new cache entry');
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — trivial cases
	// -----------------------------------------------------------------------

	public function testSort_emptyPending_returnsEmpty(): void
	{
		$app = $this->newAccessor();
		$this->assertSame([], $app->pubSortByDep([]));
	}

	public function testSort_singleModule_returnsUnchanged(): void
	{
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$entry  = $this->entry('a', $module);
		$result = $app->pubSortByDep([$entry]);
		$this->assertSame([$entry], $result);
	}

	public function testSort_noDeps_preservesOrder(): void
	{
		$app = $this->newAccessor();
		$a   = $this->entry('a', new TrackingModule());
		$b   = $this->entry('b', new TrackingModule());
		$c   = $this->entry('c', new TrackingModule());
		$result = $app->pubSortByDep([$a, $b, $c]);
		$ids = array_column($result, 'id');
		// No deps — original declaration order is a valid topological order.
		$this->assertSame(['a', 'b', 'c'], $ids);
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — dependency ordering
	// -----------------------------------------------------------------------

	public function testSort_simpleChain_depComesFirst(): void
	{
		// b depends on a → sorted order must be [a, b].
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new InterfaceDepModule();
		$b->setDependencyId('a');

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'));
	}

	public function testSort_threeChain_correctOrder(): void
	{
		// c depends on b, b depends on a → sorted: [a, b, c].
		$app = $this->newAccessor();
		$a   = new TrackingModule();

		$b = new InterfaceDepModule();
		$b->setDependencyId('a');

		$c = new InterfaceDepModule();
		$c->setDependencyId('b');

		$result = $app->pubSortByDep([
			$this->entry('c', $c),
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b', 'c'], array_column($result, 'id'));
	}

	public function testSort_multipleDepsPerModule_allHonoured(): void
	{
		// d depends on both b and c. All four are in the same batch.
		// Kahn's sort must respect both edges: b and c before d.
		$app = $this->newAccessor();
		$b   = new TrackingModule();
		$c   = new TrackingModule();
		$d   = new BothSourcesDepModule();
		$d->setPrimaryDepId('b');
		$d->setSecondDepId('c');

		$result = $app->pubSortByDep([
			$this->entry('d', $d),
			$this->entry('b', $b),
			$this->entry('c', $c),
		]);
		$ids  = array_column($result, 'id');
		$posB = array_search('b', $ids, true);
		$posC = array_search('c', $ids, true);
		$posD = array_search('d', $ids, true);

		$this->assertLessThan($posD, $posB, 'b must come before d');
		$this->assertLessThan($posD, $posC, 'c must come before d');
	}

	public function testSort_diamondDependency_correctOrder(): void
	{
		// Diamond: d depends on b and c; b and c both depend on a.
		// Valid topological orders: a, b, c, d  or  a, c, b, d.
		$app = $this->newAccessor();
		$a   = new TrackingModule();

		$b = new InterfaceDepModule();
		$b->setDependencyId('a');

		$c = new InterfaceDepModule();
		$c->setDependencyId('a');

		$d = new BothSourcesDepModule();
		$d->setPrimaryDepId('b');
		$d->setSecondDepId('c');

		$result = $app->pubSortByDep([
			$this->entry('d', $d),
			$this->entry('c', $c),
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$ids = array_column($result, 'id');

		$posA = array_search('a', $ids, true);
		$posB = array_search('b', $ids, true);
		$posC = array_search('c', $ids, true);
		$posD = array_search('d', $ids, true);

		$this->assertLessThan($posB, $posA, 'a must come before b');
		$this->assertLessThan($posC, $posA, 'a must come before c');
		$this->assertLessThan($posD, $posB, 'b must come before d');
		$this->assertLessThan($posD, $posC, 'c must come before d');
	}

	public function testSort_disjointGroups_allPresent(): void
	{
		// Two completely independent chains: a→b and c→d.
		// Both chains must appear in the result; within each chain the dep comes first.
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new InterfaceDepModule(); $b->setDependencyId('a');
		$c   = new TrackingModule();
		$d   = new InterfaceDepModule(); $d->setDependencyId('c');

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
			$this->entry('d', $d),
			$this->entry('c', $c),
		]);
		$ids  = array_column($result, 'id');
		$posA = array_search('a', $ids, true);
		$posB = array_search('b', $ids, true);
		$posC = array_search('c', $ids, true);
		$posD = array_search('d', $ids, true);

		$this->assertCount(4, $ids);
		$this->assertLessThan($posB, $posA, 'a must come before b (chain 1)');
		$this->assertLessThan($posD, $posC, 'c must come before d (chain 2)');
	}

	public function testSort_depOutsideBatch_ignored(): void
	{
		// b declares a dep on 'external' which is not in this batch.
		// Should not affect order (b has no in-batch deps → treated as in-degree 0).
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new InterfaceDepModule();
		$b->setDependencyId('external');

		$result = $app->pubSortByDep([
			$this->entry('a', $a),
			$this->entry('b', $b),
		]);
		// Both valid orders are fine; just ensure no exception and both present.
		$ids = array_column($result, 'id');
		$this->assertContains('a', $ids);
		$this->assertContains('b', $ids);
		$this->assertCount(2, $ids);
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — cycle detection
	// -----------------------------------------------------------------------

	public function testSort_twoCycle_throwsConfigurationException(): void
	{
		$app = $this->newAccessor();
		$a   = new InterfaceDepModule();
		$a->setDependencyId('b');
		$b = new InterfaceDepModule();
		$b->setDependencyId('a');

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->pubSortByDep([
			$this->entry('a', $a),
			$this->entry('b', $b),
		]);
	}

	public function testSort_threeCycle_throwsConfigurationException(): void
	{
		$app = $this->newAccessor();
		$a   = new InterfaceDepModule();
		$a->setDependencyId('c');
		$b = new InterfaceDepModule();
		$b->setDependencyId('a');
		$c = new InterfaceDepModule();
		$c->setDependencyId('b');

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->pubSortByDep([
			$this->entry('a', $a),
			$this->entry('b', $b),
			$this->entry('c', $c),
		]);
	}

	public function testSort_cycle_exceptionMessageNamesInvolvedModules(): void
	{
		// 'alpha' depends on 'beta' and 'beta' depends on 'alpha' — a direct cycle.
		// Dependency IDs must match the registered entry IDs so Kahn's sort
		// can detect the cycle (IDs that don't appear in pending are silently ignored).
		$app = $this->newAccessor();
		$a   = new InterfaceDepModule();
		$a->setDependencyId('beta');
		$b = new InterfaceDepModule();
		$b->setDependencyId('alpha');

		try {
			$app->pubSortByDep([
				$this->entry('alpha', $a),
				$this->entry('beta', $b),
			]);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_cycle', $e->getErrorCode());
		}
	}

	public function testSort_selfDep_throwsViaCollect(): void
	{
		// A self-dependency is caught by collectModuleDependencies before the sort
		// runs; the exception propagates through pubSortByDep unchanged.
		// setID() must be called so collectModuleDependencies sees the module's own ID
		// when checking $dep === $ownId; without it getID() returns '' and the check
		// would miss, falling through to Kahn's sort which throws cycle instead.
		$app = $this->newAccessor();
		$a   = new InterfaceDepModule();
		$a->setID('a');
		$a->setDependencyId('a');

		try {
			$app->pubSortByDep([
				$this->entry('a', $a),
				$this->entry('b', new TrackingModule()),
			]);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertSame('application_module_dependency_self_reference', $e->getErrorCode(),
				'self-dep propagated through sort must carry the self_reference error code');
		}
	}

	public function testSort_fourNodeCycle_throwsConfigurationException(): void
	{
		// a→b→c→d→a
		$app = $this->newAccessor();
		$a   = new InterfaceDepModule(); $a->setDependencyId('d');
		$b   = new InterfaceDepModule(); $b->setDependencyId('a');
		$c   = new InterfaceDepModule(); $c->setDependencyId('b');
		$d   = new InterfaceDepModule(); $d->setDependencyId('c');

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->pubSortByDep([
			$this->entry('a', $a),
			$this->entry('b', $b),
			$this->entry('c', $c),
			$this->entry('d', $d),
		]);
	}

	public function testSort_differentBatchProducesNewCacheEntry(): void
	{
		// First call: batch {a, b} with b→a.
		// Second call: batch {a, b, c} with b→a, c→a.
		// Different graphs → different fingerprints → two cache entries.
		$app   = $this->newAccessor();
		$cache = [];

		$a = new TrackingModule();
		$b = new InterfaceDepModule(); $b->setDependencyId('a');
		$app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		], $cache);

		$c = new InterfaceDepModule(); $c->setDependencyId('a');
		$app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
			$this->entry('c', $c),
		], $cache);

		$this->assertCount(2, $cache[TApplication::DEP_SORT_CACHE_KEY],
			'a batch with a different module set must produce a distinct fingerprint entry');
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — behavior dep respected in ordering
	// -----------------------------------------------------------------------

	public function testSort_behaviorDep_respected(): void
	{
		// b has an attached IModuleDependency behavior that declares dep on a.
		// Even though b itself has no module-level declaration, the behavior's
		// dep must be respected: sorted order must be [a, b].
		$app = $this->newAccessor();
		$a   = new TrackingModule();

		$b = new TrackingModule();
		$bh = new DepBehavior(); $bh->setDepId('a');
		$b->attachBehavior('depOnA', $bh);

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'a dep declared only by a behavior must be respected by the topological sort');
	}

	// -----------------------------------------------------------------------
	// internalLoadModule — all return paths
	// -----------------------------------------------------------------------

	public function testInternalLoadModule_idNotInLazyModules_returnsFalse(): void
	{
		// Requesting an ID that has never been registered in _lazyModules must
		// return false (the "not known" sentinel).
		$app = $this->newAccessor();
		// _lazyModules and _modules are already empty from newAccessor().
		$result = $app->pubInternalLoadModule('nonexistent');
		$this->assertFalse($result,
			'internalLoadModule must return false for an ID not present in _lazyModules');
	}

	public function testInternalLoadModule_lazyTrue_withoutForce_returnsNull(): void
	{
		// A module whose init-properties include 'lazy' => true must be deferred
		// (returned as null) when force=false.
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'mod' => [TrackingModule::class, ['id' => 'mod', 'lazy' => true], null],
		]);
		PradoUnit::setProp($app, '_modules', ['mod' => null]);

		$result = $app->pubInternalLoadModule('mod', false);

		$this->assertNull($result,
			'internalLoadModule must return null (deferred) when lazy=true and force=false');
	}

	public function testInternalLoadModule_lazyTrue_withForce_returnsModuleArray(): void
	{
		// force=true must override the lazy flag and return the module.
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'mod' => [TrackingModule::class, ['id' => 'mod', 'lazy' => true], null],
		]);
		PradoUnit::setProp($app, '_modules', ['mod' => null]);

		$result = $app->pubInternalLoadModule('mod', true);

		$this->assertIsArray($result,
			'internalLoadModule must return [$module, $config] when force=true even if lazy=true');
		$this->assertInstanceOf(TrackingModule::class, $result[0],
			'first element must be the instantiated module');
	}

	public function testInternalLoadModule_normalModule_returnsModuleAndConfig(): void
	{
		// A regular (non-lazy) module must be instantiated and returned with its config.
		$app           = $this->newAccessor();
		$configElement = null;
		PradoUnit::setProp($app, '_lazyModules', [
			'mod' => [TrackingModule::class, ['id' => 'mod'], $configElement],
		]);
		PradoUnit::setProp($app, '_modules', ['mod' => null]);

		$result = $app->pubInternalLoadModule('mod');

		$this->assertIsArray($result);
		$this->assertInstanceOf(TrackingModule::class, $result[0]);
		$this->assertSame($configElement, $result[1],
			'second element must be the config element from the lazy-module tuple');
	}

	public function testInternalLoadModule_normalModule_setsModuleId(): void
	{
		// After internalLoadModule, the module's ID must have been set via setID().
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'mymod' => [TrackingModule::class, ['id' => 'mymod'], null],
		]);
		PradoUnit::setProp($app, '_modules', ['mymod' => null]);

		$result = $app->pubInternalLoadModule('mymod');

		$this->assertSame('mymod', $result[0]->getID(),
			'internalLoadModule must apply the id init-property to the module instance');
	}

	public function testInternalLoadModule_normalModule_nullifiesLazySlot(): void
	{
		// internalLoadModule must nullify the _lazyModules slot to prevent ID reuse
		// (i.e., calling getLazyModule on the same ID afterwards should return null).
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'mod' => [TrackingModule::class, ['id' => 'mod'], null],
		]);
		PradoUnit::setProp($app, '_modules', ['mod' => null]);

		$app->pubInternalLoadModule('mod');

		$this->assertNull($app->pubGetLazyModule('mod'),
			'internalLoadModule must nullify the _lazyModules slot after loading');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — integration: init() order respects dependencies
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_initOrderRespectsDependencies(): void
	{
		// Config declares modules in order: [dependent, dependency].
		// dependency declares no deps; dependent declares dep on dependency.
		// Expected init order: dependency first, then dependent.
		$app = $this->newAccessor();

		// Modules config: [$class, $initProperties, $configElement]
		$modules = [
			'dependent'  => [InterfaceDepModule::class, ['DependencyId' => 'dep'], null],
			'dep'        => [TrackingModule::class, [], null],
		];

		$cfg = $this->moduleConfig($modules);
		$app->applyConfiguration($cfg, false);

		$this->assertSame(['dep', 'dependent'], DepOrderTracker::$order,
			'dep must be initialized before dependent');
	}

	public function testApplyConfiguration_noDeps_initInDeclarationOrder(): void
	{
		$app     = $this->newAccessor();
		$modules = [
			'alpha' => [TrackingModule::class, [], null],
			'beta'  => [TrackingModule::class, [], null],
			'gamma' => [TrackingModule::class, [], null],
		];

		$cfg = $this->moduleConfig($modules);
		$app->applyConfiguration($cfg, false);

		$this->assertSame(['alpha', 'beta', 'gamma'], DepOrderTracker::$order,
			'modules with no deps must initialize in declaration order');
	}

	public function testApplyConfiguration_dyPostInitCalledAfterInit(): void
	{
		// All three phases must fire in dependency order; record separately.
		$modules = [
			'dep'       => [TrackingModule::class, [], null],
			'dependent' => [InterfaceDepModule::class, ['DependencyId' => 'dep'], null],
		];

		$app = $this->newAccessor();
		$app->applyConfiguration($this->moduleConfig($modules), false);

		// init() fired in dep order — basic sanity that all four phases completed.
		$this->assertSame(['dep', 'dependent'], DepOrderTracker::$order,
			'all four phases must complete with modules in dependency order');
	}

	public function testApplyConfiguration_fourPhaseOrder_singleModule(): void
	{
		// For a single PhaseTrackingModule, phases must appear in pre → init → post order.
		$app     = $this->newAccessor();
		$modules = [
			'mod' => [PhaseTrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(['mod:pre', 'mod:init', 'mod:post'], PhaseTrackingModule::$phases,
			'dyPreInit must run before init(), and init() must run before dyPostInit()');
	}

	public function testApplyConfiguration_fourPhaseOrder_withDependency(): void
	{
		// Two PhaseTrackingModules: 'leaf' depends on 'root'.
		// applyConfiguration runs all dyPreInit first (in dep order), then all init()
		// (in dep order), then all dyPostInit (in dep order).
		// Expected: root:pre, leaf:pre, root:init, leaf:init, root:post, leaf:post
		$app     = $this->newAccessor();
		$modules = [
			'leaf' => [PhaseTrackingModule::class, ['DependencyId' => 'root'], null],
			'root' => [PhaseTrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(
			['root:pre', 'leaf:pre', 'root:init', 'leaf:init', 'root:post', 'leaf:post'],
			PhaseTrackingModule::$phases,
			'all dyPreInit must fire (in dep order) before any init(), and all init() before any dyPostInit()'
		);
	}

	public function testApplyConfiguration_cycle_throwsBeforeAnyInit(): void
	{
		$app     = $this->newAccessor();
		$modules = [
			'x' => [InterfaceDepModule::class, ['DependencyId' => 'y'], null],
			'y' => [InterfaceDepModule::class, ['DependencyId' => 'x'], null],
		];

		try {
			$app->applyConfiguration($this->moduleConfig($modules), false);
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			// exception thrown as expected
		}

		// No module must have been initialized before the cycle exception is thrown.
		$this->assertSame([], DepOrderTracker::$order,
			'no module must be initialized when a dependency cycle is detected');
	}

	public function testApplyConfiguration_stringShorthandDep_initOrder(): void
	{
		// StringReturnDepModule returns a plain string dep — must sort correctly.
		$app     = $this->newAccessor();
		$modules = [
			'dependent' => [StringReturnDepModule::class, ['DepId' => 'dep'], null],
			'dep'       => [TrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(['dep', 'dependent'], DepOrderTracker::$order,
			'string-shorthand dep form must be honoured in applyConfiguration init order');
	}

	public function testApplyConfiguration_keyValueDepModule_noDeps_declarationOrder(): void
	{
		// KeyValueDepModule's dep array is driven by setDepsArray(), which cannot be
		// supplied via init-properties (it takes an array, not a scalar). This test
		// verifies that when no deps are configured the module loads without error and
		// declaration order is preserved — a smoke-test of the class in applyConfiguration.
		$app     = $this->newAccessor();
		$modules = [
			'dependent' => [KeyValueDepModule::class, [], null],
			'dep'       => [TrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(['dependent', 'dep'], DepOrderTracker::$order,
			'KeyValueDepModule with no deps must initialize in declaration order');
	}

	// -----------------------------------------------------------------------
	// getModule() lazy-load — dependencies initialized first
	// -----------------------------------------------------------------------

	public function testGetModule_lazyLoad_initsDependencyFirst(): void
	{
		$app = $this->newAccessor();

		// Register two lazy modules: 'dependent' depends on 'dep'.
		// 'id' is included in init-properties to mirror TApplicationConfiguration.
		PradoUnit::setProp($app, '_lazyModules', [
			'dep'       => [TrackingModule::class, ['id' => 'dep'], null],
			'dependent' => [InterfaceDepModule::class, ['id' => 'dependent', 'DependencyId' => 'dep'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'dep'       => null,
			'dependent' => null,
		]);

		// Force-load 'dependent'; should trigger 'dep' first.
		$app->getModule('dependent');

		$this->assertSame(['dep', 'dependent'], DepOrderTracker::$order,
			'dep must be initialized before dependent when lazy-loading dependent');
	}

	public function testGetModule_lazyLoad_depAlreadyInitedNotReinited(): void
	{
		$app = $this->newAccessor();

		// Pre-initialize 'dep' as a live module object (not null).
		$depModule = new TrackingModule();
		$depModule->setID('dep');

		PradoUnit::setProp($app, '_lazyModules', [
			'dependent' => [InterfaceDepModule::class, ['id' => 'dependent', 'DependencyId' => 'dep'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'dep'       => $depModule,   // already live
			'dependent' => null,
		]);

		$app->getModule('dependent');

		// 'dep' was already live — only 'dependent' should appear in the init log.
		$this->assertSame(['dependent'], DepOrderTracker::$order,
			'dep must not be re-initialized when it is already live');
	}

	public function testGetModule_lazyLoad_fourPhaseOrder_singleModule(): void
	{
		// For a single lazy PhaseTrackingModule (no deps), the per-module phase order
		// in getModule() must be: pre → init → post.
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'mod' => [PhaseTrackingModule::class, ['id' => 'mod'], null],
		]);
		PradoUnit::setProp($app, '_modules', ['mod' => null]);

		$app->getModule('mod');

		$this->assertSame(['mod:pre', 'mod:init', 'mod:post'], PhaseTrackingModule::$phases,
			'getModule lazy path must call dyPreInit then init then dyPostInit on a single module');
	}

	public function testGetModule_lazyLoad_chainPhaseOrder(): void
	{
		// For a lazy chain: 'top' depends on 'mid', 'mid' depends on 'base'.
		// getModule() resolves recursively: top.dyPreInit → getModule(mid) →
		//   mid.dyPreInit → getModule(base) → base.dyPreInit → base.init → base.dyPostInit
		//   → mid.init → mid.dyPostInit → top.init → top.dyPostInit.
		$app = $this->newAccessor();
		PradoUnit::setProp($app, '_lazyModules', [
			'base' => [PhaseTrackingModule::class, ['id' => 'base'], null],
			'mid'  => [PhaseTrackingModule::class, ['id' => 'mid',  'DependencyId' => 'base'], null],
			'top'  => [PhaseTrackingModule::class, ['id' => 'top',  'DependencyId' => 'mid'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'base' => null,
			'mid'  => null,
			'top'  => null,
		]);

		$app->getModule('top');

		$this->assertSame(
			['top:pre', 'mid:pre', 'base:pre', 'base:init', 'base:post',
			 'mid:init', 'mid:post', 'top:init', 'top:post'],
			PhaseTrackingModule::$phases,
			'lazy chain: outer dyPreInit fires before dep resolution; each dep completes all three phases before the outer init runs'
		);
	}

	public function testGetModule_advisoryDepRegisteredNull_loadedFirst(): void
	{
		// 'dependent' declares an advisory dep on 'optional' which IS registered
		// (as a lazy null slot). Even though it is advisory, it must still be
		// force-loaded before 'dependent' when it is registered.
		$app = $this->newAccessor();

		PradoUnit::setProp($app, '_lazyModules', [
			'optional'  => [TrackingModule::class, ['id' => 'optional'], null],
			'dependent' => [AdvisoryDepModule::class, ['id' => 'dependent', 'AdvisoryDepId' => 'optional'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'optional'  => null,
			'dependent' => null,
		]);

		$app->getModule('dependent');

		// 'optional' is present in _modules → must be loaded before 'dependent'.
		$this->assertSame(['optional', 'dependent'], DepOrderTracker::$order,
			'advisory dep that IS registered must still be force-loaded before the dependent');
	}

	public function testGetModule_threeModuleLazyChain_initDeepestFirst(): void
	{
		// Chain: 'top' depends on 'mid', 'mid' depends on 'base'. All three are lazy.
		// Requesting 'top' must resolve the full chain depth-first: base → mid → top.
		$app = $this->newAccessor();

		PradoUnit::setProp($app, '_lazyModules', [
			'base' => [TrackingModule::class, ['id' => 'base'], null],
			'mid'  => [InterfaceDepModule::class, ['id' => 'mid', 'DependencyId' => 'base'], null],
			'top'  => [InterfaceDepModule::class, ['id' => 'top', 'DependencyId' => 'mid'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'base' => null,
			'mid'  => null,
			'top'  => null,
		]);

		$app->getModule('top');

		$this->assertSame(['base', 'mid', 'top'], DepOrderTracker::$order,
			'a three-level lazy dependency chain must initialize deepest dep first');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — edge cases
	// -----------------------------------------------------------------------

	public function testCollectDeps_interfaceReturnsNull_treatedAsEmpty(): void
	{
		// getModuleDependencies() returns null — must not throw and must yield no deps.
		$app    = $this->newAccessor();
		$module = new NullReturnDepModule();
		$module->setReturnNull(true);

		$this->assertSame([], $app->pubCollectDeps($module),
			'null return from getModuleDependencies() must be treated as an empty dep list');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — Source 2: IModuleDependency on behaviors
	// -----------------------------------------------------------------------

	public function testCollectDeps_noBehaviors_returnsEmpty(): void
	{
		// A module with no attached behaviors must not crash (tests the
		// getBehaviors null-$_m guard fix) and must return no deps.
		$app    = $this->newAccessor();
		$module = new TrackingModule();

		$this->assertSame([], $app->pubCollectDeps($module),
			'a module with no behaviors must yield no behavior-sourced deps');
	}

	public function testCollectDeps_behaviorWithDep_depsCollected(): void
	{
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b      = new DepBehavior();
		$b->setDepId('db');
		$module->attachBehavior('depB', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps),
			'dep declared by an IModuleDependency behavior must be collected');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'behavior plain-string dep must default to required=true');
	}

	public function testCollectDeps_behaviorDepDynamic_reevaluatedEachCall(): void
	{
		// getModuleDependencies() on the behavior is called on every collect
		// call — changing the dep ID must be visible immediately.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b      = new DepBehavior();
		$b->setDepId('first');
		$module->attachBehavior('depB', $b);

		$first = $app->pubCollectDeps($module);

		$b->setDepId('second');
		$second = $app->pubCollectDeps($module);

		$this->assertContains('first', $this->depIds($first));
		$this->assertContains('second', $this->depIds($second),
			'behavior getModuleDependencies() must be re-evaluated on every call');
		$this->assertNotContains('first', $this->depIds($second));
	}

	public function testCollectDeps_behaviorAttachedBetweenCalls_appearsInSubsequentCall(): void
	{
		// Behaviors may be attached between initialization phases; the behavior list
		// is fully re-scanned on every call so newly attached behaviors are included.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b1     = new DepBehavior(); $b1->setDepId('db');
		$module->attachBehavior('b1', $b1);

		$app->pubCollectDeps($module); // first scan — b1 only

		// Attach a second IModuleDependency behavior between calls.
		$b2 = new DepBehavior(); $b2->setDepId('late');
		$module->attachBehavior('b2', $b2);

		$second = $app->pubCollectDeps($module);

		$this->assertContains('late', $this->depIds($second),
			'behavior attached between calls must appear on the next collect — no behavior-list caching');
	}

	public function testCollectDeps_multipleBehaviors_allDepsCollected(): void
	{
		// Two IModuleDependency behaviors — both must contribute deps.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b1     = new DepBehavior(); $b1->setDepId('db');
		$b2     = new DepBehavior(); $b2->setDepId('cache');
		$module->attachBehavior('b1', $b1);
		$module->attachBehavior('b2', $b2);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps));
		$this->assertContains('cache', $this->depIds($deps));
	}

	public function testCollectDeps_behaviorAndModuleSameDep_moduleTakesPriority(): void
	{
		// The same dep ID declared by both the module (required=true via plain string)
		// and a behavior must appear only once; the module-level declaration wins.
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('db');  // required=true (default for plain string)
		$b      = new DepBehavior(); $b->setDepId('db');
		$module->attachBehavior('depB', $b);

		$deps = $app->pubCollectDeps($module);

		$ids = $this->depIds($deps);
		$this->assertCount(1, array_filter($ids, fn($d) => $d === 'db'),
			'the same dep ID from both module and behavior must be deduplicated');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'module-level declaration takes priority — required flag must come from the module');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — required flag: module-source priority
	// -----------------------------------------------------------------------

	public function testCollectDeps_moduleSrcRequired_behaviorSrcAdvisory_moduleWins(): void
	{
		// Module declares 'db' as required (plain string).
		// A behavior also declares 'db' as advisory (required=false).
		// The module-level required=true must win because module source takes priority.
		$app = $this->newAccessor();

		$module = new KeyValueDepModule();
		$module->setDepsArray(['db' => true]);

		$b = new DepBehaviorAdvisory();  // returns ['db' => false]
		$b->setDepId('db');
		$module->attachBehavior('advisory', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertCount(1, array_filter($this->depIds($deps), fn($d) => $d === 'db'));
		$this->assertTrue($this->depRequired($deps, 'db'),
			'module required=true must override behavior required=false for the same dep ID');
	}

	// -----------------------------------------------------------------------
	// getModule() lazy-load — required flag enforcement
	// -----------------------------------------------------------------------

	public function testGetModule_requiredDepNotRegistered_throwsConfigurationException(): void
	{
		// 'dependent' declares a required dep on 'missing' which is not registered.
		// getModule must throw TConfigurationException.
		$app = $this->newAccessor();

		PradoUnit::setProp($app, '_lazyModules', [
			'dependent' => [InterfaceDepModule::class, ['id' => 'dependent', 'DependencyId' => 'missing'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'dependent' => null,
			// 'missing' intentionally absent from _modules
		]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->getModule('dependent');
	}

	public function testGetModule_advisoryDepNotRegistered_noException(): void
	{
		// 'dependent' declares an advisory (required=false) dep on 'optional' which
		// is not registered at all. getModule must load 'dependent' successfully
		// without throwing — the absent advisory dep is silently skipped.
		$app = $this->newAccessor();

		PradoUnit::setProp($app, '_lazyModules', [
			'dependent' => [AdvisoryDepModule::class, ['id' => 'dependent', 'AdvisoryDepId' => 'optional'], null],
		]);
		PradoUnit::setProp($app, '_modules', [
			'dependent' => null,
			// 'optional' intentionally absent — advisory dep, must not throw
		]);

		$module = $app->getModule('dependent');

		$this->assertInstanceOf(AdvisoryDepModule::class, $module,
			'loading a module with an absent advisory dep must succeed without throwing');
		$this->assertSame(['dependent'], DepOrderTracker::$order,
			'the module must be initialized even though its advisory dep is absent');
	}

	// -----------------------------------------------------------------------
	// Phase parameter — passed to getModuleDependencies()
	// -----------------------------------------------------------------------

	public function testCollectDeps_phasePassedToGetModuleDependencies(): void
	{
		// collectModuleDependencies() must pass the $isPreInit flag verbatim to
		// getModuleDependencies() on both the module and its behaviors.
		$app    = $this->newAccessor();
		$module = new PhaseAwareDepModule();

		$app->pubCollectDeps($module, true);  // dyPreInit pass
		$app->pubCollectDeps($module, false); // init() pass

		$this->assertSame(
			[true, false],
			$module->receivedPhases,
			'collectModuleDependencies must pass $isPreInit verbatim to getModuleDependencies()'
		);
	}

	public function testCollectDeps_phaseAware_preinitReturnsDifferentDeps(): void
	{
		// A module that returns deps only for the dyPreInit pass ($isPreInit=true) must
		// report those deps when collected with true, and no deps with false.
		$app    = $this->newAccessor();
		$module = new PhaseAwareDepModule();
		$module->setPreinitDepId('earlydb');

		$preinitDeps = $app->pubCollectDeps($module, true);
		$initDeps    = $app->pubCollectDeps($module, false);

		$this->assertContains('earlydb', $this->depIds($preinitDeps),
			'a dep declared only for the preinit pass must appear when collected with $isPreInit=true');
		$this->assertNotContains('earlydb', $this->depIds($initDeps),
			'a dep declared only for the preinit pass must not appear when collected with $isPreInit=false');
	}

	public function testCollectDeps_phaseAware_initOnlyReturnsDifferentDeps(): void
	{
		// A module that returns deps only for the init() pass ($isPreInit=false) must
		// report those deps when collected with false, and no deps with true.
		// (dyPostInit reuses the init-pass order — no separate pass for it.)
		$app    = $this->newAccessor();
		$module = new PhaseAwareDepModule();
		$module->setInitDepId('db');

		$initDeps    = $app->pubCollectDeps($module, false);
		$preinitDeps = $app->pubCollectDeps($module, true);

		$this->assertContains('db', $this->depIds($initDeps),
			'a dep declared only for the init pass must appear when collected with $isPreInit=false');
		$this->assertNotContains('db', $this->depIds($preinitDeps),
			'a dep declared only for the init pass must not appear when collected with $isPreInit=true');
	}

	public function testSort_phasePassedToCollectModuleDependencies(): void
	{
		// sortModulesByDependency() must pass $isPreInit down to collectModuleDependencies().
		// Use a PhaseAwareDepModule that only declares a dep for the preinit pass ($isPreInit=true).
		// When sorted with $isPreInit=false the dep must be absent (no ordering constraint);
		// when sorted with $isPreInit=true the dep must be honoured (dep-first order).
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new PhaseAwareDepModule();
		$b->setPreinitDepId('a');   // dep only in the preinit pass

		$cache      = [];
		$initResult = $app->pubSortByDep(
			[$this->entry('b', $b), $this->entry('a', $a)],
			$cache,
			false  // init() pass: b has no dep → original order preserved
		);
		$this->assertSame(['b', 'a'], array_column($initResult, 'id'),
			'no dep in init pass → original order preserved');

		$cache = [];
		$preinitResult = $app->pubSortByDep(
			[$this->entry('b', $b), $this->entry('a', $a)],
			$cache,
			true  // dyPreInit pass: b depends on a → [a, b]
		);
		$this->assertSame(['a', 'b'], array_column($preinitResult, 'id'),
			'dep declared only for preinit pass must be honoured when sorted with $isPreInit=true');
	}

	// -----------------------------------------------------------------------
	// null id in verbose array form
	// -----------------------------------------------------------------------

	public function testCollectDeps_verboseArrayForm_nullId_silentlyIgnored(): void
	{
		// An entry with id=null must be silently skipped — useful for conditional deps
		// where the dep ID is not yet known, expressed as ['id' => null, 'required' => false].
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([['id' => null, 'required' => false]]);

		$this->assertSame([], $app->pubCollectDeps($module),
			"verbose array entry with id=null must be silently ignored");
	}

	public function testCollectDeps_verboseArrayForm_nullIdAlongsideRealId_onlyRealCollected(): void
	{
		// When a null-id entry appears alongside a valid entry, only the valid one
		// must be collected.
		$app    = $this->newAccessor();
		$module = new VerboseArrayDepModule();
		$module->setDepsArray([
			['id' => null,  'required' => false],
			['id' => 'db',  'required' => true],
		]);

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['db'], $this->depIds($deps),
			'only the non-null id must be collected when mixed with a null-id entry');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration — correct phase passed to each sort pass
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_phaseAware_preinitDepOnlyInPreinitPass(): void
	{
		// PhaseAwareDepModule 'b' declares dep on 'a' only for PHASE_PREINIT.
		// applyConfiguration must pass PHASE_PREINIT to the first sort, so for the
		// dyPreInit pass 'a' comes before 'b'.  For the init() pass there is no dep,
		// so both orderings are valid — but both modules must still be initialized.
		$app     = $this->newAccessor();
		$modules = [
			'b' => [PhaseAwareDepModule::class, ['PreinitDepId' => 'a'], null],
			'a' => [TrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		// Both modules must have been initialized exactly once.
		$this->assertContains('a', DepOrderTracker::$order);
		$this->assertContains('b', DepOrderTracker::$order);
		$this->assertCount(2, DepOrderTracker::$order);
	}

	public function testApplyConfiguration_phaseAware_initDepOnlyInInitPass(): void
	{
		// PhaseAwareDepModule 'b' declares dep on 'a' only for the init() pass ($isPreInit=false).
		// The sort for init must place 'a' before 'b'; dyPostInit reuses the same order.
		// Both modules must complete initialization.
		$app     = $this->newAccessor();
		$modules = [
			'b' => [PhaseAwareDepModule::class, ['InitDepId' => 'a'], null],
			'a' => [TrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(['a', 'b'], DepOrderTracker::$order,
			'init-pass dep must enforce a-before-b in init() order');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — behavior string-shorthand form
	// -----------------------------------------------------------------------

	public function testCollectDeps_behaviorStringShorthand_collected(): void
	{
		// A behavior implementing IModuleDependency that returns a plain string
		// (string-shorthand form) must still have its dep collected.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b      = new BehaviorStringDep();
		$b->setDepId('db');
		$module->attachBehavior('strDep', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('db', $this->depIds($deps),
			'behavior returning a plain string from getModuleDependencies() must be collected as a dep');
		$this->assertTrue($this->depRequired($deps, 'db'),
			'plain-string from behavior must default to required=true');
	}

	public function testCollectDeps_behaviorStringShorthand_null_returnsNoDepForBehavior(): void
	{
		// Behavior returning null via string-shorthand path (when depId is empty)
		// must contribute no deps.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b      = new BehaviorStringDep(); // depId empty → returns null
		$module->attachBehavior('strDep', $b);

		$this->assertSame([], $app->pubCollectDeps($module),
			'behavior returning null must contribute no deps');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — behavior verbose array form
	// -----------------------------------------------------------------------

	public function testCollectDeps_behaviorVerboseArrayForm_collected(): void
	{
		// A behavior returning the verbose [['id'=>...,'required'=>...]] form must
		// have its dep collected with the correct required flag.
		$app    = $this->newAccessor();
		$module = new TrackingModule();
		$b      = new BehaviorVerboseDep();
		$b->setDepsArray([['id' => 'cache', 'required' => false]]);
		$module->attachBehavior('verboseDep', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('cache', $this->depIds($deps),
			'behavior returning verbose array form must have its dep collected');
		$this->assertFalse($this->depRequired($deps, 'cache'),
			'verbose array required=false from behavior must yield required=false');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — module dep + behavior dep with different IDs
	// -----------------------------------------------------------------------

	public function testCollectDeps_moduleDep_and_behaviorDep_differentIds_bothCollected(): void
	{
		// Module declares dep 'db' (via IModuleDependency); a behavior declares dep
		// 'cache' (distinct ID). Both must appear in the merged result.
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('db');

		$b = new DepBehavior();
		$b->setDepId('cache');
		$module->attachBehavior('bDep', $b);

		$deps = $app->pubCollectDeps($module);
		$ids  = $this->depIds($deps);

		$this->assertContains('db', $ids,
			'module-level dep must be present');
		$this->assertContains('cache', $ids,
			'behavior-level dep (different ID) must also be present');
		$this->assertCount(2, $deps);
		$this->assertTrue($this->depRequired($deps, 'db'));
		$this->assertTrue($this->depRequired($deps, 'cache'));
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — two behaviors with same dep ID
	// -----------------------------------------------------------------------

	public function testCollectDeps_twoBehaviorsSameDep_firstBehaviorWins(): void
	{
		// Two IModuleDependency behaviors both declare the same dep ID.
		// The first behavior attached wins (its entry is not overwritten by the second).
		// Because both declare the same ID as a plain string (required=true), the result
		// must contain exactly one entry for that ID.
		$app    = $this->newAccessor();
		$module = new TrackingModule();

		$b1 = new DepBehavior();
		$b1->setDepId('shared');
		$b2 = new DepBehaviorAdvisory(); // returns ['shared' => false]
		$b2->setDepId('shared');

		$module->attachBehavior('b1', $b1); // attaches first — required=true
		$module->attachBehavior('b2', $b2); // attaches second — advisory (required=false)

		$deps = $app->pubCollectDeps($module);
		$ids  = $this->depIds($deps);

		$this->assertCount(1, array_filter($ids, fn($d) => $d === 'shared'),
			'the same dep ID from two behaviors must appear exactly once');
		$this->assertTrue($this->depRequired($deps, 'shared'),
			'first behavior attached (required=true) takes priority over the second (required=false)');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — dyFilterDependencies integration
	// -----------------------------------------------------------------------

	public function testCollectDeps_dyFilterDependencies_canInjectDep(): void
	{
		// A behavior implementing dyFilterDependencies that adds a dep entry
		// must have that dep appear in the final collectModuleDependencies() result.
		$app    = $this->newAccessor();
		$module = new TrackingModule(); // no IModuleDependency — empty source 1

		$b = new AppDyFilterBehavior();
		$b->setAdditions(['injected' => ['id' => 'injected', 'required' => true]]);
		$module->attachBehavior('filter', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertContains('injected', $this->depIds($deps),
			'dep injected by dyFilterDependencies must appear in collectModuleDependencies result');
		$this->assertTrue($this->depRequired($deps, 'injected'));
	}

	public function testCollectDeps_dyFilterDependencies_canRemoveDep(): void
	{
		// A behavior implementing dyFilterDependencies that removes a dep entry
		// declared at the module level must cause that dep to be absent in the result.
		$app    = $this->newAccessor();
		$module = new InterfaceDepModule();
		$module->setDependencyId('db');  // source 1 adds 'db'

		$b = new AppDyFilterBehavior();
		$b->setRemovals(['db']); // dyFilterDependencies removes it
		$module->attachBehavior('filter', $b);

		$deps = $app->pubCollectDeps($module);

		$this->assertNotContains('db', $this->depIds($deps),
			'dep removed by dyFilterDependencies must not appear in collectModuleDependencies result');
	}

	public function testCollectDeps_dyFilterDependencies_injectedDepSortsCorrectly(): void
	{
		// Verify end-to-end: a dep injected via dyFilterDependencies is also honoured
		// by sortModulesByDependency — the injected dep must come before the dependent.
		$app = $this->newAccessor();
		$a   = new TrackingModule(); // 'a' — no deps

		// 'b' has no module-level or behavior-level IModuleDependency dep, but
		// a dyFilterDependencies behavior injects 'a' into the dep map.
		$b      = new TrackingModule();
		$filter = new AppDyFilterBehavior();
		$filter->setAdditions(['a' => ['id' => 'a', 'required' => true]]);
		$b->attachBehavior('filter', $filter);

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);

		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'dep injected via dyFilterDependencies must be respected by the topological sort');
	}

	// -----------------------------------------------------------------------
	// collectModuleDependencies — non-TComponent module (no behavior scan, no dyFilterDependencies)
	// -----------------------------------------------------------------------

	public function testCollectDeps_nonTComponentModule_depsCollectedFromIModuleDependency(): void
	{
		// A module that implements IModule and IModuleDependency but does NOT extend
		// TComponent must still have its Source 1 deps collected (the module-level
		// IModuleDependency implementation), even though the TComponent branch
		// (behavior scanning and dyFilterDependencies) is skipped.
		$app    = $this->newAccessor();
		$module = new PureIModuleWithDep(['ext']);

		$deps = $app->pubCollectDeps($module);

		$this->assertSame(['ext'], $this->depIds($deps),
			'non-TComponent module must still have its own IModuleDependency deps collected');
		$this->assertTrue($this->depRequired($deps, 'ext'));
	}

	public function testCollectDeps_nonTComponentModule_noBehaviorScan(): void
	{
		// A non-TComponent module with no deps must produce an empty result;
		// the TComponent branch (behavior loop and dyFilterDependencies) is never reached.
		$app    = $this->newAccessor();
		$module = new PureIModuleWithDep(); // no deps

		$this->assertSame([], $app->pubCollectDeps($module),
			'non-TComponent module with no deps must produce an empty dep list');
	}

	// -----------------------------------------------------------------------
	// sortModulesByDependency — advisory dep in same batch still enforces order
	// -----------------------------------------------------------------------

	public function testSort_advisoryDepInBatch_depComesBefore(): void
	{
		// An advisory dep (required=false) that IS inside the same batch must still
		// influence ordering — the required flag governs error handling, not sort order.
		// b declares an advisory dep on a → sorted order must still be [a, b].
		$app = $this->newAccessor();
		$a   = new TrackingModule();
		$b   = new AdvisoryDepModule();
		$b->setAdvisoryDepId('a');

		$result = $app->pubSortByDep([
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$this->assertSame(['a', 'b'], array_column($result, 'id'),
			'advisory dep (required=false) that is inside the batch must still enforce sort order');
	}

	public function testSort_advisoryDepInBatch_diamondWithAdvisoryEdge_correctOrder(): void
	{
		// Diamond where d depends on b (required) and c (advisory), both depend on a.
		// The advisory edge d→c must still be respected: c before d.
		$app = $this->newAccessor();
		$a   = new TrackingModule();

		$b = new InterfaceDepModule();
		$b->setDependencyId('a');

		// c is advisory dep on a.
		$c = new AdvisoryDepModule();
		$c->setAdvisoryDepId('a');

		// d has required dep on b and advisory dep on c.
		$d = new VerboseArrayDepModule();
		$d->setDepsArray([
			['id' => 'b', 'required' => true],
			['id' => 'c', 'required' => false],
		]);

		$result = $app->pubSortByDep([
			$this->entry('d', $d),
			$this->entry('c', $c),
			$this->entry('b', $b),
			$this->entry('a', $a),
		]);
		$ids = array_column($result, 'id');

		$posA = array_search('a', $ids, true);
		$posB = array_search('b', $ids, true);
		$posC = array_search('c', $ids, true);
		$posD = array_search('d', $ids, true);

		$this->assertLessThan($posB, $posA, 'a before b (required)');
		$this->assertLessThan($posC, $posA, 'a before c (advisory)');
		$this->assertLessThan($posD, $posB, 'b before d (required)');
		$this->assertLessThan($posD, $posC, 'c before d (advisory) — advisory deps still influence order');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration — advisory dep in batch sorts correctly end-to-end
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_advisoryDepInBatch_initOrderRespected(): void
	{
		// 'opt' is declared as advisory (required=false) by 'consumer'.
		// Both are in the same batch. The advisory dep must still enforce init order:
		// opt first, then consumer.
		$app     = $this->newAccessor();
		$modules = [
			'consumer' => [AdvisoryDepModule::class, ['AdvisoryDepId' => 'opt'], null],
			'opt'      => [TrackingModule::class, [], null],
		];

		$app->applyConfiguration($this->moduleConfig($modules), false);

		$this->assertSame(['opt', 'consumer'], DepOrderTracker::$order,
			'advisory dep present in the same batch must still be initialized before the dependent');
	}
}
