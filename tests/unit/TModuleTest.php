<?php

use Prado\TModule;
use Prado\Util\TBehavior;

// =============================================================================
// Helper fixtures
// =============================================================================

/**
 * Minimal concrete TModule for testing — no additional logic.
 */
class ConcreteModule extends TModule
{
	public function init($config): void
	{
		parent::init($config);
	}
}

/**
 * A behavior that implements dyFilterDependencies.
 * Removes any dep whose ID appears in $removals and appends any deps in $additions.
 */
class FilterDepsBehavior extends TBehavior
{
	/** @var string[] IDs to remove from the dependency map. */
	private array $_removals = [];

	/** @var array<string,array{id:string,required:bool}> Entries to add. */
	private array $_additions = [];

	/** Records every $deps array the behavior receives. */
	public array $receivedDeps = [];

	public function setRemovals(array $removals): void
	{
		$this->_removals = $removals;
	}

	public function setAdditions(array $additions): void
	{
		$this->_additions = $additions;
	}

	/**
	 * @param array<string,array{id:string,required:bool}> $deps
	 * @return array<string,array{id:string,required:bool}>
	 */
	public function dyFilterDependencies(array $deps): array
	{
		$this->receivedDeps[] = $deps;
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
 * A behavior that does NOT implement dyFilterDependencies.
 * Used to confirm that unrelated behaviors are left alone.
 */
class NoOpBehavior extends TBehavior
{
	public bool $called = false;
}

/**
 * A callchain-aware behavior that implements dyFilterDependencies using the
 * TCallChain parameter for true sequential filtering.
 * It applies its own removals/additions and then forwards the modified map to
 * the next behavior in the chain via $callchain->dyFilterDependencies($deps).
 */
class CallChainFilterDepsBehavior extends TBehavior
{
	/** @var string[] IDs to remove before forwarding to the next behavior. */
	private array $_removals = [];

	/** @var array<string,array{id:string,required:bool}> Entries to add before forwarding. */
	private array $_additions = [];

	/** Records the $deps this behavior received. */
	public array $receivedDeps = [];

	public function setRemovals(array $removals): void
	{
		$this->_removals = $removals;
	}

	public function setAdditions(array $additions): void
	{
		$this->_additions = $additions;
	}

	/**
	 * @param array<string,array{id:string,required:bool}> $deps
	 * @param \Prado\Util\TCallChain $callchain
	 * @return array<string,array{id:string,required:bool}>
	 */
	public function dyFilterDependencies(array $deps, \Prado\Util\TCallChain $callchain): array
	{
		$this->receivedDeps[] = $deps;
		foreach ($this->_removals as $id) {
			unset($deps[$id]);
		}
		foreach ($this->_additions as $id => $entry) {
			$deps[$id] = $entry;
		}
		return $callchain->dyFilterDependencies($deps);
	}
}

/**
 * A behavior that records whether dyInit was dispatched to it.
 */
class SpyInitBehavior extends TBehavior
{
	public bool $initCalled = false;

	public function dyInit(mixed $config): mixed
	{
		$this->initCalled = true;
		return $config;
	}
}

// =============================================================================
// TModuleTest
// =============================================================================

/**
 * Unit tests for TModule, focused on the dyFilterDependencies dy-event contract.
 */
class TModuleTest extends PHPUnit\Framework\TestCase
{
	// ── helpers ──────────────────────────────────────────────────────────────

	private function makeModule(): ConcreteModule
	{
		$m = new ConcreteModule();
		$m->setID('testModule');
		return $m;
	}

	/**
	 * Build a dep-map entry identical to the format TApplication produces.
	 *
	 * @return array{id:string,required:bool}
	 */
	private function entry(string $id, bool $required = true): array
	{
		return ['id' => $id, 'required' => $required];
	}

	// ── getID / setID ─────────────────────────────────────────────────────────

	public function testGetIdReturnsValueSetBySetId(): void
	{
		$m = new ConcreteModule();
		$m->setID('my-module');
		$this->assertSame('my-module', $m->getID());
	}

	public function testGetIdDefaultsToNull(): void
	{
		$m = new ConcreteModule();
		$this->assertNull($m->getID());
	}

	// ── dyFilterDependencies — no behaviors ───────────────────────────────────

	public function testDyFilterDependencies_noBehaviors_returnsInputUnchanged(): void
	{
		$m    = $this->makeModule();
		$deps = ['db' => $this->entry('db'), 'cache' => $this->entry('cache', false)];

		$result = $m->dyFilterDependencies($deps);

		$this->assertSame($deps, $result,
			'With no behaviors attached the dep map must be returned as-is.');
	}

	public function testDyFilterDependencies_noBehaviors_emptyMap_returnsEmpty(): void
	{
		$m      = $this->makeModule();
		$result = $m->dyFilterDependencies([]);
		$this->assertSame([], $result);
	}

	// ── dyFilterDependencies — single behavior ────────────────────────────────

	public function testDyFilterDependencies_behaviorReceivesOriginalMap(): void
	{
		$m    = $this->makeModule();
		$b    = new FilterDepsBehavior();
		$m->attachBehavior('filter', $b);

		$deps = ['db' => $this->entry('db')];
		$m->dyFilterDependencies($deps);

		$this->assertCount(1, $b->receivedDeps,
			'The behavior must be called exactly once.');
		$this->assertSame($deps, $b->receivedDeps[0],
			'The behavior must receive the full original dep map.');
	}

	public function testDyFilterDependencies_behaviorCanRemoveDep(): void
	{
		$m = $this->makeModule();
		$b = new FilterDepsBehavior();
		$b->setRemovals(['optional']);
		$m->attachBehavior('filter', $b);

		$deps = [
			'db'       => $this->entry('db'),
			'optional' => $this->entry('optional', false),
		];

		$result = $m->dyFilterDependencies($deps);

		$this->assertArrayHasKey('db', $result);
		$this->assertArrayNotHasKey('optional', $result,
			'The behavior must be able to remove a dep by unsetting it.');
	}

	public function testDyFilterDependencies_behaviorCanAddDep(): void
	{
		$m = $this->makeModule();
		$b = new FilterDepsBehavior();
		$b->setAdditions(['extra' => $this->entry('extra')]);
		$m->attachBehavior('filter', $b);

		$result = $m->dyFilterDependencies(['db' => $this->entry('db')]);

		$this->assertArrayHasKey('extra', $result,
			'The behavior must be able to inject a new dependency.');
		$this->assertSame('extra', $result['extra']['id']);
		$this->assertTrue($result['extra']['required']);
	}

	public function testDyFilterDependencies_behaviorReturningInputUnchanged_preservesMap(): void
	{
		$m = $this->makeModule();
		$b = new FilterDepsBehavior(); // no removals or additions → returns unchanged
		$m->attachBehavior('filter', $b);

		$deps   = ['db' => $this->entry('db'), 'cache' => $this->entry('cache')];
		$result = $m->dyFilterDependencies($deps);

		$this->assertSame($deps, $result);
	}

	// ── dyFilterDependencies — multiple behaviors ─────────────────────────────

	public function testDyFilterDependencies_twoBehaviors_bothCalled(): void
	{
		$m  = $this->makeModule();
		$b1 = new FilterDepsBehavior();
		$b2 = new FilterDepsBehavior();
		$m->attachBehavior('filter1', $b1);
		$m->attachBehavior('filter2', $b2);

		$deps = ['db' => $this->entry('db')];
		$m->dyFilterDependencies($deps);

		$this->assertCount(1, $b1->receivedDeps, 'First behavior must be called.');
		$this->assertCount(1, $b2->receivedDeps, 'Second behavior must be called.');
	}

	public function testDyFilterDependencies_twoBehaviors_lastReturnWins(): void
	{
		// Without explicit callchain forwarding, TCallChain calls all behaviors with
		// the same original args and returns the LAST behavior's return value.
		// b1 removes 'cache' but its return is overwritten by b2.
		// b2 removes 'optional' and its return is what the caller receives.
		// So the result keeps 'cache' (b2 never saw b1's output) but drops 'optional'.
		$m  = $this->makeModule();
		$b1 = new FilterDepsBehavior();
		$b1->setRemovals(['cache']);
		$b2 = new FilterDepsBehavior();
		$b2->setRemovals(['optional']);
		$m->attachBehavior('filter1', $b1);
		$m->attachBehavior('filter2', $b2);

		$deps = [
			'db'       => $this->entry('db'),
			'cache'    => $this->entry('cache'),
			'optional' => $this->entry('optional', false),
		];

		$result = $m->dyFilterDependencies($deps);

		$this->assertArrayHasKey('db', $result);
		// b2 is last and does not remove 'cache', so 'cache' survives.
		$this->assertArrayHasKey('cache', $result,
			'Without callchain forwarding, b1\'s return is discarded; b2 wins.');
		// b2 removed 'optional' from its (unfiltered) view of the original map.
		$this->assertArrayNotHasKey('optional', $result,
			'b2 (last behavior) must have removed optional from the result.');
	}

	public function testDyFilterDependencies_secondBehaviorReceivesOriginalMap(): void
	{
		// Without explicit callchain forwarding, each behavior is called with the
		// original args — not with the previous behavior's return value.
		$m  = $this->makeModule();
		$b1 = new FilterDepsBehavior();
		$b1->setRemovals(['cache']);
		$b2 = new FilterDepsBehavior();
		$m->attachBehavior('filter1', $b1);
		$m->attachBehavior('filter2', $b2);

		$deps = ['db' => $this->entry('db'), 'cache' => $this->entry('cache')];
		$m->dyFilterDependencies($deps);

		$this->assertCount(1, $b2->receivedDeps);
		$this->assertArrayHasKey('cache', $b2->receivedDeps[0],
			'b2 must receive the original map, not b1\'s filtered output.');
	}

	// ── dyFilterDependencies — callchain-aware behaviors (true sequential filter) ─

	public function testDyFilterDependencies_callchainBehavior_singleBehavior_filtersCorrectly(): void
	{
		$m = $this->makeModule();
		$b = new CallChainFilterDepsBehavior();
		$b->setRemovals(['cache']);
		$m->attachBehavior('filter', $b);

		$deps   = ['db' => $this->entry('db'), 'cache' => $this->entry('cache')];
		$result = $m->dyFilterDependencies($deps);

		$this->assertArrayHasKey('db', $result);
		$this->assertArrayNotHasKey('cache', $result);
	}

	public function testDyFilterDependencies_callchainBehaviors_chainOutput(): void
	{
		// Callchain-aware behaviors forward modified deps to the next behavior.
		// b1 removes 'cache'; b2 receives b1's output and removes 'optional'.
		// Final result should have only 'db'.
		$m  = $this->makeModule();
		$b1 = new CallChainFilterDepsBehavior();
		$b1->setRemovals(['cache']);
		$b2 = new CallChainFilterDepsBehavior();
		$b2->setRemovals(['optional']);
		$m->attachBehavior('filter1', $b1);
		$m->attachBehavior('filter2', $b2);

		$deps = [
			'db'       => $this->entry('db'),
			'cache'    => $this->entry('cache'),
			'optional' => $this->entry('optional', false),
		];

		$result = $m->dyFilterDependencies($deps);

		$this->assertArrayHasKey('db', $result);
		$this->assertArrayNotHasKey('cache', $result,
			'b2 must not see cache because b1 removed it via callchain forwarding.');
		$this->assertArrayNotHasKey('optional', $result,
			'b2 must have removed optional.');
	}

	public function testDyFilterDependencies_callchainBehaviors_secondReceivesFirstOutput(): void
	{
		// b1 removes 'cache' and forwards via callchain → b2 receives map without 'cache'.
		$m  = $this->makeModule();
		$b1 = new CallChainFilterDepsBehavior();
		$b1->setRemovals(['cache']);
		$b2 = new CallChainFilterDepsBehavior();
		$m->attachBehavior('filter1', $b1);
		$m->attachBehavior('filter2', $b2);

		$deps = ['db' => $this->entry('db'), 'cache' => $this->entry('cache')];
		$m->dyFilterDependencies($deps);

		$this->assertCount(1, $b2->receivedDeps);
		$this->assertArrayNotHasKey('cache', $b2->receivedDeps[0],
			'Callchain-aware b2 must receive b1\'s filtered output.');
	}

	// ── dyFilterDependencies — unrelated behavior not involved ────────────────

	public function testDyFilterDependencies_unrelatedBehavior_doesNotInterfere(): void
	{
		$m  = $this->makeModule();
		$nb = new NoOpBehavior();
		$m->attachBehavior('noop', $nb);

		$deps   = ['db' => $this->entry('db')];
		$result = $m->dyFilterDependencies($deps);

		// The NoOpBehavior has no dyFilterDependencies method; the dep map
		// must pass through the dy-dispatch unchanged.
		$this->assertSame($deps, $result);
		$this->assertFalse($nb->called);
	}

	// ── dyFilterDependencies — empty map edge cases ───────────────────────────

	public function testDyFilterDependencies_behaviorWithEmptyInput_canAddDeps(): void
	{
		$m = $this->makeModule();
		$b = new FilterDepsBehavior();
		$b->setAdditions(['injected' => $this->entry('injected')]);
		$m->attachBehavior('filter', $b);

		$result = $m->dyFilterDependencies([]);

		$this->assertArrayHasKey('injected', $result,
			'Behavior must be able to inject deps even when the input map is empty.');
	}

	public function testDyFilterDependencies_behaviorWithEmptyInput_returnsEmpty(): void
	{
		$m = $this->makeModule();
		$b = new FilterDepsBehavior(); // no-op behavior
		$m->attachBehavior('filter', $b);

		$result = $m->dyFilterDependencies([]);

		$this->assertSame([], $result);
	}

	// ── init ──────────────────────────────────────────────────────────────────

	public function testInitCallsDyInit(): void
	{
		$m = $this->makeModule();
		$b = new SpyInitBehavior();
		$m->attachBehavior('spy', $b);
		$m->init(null);
		$this->assertTrue($b->initCalled, 'init() must dispatch dyInit to attached behaviors.');
	}
}
