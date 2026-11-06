<?php

/**
 * TWeakCollectionTrait class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * TWeakCollectionTrait trait
 *
 * This is the common WeakMap caching implementation for Weak Collections. It constructs
 * a WeakMap, where available, and tracks the number of times an object is {@see
 * weakAdd added} to and {@see weakRemove removed} from the Collection.
 *
 * When the PHP environment invalidates a WeakReference, it is no longer linked in
 * the WeakMap.  The number of known objects is tracked and upon deviations in the
 * WeakMap count then {@see weakChanged} becomes true.  When weakChanged is true
 * the implementing class can scrub the list of invalidated WeakReference.
 *
 * There are utility functions for managing the WeakMap to {@see weakStart start},
 * {@see weakRestart restart}, {@see weakClone clone}, and {@see weakStop stop}
 * the WeakMap.  The total number of objects in the WeakMap can be retrieved with
 * {@see weakCount} and the count of each object with {@see weakObjectCount}.
 *
 * ## Scrubbing contract
 *
 * Every Weak collection that uses this trait implements a `scrubWeakReferences()`
 * method that removes entries whose backing `WeakReference` has lost its referent.
 * Because PHP's cyclic garbage collector can fire object destructors between any
 * two opcodes — and those destructors can call back into the collection through
 * {@see \Prado\TComponent::__destruct} → `unlisten()` → `remove()` / `add()` — the
 * scrub must remain correct in the face of mid-loop mutations of its own backing
 * store. Each implementer follows the same two-pass algorithm:
 *
 *  - **Pass 1 — Identify.** A `foreach` over the backing store iterates a
 *    copy-on-write snapshot of the array, so destructor-triggered mutations
 *    during the loop cannot disturb the iteration. Every stale `WeakReference`
 *    is recorded in a local set keyed by `spl_object_id($weakRef)` — the
 *    `WeakReference` *object* itself is still alive, only its referent has
 *    been GC'd.
 *
 *  - **Pass 2 — Remove.** A second walk operates on the backing store's
 *    *current* state (whatever destructors have left it in) and removes only
 *    entries whose `WeakReference` identity is in the stale set. Live items
 *    inserted by destructors during Pass 1 are not in that set, so they are
 *    preserved.
 *
 * Identity-based removal — rather than the previous positional in-place splice
 * — makes the scrub re-entrant-safe: destructor-time `insert()` and `remove()`
 * calls on the same collection mutate the backing store directly without
 * dropping the work or deadlocking. The {@see isScrubbing}/{@see setScrubbing}
 * flag remains as the *outer* guard that prevents a nested scrub-of-scrub when
 * a destructor that fires mid-scrub itself triggers another scrub; the write
 * paths do not consult it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 * @see https://www.php.net/manual/en/class.weakmap.php
 */
trait TWeakCollectionTrait
{
	/**
	 * @var null|\WeakMap Maintains the cache of list objects.  Any invalid weak references
	 *  change the WeakMap count automatically.  This also tracks the number of times
	 *  on object has been inserted in the collection.
	 */
	private ?object $_weakMap = null;

	/** @var int Number of known objects in the collection. */
	private int $_weakCount = 0;

	/** @var bool Re-entrancy guard for {@see scrubWeakReferences}: true while executing. */
	private bool $_scrubbing = false;

	/** @var ?bool Whether GC'd entries are automatically discarded. Null = lazy init. */
	private ?bool $_discardInvalid = null;

	/** @var int Number of {@see \Prado\TEventHandler} instances currently tracked. */
	private int $_eventHandlerCount = 0;

	/**
	 * Returns true if {@see scrubWeakReferences} is currently executing.
	 * The re-entrancy guard prevents cyclic GC from invoking a second scrub
	 * while the outer loop is still iterating.
	 * @return bool
	 * @since 4.3.3
	 */
	protected function isScrubbing(): bool
	{
		return $this->_scrubbing;
	}

	/**
	 * Sets or clears the {@see scrubWeakReferences} re-entrancy guard.
	 * @param bool $value True when entering the scrub loop; false on exit.
	 * @since 4.3.3
	 */
	protected function setScrubbing(bool $value): void
	{
		$this->_scrubbing = $value;
	}

