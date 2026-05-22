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
 * The abstract method {@see getIteratorArrayCopy()} is the sole contract between
 * this trait and the using class.  It must return a copy of the array to iterate
 * over; the copy is stored in the backing store on first access and reused for all
 * subsequent iterations.  {@see TConstantReflectionTrait} satisfies this contract
 * automatically (returning the class's own constants via reflection), so classes
 * that use both traits require no further implementation.  A class must always define
 * its own `getIteratorArrayCopy()` to supply its array.
 *
 * Override {@see getIteratorArray()} instead to supply the array directly — bypassing
 * the copy — when the using class manages the backing store itself.
 *
 * The backing store (`$_iterator_array`) starts as `null`.  On the first iterator
 * access the protected helper {@see ensureIteratorArray()} calls
 * {@see getIteratorArrayCopy()} and caches the result.  The protected accessors
 * {@see getIteratorArrayDirect()} and {@see setIteratorArrayDirect()} allow
 * inspection and replacement of the store without triggering the lazy-load.
 *
 * The using class must declare `implements \Iterator` on its own class signature;
 * this trait supplies the five required method bodies.
 *
 * ## Usage with TConstantReflectionTrait (no override needed)
 *
 * ```php
 * class TTextAlign implements \Prado\IEnumerable, \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *     use \Prado\Util\Traits\TConstantReflectionTrait; // provides getIteratorArrayCopy()
 *
 *     const Left  = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 *
 * ## Usage without TConstantReflectionTrait (`getIteratorArrayCopy` implementation required)
 *
 * ```php
 * class MyList implements \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *
 *     public function getIteratorArrayCopy(): array
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
	 * Returns a copy of the array to iterate over, cached on first access.
	 *
	 * @return array The array to copy into the backing store.
	 * @see \Prado\Util\Traits\TConstantReflectionTrait
	 */
	abstract public function getIteratorArrayCopy(): array;

	/**
	 * Ensures `$_iterator_array` is populated, loading it on first call.
	 *
	 * Called internally by every iterator method before accessing the array.
	 */
	protected function ensureIteratorArray(): void
	{
		if ($this->getIteratorArrayDirect() === null) {
			$this->setIteratorArrayDirect($this->getIteratorArrayCopy());
		}
	}

	/**
	 * Returns a reference to the raw backing store without triggering lazy loading.
	 *
	 * Returns `null` when the store has not been populated yet.  Use
	 * {@see getEnsuredIteratorArray()} when a non-null, loaded array is required.
	 *
	 * @return ?array Reference to `$_iterator_array`.
	 */
	protected function &getIteratorArrayDirect(): ?array
	{
		return $this->_iterator_array;
	}

	/**
	 * Replaces the backing array directly, bypassing lazy loading.
	 *
	 * Passing `null` resets the store so that the next iterator access triggers
	 * a fresh call to {@see getIteratorArrayCopy()}.
	 *
	 * @param ?array $array The replacement array, or `null` to force a reload.
	 */
	protected function setIteratorArrayDirect(?array $array): void
	{
		$this->_iterator_array = $array;
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
	protected function &getIteratorArray(): array
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
		return current($this->getIteratorArray());
	}

	/**
	 * Returns the key at the current iterator position.
	 *
	 * @return mixed The current key.
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return key($this->getIteratorArray());
	}

	/**
	 * Advances the iterator to the next element.
	 */
	public function next(): void
	{
		next($this->getIteratorArray());
	}

	/**
	 * Rewinds the iterator to the first element.
	 */
	public function rewind(): void
	{
		reset($this->getIteratorArray());
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
		return key($this->getIteratorArray()) !== null;
	}
}
