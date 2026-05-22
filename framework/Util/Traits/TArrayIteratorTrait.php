<?php

/**
 * TArrayIteratorTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

/**
 * TArrayIteratorTrait trait.
 *
 * TArrayIteratorTrait provides a lazy-loading {@see \Iterator} implementation backed
 * by any array the using class can supply.  The trait is deliberately free of any
 * reflection or domain-specific logic: it does not know or care where the array
 * comes from.
 *
 * The abstract method {@see getIteratorArray()} is the sole contract between this
 * trait and the using class.  It must be satisfied by either a concrete
 * implementation in the class itself or by another used trait.
 * {@see TConstantReflectionTrait} satisfies this contract automatically (returning
 * the class's own constants via reflection), so classes that use both traits
 * require no further implementation.  A class may always define its own
 * `getIteratorArray()` to override the default provided by `TConstantReflectionTrait`.
 *
 * The backing store (`$_iterator_array`) starts as `null`.  On the first iterator
 * access the protected helper {@see ensureIteratorArray()} calls
 * {@see getIteratorArray()} and caches the result.  The public accessors
 * {@see getIteratorArrayDirect()} and {@see setIteratorArrayDirect()} allow
 * inspection and replacement of the store without triggering or bypassing the
 * lazy-load cycle.
 *
 * The using class must declare `implements \Iterator` on its own class signature;
 * this trait supplies the five required method bodies.
 *
 * ## Usage with TConstantReflectionTrait (no override needed)
 *
 * ```php
 * class TTextAlign implements \Prado\IEnumerable, \Iterator
 * {
 *     use \Prado\Util\Traits\TConstantReflectionTrait; // provides getIteratorArray()
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *
 *     const Left  = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 *
 * ## Usage without TConstantReflectionTrait (`getIteratorArray` implementation required)
 *
 * ```php
 * class MyList implements \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *
 *     public function getIteratorArray(): array
 *     {
 *         return ['a' => 1, 'b' => 2];
 *     }
 * }
 * ```
 *
 * {@see \Prado\TEnumerable} is the canonical base class and demonstrates this pattern.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TConstantReflectionTrait
 * @see \Prado\IEnumerable
 * @since 4.4.0
 */
trait TArrayIteratorTrait
{
	/** @var ?array Backing store for iteration; null until first load. */
	private ?array $_iterator_array = null;

	/**
	 * Returns the array that backs this iterator.
	 *
	 * Implement this method to supply the array the iterator walks over.
	 * {@see TConstantReflectionTrait} provides a default implementation that
	 * returns the using class's own constants via reflection; a class may
	 * override it to supply any array.
	 *
	 * @return array The array to iterate over.
	 */
	abstract public function getIteratorArray(): array;

	/**
	 * Ensures `$_iterator_array` is populated, loading it on first call.
	 *
	 * Called internally by every iterator method before accessing the array.
	 */
	protected function ensureIteratorArray(): void
	{
		if ($this->getIteratorArrayDirect() === null) {
			$this->setIteratorArrayDirect($this->getIteratorArray());
		}
	}

	/**
	 * Returns a reference to the backing array, populating it on first call.
	 *
	 * Called by every iterator method so that PHP's internal array-pointer functions
	 * ({@see current()}, {@see key()}, {@see next()}, {@see reset()}) operate on the
	 * live store rather than a copy.  The store is guaranteed to be non-null on
	 * return because {@see ensureIteratorArray()} is called first.
	 *
	 * @return array Reference to `$_iterator_array`.
	 */
	protected function &getIteratorArrayDirect(): array
	{
		return $this->_iterator_array;
	}

	/**
	 * Replaces the backing array directly, bypassing lazy loading.
	 *
	 * Passing `null` resets the store so that the next iterator access triggers
	 * a fresh call to {@see getIteratorArray()}.
	 *
	 * @param ?array $array The replacement array, or `null` to force a reload.
	 */
	protected function setIteratorArrayDirect(?array $array): void
	{
		$this->_iterator_array = $array;
	}

	/**
	 * Returns the backing array directly, or `null` if not yet loaded.
	 *
	 * Returns the raw store without triggering a load.  A `null` return means the
	 * store has not been populated yet and the next iterator access will call
	 * {@see getIteratorArray()}.
	 *
	 * @return ?array
	 */
	protected function &getEnsuredIteratorArray(): ?array
	{
		$this->ensureIteratorArray();
		return $this->getIteratorArrayDirect();
	}

	/**
	 * Returns the value at the current iterator position.
	 *
	 * @return mixed The current value.
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return current($this->getEnsuredIteratorArray());
	}

	/**
	 * Returns the key at the current iterator position.
	 *
	 * @return mixed The current key.
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return key($this->getEnsuredIteratorArray());
	}

	/**
	 * Advances the iterator to the next element.
	 */
	public function next(): void
	{
		next($this->getEnsuredIteratorArray());
	}

	/**
	 * Rewinds the iterator to the first element.
	 */
	public function rewind(): void
	{
		reset($this->getEnsuredIteratorArray());
	}

	/**
	 * Returns whether the current iterator position is valid.
	 *
	 * Uses {@see key()} rather than {@see current()} so that a backing array
	 * entry whose value is `false` does not prematurely terminate iteration.
	 *
	 * @return bool `true` while the iterator points to a valid element.
	 */
	public function valid(): bool
	{
		return key($this->getEnsuredIteratorArray()) !== null;
	}
}
