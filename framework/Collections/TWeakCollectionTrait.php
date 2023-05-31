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
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
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
	 * @param array &$exprops Properties to remove from serialize.
	 */
	protected function _weakZappableSleepProps(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_weakMap";
		$exprops[] = "\0" . __CLASS__ . "\0_weakCount";
	}
}
