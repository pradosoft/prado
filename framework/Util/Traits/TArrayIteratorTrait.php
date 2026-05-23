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
 * TArrayIteratorTrait supplies the five {@see \Iterator} method bodies —
 * {@see current()}, {@see key()}, {@see next()}, {@see rewind()}, and
 * {@see valid()} — delegating every call to the abstract
 * {@see getIteratorArray()}, which subclasses or companion traits must implement.
 *
 * This trait is deliberately minimal: it owns no state, performs no caching, and
 * carries no reflection or domain logic.  Two typical implementations exist:
 *
 * - **{@see TArrayCopyIteratorTrait}** — adds a `?array $_iterator_array` backing
 *   store with lazy loading via an abstract {@see getIteratorArrayCopy()}.  Use this
 *   when the iterator should operate over a snapshot copy that is built once and
 *   reused.  This is the most common pattern and is used by {@see \Prado\TEnumerable}.
 *
 * - **Direct `getIteratorArray()` override** — the using class returns a reference
 *   to its own live array, bypassing any copy-and-cache mechanism.  Use this when
 *   the class already owns the backing store and wants the iterator to operate on
 *   that array directly.
 *
 * The using class must declare `implements \Iterator` on its own class signature.
 *
 * ## Usage with a live array (no copy)
 *
 * ```php
 * class MyCollection implements \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayIteratorTrait;
 *
 *     private array $_items = [];
 *
 *     protected function &getIteratorArray(): array
 *     {
 *         return $this->_items;
 *     }
 * }
 * ```
 *
 * ## Usage with a snapshot copy (via TArrayCopyIteratorTrait)
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
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TArrayCopyIteratorTrait
 * @see \Prado\IEnumerable
 * @since 4.4.0
 */
trait TArrayIteratorTrait
{
	/**
	 * Returns a reference to the array that backs this iterator.
	 *
	 * Called by every iterator method so that PHP's internal array-pointer functions
	 * ({@see current()}, {@see key()}, {@see next()}, {@see reset()}) operate on the
	 * same live array.  Implementations must return a reference; the return value must
	 * never be `null`.
	 *
	 * {@see TArrayCopyIteratorTrait} provides a lazy-loading implementation backed
	 * by a cached snapshot copy.  Alternatively, override this method directly in the
	 * using class to return a reference to an array the class already owns.
	 *
	 * @return array Reference to the backing array.
	 */
	abstract protected function &getIteratorArray(): array;

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
