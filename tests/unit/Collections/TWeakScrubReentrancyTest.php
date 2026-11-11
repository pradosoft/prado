<?php

/**
 * TWeakScrubReentrancyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TWeakCallableCollection;
use Prado\Collections\TWeakList;
use Prado\Collections\TWeakMap;
use Prado\TComponent;
use Prado\TEventHandler;

/**
 * Fixture component whose destructor calls a callable supplied at construction
 * time. The destructor lets the test arrange for arbitrary side effects (e.g.
 * inserting a new entry into a Weak collection that is currently being
 * scrubbed) and capture whether the destructor actually fired.
 */
class WeakScrubReentryTarget extends TComponent
{
	/** @var ?\Closure The closure to invoke from `__destruct`. */
	public ?\Closure $onDestruct = null;

	/** @var bool Set to true once `__destruct` has run. */
	public bool $destructorFired = false;

	public function noopHandler()
	{
		// A trivial method so this instance is callable as [$this, 'noopHandler'].
	}

	public function __destruct()
	{
		$this->destructorFired = true;
		if ($this->onDestruct !== null) {
			($this->onDestruct)();
		}
		parent::__destruct();
	}
}

/**
 * Subclass that injects a callable mid-scrub by overriding `weakResetCount()`,
 * the last hook called inside the scrub's `try` block — between the
 * snapshot/identify pass and the final accounting reset. Used to verify that
 * an insert performed *during* the scrub's window is preserved by the
 * two-pass algorithm.
 */
class WeakScrubReentryInjector extends TWeakCallableCollection
{
	/** @var array<int,callable> Callables to insert between the scrub passes. */
	public array $injectMidScrub = [];

	protected function weakResetCount(?int $count = null): void
	{
		foreach ($this->injectMidScrub as $callable) {
			$this->add($callable);
		}
		$this->injectMidScrub = [];
		parent::weakResetCount($count);
	}
}

/**
 * Subclass that runs a callable once, right after turning DiscardInvalid on
 * registers its first object.  The callable drops the last reference to an
 * object whose destructor removes an entry that is not registered yet.
 */
class WeakMapTransitionInjector extends TWeakMap
{
	/** @var ?\Closure The closure to invoke after the first registration. */
	public ?\Closure $afterFirstRegister = null;

	public function enableDiscardInvalid(): void
	{
		$this->setDiscardInvalid(true);
	}

	public function getWeakObjectCount(object $object): ?int
	{
		return $this->weakObjectCount($object);
	}

	protected function weakAdd(object $object): int
	{
		$count = parent::weakAdd($object);
		if (($callable = $this->afterFirstRegister) !== null) {
			$this->afterFirstRegister = null;
			$callable();
		}
		return $count;
	}
}

/**
 * TWeakMap whose DiscardInvalid can be turned on after it is set false, so a
 * test can run the registration pass over a populated map.
 */
class WeakScrubReentryMap extends TWeakMap
{
	public function enableDiscardInvalid(): void
	{
		$this->setDiscardInvalid(true);
	}
}

/**
 * Regression tests for the snapshot/identify + identity-based-remove
 * scrubbing algorithm shared by {@see TWeakCallableCollection}, {@see TWeakList},
 * and {@see TWeakMap}. The earlier implementation guarded the scrub with a
 * re-entrancy mutex and silently dropped destructor-time inserts/removes.
 * The new algorithm permits re-entrant writes to land in `_d` without
 * disturbing the scrub's correctness.
 *
 * @package System.Collections
 */