	/**
	 * Initializes a new WeakMap
	 */
	protected function weakStart(): void
	{
		$this->_weakMap = new \WeakMap();
	}

	/**
	 * Restarts the WeakMap cache if it exists.
	 */
	protected function weakRestart(): void
	{
		if ($this->_weakMap) {
			$this->_weakMap = new \WeakMap();
		}
	}

	/**
	 * Stops the WeakMap cache.
	 */
	protected function weakClone(): void
	{
		if ($this->_weakMap) {
			$this->_weakMap = clone $this->_weakMap;
		}
	}

	/**
	 * Checks if the WeakMap has changed.
	 * @return bool Are there any changes to the WeakMap.
	 */
	protected function weakChanged(): bool
	{
		return $this->_weakMap && $this->_weakMap->count() !== $this->_weakCount;
	}

	/**
	 * Resets the new number of known objects in the list to the current WeakMap count.
	 */
	protected function weakResetCount(): void
	{
		if ($this->_weakMap) {
			$this->_weakCount = $this->_weakMap->count();
		}
	}

	/**
	 * @return ?int The number of items being tracked in the WeakMap. null if there is
	 *   no WeakMap.
	 */
	protected function weakCount(): ?int
	{
		if ($this->_weakMap) {
			return $this->_weakMap->count();
		}
		return null;
	}

	/**
	 * Adds or updates an object to track with the WeakMap.
	 * @param object $object The object being tracked for existence.
	 * @return int The number of times the object has been added.
	 */
	protected function weakAdd(object $object): int
	{
		if ($this->_weakMap) {
			if (!$this->_weakMap->offsetExists($object)) {
				$count = 1;
				$this->_weakCount++;
			} else {
				$count = $this->_weakMap->offsetGet($object) + 1;
			}
			$this->_weakMap->offsetSet($object, $count);
			return $count;
		}
		return 0;
	}

	/**
	 * @param object $object The object being tracked.
	 * @return ?int The number of instances of the object in the Collection.
	 */
	protected function weakObjectCount(object $object): ?int
	{
		if ($this->_weakMap && $this->_weakMap->offsetExists($object)) {
			return $this->_weakMap->offsetGet($object);
		}
		return null;
	}

	/**
	 * Removes or updates an object from WeakMap tracking.
	 * @param object $object The object being tracked for existence.
	 * @return int The number of remaining instances of the object in the Collection.
	 */
	protected function weakRemove(object $object): int
	{
		if ($this->_weakMap) {
			$count = $this->_weakMap->offsetGet($object) - 1;
			if ($count) {
				$this->_weakMap->offsetSet($object, $count);
			} else {
				$this->_weakMap->offsetUnset($object);
				$this->_weakCount--;
			}
			return $count;
		}
		return 0;
	}

	/**
	 * Stops the WeakMap cache.
	 */
	protected function weakStop(): void
	{
		$this->_weakMap = null;
	}

	/**
	 * Appends weak-collection properties that must be excluded from serialization.
	 *
	 * Always excluded (pure runtime state):
	 *  - `_weakMap` / `_weakCount` — rebuilt from data on wakeup
	 *  - `_scrubbing` — transient re-entrancy flag, always false at rest
	 *  - `_eventHandlerCount` — derived from data, always 0 after wakeup
	 *
	 * Conditionally excluded:
	 *  - `_discardInvalid` when null — null means "lazy: derive from ReadOnly",
	 *    so persisting null is harmless but storing the derived value is preferable;
	 *    excluding null causes wakeup to re-derive correctly.
	 *
	 * @param array &$exprops Properties to remove from serialize.
	 * @since 4.3.0
	 */
	protected function _weakZappableSleepProps(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_weakMap";
		$exprops[] = "\0" . __CLASS__ . "\0_weakCount";
		$exprops[] = "\0" . __CLASS__ . "\0_scrubbing";
		$exprops[] = "\0" . __CLASS__ . "\0_eventHandlerCount";
		if ($this->_discardInvalid === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_discardInvalid";
		}
	}
}
