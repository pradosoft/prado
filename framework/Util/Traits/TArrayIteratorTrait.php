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
 * carries no reflection or domain logic.  {@see getIteratorArray()} can be satisfied
 * in two ways:
 *
 * - **Direct `getIteratorArray()` override** — the using class implements
 *   {@see getIteratorArray()} to return a reference to an array it already owns.
 *   Use this when the class manages its own backing store and wants the iterator
 *   to operate directly on that array.
 *
 * - **{@see TArrayCopyIteratorTrait}** — composes this trait and adds a
 *   `?array $_iterator_array` backing store with lazy loading via an abstract
 *   {@see getIteratorArrayCopy()}.  Use this when the iterator should operate over
 *   a snapshot copy that is built once and reused; this is the pattern used by
 *   {@see \Prado\TEnumerable}.
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
 * @see \Iterator
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
	 * Part of the {@see \Iterator} interface.
	 *
	 * @return mixed The current value.
	 * @see \Iterator
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return current($this->getIteratorArray());
	}

	/**
	 * Returns the key at the current iterator position.
	 *
	 * Part of the {@see \Iterator} interface.
	 *
	 * @return mixed The current key.
	 * @see \Iterator
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return key($this->getIteratorArray());
	}

	/**
	 * Advances the iterator to the next element.
	 *
	 * Part of the {@see \Iterator} interface.
	 * @see \Iterator
	 */
	public function next(): void
	{
		next($this->getIteratorArray());
	}

	/**
	 * Rewinds the iterator to the first element.
	 *
	 * Part of the {@see \Iterator} interface.
	 * @see \Iterator
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
	 * Part of the {@see \Iterator} interface.
	 *
	 * @return bool `true` while the iterator points to a valid element.
	 * @see \Iterator
	 */
	public function valid(): bool
	{
		return key($this->getIteratorArray()) !== null;
	}
}
