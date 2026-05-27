<?php

/**
 * TWeakScrubReentrancyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Collections\TWeakCallableCollection;
use Prado\Collections\TWeakList;
use Prado\Collections\TWeakMap;
use Prado\TComponent;

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
 * Regression tests for the snapshot/identify + identity-based-remove
 * scrubbing algorithm shared by {@see TWeakCallableCollection}, {@see TWeakList},
 * and {@see TWeakMap}. The earlier implementation guarded the scrub with a
 * re-entrancy mutex and silently dropped destructor-time inserts/removes.
 * The new algorithm permits re-entrant writes to land in `_d` without
 * disturbing the scrub's correctness.
 *
 * @package System.Collections
 */
class TWeakScrubReentrancyTest extends PHPUnit\Framework\TestCase
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
}