class TWeakScrubReentrancyTest extends \PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// TWeakCallableCollection
	// -----------------------------------------------------------------------

	public function testWCC_scrubRemovesStaleEntries_preservesLiveOnes(): void
	{
		$col = new TWeakCallableCollection();

		// Two live targets (kept by local refs) + one that we'll drop to make stale.
		$keepA = new WeakScrubReentryTarget();
		$keepB = new WeakScrubReentryTarget();
		$drop  = new WeakScrubReentryTarget();

		$col->add([$keepA, 'noopHandler']);
		$col->add([$drop,  'noopHandler']);
		$col->add([$keepB, 'noopHandler']);

		$this->assertSame(3, $col->getCount());

		// Drop the third target so its WeakReference becomes stale.
		unset($drop);

		// Trigger a scrub (flattenPriorities calls scrubWeakReferences).
		$col->toArray();

		$this->assertSame(2, $col->getCount(), 'Stale entry must be removed');
	}

	public function testWCC_destructorInsertDuringScrubSurvives(): void
	{
		$col = new TWeakCallableCollection();

		// Live targets we keep referenced.
		$keep   = new WeakScrubReentryTarget();
		$inject = new WeakScrubReentryTarget();

		// Stale-bound target whose destructor will insert a new entry mid-scrub.
		// The destructor fires synchronously when our external ref drops, which
		// is BEFORE the scrub starts — but because the new insert lands in $_d
		// as a normal live entry, the subsequent scrub must NOT remove it.
		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($col, $inject): void {
			$col->add([$inject, 'noopHandler']);
		};

		$col->add([$keep, 'noopHandler']);
		$col->add([$trap, 'noopHandler']);

		$this->assertSame(2, $col->getCount());

		// Drop the trap: its __destruct fires and adds the $inject entry.
		unset($trap);

		// Now scrub. The stale trap entry must be removed; the destructor-time
		// insert must survive intact.
		$col->toArray();

		$this->assertSame(
			2,
			$col->getCount(),
			'Original live entry ($keep) and destructor-time insert ($inject) must both survive; stale trap entry must be removed'
		);
	}

	public function testWCC_midScrubInsertIsPreserved(): void
	{
		$col = new WeakScrubReentryInjector();

		$keep    = new WeakScrubReentryTarget();
		$injected = new WeakScrubReentryTarget();
		$drop    = new WeakScrubReentryTarget();

		$col->add([$keep, 'noopHandler']);
		$col->add([$drop, 'noopHandler']);

		// Arrange: between Pass 1 and the final accounting, inject a new live
		// callable. The two-pass scrub must NOT splice it back out — it isn't
		// in the stale-id set.
		$col->injectMidScrub[] = [$injected, 'noopHandler'];

		// Drop $drop to make its entry stale, then trigger the scrub.
		unset($drop);
		$col->toArray();

		$this->assertSame(2, $col->getCount(), 'Live entry plus mid-scrub injected entry must both survive');
	}

	public function testWCC_noOpScrubOnAllLiveEntries(): void
	{
		$col = new TWeakCallableCollection();
		$a = new WeakScrubReentryTarget();
		$b = new WeakScrubReentryTarget();
		$col->add([$a, 'noopHandler']);
		$col->add([$b, 'noopHandler']);

		$beforeCount = $col->getCount();
		$col->toArray();
		$this->assertSame($beforeCount, $col->getCount(), 'Scrub must not remove live entries');
	}

	// -----------------------------------------------------------------------
	// TWeakList
	// -----------------------------------------------------------------------

	public function testWeakList_scrubRemovesStaleEntries(): void
	{
		$list = new TWeakList();

		$keepA = new WeakScrubReentryTarget();
		$keepB = new WeakScrubReentryTarget();
		$drop  = new WeakScrubReentryTarget();

		$list->add($keepA);
		$list->add($drop);
		$list->add($keepB);
		$this->assertSame(3, $list->getCount());

		unset($drop);
		// Force a scrub via toArray (which calls flattenPriorities -> scrub on
		// the priority-list variant, or iteration on TWeakList).
		$list->toArray();

		$this->assertSame(2, $list->getCount(), 'Stale entry must be removed from TWeakList');
	}

	public function testWeakList_destructorInsertDuringScrubSurvives(): void
	{
		$list = new TWeakList();

		$keep   = new WeakScrubReentryTarget();
		$inject = new WeakScrubReentryTarget();
		$trap   = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($list, $inject): void {
			$list->add($inject);
		};

		$list->add($keep);
		$list->add($trap);
		$this->assertSame(2, $list->getCount());

		unset($trap);
		$list->toArray();

		$this->assertSame(
			2,
			$list->getCount(),
			'TWeakList: live entry + destructor-time insert must both survive'
		);
	}

	// -----------------------------------------------------------------------
	// TWeakMap
	// -----------------------------------------------------------------------

	public function testWeakMap_scrubRemovesStaleEntries(): void
	{
		$map = new TWeakMap();

		// TWeakMap uses scalar keys; the "weak" part is the values.
		$keepValue = new WeakScrubReentryTarget();
		$dropValue = new WeakScrubReentryTarget();

		$map->add('keep', $keepValue);
		$map->add('drop', $dropValue);

		$this->assertSame(2, $map->getCount());

		unset($dropValue);
		// Iterate to trigger the scrub.
		iterator_to_array($map);

		$this->assertSame(1, $map->getCount(), 'TWeakMap: entry with stale value must be removed');
	}

	public function testWeakMap_destructorRemovesUnregisteredEntryDuringDiscardInvalidTransition(): void
	{
		$map = new WeakMapTransitionInjector(null, null, false);
		$keep = new WeakScrubReentryTarget();
		$removed = new WeakScrubReentryTarget();
		$map->add('keep', $keep);
		$map->add('removed', $removed);

		$fired = false;
		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($map, &$fired): void {
			$fired = true;
			$map->remove('removed');
		};
		$map->afterFirstRegister = function () use (&$trap): void {
			$trap = null;
		};

		$map->enableDiscardInvalid();

		$this->assertTrue($fired, 'The destructor fired during the transition');
		$this->assertTrue($map->getDiscardInvalid());
		$this->assertSame(['keep' => $keep], $map->toArray());
		$this->assertSame(1, $map->getWeakObjectCount($keep));

		$keep = null;
		$this->assertSame(0, $map->getCount(), 'The registered entry is scrubbed once its value is collected');
	}

	public function testWeakMap_destructorRemovesDuplicateValueDuringDiscardInvalidTransition(): void
	{
		$map = new WeakMapTransitionInjector(null, null, false);
		$value = new WeakScrubReentryTarget();
		$map->add('first', $value);
		$map->add('second', $value);

		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($map): void {
			$map->remove('second');
		};
		$map->afterFirstRegister = function () use (&$trap): void {
			$trap = null;
		};

		$map->enableDiscardInvalid();

		$this->assertSame(['first' => $value], $map->toArray());
		$this->assertSame(1, $map->getWeakObjectCount($value), 'The remaining entry keeps its value registered');

		$value = null;
		$this->assertSame(0, $map->getCount());
	}

	// -----------------------------------------------------------------------
	// clear() with a destructor mutating the collection
	// -----------------------------------------------------------------------

	/**
	 * Returns a Closure that is the only holder of a target whose destructor runs
	 * $onDestruct, so removing the Closure from a collection fires the destructor.
	 */
	private function closureHoldingTrap(\Closure $onDestruct, ?bool &$fired): \Closure
	{
		$fired = false;
		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($onDestruct, &$fired): void {
			$fired = true;
			$onDestruct();
		};
		return function () use ($trap): void {
		};
	}

	public function testWeakList_clearSurvivesDestructorRemovingAnEntry(): void
	{
		$list = new TWeakList();
		$keep = new WeakScrubReentryTarget();
		$list->add($keep);
		$list->add($this->closureHoldingTrap(function () use ($list, $keep): void {
			$list->remove($keep);
		}, $fired));

		$list->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $list->getCount());
		$this->assertSame([], $list->toArray());
		$this->assertSame(0, $list->add($keep), 'The list is usable after clear');
	}

	public function testWeakList_clearRemovesEntryInsertedByDestructor(): void
	{
		$list = new TWeakList();
		$inject = new WeakScrubReentryTarget();
		$list->add($this->closureHoldingTrap(function () use ($list, $inject): void {
			$list->add($inject);
		}, $fired));

		$list->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $list->getCount());
	}

	public function testWeakMap_clearSurvivesDestructorRemovingAnEntry(): void
	{
		$map = new TWeakMap();
		$map->add('a', $this->closureHoldingTrap(function () use ($map): void {
			$map->remove('c');
		}, $fired));
		$map->add('b', 'x');
		$map->add('c', 'y');

		$map->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $map->getCount());
		$this->assertSame([], $map->toArray());
	}

	public function testWeakMap_clearRemovesEntryAddedByDestructor(): void
	{
		$map = new TWeakMap();
		$map->add('a', $this->closureHoldingTrap(function () use ($map): void {
			$map->add('added', 'x');
		}, $fired));

		$map->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $map->getCount());
		$this->assertSame([], $map->toArray());
	}

	public function testWCC_clearSurvivesDestructorRemovingEntryAtAnotherPriority(): void
	{
		$col = new TWeakCallableCollection();
		$keep = new WeakScrubReentryTarget();
		$col->add($this->closureHoldingTrap(function () use ($col, $keep): void {
			$col->remove([$keep, 'noopHandler']);
		}, $fired), 5);
		$col->add([$keep, 'noopHandler'], 10);

		$col->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $col->getCount());
		$this->assertSame([], $col->getPriorities());
	}

	public function testWCC_clearSurvivesDestructorRemovingEntryAtTheSamePriority(): void
	{
		$col = new TWeakCallableCollection();
		$keep = new WeakScrubReentryTarget();
		$col->add([$keep, 'noopHandler'], 5);
		$col->add('strlen', 5);
		$col->add($this->closureHoldingTrap(function () use ($col, $keep): void {
			$col->remove([$keep, 'noopHandler']);
		}, $fired), 5);

		$col->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $col->getCount());
		$this->assertSame([], $col->toArray());
	}

	public function testWCC_clearRemovesEntryInsertedByDestructor(): void
	{
		$col = new TWeakCallableCollection();
		$inject = new WeakScrubReentryTarget();
		$col->add($this->closureHoldingTrap(function () use ($col, $inject): void {
			$col->add([$inject, 'noopHandler']);
		}, $fired));

		$col->clear();

		$this->assertTrue($fired);
		$this->assertSame(0, $col->getCount());
		$this->assertSame([], $col->getPriorities());
	}

	/**
	 * A stale TEventHandler whose data object's destructor removes a later key
	 * while the scrub's removal pass runs. The pass skips the vanished key.
	 */
	public function testWeakMap_destructorRemoveDuringScrubIsSkipped(): void
	{
		$map = new TWeakMap();
		$keep = new WeakScrubReentryTarget();
		$dead = new WeakScrubReentryTarget();
		$fired = false;
		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($map, &$fired): void {
			$fired = true;
			$map->remove('victim');
		};

		$map->add('stale', new TEventHandler([$dead, 'noopHandler'], $trap));
		$map->add('plain', 'text');
		$map->add('victim', 'removed by the destructor');
		$map->add('keep', $keep);
		$trap = null;
		$dead = null;

		$this->assertSame(['plain' => 'text', 'keep' => $keep], $map->toArray());
		$this->assertTrue($fired, 'The TEventHandler data destructor must run during the scrub.');
		$this->assertSame(2, $map->getCount());
	}

	/**
	 * The same destructor-time removal while DiscardInvalid turns on: the
	 * registration pass skips the vanished key and drops the stale entry.
	 */
	public function testWeakMap_destructorRemoveDuringDiscardInvalidTransitionIsSkipped(): void
	{
		$map = new WeakScrubReentryMap(null, false, false);
		$keep = new WeakScrubReentryTarget();
		$dead = new WeakScrubReentryTarget();
		$fired = false;
		$trap = new WeakScrubReentryTarget();
		$trap->onDestruct = function () use ($map, &$fired): void {
			$fired = true;
			$map->remove('victim');
		};

		$map->add('stale', new TEventHandler([$dead, 'noopHandler'], $trap));
		$map->add('plain', 'text');
		$map->add('victim', 'removed by the destructor');
		$map->add('keep', $keep);
		$trap = null;
		$dead = null;

		$map->enableDiscardInvalid();

		$this->assertTrue($fired, 'The TEventHandler data destructor must run during the transition.');
		$this->assertTrue($map->getDiscardInvalid());
		$this->assertSame(['plain' => 'text', 'keep' => $keep], $map->toArray());
	}
}
