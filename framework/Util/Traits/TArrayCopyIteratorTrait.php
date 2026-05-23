<?php

/**
 * TArrayCopyIteratorTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

/**
 * TArrayCopyIteratorTrait trait.
 *
 * TArrayCopyIteratorTrait extends {@see TArrayIteratorTrait} with a lazy-loading,
 * copy-and-cache backing store.  It owns a `?array $_iterator_array` property that
 * starts as `null` and is populated on the first iterator access by calling the
 * abstract {@see getIteratorArrayCopy()}.  The populated array is then reused for all
 * subsequent iterations.
 *
 * Subclasses implement {@see getIteratorArrayCopy()} to supply the snapshot that
 * seeds the backing store.  {@see TConstantReflectionTrait} satisfies this contract
 * automatically by returning the class's own constants via reflection; when combined
 * with this trait no additional implementation is needed.
 *
 * The protected helpers {@see ensureIteratorArray()}, {@see getIteratorArrayDirect()},
 * and {@see setIteratorArrayDirect()} allow subclasses to pre-warm, inspect, or
 * replace the backing store without going through the lazy-load path.
 * Passing `null` to {@see setIteratorArrayDirect()} resets the store so the next
 * iterator access triggers a fresh call to {@see getIteratorArrayCopy()}.
 *
 * The using class must declare `implements \Iterator` on its own class signature;
 * the five required method bodies come from {@see TArrayIteratorTrait}.
 *
 * ## Usage with a snapshot copy
 *
 * ```php
 * class MyList implements \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayCopyIteratorTrait;
 *
 *     protected function getIteratorArrayCopy(): array
 *     {
 *         return ['a' => 1, 'b' => 2];
 *     }
 * }
 * ```
 *
 * ## Usage with TConstantReflectionTrait (no conflict)
 *
 * `TArrayCopyIteratorTrait` declares `getIteratorArrayCopy()` as abstract;
 * `TConstantReflectionTrait` provides a concrete implementation.  PHP resolves the
 * abstract automatically:
 *
 * ```php
 * class TTextAlign implements \Prado\IEnumerable, \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayCopyIteratorTrait,
 *         \Prado\Util\Traits\TConstantReflectionTrait;
 *
 *     const Left  = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 *
 * {@see \Prado\TEnumerable} is the canonical base class and demonstrates this pattern.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TArrayIteratorTrait
 * @see TConstantReflectionTrait
 * @see \Prado\IEnumerable
 * @since 4.4.0
 */
trait TArrayCopyIteratorTrait
{
	use TArrayIteratorTrait;

	/** @var ?array Backing store for iteration; null until first load. */
	private ?array $_iterator_array = null;

	/**
	 * Returns the array to seed the backing store on first iterator access.
	 *
	 * Implement this method to supply the initial contents of the backing store.
	 * The return value is stored once and reused for all subsequent iterations.
	 * To supply a live reference instead of a snapshot copy, override
	 * {@see getIteratorArray()} directly.
	 *
	 * {@see TConstantReflectionTrait} provides a concrete implementation that
	 * returns the class's own constants via reflection; combining both traits
	 * requires no further implementation.
	 *
	 * @return array The array to copy into the backing store.
	 */
	abstract protected function getIteratorArrayCopy(): array;

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
	 * Returns `null` when the store has not been populated yet.  Call
	 * {@see ensureIteratorArray()} first when a populated array is required.
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
	 * live store rather than a copy.  The null check inlined here — rather than
	 * delegating to {@see ensureIteratorArray()} — lets static analysis confirm that
	 * the returned reference is always a non-null `array`.
	 *
	 * @return array Reference to the backing array.
	 */
	protected function &getIteratorArray(): array
	{
		if ($this->_iterator_array === null) {
			$this->_iterator_array = $this->getIteratorArrayCopy();
		}
		return $this->_iterator_array;
	}

}
