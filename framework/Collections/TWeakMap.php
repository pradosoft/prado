<?php

/**
 * TWeakMap class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TEventHandler;
use Prado\TPropertyValue;
use Closure;
use Traversable;
use WeakReference;

/**
 * TWeakMap class
 *
 * TWeakMap implements a key-value collection where object *values* are held as
 * {@see WeakReference}, so the map does not prevent its values from being garbage-
 * collected.  Keys are always strongly retained (as PHP arrays require string or
 * integer keys).
 *
 * Non-object values (null, string, int, bool, array) are stored directly and are
 * never weakened.  {@see \Closure} objects are also stored directly to prevent
 * anonymous functions from being collected if the map is their only reference.
 * Objects implementing {@see IWeakRetainable} (including {@see \Prado\TEventHandler})
 * are stored directly; for TEventHandler the inner callable object is tracked in the
 * WeakMap rather than the TEventHandler wrapper itself.  All other object values are
 * wrapped in {@see WeakReference} for storage.
 *
 * TWeakMap supports two modes controlled by {@see setDiscardInvalid DiscardInvalid}:
 *
 * - **true** (default for mutable maps): when a value object is garbage-collected,
 *   its entry is silently removed from the map.  The count and key set shrink.
 * - **false** (default for read-only maps): entries are retained; a GC'd value
 *   resolves to `null` on read.  The count and key set are stable.
 *
 * A PHP 8 {@see WeakMap} is used internally to detect garbage-collection events
 * efficiently; see {@see TWeakCollectionTrait} for the implementation.
 *
 * Re-entrancy: PHP's cyclic garbage collector can fire destructors between any two
 * opcodes, potentially calling back into {@see scrubWeakReferences} while it is
 * already executing.  The {@see TWeakCollectionTrait::isScrubbing} guard prevents the inner call from
 * modifying the internal array while the outer loop is iterating; any entries
 * skipped by the inner call are cleaned on the next outer pass.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TWeakMap extends TMap implements IWeakCollection, ICollectionFilter
{
	use TWeakCollectionTrait;

	/**
	 * Constructor.
	 * @param null|array|\Traversable $data Initial data. Default null.
	 * @param ?bool $readOnly Whether the map is read-only. Default null.
	 * @param ?bool $discardInvalid Whether GC'd entries are removed (true) or
	 *   retained as null (false). Default null: opposite of $readOnly.
	 */
	public function __construct($data = null, $readOnly = null, $discardInvalid = null)
	{
		parent::__construct($data, $readOnly);
		$this->setDiscardInvalid($discardInvalid);
	}

	/**
	 * Cloning a TWeakMap requires cloning the WeakMap cache.
	 */
	public function __clone()
	{
		$this->weakClone();
		parent::__clone();
	}

	/**
	 * Waking up a TWeakMap re-initialises the WeakMap cache if discardInvalid is
	 * active (data is not serialised, so the cache starts empty).
	 */
	public function __wakeup()
	{
		if ($this->_discardInvalid) {
			$this->weakStart();
		}
		parent::__wakeup();
	}

	// -------------------------------------------------------------------------
	// WeakMap bookkeeping helpers
	// -------------------------------------------------------------------------

	/**
	 * Adds an object value to the WeakMap cache.  For TEventHandler values the
	 * inner callable object is tracked rather than the handler wrapper.
	 * Closures are skipped — they are strongly retained by the map on purpose.
	 * @param object $object The stored value (original, before WeakReference wrapping).
	 * @return int The updated instance count for the tracked object.
	 */
	protected function weakCustomAdd(object $object): int
	{
		if ($object instanceof TEventHandler) {
			$this->_eventHandlerCount++;
			$inner = $object->getHandlerObject();

			if ($inner !== null) {
				return $this->weakAdd($inner);
			}

			return 0;
		}

		if ($object instanceof Closure) {
			return 0;
		}

		return $this->weakAdd($object);
	}

	/**
	 * Removes an object value from the WeakMap cache.  Mirrors {@see weakCustomAdd}.
	 *
	 * The stored value may be the original object, a {@see WeakReference} wrapping it
	 * (from {@see filterItemForInput}), a {@see \Prado\TEventHandler}, or a {@see \Closure}.
	 * WeakReferences are resolved to the actual object before removal; a null resolution
	 * (GC'd) is a no-op since the WeakMap entry was already auto-removed.
	 *
	 * @param object $object The stored value (may be a WeakReference or original object).
	 * @return int The remaining instance count for the tracked object.
	 */
	protected function weakCustomRemove(object $object): int
	{
		if ($object instanceof TEventHandler) {
			$this->_eventHandlerCount--;
			$inner = $object->getHandlerObject();

			if ($inner !== null) {
				return $this->weakRemove($inner);
			}

			return 0;
		}

		if ($object instanceof Closure) {
			return 0;
		}

		// filterItemForInput wraps regular objects in WeakReference for storage.
		// Resolve back to the actual object before interacting with the WeakMap.
		if ($object instanceof WeakReference) {
			$actual = $object->get();

			if ($actual !== null) {
				return $this->weakRemove($actual);
			}

			// Already GC'd — WeakMap dropped the entry automatically; nothing to do.
			return 0;
		}

		return $this->weakRemove($object);
	}

	// -------------------------------------------------------------------------
	// ICollectionFilter — WeakReference encoding/decoding
	// -------------------------------------------------------------------------

	/**
	 * Converts an object value into a {@see WeakReference} for internal storage.
	 * {@see \Closure}, {@see IWeakRetainable} objects (including TEventHandler), and
	 * non-objects are stored as-is.  Already-wrapped WeakReferences are unchanged.
	 * @param mixed &$item The value to convert.
	 */
	public static function filterItemForInput(&$item): void
	{
		if (
			is_object($item)
			&& !($item instanceof WeakReference)
			&& !($item instanceof Closure)
			&& !($item instanceof IWeakRetainable)
		) {
			$item = WeakReference::create($item);
		}
	}

	/**
	 * Converts a stored value back to its original form.
	 * A dead {@see WeakReference} (GC'd object) resolves to null.
	 * A {@see \Prado\TEventHandler} with no live handler resolves to null.
	 * @param mixed &$item The stored value to restore.
	 */
	public static function filterItemForOutput(&$item): void
	{
		if ($item instanceof WeakReference) {
			$item = $item->get();
		} elseif (($item instanceof TEventHandler) && !$item->hasHandler()) {
			$item = null;
		}
	}

	// -------------------------------------------------------------------------
	// Scrubbing
	// -------------------------------------------------------------------------

	/**
	 * Removes entries from the keyed map `_d` whose stored `WeakReference`
	 * has lost its referent, following the two-pass snapshot-identify +
	 * identity-remove scrubbing contract documented on
	 * {@see TWeakCollectionTrait}.
	 *
	 * Class-specific behaviour: keys are scalar (string/int) and the stale
	 * set is keyed by `spl_object_id` of the **stored object** (the entry
	 * value or its wrapping `TEventHandler`), not the inner `WeakReference`'s
	 * referent — which has already been GC'd. `_eventHandlerCount` is
	 * decremented per removal during Pass 2; a destructor-time `remove()`
	 * that already deleted the same key simply leaves nothing for Pass 2 to
	 * skip without double-counting.
	 */
	protected function scrubWeakReferences(): void
	{
		if ($this->isScrubbing() || !$this->getDiscardInvalid() || !$this->weakChanged()) {
			return;
		}
		$this->setScrubbing(true);
		try {
			// Pass 1: identify entries whose stored object's WeakReference is stale.
			// Key by spl_object_id of the *stored* object so Pass 2 can match
			// against $_d's current state even if the same key has been overwritten.
			$staleEntryIds = [];
			foreach ($this->_d as $stored) {
				if (!is_object($stored)) {
					continue;
				}
				$ref = $stored;
				if ($ref instanceof TEventHandler) {
					$ref = $ref->getHandlerObject(true);
				}
				if (($ref instanceof WeakReference) && $ref->get() === null) {
					$staleEntryIds[spl_object_id($stored)] = true;
				}
			}

			// Pass 2: remove stale entries from _d's current state.
			foreach (array_keys($this->_d) as $key) {
				if (!array_key_exists($key, $this->_d)) {
					continue;
				}
				$stored = $this->_d[$key];
				if (!is_object($stored) || !isset($staleEntryIds[spl_object_id($stored)])) {
					continue;
				}
				$isEventHandler = $stored instanceof TEventHandler;
				unset($this->_d[$key]);
				if ($isEventHandler) {
					$this->_eventHandlerCount--;
				}
			}
			$this->weakResetCount();
		} finally {
			$this->setScrubbing(false);
		}
	}

	// -------------------------------------------------------------------------
	// DiscardInvalid
	// -------------------------------------------------------------------------

	/**
	 * @return bool Whether GC'd entries are automatically removed from the map.
	 */
	public function getDiscardInvalid(): bool
	{
		$this->collapseDiscardInvalid();
		return $this->_discardInvalid;
	}

	/**
	 * Ensures DiscardInvalid is initialised to its default (opposite of ReadOnly).
	 */
	protected function collapseDiscardInvalid(): void
	{
		if ($this->_discardInvalid === null) {
			$this->setDiscardInvalid(!$this->getReadOnly());
		}
	}

	/**
	 * Sets whether GC'd entries are automatically discarded.
	 *
	 * Once set externally this property is locked — only the object itself (e.g.
	 * the constructor or a subclass) may change it again.  External callers that
	 * attempt a second set will receive a {@see TInvalidOperationException}.
	 *
	 * When transitioning to true an existing WeakMap cache is started and the
	 * current entries are scanned: live objects are registered, dead entries
	 * are removed immediately.  When transitioning to false the WeakMap cache
	 * is stopped.
	 *
	 * @param ?bool $value true to discard, false to retain, null is a no-op.
	 * @throws TInvalidOperationException if already set and called from outside.
	 */
	public function setDiscardInvalid($value): void
	{
		if ($value === $this->_discardInvalid) {
			return;
		}

		if ($this->_discardInvalid !== null && !Prado::isCallingSelf()) {
			throw new TInvalidOperationException('weak_no_set_discard_invalid', $this::class);
		}

		$value = TPropertyValue::ensureBoolean($value);

		if ($value && !$this->_discardInvalid) {
			$this->weakStart();

			foreach (array_keys($this->_d) as $key) {
				if (!array_key_exists($key, $this->_d)) {
					continue;
				}
				$stored = $this->_d[$key];

				if (!is_object($stored)) {
					continue;
				}

				$isEventHandler = false;
				$object = $stored;

				if ($isEventHandler = ($stored instanceof TEventHandler)) {
					$object = $stored->getHandlerObject(true);
				}

				if ($object instanceof WeakReference) {
					$object = $object->get();
				}

				if ($object === null) {
					unset($this->_d[$key]);

					if ($isEventHandler) {
						$this->_eventHandlerCount--;
					}
				} elseif (!($stored instanceof Closure)) {
					$this->weakAdd($object);
				}
			}

			$this->weakResetCount();
		} elseif (!$value && $this->_discardInvalid) {
			$this->weakStop();
		}

		$this->_discardInvalid = $value;
	}

	// -------------------------------------------------------------------------
	// TMap overrides
	// -------------------------------------------------------------------------

	/**
	 * Returns an iterator over the live (dereferenced) entries in the map.
	 * @return \Iterator
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->toArray());
	}

	/**
	 * Returns the number of entries in the map. GC'd entries are scrubbed first
	 * when DiscardInvalid is true.
	 * @return int
	 */
	public function getCount(): int
	{
		$this->scrubWeakReferences();
		return parent::getCount();
	}

	/**
	 * Returns the value at the specified key, with WeakReference resolved.
	 * A GC'd value returns null.  A missing key calls the dyNoItem behaviour.
	 * @param mixed $key
	 * @return mixed
	 */
	public function itemAt($key)
	{
		$this->scrubWeakReferences();

		if (!isset($this->_d[$key]) && !array_key_exists($key, $this->_d)) {
			return $this->dyNoItem(null, $key);
		}

		$value = $this->_d[$key];
		static::filterItemForOutput($value);
		return $value;
	}

	/**
	 * Adds or replaces an entry in the map.
	 *
	 * When a key already exists the old value's WeakMap registration is removed
	 * before the new value is registered.  Object values are wrapped in
	 * WeakReference for storage (except Closure and IWeakRetainable).
	 *
	 * @param mixed $key   String or integer key, or null to append.
	 * @param mixed $value The value to store.
	 * @throws TInvalidOperationException if the map is read-only.
	 * @return mixed The key actually used (useful when $key is null).
	 */
	public function add($key, $value): mixed
	{
		$this->collapseDiscardInvalid();
		$this->collapseReadOnly();

		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('map_readonly', $this::class);
		}

		$this->scrubWeakReferences();

		// Remove old WeakMap entry when overwriting an existing key.
		if ($key !== null && (isset($this->_d[$key]) || array_key_exists($key, $this->_d))) {
			$oldStored = $this->_d[$key];

			if (is_object($oldStored)) {
				$this->weakCustomRemove($oldStored);
			}
		}

		if (is_object($value)) {
			$this->weakCustomAdd($value);
		}

		$stored = $value;
		static::filterItemForInput($stored);

		if ($key === null) {
			$this->_d[] = $stored;
			$key = array_key_last($this->_d);
		} else {
			$this->_d[$key] = $stored;
		}

		$this->dyAddItem($key, $value);
		return $key;
	}

	/**
	 * Removes the entry with the specified key and returns its (dereferenced) value.
	 * Returns null if the key does not exist.
	 * @param mixed $key
	 * @throws TInvalidOperationException if the map is read-only.
	 * @return mixed The removed value, or null if the key was absent.
	 */
	public function remove($key)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('map_readonly', $this::class);
		}

		$this->scrubWeakReferences();

		if (!isset($this->_d[$key]) && !array_key_exists($key, $this->_d)) {
			return null;
		}

		$stored = $this->_d[$key];
		unset($this->_d[$key]);

		$value = $stored;
		static::filterItemForOutput($value);

		if (is_object($stored)) {
			$this->weakCustomRemove($stored);
		}

		$this->dyRemoveItem($key, $value);
		return $value;
	}

	/**
	 * Removes all entries whose (dereferenced) value equals $item.
	 * Returns an array of [key => removed value] pairs.
	 * @param mixed $item
	 * @throws TInvalidOperationException if the map is read-only.
	 * @return array<mixed, mixed>
	 */
	public function removeItem(mixed $item): array
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('map_readonly', $this::class);
		}

		$removed = [];

		foreach ($this->toArray() as $key => $value) {
			if ($item === $value) {
				$removed[$key] = $this->remove($key);
			}
		}

		return $removed;
	}

	/**
	 * Removes all entries and resets the WeakMap cache.
	 */
	public function clear(): void
	{
		$c = count($this->_d);

		foreach (array_keys($this->_d) as $key) {
			$stored = $this->_d[$key];
			$value = $stored;
			static::filterItemForOutput($value);
			unset($this->_d[$key]);
			$this->dyRemoveItem($key, $value);
		}

		if ($c) {
			$this->weakRestart();
		}
	}

	/**
	 * Returns whether the map contains the specified key.
	 * GC'd entries are scrubbed first when DiscardInvalid is true.
	 * @param mixed $key
	 * @return bool
	 */
	public function contains($key): bool
	{
		$this->scrubWeakReferences();
		return parent::contains($key);
	}

	/**
	 * Returns the key(s) whose (dereferenced) value equals $item.
	 * @param mixed $item
	 * @param bool $multiple When true (default) returns all matching keys as an array;
	 *   when false returns the first matching key or false.
	 * @return mixed
	 */
	public function keyOf($item, bool $multiple = true): mixed
	{
		$arr = $this->toArray();

		if ($multiple) {
			$result = [];

			foreach ($arr as $key => $value) {
				if ($item === $value) {
					$result[$key] = $value;
				}
			}

			return $result;
		}

		return array_search($item, $arr, true);
	}

	/**
	 * Returns all entries as a plain array with WeakReferences resolved.
	 * GC'd entries are scrubbed first when DiscardInvalid is true.
	 * @return array<mixed, mixed>
	 */
	public function toArray(): array
	{
		$this->scrubWeakReferences();
		$items = $this->_d;

		foreach ($items as &$item) {
			static::filterItemForOutput($item);
		}
		unset($item);

		return $items;
	}

	/**
	 * Replaces all entries with the contents of $data.
	 * @param mixed $data Array or Traversable.
	 * @throws TInvalidDataTypeException if $data is neither an array nor Traversable.
	 */
	public function copyFrom($data): void
	{
		if (is_array($data) || $data instanceof Traversable) {
			if (count($this->_d) > 0) {
				$this->clear();
			}

			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Merges the contents of $data into the map, overwriting duplicate keys.
	 * @param mixed $data Array or Traversable.
	 * @throws TInvalidDataTypeException if $data is neither an array nor Traversable.
	 */
	public function mergeWith($data): void
	{
		if (is_array($data) || $data instanceof Traversable) {
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Returns the array of serialisation-excluded property names.
	 * The data array is excluded because values are weakly held and cannot be
	 * meaningfully persisted.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		// Temporarily clear _d so TMap's implementation marks it for exclusion.
		$d = $this->_d;
		$this->_d = [];
		parent::_getZappableSleepProps($exprops);
		$this->_d = $d;

		$this->_weakZappableSleepProps($exprops);
	}
}
